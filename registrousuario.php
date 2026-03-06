<?php
$nombre = $_POST['nombre'];
$email = $_POST['email'];
$password = $_POST['password'];

$servername = "YOUR_HOST";
$username = "YOUR_DB_USER";
$password_db = "YOUR_DB_PASSWORD";
$dbname = "YOUR_DB_NAME";

$conn = new mysqli($servername, $username, $password_db, $dbname);


if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$sql_check = "SELECT email FROM login WHERE email = ?";
$stmt_check = $conn->prepare($sql_check);

if ($stmt_check === false) {
    die("Error al preparar la consulta de verificación: " . $conn->error);
}

$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    $stmt_check->close();
    $conn->close();
    header("Location: indexLogin.php?registro=duplicado");
    exit();
}

$tipo = 'sin permisos';
if (preg_match('/^ch\d+@chapala\.tecmm\.edu\.mx$/', $email)) {
    $tipo = 'Alumno';
}

$sql_login = "INSERT INTO login (id, nombre, email, password, tipo) 
              VALUES (0, ?, ?, ?, ?)";


$stmt_login = $conn->prepare($sql_login);
if ($stmt_login === false) {
    die("Error al preparar la consulta de login: " . $conn->error);
}


$password_hash = password_hash($password, PASSWORD_DEFAULT);


$stmt_login->bind_param("ssss", $nombre, $email, $password_hash, $tipo);


if ($stmt_login->execute()) {
    if (preg_match('/^ch\d+@chapala\.tecmm\.edu\.mx$/', $email)) {
     
        $id_alumno = preg_replace('/^ch(\d+)@.*$/', '$1', $email);
        
     
        $sql_alumno = "INSERT INTO alumnos (id, nombre, carrera, creditos, calificacion, nombreProyecto, status, dictamen) 
                       VALUES (?, ?, '', 0, 0, '', 'inactivo', '')";
        
        $stmt_alumno = $conn->prepare($sql_alumno);
        if ($stmt_alumno === false) {
            die("Error al preparar la consulta de alumno: " . $conn->error);
        }
        
   
        $stmt_alumno->bind_param("is", $id_alumno, $nombre);
        
        if (!$stmt_alumno->execute()) {
        
            $stmt_alumno->close();
            $stmt_login->close();
            $stmt_check->close();
            $conn->close();
            header("Location: indexLogin.php?registro=error_alumno");
            exit();
        }
        $stmt_alumno->close();
    }
    
    $stmt_login->close();
    $stmt_check->close();
    $conn->close();
    header("Location: indexLogin.php?registro=exito");
    exit();
} else {

    $stmt_login->close();
    $stmt_check->close();
    $conn->close();
    header("Location: indexLogin.php?registro=error_login");
    exit();
}
?>