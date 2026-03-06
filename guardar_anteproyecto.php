<?php
header('Content-Type: application/json');

$db_config = [
   'host' => "YOUR_DB_NAME",
    'user' => "YOUR_DB_USER",
    'pass' => "YOUR_DB_PASSWORD",
    'name' => "YOUR_DB_NAME"
];

try {
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    // Obtener datos del POST
    $id_alumno = $_POST['id_alumno'];
    $nombreProyecto = $_POST['nombreProyecto'];
    $archivo_anteproyecto = $_POST['archivo_anteproyecto'];

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // 1. Actualizar el nombre del proyecto en la tabla alumnos
        $stmt_alumno = $conn->prepare("UPDATE alumnos SET nombreProyecto = ?, status = 'revision anteproyecto' WHERE id = ?");
        $stmt_alumno->bind_param("si", $nombreProyecto, $id_alumno);
        $stmt_alumno->execute();

        // 2. Actualizar el archivo del anteproyecto en la tabla documentos
        $stmt_documento = $conn->prepare("UPDATE documentos SET archivo_anteproyecto = ? WHERE alumno = ?");
        $stmt_documento->bind_param("si", $archivo_anteproyecto, $id_alumno);
        $stmt_documento->execute();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Anteproyecto guardado correctamente'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>