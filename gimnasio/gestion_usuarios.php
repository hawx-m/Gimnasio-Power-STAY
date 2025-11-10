<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    $rol = $_POST['rol'];
    $telefono = $_POST['telefono'];
    $edad = $_POST['edad'];
    
    $conn->begin_transaction();
    
    try {
        // Verificar si el usuario ya existe
        $verificar = $conn->query("SELECT * FROM usuarios WHERE nombre_usuario='$usuario' OR correo='$correo'");
        if ($verificar->num_rows > 0) {
            throw new Exception('El usuario o correo ya existe.');
        }

        // Si es cliente, crear registro en tabla clientes
        if ($rol == 'cliente') {
            $sql_cliente = "INSERT INTO clientes (nombre, edad, telefono, correo, fecha_registro, membresia) 
                           VALUES ('$nombre', '$edad', '$telefono', '$correo', CURDATE(), 'Básica')";
            
            if (!$conn->query($sql_cliente)) {
                throw new Exception('Error al registrar el cliente: ' . $conn->error);
            }
            $id_cliente = $conn->insert_id;
        } else {
            $id_cliente = NULL;
        }

        // Hash de contraseña
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        
        // Insertar en usuarios
        $sql_usuario = "INSERT INTO usuarios (nombre_usuario, contraseña, correo, rol, id_cliente) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_usuario);
        $stmt->bind_param("ssssi", $usuario, $contrasena_hash, $correo, $rol, $id_cliente);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al registrar el usuario: ' . $stmt->error);
        }
        
        $conn->commit();
        $mensaje = "<div class='success'>Usuario registrado exitosamente</div>";
        
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "<div class='error'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .nav-links {
            text-align: center;
            margin: 20px 0;
        }
        .nav-links a {
            color: #002b5b;
            text-decoration: none;
            margin: 0 10px;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="nav-links">
            <a href="panel_admin.php">← Volver al Panel</a> | 
            <a href="lista_usuarios.php">Ver Lista de Usuarios</a>
        </div>

        <div class="formulario">
            <h1>Registrar Nuevo Usuario</h1>
            <?php echo $mensaje; ?>
            <form method="post">
                <div class="usuario">
                    <input type="text" name="nombre" required>
                    <label>Nombre completo *</label>
                </div>
                <div class="usuario">
                    <input type="email" name="correo" required>
                    <label>Correo electrónico *</label>
                </div>
                <div class="usuario">
                    <input type="text" name="usuario" required minlength="4">
                    <label>Nombre de usuario *</label>
                </div>
                <div class="usuario">
                    <input type="password" name="contrasena" required minlength="6">
                    <label>Contraseña * (mínimo 6 caracteres)</label>
                </div>
                <div class="usuario">
                    <select name="rol" required>
                        <option value="">Seleccionar rol *</option>
                        <option value="administrador">Administrador</option>
                        <option value="editor">Editor</option>
                        <option value="consultor">Consultor</option>
                        <option value="cliente">Cliente</option>
                        <option value="entrenador">Entrenador</option>
                    </select>
                    <label>Rol del usuario</label>
                </div>
                <div class="usuario">
                    <input type="tel" name="telefono">
                    <label>Teléfono</label>
                </div>
                <div class="usuario">
                    <input type="number" name="edad" min="16" max="100">
                    <label>Edad</label>
                </div>
                <div>
                    <input type="submit" value="Registrar Usuario">
                </div>
            </form>
        </div>
    </div>
</body>
</html>