<?php
session_start();

if(isset($_COOKIE['remember_email']) && isset($_COOKIE['remember_password']) && !isset($_SESSION['user_id'])) {
    $remembered_email = $_COOKIE['remember_email'];
    $remembered_password = $_COOKIE['remember_password'];
    
    $db_config = [
    'host' => 'YOUR_DB_HOST',
    'user' => 'YOUR_DB_USER',
    'pass' => 'YOUR_DB_PASSWORD',
    'name' => 'YOUR_DB_NAME'
    ];
    
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    $email = $conn->real_escape_string($remembered_email);
    $query = "SELECT id, nombre, email, password, tipo FROM login WHERE email = '$email'";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        if(password_verify($remembered_password, $usuario['password'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nombre'];
            $_SESSION['user_type'] = $usuario['tipo'];
            $_SESSION['user_email'] = $usuario['email'];
            
            redirigirUsuario($usuario['tipo'], $usuario['id'], $usuario['nombre']);
        }
    }
    $conn->close();
}

$registro = isset($_GET['registro']) ? $_GET['registro'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $db_config = [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '12345',
        'name' => 'residencia'
    ];
    
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $query = "SELECT id, nombre, email, password, tipo FROM login WHERE email = '$email'";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        if(password_verify($password, $usuario['password'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nombre'];
            $_SESSION['user_type'] = $usuario['tipo'];
            $_SESSION['user_email'] = $usuario['email'];
            
            if(isset($_POST['remember'])) {
                $cookie_expiry = time() + (30 * 24 * 60 * 60);
                setcookie('remember_email', $email, $cookie_expiry, '/');
                setcookie('remember_password', $password, $cookie_expiry, '/');
            } else {
                if(isset($_COOKIE['remember_email'])) {
                    setcookie('remember_email', '', time() - 3600, '/');
                }
                if(isset($_COOKIE['remember_password'])) {
                    setcookie('remember_password', '', time() - 3600, '/');
                }
            }
            
            redirigirUsuario($usuario['tipo'], $usuario['id'], $usuario['nombre']);
        } else {
            $error_login = "Contraseña incorrecta";
        }
    } else {
        $error_login = "Usuario no encontrado";
    }
    
    $conn->close();
}

function redirigirUsuario($tipo, $id, $nombre) {
    switch (strtolower($tipo)) {
        case 'alumno':
            $db_config = [
                'host' => 'localhost',
                'user' => 'root',
                'pass' => '12345',
                'name' => 'residencia'
            ];
            
            $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
            
            $nombre_limpio = $conn->real_escape_string($nombre);
            $query_alumno = "SELECT id, carrera, creditos FROM alumnos WHERE nombre = '$nombre_limpio'";
            $result_alumno = $conn->query($query_alumno);
            
            if ($result_alumno->num_rows > 0) {
                $alumno = $result_alumno->fetch_assoc();
                $alumno_id = $alumno['id'];
                
                $_SESSION['id_alumno'] = $alumno_id;
                
                if (!empty($alumno['carrera']) && $alumno['carrera'] !== '') {
                    $conn->close();
                    header("Location: test.php?id=" . $alumno_id);
                    exit();
                } else {
                    $conn->close();
                    header("Location: indexDocumentos.php");
                    exit();
                }
            } else {
                // Si no existe en la tabla alumnos, redirigir a subir documentos
                // Primero crear el registro en alumnos
                $query_insert = "INSERT INTO alumnos (nombre) VALUES ('$nombre_limpio')";
                if ($conn->query($query_insert)) {
                    $nuevo_id = $conn->insert_id;
                    $_SESSION['id_alumno'] = $nuevo_id;
                    $conn->close();
                    header("Location: indexDocumentos.php");
                    exit();
                } else {
                    // Si falla la inserción, redirigir igual a documentos
                    $conn->close();
                    header("Location: indexDocumentos.php");
                    exit();
                }
            }
            
        case 'asesor':
            $db_config = [
                'host' => 'YOUR_DB_HOST',
                'user' => 'YOUR_DB_USER',
                'pass' => 'YOUR_DB_PASSWORD',
                'name' => 'YOUR_DB_NAME'
            ];
            
            $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
            $query_asesor = "SELECT id FROM asesor WHERE nombre = '$nombre'";
            $result_asesor = $conn->query($query_asesor);
            
            if ($result_asesor->num_rows > 0) {
                $asesor = $result_asesor->fetch_assoc();
                $asesor_id = $asesor['id'];
                $conn->close();
                header("Location: indexAsesor.php?asesor_id=$asesor_id");
            } else {
                $conn->close();
                header("Location: indexAsesor.php");
            }
            exit();
            
        case 'jefe de academia':
            header("Location: indexJefe.php");
            exit();
            
        case 'coordinador':
            header("Location: indexCoordinador.php");
            exit();
            
        default:
            $error_login = "Tipo de usuario no válido";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="STYLES/Style.css">

     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body> 

    <div class="row">
        <div class="col-12 col-md-6 encabezado">
            <div class="titulo mx-3 mx-md-5">
                 <h1>SISTEMA GESTOR DE RESIDENCIA PROFESIONAL</h1>
            </div>
        </div>   
    </div> 

    <div class="barraAzul">
            <img src="Imagenes/LogoTECNM.png" alt="Logo TECNM">
            <img src="Imagenes/LogoMicro.png" alt="LogoMicro">
            <img src="Imagenes/Logo.png" alt="Logo">
            <img src="Imagenes/LogoEdu.png" alt="Logo">
            <img src="Imagenes/LogoJalisco.png" alt="LogoJalisco">
        </div>
    <main>
        <div class="contenedor__todo">     
            <div class="caja__trasera">
                <div class="caja__trasera-login">
                    <h3>¿Ya tienes una cuenta?</h3>
                    <p>Inicia sesión para entrar en la página</p>
                    <button id="btn__iniciar-sesion">Iniciar Sesión</button>
                </div>
                <div class="caja__trasera-register">
                    <h3>¿Aún no tienes una cuenta?</h3>
                    <p>Regístrate para que puedas iniciar sesión</p>
                    <button id="btn__registrarse">Regístrarse</button>
                </div>
            </div>
            <div class="contenedor__login-register">

                <form action="" method="POST" class="formulario__login">
                    <h2>Iniciar Sesión</h2>
                    <input type="text" name="email" placeholder="Correo Electronico" required 
                           value="<?php echo isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : ''; ?>">
                    <input type="password" name="password" placeholder="Contraseña" required>
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" 
                               <?php echo isset($_COOKIE['remember_email']) ? 'checked' : ''; ?>>
                        <label for="remember">Recordar mis datos</label>
                    </div>
                    <button type="submit" name="login">Entrar</button>
                </form>

                <form action="registrousuario.php" class="formulario__register" method="POST">
                    <h2>Regístrarse</h2>
                    <input name="nombre" type="text" placeholder="Nombre completo" required>
                    <input name="email" type="text" placeholder="Correo Electronico" required>
                    <input name="password" type="password" placeholder="Contraseña" required>
                    <input class="botonazul" type="submit" value="Regístrarse"/>
                </form>
            </div>
        </div>
    </main>
    <script src="js/scriptLogin.js"></script>

    <?php 
    
    if (isset($error_login)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error al iniciar sesión',
                text: '<?php echo $error_login; ?>',
                confirmButtonText: 'Aceptar'
            });
        </script>
    <?php endif; ?>

    <?php 
    if ($registro === 'exito'): ?>
        <script>
           
            Swal.fire({
                icon: 'success',
                title: '¡Registrado con éxito!',
                text: 'Ahora puedes iniciar sesión con tu cuenta.',
                confirmButtonText: 'Aceptar'
            });
        </script>
    <?php endif; ?>

    <?php 
    if ($registro === 'duplicado'): ?>
        <script>
         
            Swal.fire({
                icon: 'error',
                title: '¡Registro duplicado!',
                text: 'Contacta al Coordinador para cambiar contraseña',
                confirmButtonText: 'Aceptar'
            });
        </script>
    <?php endif; ?>

    <style>
        .remember-me {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        
        .remember-me input {
            margin-right: 8px;
        }
        
        .remember-me label {
            color: #555;
            font-size: 14px;
        }
    </style>
</body>
</html>