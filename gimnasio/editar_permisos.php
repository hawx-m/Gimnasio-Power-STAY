<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// Obtener usuarios
$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY nombre_usuario");

// Procesar actualización de roles
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_roles'])) {
    foreach ($_POST['roles'] as $id_usuario => $nuevo_rol) {
        $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $nuevo_rol, $id_usuario);
        $stmt->execute();
    }
    $mensaje = "<div class='success'>Roles actualizados exitosamente</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Permisos y Roles - Admin</title>
    <link rel="stylesheet" href="reservaciones.css">
    <style>
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .role-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .user-row {
            display: grid;
            grid-template-columns: 2fr 2fr 2fr 1fr;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        .user-header {
            font-weight: bold;
            background: #f8f9fa;
            border-bottom: 2px solid #002b5b;
        }
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-container {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
  <header>
    <h1>Editar Permisos y Roles</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="lista_usuarios.php">Lista Usuarios</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <?php echo $mensaje; ?>
    
    <form method="post">
        <div class="role-form">
            <div class="user-row user-header">
                <div>Usuario</div>
                <div>Correo</div>
                <div>Rol Actual</div>
                <div>Nuevo Rol</div>
            </div>
            
            <?php while($user = $usuarios->fetch_assoc()): ?>
            <div class="user-row">
                <div><?php echo $user['nombre_usuario']; ?></div>
                <div><?php echo $user['correo']; ?></div>
                <div>
                    <span style="background: #002b5b; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">
                        <?php echo $user['rol']; ?>
                    </span>
                </div>
                <div>
                    <select name="roles[<?php echo $user['id_usuario']; ?>]">
                        <option value="administrador" <?php echo $user['rol'] == 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                        <option value="editor" <?php echo $user['rol'] == 'editor' ? 'selected' : ''; ?>>Editor</option>
                        <option value="consultor" <?php echo $user['rol'] == 'consultor' ? 'selected' : ''; ?>>Consultor</option>
                    </select>
                </div>
            </div>
            <?php endwhile; ?>
        </div><br>

        <div class="btn-container">
            <button type="submit" name="actualizar_roles" style="background: #002b5b; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                Guardar Cambios de Roles
            </button>
        </div>
    </form>

    <div style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 10px;">
        <h3>Descripción de Roles</h3>
        <ul>
            <li><strong>Administrador:</strong> Acceso completo a todas las funciones del sistema</li>
            <li><strong>Editor:</strong> Puede gestionar clases, entrenadores y reservaciones</li>
            <li><strong>Consultor:</strong> Solo puede ver reportes y estadísticas</li>
        </ul>
    </div>
  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Edición de Roles</p>
  </footer>
</body>
</html>