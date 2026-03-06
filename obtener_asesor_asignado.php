<?php
$db_config = [
    'host' => 'YOUR_HOST', 
    'user' => 'YOUR_DB_USER', 
    'pass' => 'YOUR_DB_PASSWORD', 
    'name' => 'YOUR_DB_NAME'
];

header('Content-Type: application/json');

$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);


if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conn->connect_error]));
}


$id_alumno = isset($_GET['id_alumno']) ? intval($_GET['id_alumno']) : 0;

if ($id_alumno <= 0) {
    echo json_encode(['asesor' => '--Por definir--']);
    exit;
}


$query_proyecto = "SELECT nombreProyecto FROM alumnos WHERE id = ?";
$stmt = $conn->prepare($query_proyecto);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['asesor' => '--Por definir--']);
    exit;
}

$alumno = $result->fetch_assoc();
$nombre_proyecto = $alumno['nombreProyecto'];

if (empty($nombre_proyecto)) {
    echo json_encode(['asesor' => '--Por definir--']);
    exit;
}

$query_asesor = "SELECT nombre FROM asesor WHERE nombreProyecto = ?";
$stmt = $conn->prepare($query_asesor);
$stmt->bind_param("s", $nombre_proyecto);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['asesor' => '--Por definir--']);
    exit;
}

$asesor = $result->fetch_assoc();
echo json_encode(['asesor' => $asesor['nombre']]);

$conn->close();
?>