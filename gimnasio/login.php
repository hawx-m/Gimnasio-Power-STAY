<?php
session_start();
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    $rol = $_POST['rol'];
    
    $sql = "SELECT u.*, c.id_cliente 
            FROM usuarios u 
            LEFT JOIN clientes c ON u.correo = c.correo 
            WHERE u.nombre_usuario = ? AND u.rol = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usuario, $rol);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($contrasena, $user['contraseña'])) {
            $_SESSION['usuario'] = $user['nombre_usuario'];
            $_SESSION['id_cliente'] = $user['id_cliente'];
            $_SESSION['rol'] = $user['rol'];
            
            // Redirigir según el rol
            switch($rol) {
                case 'administrador':
                    header("Location: panel_admin.php");
                    break;
                case 'editor':
                    header("Location: panel_editor.php");
                    break;
                case 'consultor':
                    header("Location: calendario_consultor.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            echo "<script>alert('Contraseña incorrecta');</script>";
        }
    } else {
        echo "<script>alert('Usuario no encontrado o rol incorrecto');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .rol-selector {
            margin: 20px 0;
        }
        .rol-selector select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="formulario">
        <h1>💪 INICIAR SESIÓN</h1>
        <form method="post">
            <div class="usuario">
                <input type="text" name="usuario" required>
                <label>Nombre de usuario</label>
            </div>
            <div class="usuario">
                <input type="password" name="contrasena" required>
                <label>Contraseña</label>
            </div>
            <div class="rol-selector">
                <select name="rol" required>
                    <option value="">Selecciona tu rol</option>
                    <option value="administrador">Administrador</option>
                    <option value="editor">Editor</option>
                    <option value="consultor">Consultor</option>
                </select>
            </div>
            <div>
                <input type="submit" value="Iniciar Sesión">
            </div>
            <div class="registrarse">
                ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
            </div>
        </form>
    </div>
</body>
</html>