<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    
    // Configuración de la subida de archivos
    $directorioSubida = "uploads/";
    $archivoSubido = $directorioSubida . basename($_FILES["archivo"]["name"]);
    $tipoArchivo = strtolower(pathinfo($archivoSubido, PATHINFO_EXTENSION));
    $tamanoArchivo = $_FILES["archivo"]["size"];
    
    $errores = [];
    
    if ($tipoArchivo != "pdf") {
        $errores[] = "Solo se permiten archivos PDF.";
    }
    
    if ($tamanoArchivo > 5000000) {
        $errores[] = "El archivo es demasiado grande. Tamaño máximo: 5MB.";
    }
    
    if (empty($errores)) {
        if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $archivoSubido)) {
            echo "<h2>Archivo subido correctamente</h2>";
            echo "<p><strong>Nombre:</strong> $nombre</p>";
            echo "<p><strong>Email:</strong> $email</p>";
            echo "<p><strong>Archivo:</strong> " . htmlspecialchars(basename($_FILES["archivo"]["name"])) . "</p>";
        } else {
            echo "<p>Hubo un error al subir el archivo.</p>";
        }
    } else {
        echo "<h2>Errores encontrados:</h2>";
        foreach ($errores as $error) {
            echo "<p>- $error</p>";
        }
        echo '<p><a href="javascript:history.back()">Volver al formulario</a></p>';
    }
} else {
    header("Location: index.html");
    exit;
}
?>