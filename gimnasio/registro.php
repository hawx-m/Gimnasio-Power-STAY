<?php
session_start();
include("conexion.php");

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    
    try {
        // Verificar si el usuario ya existe
        $verificar = $conn->query("SELECT * FROM usuarios WHERE nombre_usuario='$usuario' OR correo='$correo'");
        if ($verificar->num_rows > 0) {
            throw new Exception('El usuario o correo ya existe.');
        }
        
        // Usar hash para contraseñas
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        
        $sql_usuario = "INSERT INTO usuarios (nombre_usuario, contraseña, correo, rol) 
                       VALUES ('$usuario', '$contrasena_hash', '$correo', 'cliente')";
        
        if (!$conn->query($sql_usuario)) {
            throw new Exception('Error al registrar el usuario: ' . $conn->error);
        }
        
        // Guardar en sesión
        $_SESSION['usuario'] = $usuario;
        $_SESSION['rol'] = 'cliente';
        
        $mensaje = "<script>
            alert('Registro completado exitosamente'); 
            window.location='login.php';
        </script>";
        
    } catch (Exception $e) {
        $mensaje = "<script>alert('" . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php echo $mensaje; ?>
    
    <div class="formulario">
        <h1>📝 REGISTRARSE</h1>
        <form method="post">
            <div class="usuario">
                <input type="text" name="nombre" required>
                <label>Nombre completo</label>
            </div>
            <div class="usuario">
                <input type="email" name="correo" required>
                <label>Correo electrónico</label>
            </div>
            <div class="usuario">
                <input type="text" name="usuario" required minlength="4">
                <label>Nombre de usuario</label>
            </div>
            <div class="usuario">
                <input type="password" name="contrasena" required minlength="6">
                <label>Contraseña (mínimo 6 caracteres)</label>
            </div>
            <div>
                <input type="submit" value="Registrar">
            </div>
            <div class="registrarse">
                <a href="login.php">Volver al inicio de sesión</a>
            </div>
        </form>
    </div>
</body>
</html>