<?php
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

$db_config = [
    'host' => 'YOUR_DB_NAME',
    'user' => 'YOUR_DB_USER',
    'pass' => 'YOUR_DB_PASSWORD',
    'name' => 'YOUR_DB_NAME'
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    $json = file_get_contents('php://input');
    if ($json === false) {
        throw new Exception('Error al leer los datos de entrada');
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    if (!isset($data['id_alumno']) || !isset($data['asesores'])) {
        throw new Exception('Datos incompletos: id_alumno y asesores son requeridos');
    }

    $id_alumno = filter_var($data['id_alumno'], FILTER_VALIDATE_INT);
    if ($id_alumno === false || $id_alumno <= 0) {
        throw new Exception('ID de alumno inválido');
    }

    $asesores = trim($data['asesores']);
    if (empty($asesores)) {
        throw new Exception('La lista de asesores no puede estar vacía');
    }

    $asesoresArray = array_filter(array_map('trim', explode(',', $asesores)));
    if (count($asesoresArray) < 3) {
        throw new Exception('Debe proporcionar al menos 3 asesores');
    }
    if (count($asesoresArray) > 3) {
        throw new Exception('Solo puede proporcionar máximo 3 asesores');
    }

    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos: ' . $conn->connect_error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Asesores guardados correctamente'
    ]);

} catch (Exception $e) {
    error_log('Error en guardar_asesores.php: ' . $e->getMessage());
    
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>