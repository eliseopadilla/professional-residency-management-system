<?php
require_once 'evaluacion.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$evaluaciones = $data['evaluaciones'];

$conexion = conectarBD();
$resultados = [];
$success = true;
$message = '';

try {
    $stmt = $conexion->prepare("UPDATE alumnos SET dictamen = ? WHERE id = ?");
    
    foreach ($evaluaciones as $eval) {
        $stmt->bind_param("ss", $eval['dictamen'], $eval['id']);
        if (!$stmt->execute()) {
            $success = false;
            $message = "Error al actualizar alumno ID: " . $eval['id'];
            break;
        }
    }
    
    $stmt->close();
} catch (Exception $e) {
    $success = false;
    $message = $e->getMessage();
}

$conexion->close();

echo json_encode([
    'success' => $success,
    'message' => $message
]);
?>