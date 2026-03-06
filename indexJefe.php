<?php
require_once 'evaluacion.php';
$conexion = conectarBD();

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

session_start();

if (!isset($_SESSION['comentarios'])) {
    $_SESSION['comentarios'] = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['comentario'])) {
        $comentario = trim($_POST['comentario']);
        if (!empty($comentario)) {
            $_SESSION['comentarios'][] = [
                'texto' => $comentario,
                'fecha' => date('Y-m-d H:i:s')
            ];
        }
    }

    elseif (isset($_POST['eliminar_comentario'])) {
        $index = $_POST['eliminar_comentario'];
        if (isset($_SESSION['comentarios'][$index])) {
            unset($_SESSION['comentarios'][$index]);
            $_SESSION['comentarios'] = array_values($_SESSION['comentarios']);
        }
    }
}

$comentarios = $_SESSION['comentarios'];

$sql = "SELECT a.id, a.nombreProyecto, a.dictamen, d.archivo_Anteproyecto
        FROM alumnos a
        LEFT JOIN documentos d ON d.id = (
            SELECT id FROM documentos 
            WHERE alumno = a.id 
              AND archivo_Anteproyecto IS NOT NULL 
            ORDER BY id DESC 
            LIMIT 1
        )
        WHERE a.nombreProyecto IS NOT NULL AND a.nombreProyecto != ''";

$result = $conexion->query($sql);

if (!$result) {
    die("Error en la consulta: " . $conexion->error);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>EVALUACION</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="STYLES/styleJefe.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.min.js"></script>
  <style>
    #comentarios {
      background-color: white;
      color: black;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-top: 10px;
      text-align: left;
      max-height: 300px;
      overflow-y: auto;
    }
    .comentario-item {
      margin-bottom: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
      position: relative;
    }
    .comentario-texto {
      margin-right: 30px;
    }
    .comentario-fecha {
      font-size: 0.8em;
      color: #666;
    }
    .eliminar-comentario {
      position: absolute;
      right: 5px;
      top: 5px;
      background: #ff6b6b;
      color: white;
      border: none;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 12px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    #comentario {
      width: 100%;
      height: 100px;
      margin-top: 10px;
      padding: 10px;
      border-radius: 5px;
    }
    .botones {
      margin-top: 10px;
    }
    .swal2-popup {
      color: #000000 !important;
    }
    .swal2-title {
      color: #000000 !important;
    }
    .swal2-content {
      color: #000000 !important;
    }
    .modal-fullscreen {
      max-width: 100%;
      margin: 0;
    }
    .modal-fullscreen .modal-content {
      height: 100vh;
    }
    .modal-fullscreen .modal-body {
      overflow-y: auto;
    }
    .ver-anteproyecto {
      color: #007bff;
      cursor: pointer;
      text-decoration: underline;
    }
    .ver-anteproyecto:hover {
      color: #0056b3;
    }
    .error-message {
      color: red;
      font-weight: bold;
      margin: 20px 0;
    }
    #pdfCanvas {
      width: 100%;
      border: 1px solid #ccc;
    }
    #pdfContainer {
      text-align: center;
    }
    .pdf-message {
      margin: 20px 0;
      color: #666;
    }
  </style>
</head>
<body>
  <div class="container-fluid p-0 text-white text-center">
    <div class="barra-azul bg-primary">
      <h1></h1>
      <img src="Imagenes/logoazul.png" alt="Logo">
    </div>
    
    <div class="contenedor">
      <?php if (!$conexion): ?>
        <div class="error-message">
          Error: No se pudo establecer conexión con el servidor de base de datos
        </div>
      <?php endif; ?>
      
      <table class="table table-bordered">
        <thead>
          <tr>
            <th scope="col">Nombre del Anteproyecto</th>
            <th scope="col">Visualizar</th>
            <th scope="col">Aprobado</th>
            <th scope="col">Aprobado con Modificaciones</th>
            <th scope="col">No Aprobado</th>
          </tr>
        </thead>
        <tbody id="tabla">
          <?php
          if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                  $aprobadoChecked = $row['dictamen'] == 'APROBADO' ? 'checked' : '';
                  $modificacionesChecked = $row['dictamen'] == 'APROBADO CON MODIFICACIONES' ? 'checked' : '';
                  $noAprobadoChecked = $row['dictamen'] == 'NO APROBADO' ? 'checked' : '';
                  
                  $projectName = htmlspecialchars($row['nombreProyecto']);
                  $hasPdf = !empty($row['archivo_Anteproyecto']);
                  
                  echo "<tr>
                          <td>$projectName</td>
                          <td>";
                  
                  if ($hasPdf) {
                      echo "<span class='ver-anteproyecto' onclick='verAnteproyecto(\"".htmlspecialchars($row['archivo_Anteproyecto'])."\")'>
                              Ver Anteproyecto
                            </span>";
                  } else {
                      echo "<span class='text-muted'>No disponible</span>";
                  }
                  
                  echo "</td>
                          <td><input type='radio' name='eval_".$row['id']."' value='APROBADO' $aprobadoChecked></td>
                          <td><input type='radio' name='eval_".$row['id']."' value='APROBADO CON MODIFICACIONES' $modificacionesChecked></td>
                          <td><input type='radio' name='eval_".$row['id']."' value='NO APROBADO' $noAprobadoChecked></td>
                        </tr>";
              }
          } else {
              echo "<tr><td colspan='5'>No se encontraron alumnos con anteproyecto registrado</td></tr>";
          }
          $conexion->close();
          ?>
        </tbody>
      </table>

      <div class="comentario">
        <form method="POST" action="">
          <textarea id="comentario" name="comentario" placeholder="AÑADE TU COMENTARIO..."></textarea>
          <div class="botones">
            <button type="submit">ENVIAR</button>
          </div>
        </form>
      </div>

      <div class="comentarios" id="comentarios">
        <?php foreach ($comentarios as $index => $comentario): ?>
          <div class="comentario-item">
            <div class="comentario-texto"><?php echo htmlspecialchars($comentario['texto']); ?></div>
            <div class="comentario-fecha"><?php echo date('d/m/Y H:i', strtotime($comentario['fecha'])); ?></div>
            <form method="POST" action="" style="display: inline;">
              <input type="hidden" name="eliminar_comentario" value="<?php echo $index; ?>">
              <button type="submit" class="eliminar-comentario" title="Eliminar comentario">×</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="text-align: center; margin: 15px 0;">
        <button onclick="confirmarEvaluaciones()">CONFIRMAR EVALUACIÓN</button>
      </div>
    </div>
  </div>

  <!-- Modal para visualizar el PDF con pdf.js -->
  <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="pdfModalLabel">Visualización de Anteproyecto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="pdfContainer">
            <div id="pdfMessage" class="pdf-message" style="display: none;"></div>
            <canvas id="pdfCanvas"></canvas>
          </div>
          <div class="text-center mt-3" id="pdfControls" style="display: none;">
            <button class="btn btn-secondary me-2" id="prevPage">Anterior</button>
            <span id="pageNum">1</span> / <span id="pageCount">0</span>
            <button class="btn btn-secondary ms-2" id="nextPage">Siguiente</button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary" id="downloadPdf" style="display: none;">Descargar PDF</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.worker.min.js';
    
    let pdfDoc = null;
    let pageNum = 1;
    let pageRendering = false;
    let pageNumPending = null;
    const scale = 1.5;
    let currentPdfData = null;
    
    function renderPage(num) {
      pageRendering = true;
      pdfDoc.getPage(num).then(function(page) {
        const viewport = page.getViewport({ scale: scale });
        const canvas = document.getElementById('pdfCanvas');
        const context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        
        const renderContext = {
          canvasContext: context,
          viewport: viewport
        };
        
        const renderTask = page.render(renderContext);
        
        renderTask.promise.then(function() {
          pageRendering = false;
          if (pageNumPending !== null) {
            renderPage(pageNumPending);
            pageNumPending = null;
          }
        });
      });
      
      document.getElementById('pageNum').textContent = num;
    }
    
    function queueRenderPage(num) {
      if (pageRendering) {
        pageNumPending = num;
      } else {
        renderPage(num);
      }
    }
    
    function onPrevPage() {
      if (pageNum <= 1) {
        return;
      }
      pageNum--;
      queueRenderPage(pageNum);
    }
    
    function onNextPage() {
      if (pageNum >= pdfDoc.numPages) {
        return;
      }
      pageNum++;
      queueRenderPage(pageNum);
    }
    
    function verAnteproyecto(pdfData) {
      // Mostrar loader mientras se procesa el PDF
      Swal.fire({
        title: 'Cargando anteproyecto',
        html: 'Por favor espera mientras procesamos el documento...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      try {
        // Verificar que el pdfData no esté vacío
        if (!pdfData || pdfData.trim() === "") {
          throw new Error("El PDF está vacío o no se proporcionó datos.");
        }

        // Convertir el string a Uint8Array
        const raw = window.atob(pdfData);
        const rawLength = raw.length;
        const array = new Uint8Array(new ArrayBuffer(rawLength));
        
        for(let i = 0; i < rawLength; i++) {
          array[i] = raw.charCodeAt(i);
        }

        currentPdfData = array;

        pdfjsLib.getDocument({ data: array }).promise
          .then(function(pdf) {
            Swal.close();
            pdfDoc = pdf;
            document.getElementById('pageCount').textContent = pdf.numPages;
            pageNum = 1;
            renderPage(1);

            document.getElementById('pdfControls').style.display = 'block';
            document.getElementById('downloadPdf').style.display = 'block';
            document.getElementById('pdfMessage').style.display = 'none';
            document.getElementById('pdfCanvas').style.display = 'block';

            const pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
            pdfModal.show();
          })
          .catch(function(error) {
            Swal.close();
            document.getElementById('pdfMessage').textContent = 'Error al cargar el PDF: ' + error.message;
            document.getElementById('pdfMessage').style.display = 'block';
            document.getElementById('pdfControls').style.display = 'none';
            document.getElementById('downloadPdf').style.display = 'none';
            document.getElementById('pdfCanvas').style.display = 'none';
            
            const pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
            pdfModal.show();
            
            console.error("Error al cargar el PDF:", error);
          });
      } catch (error) {
        Swal.close();
        document.getElementById('pdfMessage').textContent = 'Error al procesar el PDF: ' + error.message;
        document.getElementById('pdfMessage').style.display = 'block';
        document.getElementById('pdfControls').style.display = 'none';
        document.getElementById('downloadPdf').style.display = 'none';
        document.getElementById('pdfCanvas').style.display = 'none';
        
        const pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
        pdfModal.show();
        
        console.error("Error en verAnteproyecto():", error);
      }
    }
    
    document.getElementById('prevPage').addEventListener('click', onPrevPage);
    document.getElementById('nextPage').addEventListener('click', onNextPage);

    document.getElementById('downloadPdf').addEventListener('click', function() {
      if (!currentPdfData) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No hay PDF para descargar',
          confirmButtonText: 'Aceptar'
        });
        return;
      }

      const blob = new Blob([currentPdfData], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'anteproyecto.pdf';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });

    function confirmarEvaluaciones() {
      const evaluaciones = [];
      document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const idAlumno = radio.name.split('_')[1];
        evaluaciones.push({
          id: idAlumno,
          dictamen: radio.value
        });
      });

      if (evaluaciones.length === 0) {
        Swal.fire({
          icon: 'error',
          title: 'Evaluación incompleta',
          text: 'Debes evaluar al menos un alumno',
          confirmButtonText: 'Entendido'
        });
        return;
      }

      Swal.fire({
        title: 'Procesando evaluaciones',
        html: 'Por favor espera mientras guardamos los resultados...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      fetch('dictamen.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ evaluaciones: evaluaciones })
      })
      .then(response => {
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        return response.json();
      })
      .then(data => {
        Swal.close();
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: '¡Evaluaciones guardadas!',
            text: 'Los dictámenes se han registrado correctamente',
            confirmButtonText: 'Aceptar'
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'Ocurrió un problema al guardar las evaluaciones',
            confirmButtonText: 'Entendido'
          });
        }
      })
      .catch(error => {
        Swal.close();
        Swal.fire({
          icon: 'error',
          title: 'Error de conexión',
          text: 'No se pudo conectar con el servidor para guardar las evaluaciones',
          confirmButtonText: 'Entendido'
        });
        console.error('Error:', error);
      });
    }
  </script>
</body>
</html>