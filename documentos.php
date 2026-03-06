<?php
session_start();

if (!isset($_SESSION['id_alumno'])) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Debes iniciar sesión primero',
        'code' => 401
    ]);
    exit;
}

$id_alumno = $_SESSION['id_alumno'];

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require __DIR__.'/vendor/autoload.php';

$db_config = [
    'host' => 'sql204.infinityfree.com',
    'user' => 'if0_40393242',
    'pass' => 'ZBCbazcIqTyh',
    'name' => 'if0_40393242_residencia'
];

try {
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $conn->connect_error, 500);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido", 405);
    }

    if (!isset($_FILES['kardex']) || !isset($_FILES['liberacion'])) {
        throw new Exception("Debes subir ambos archivos (kardex y liberación)", 400);
    }

    $stmt_exist = $conn->prepare("SELECT id FROM documentos WHERE alumno = ?");
    $stmt_exist->bind_param("i", $id_alumno);
    $stmt_exist->execute();
    $stmt_exist->store_result();

    if ($stmt_exist->num_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Ya has subido tus documentos previamente',
            'redirect_url' => 'http://localhost/proy/test.php?id=' . $id_alumno
        ]);
        exit;
    }
    $stmt_exist->close();

    $kardex = $_FILES['kardex'];
    $liberacion = $_FILES['liberacion'];
   
    $max_size = 5 * 1024 * 1024; 
    if ($kardex['size'] > $max_size || $liberacion['size'] > $max_size) {
        throw new Exception("El tamaño máximo por archivo es 5MB", 400);
    }

    if ($kardex['type'] !== 'application/pdf' || $liberacion['type'] !== 'application/pdf') {
        throw new Exception("Solo se permiten archivos PDF", 400);
    }

    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($kardex['tmp_name']);
    $text = $pdf->getText();

    error_log("Texto del PDF:\n" . $text);

    $kardexData = extractKardexData($text);

    $kardexContent = file_get_contents($kardex['tmp_name']);
    $liberacionContent = file_get_contents($liberacion['tmp_name']);

    if ($kardexContent === false || $liberacionContent === false) {
        throw new Exception("Error al procesar los archivos", 500);
    }

    $kardexBase64 = base64_encode($kardexContent);
    $liberacionBase64 = base64_encode($liberacionContent);
    $emptyContent = base64_encode(''); 

    $conn->begin_transaction();

    try {
        $stmt_docs = $conn->prepare("INSERT INTO documentos (alumno, archivo_kardex, archivo_liberacion, fecha_subida, archivo_anteproyecto) 
                                   VALUES (?, ?, ?, NOW(), ?)");
        $stmt_docs->bind_param("isss", 
            $id_alumno,
            $kardexBase64,
            $liberacionBase64,
            $emptyContent
        );
        $stmt_docs->execute();

        $stmt_update = $conn->prepare("UPDATE alumnos SET 
                                     nombre = ?,
                                     carrera = ?,
                                     creditos = ? 
                                     WHERE id = ?");
        $stmt_update->bind_param("ssii",
            $kardexData['nombre_alumno'] ?? null,
            $kardexData['carrera'] ?? null,
            $kardexData['creditos_aprobados'] ?? null,
            $id_alumno
        );
        $stmt_update->execute();

        $conn->commit();

        $new_id = $stmt_docs->insert_id;

        echo json_encode([
            'success' => true,
            'message' => 'Documentos registrados exitosamente',
            'redirect_url' => 'http://localhost/proy/test.php?id='.$id_alumno,
            'documento_id' => $new_id,
            'alumno' => [
                'id' => $id_alumno,
                'nombre' => $kardexData['nombre_alumno'] ?? '',
                'carrera' => $kardexData['carrera'] ?? '',
                'creditos' => $kardexData['creditos_aprobados'] ?? 0,
                'creditos_totales' => $kardexData['creditos_totales'] ?? 260
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'debug' => isset($text) ? substr($text, 0, 200) : null
    ]);
    exit;
}

function extractKardexData($text) {
    $data = [
        'id_alumno' => null,
        'nombre_alumno' => null,
        'carrera' => null,
        'creditos_aprobados' => null,
        'creditos_totales' => null
    ];

    // Extraer número de control (9 dígitos exactos antes del '-') y nombre completo
    if (preg_match('/ESTUDIANTE:\s*(\d{9})\s*-\s*([^\n]+)/i', $text, $matches)) {
        $data['id_alumno'] = trim($matches[1]);
        $data['nombre_alumno'] = trim(preg_replace('/\s+/', ' ', $matches[2]));
    }

    // Extraer carrera
    if (preg_match('/CARRERA:\s*[^-]+-\s*([^\n\/]+)/i', $text, $matches)) {
        $data['carrera'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
    }

    // Extraer créditos
    if (preg_match('/CRÉDITOS:\s*(\d+)\s*APROBADOS\s*DE\s*(\d+)/i', $text, $matches)) {
        $data['creditos_aprobados'] = (int)$matches[1];
        $data['creditos_totales'] = (int)$matches[2];
    }

    return $data;
}
