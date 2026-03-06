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
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}

$id_alumno = $_GET['id'] ?? 0;

$query = "SELECT status, nombreProyecto, archivo_anteproyecto, asesores, dictamen FROM alumnos WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    $permite_edicion = empty($row['dictamen']) || 
        in_array(strtolower($row['dictamen']), ['aprobado con modificaciones', 'no aprobado']);
    
    echo json_encode([
        'anteproyecto' => [
            'nombre' => $row['nombreProyecto'],
            'archivo' => $row['archivo_anteproyecto'],
            'permite_edicion' => $permite_edicion
        ],
        'asesores' => $row['asesores'],
        'dictamen' => $row['dictamen'],
        'status' => $row['status']
    ]);
} else {
    echo json_encode(['error' => 'Alumno no encontrado']);
}

$stmt->close();
$conn->close();
?>