<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

// Obtener lista de entrenadores con conteo de clases
$entrenadores = $conn->query("
    SELECT e.*, COUNT(c.id_clase) as total_clases
    FROM entrenadores e 
    LEFT JOIN clases c ON e.id_entrenador = c.id_entrenador 
    GROUP BY e.id_entrenador 
    ORDER BY e.nombre
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Entrenadores - Admin</title>
    <link rel="stylesheet" href="reservaciones.css">
    <style>
        .actions a {
            margin: 5px 10px;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .edit-btn { background: #ffc107; color: black; margin: 5px 10px;}
        .delete-btn { background: #dc3545; color: white; margin: 5px 10px;}
        .classes-btn { background: #0d6efd; color: white; margin: 5px 10px;}
        .stat-badge {
            background: #002a5bcc;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
  <header>
    <h1>Lista de Entrenadores</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <div style="margin-bottom: 20px;">
      <a href="registrar_entrenador.php" style="background: #002b5b; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none;">
        Agregar Nuevo Entrenador
      </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Especialidad</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Clases</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while($entrenador = $entrenadores->fetch_assoc()): ?>
            <tr>
                <td><?php echo $entrenador['id_entrenador']; ?></td>
                <td><strong><?php echo $entrenador['nombre']; ?></strong></td>
                <td><?php echo $entrenador['especialidad']; ?></td>
                <td><?php echo $entrenador['telefono']; ?></td>
                <td><?php echo $entrenador['correo']; ?></td>
                <td>
                    <span class="stat-badge"><?php echo $entrenador['total_clases']; ?></span>
                </td>
                <td class="actions">
                    <a href="asignar_clases.php?id=<?php echo $entrenador['id_entrenador']; ?>" class="classes-btn">Clases</a><br>
                    <a href="editar_entrenador.php?id=<?php echo $entrenador['id_entrenador']; ?>" class="edit-btn">Editar</a><br>
                    <a href="eliminar_entrenador.php?id=<?php echo $entrenador['id_entrenador']; ?>" class="delete-btn" 
                       onclick="return confirm('¿Estás seguro de eliminar este entrenador?')">Eliminar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if($entrenadores->num_rows == 0): ?>
        <p style="text-align: center; color: #666; margin-top: 20px;">No hay entrenadores registrados.</p>
    <?php endif; ?>
  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Lista de Entrenadores</p>
  </footer>
</body>
</html>