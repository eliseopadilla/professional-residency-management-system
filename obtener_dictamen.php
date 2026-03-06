<?php
header('Content-Type: application/json');

$db_config = [
    'host' => 'YOUR_HOST',
    'user' => 'YOUR_DB_USER',
    'pass' => 'YOUR_DB_PASSWORD',
    'name' => 'YOUR_DB_NAME'
];

$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conn->connect_error]));
}

$id_alumno = $_GET['id_alumno'] ?? null;

if (!$id_alumno) {
    die(json_encode(['success' => false, 'message' => 'ID de alumno no proporcionado']));
}

$query = "SELECT dictamen FROM alumnos WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'dictamen' => $row['dictamen'] ?? '--Por definir--']);
} else {
    echo json_encode(['success' => false, 'message' => 'Alumno no encontrado']);
}

$stmt->close();
$conn->close();
?>