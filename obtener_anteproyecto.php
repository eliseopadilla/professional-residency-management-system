<?php
header('Content-Type: application/json');

$db_config = [
    'host' => 'YOUR_HOST', 
    'user' => 'YOUR_DB_USER', 
    'pass' => 'YOUR_DB_PASSWORD', 
    'name' => 'YOUR_DB_NAME'
];

try {
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    $id_alumno = $_GET['id_alumno'];

    $stmt_alumno = $conn->prepare("SELECT nombreProyecto FROM alumnos WHERE id = ?");
    $stmt_alumno->bind_param("i", $id_alumno);
    $stmt_alumno->execute();
    $result_alumno = $stmt_alumno->get_result();
    $alumno = $result_alumno->fetch_assoc();

    $stmt_documento = $conn->prepare("SELECT archivo_anteproyecto FROM documentos WHERE alumno = ?");
    $stmt_documento->bind_param("i", $id_alumno);
    $stmt_documento->execute();
    $result_documento = $stmt_documento->get_result();
    $documento = $result_documento->fetch_assoc();

    echo json_encode([
        'success' => true,
        'nombreProyecto' => $alumno['nombreProyecto'] ?? null,
        'archivo_anteproyecto' => $documento['archivo_anteproyecto'] ?? null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>