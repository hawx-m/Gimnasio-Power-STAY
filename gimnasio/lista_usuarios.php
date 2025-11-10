<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

// Obtener lista de usuarios
$usuarios = $conn->query("
    SELECT u.*, c.nombre as nombre_cliente, c.telefono 
    FROM usuarios u 
    LEFT JOIN clientes c ON u.id_cliente = c.id_cliente 
    ORDER BY u.fecha_creacion DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Usuarios - Admin</title>
    <link rel="stylesheet" href="reservaciones.css">
    <style>
        .actions a {
            margin: 0 5px;
            text-decoration: none;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        .edit-btn { background: #ffc107; color: black; }
        .delete-btn { background: #dc3545; color: white; }
        .role-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .role-admin { background: #dc3545; color: white; }
        .role-editor { background: #fd7e14; color: white; }
        .role-consultor { background: #20c997; color: white; }
        .role-cliente { background: #0d6efd; color: white; }
        .role-entrenador { background: #6f42c1; color: white; }
    </style>
</head>
<body>
  <header>
    <h1>Lista de Usuarios</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <div style="margin-bottom: 20px;">
      <a href="gestion_usuarios.php" style="background: #002b5b; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none;">
        Agregar Nuevo Usuario
      </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Cliente Asociado</th>
                <th>Teléfono</th>
                <th>Fecha Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while($user = $usuarios->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id_usuario']; ?></td>
                <td><?php echo $user['nombre_usuario']; ?></td>
                <td><?php echo $user['correo']; ?></td>
                <td>
                    <span class="role-badge role-<?php echo $user['rol']; ?>">
                        <?php echo $user['rol']; ?>
                    </span>
                </td>
                <td><?php echo $user['nombre_cliente'] ?? 'N/A'; ?></td>
                <td><?php echo $user['telefono'] ?? 'N/A'; ?></td>
                <td><?php echo date('d/m/Y', strtotime($user['fecha_creacion'])); ?></td>
                <td class="actions">
                    <a href="editar_usuario.php?id=<?php echo $user['id_usuario']; ?>" class="edit-btn">Editar</a>
                    <a href="eliminar_usuario.php?id=<?php echo $user['id_usuario']; ?>" class="delete-btn" 
                       onclick="return confirm('¿Estás seguro de eliminar este usuario?')">Eliminar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if($usuarios->num_rows == 0): ?>
        <p style="text-align: center; color: #666; margin-top: 20px;">No hay usuarios registrados.</p>
    <?php endif; ?>
  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Lista de Usuarios</p>
  </footer>
</body>
</html>