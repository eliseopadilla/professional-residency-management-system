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

    $id_alumno = isset($_GET['id_alumno']) ? intval($_GET['id_alumno']) : 0;

    if ($id_alumno <= 0) {
        throw new Exception("ID de alumno inválido");
    }

    $stmt_documento = $conn->prepare("SELECT archivo_anteproyecto FROM documentos WHERE alumno = ?");
    $stmt_documento->bind_param("i", $id_alumno);
    $stmt_documento->execute();
    $result_documento = $stmt_documento->get_result();
    
    if ($result_documento->num_rows === 0) {
        throw new Exception("No se encontró el anteproyecto para este alumno");
    }

    $documento = $result_documento->fetch_assoc();

    echo json_encode([
        'success' => true,
        'archivo' => $documento['archivo_anteproyecto']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>