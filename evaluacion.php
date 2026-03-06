<?php
function conectarBD() {
    $servidor = "YOUR_DB_HOST";
    $usuario = "YOUR_DB_USER";;
    $contrasena = "YOUR_DB_PASSWORD";
    $basedatos = "YOUR_DB_NAME";

    
    $conexion = new mysqli($servidor, $usuario, $contrasena, $basedatos);

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $conexion->set_charset("utf8");
    return $conexion;
}
?>