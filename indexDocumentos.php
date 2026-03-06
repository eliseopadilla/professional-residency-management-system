<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentación</title>
    <link rel="stylesheet" href="STYLES/styleDocumentos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid p-2">
        <form id="documentosForm" action="documentos.php" method="POST" enctype="multipart/form-data">
            <header class="header bg-primary">
                <img src="Imagenes/logoazul.png" alt="Logo del tecnologico" class="logo">
            </header>
            <div class="container">
                <h2>Kardex</h2>
                <label for="fileInput" class="file-label">Seleccionar archivo</label>
                <input type="file" id="fileInput" accept="pdf" class="btt">
                <span id="fileName"></span>
            </div>
            <div class="container">
                <h2>Carta de liberación del servicio</h2>
                <label for="fileInput1" class="file-label">Seleccionar archivo</label> <!-- Cambié el 'for' -->
                <input type="file" id="fileInput1" accept="pdf" class="btt">
                <span id="fileName1"></span>
            </div>

            <button id="button-enviar-archivos" class="Enviar" type="submit">Enviar documentos</button>
        </form>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</html>