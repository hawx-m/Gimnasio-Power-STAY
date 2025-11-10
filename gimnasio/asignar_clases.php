<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$id_entrenador = $_GET['id'] ?? null;
if (!$id_entrenador) {
    header("Location: lista_entrenadores.php");
    exit();
}

// Obtener información del entrenador
$entrenador = $conn->query("SELECT * FROM entrenadores WHERE id_entrenador = $id_entrenador")->fetch_assoc();
$clases_entrenador = $conn->query("SELECT * FROM clases WHERE id_entrenador = $id_entrenador");
$clases_disponibles = $conn->query("SELECT * FROM clases WHERE id_entrenador IS NULL OR id_entrenador = ''");

$mensaje = "";

// Asignar nueva clase
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['asignar_clase'])) {
    $id_clase = $_POST['id_clase'];
    
    try {
        $stmt = $conn->prepare("UPDATE clases SET id_entrenador = ? WHERE id_clase = ?");
        $stmt->bind_param("ii", $id_entrenador, $id_clase);
        
        if ($stmt->execute()) {
            $mensaje = "<div class='success'>Clase asignada exitosamente</div>";
            header("Refresh:2");
        } else {
            throw new Exception('Error al asignar la clase: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        $mensaje = "<div class='error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Remover clase
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remover_clase'])) {
    $id_clase = $_POST['id_clase'];
    
    try {
        $stmt = $conn->prepare("UPDATE clases SET id_entrenador = NULL WHERE id_clase = ?");
        $stmt->bind_param("i", $id_clase);
        
        if ($stmt->execute()) {
            $mensaje = "<div class='success'>Clase removida exitosamente</div>";
            header("Refresh:2");
        } else {
            throw new Exception('Error al remover la clase: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        $mensaje = "<div class='error'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Clases - Admin</title>
    <link rel="stylesheet" href="reservaciones.css">
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
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .clases-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .clases-section {
                grid-template-columns: 1fr;
            }
        }
        .clase-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #002b5b;
        }
        .btn-small {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        .btn-assign { background: #28a745; color: white; }
        .btn-remove { background: #dc3545; color: white; }
    </style>
</head>
<body>
  <header>
    <h1>Asignar Clases a Entrenador</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="lista_entrenadores.php">Lista Entrenadores</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <?php echo $mensaje; ?>
    
    <div class="info-card">
        <h2>Entrenador: <?php echo $entrenador['nombre']; ?></h2>
        <p><strong>Especialidad:</strong> <?php echo $entrenador['especialidad']; ?></p>
        <p><strong>Correo:</strong> <?php echo $entrenador['correo']; ?></p>
        <p><strong>Teléfono:</strong> <?php echo $entrenador['telefono']; ?></p>
    </div>

    <div class="clases-section">
        <!-- Clases asignadas -->
        <div>
            <h3>Clases Asignadas</h3>
            <?php if ($clases_entrenador->num_rows > 0): ?>
                <?php while($clase = $clases_entrenador->fetch_assoc()): ?>
                <div class="clase-card">
                    <strong><?php echo $clase['nombre_clase']; ?></strong>
                    <p><small>Horario: <?php echo $clase['horario']; ?></small></p>
                    <p><small>Capacidad: <?php echo $clase['capacidad']; ?> personas</small></p>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="id_clase" value="<?php echo $clase['id_clase']; ?>">
                        <button type="submit" name="remover_clase" class="btn-small btn-remove">Remover</button>
                    </form>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No hay clases asignadas.</p>
            <?php endif; ?>
        </div>

        <!-- Clases disponibles para asignar -->
        <div>
            <h3>Asignar Nueva Clase</h3>
            <?php if ($clases_disponibles->num_rows > 0): ?>
                <?php while($clase = $clases_disponibles->fetch_assoc()): ?>
                <div class="clase-card">
                    <strong><?php echo $clase['nombre_clase']; ?></strong>
                    <p><small>Horario: <?php echo $clase['horario']; ?></small></p>
                    <p><small>Capacidad: <?php echo $clase['capacidad']; ?> personas</small></p>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="id_clase" value="<?php echo $clase['id_clase']; ?>">
                        <button type="submit" name="asignar_clase" class="btn-small btn-assign">Asignar</button>
                    </form>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No hay clases disponibles para asignar.</p>
            <?php endif; ?>
        </div>
    </div>
  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Asignación de Clases</p>
  </footer>
</body>
</html>