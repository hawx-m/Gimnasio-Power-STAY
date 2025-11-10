<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// Obtener clases para editar cupos
$clases = $conn->query("
    SELECT c.*, e.nombre as nombre_entrenador,
           COUNT(r.id_reservacion) as reservaciones_activas
    FROM clases c 
    LEFT JOIN entrenadores e ON c.id_entrenador = e.id_entrenador 
    LEFT JOIN reservaciones r ON c.id_clase = r.id_clase AND r.estado IN ('Pendiente', 'Confirmada')
    GROUP BY c.id_clase 
    ORDER BY c.nombre_clase
");

// Actualizar cupos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_cupos'])) {
    foreach ($_POST['cupos'] as $id_clase => $nueva_capacidad) {
        // Verificar que la nueva capacidad no sea menor que las reservaciones activas
        $clase_data = $conn->query("
            SELECT COUNT(*) as reservas_activas 
            FROM reservaciones 
            WHERE id_clase = $id_clase AND estado IN ('Pendiente', 'Confirmada')
        ")->fetch_assoc();
        
        if ($nueva_capacidad >= $clase_data['reservas_activas']) {
            $stmt = $conn->prepare("UPDATE clases SET capacidad = ? WHERE id_clase = ?");
            $stmt->bind_param("ii", $nueva_capacidad, $id_clase);
            $stmt->execute();
        }
    }
    $mensaje = "<div class='success'>Cupos actualizados exitosamente</div>";
    header("Refresh:2");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Definir Cupos Máximos - Admin</title>
    <link rel="stylesheet" href="reservaciones.css">
    <style>
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #ffeaa7;
        }
        .clase-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        .clase-header {
            font-weight: bold;
            background: #f8f9fa;
            border-bottom: 2px solid #002b5b;
        }
        .cupo-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .btn-container {
            text-align: center;
            margin: 20px 0;
        }
        .reserva-warning {
            color: #dc3545;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .reserva-ok {
            color: #28a745;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
  <header>
    <h1>Definir Cupos Máximos</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="lista_clases.php">Lista Clases</a> | 
      <a href="configurar_horarios.php">Configurar Horarios</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <?php echo $mensaje; ?>
    
    <div class="warning">
      <strong>⚠️ Importante:</strong> No puedes establecer un cupo menor al número de reservaciones activas. 
      Las reservaciones activas se muestran en la columna "Reservas Activas".
    </div>

    <form method="post">
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div class="clase-row clase-header">
                <div>Nombre Clase</div>
                <div>Entrenador</div>
                <div>Horario</div>
                <div>Cupo Actual</div>
                <div>Reservas Activas</div>
                <div>Nuevo Cupo</div>
            </div>
            
            <?php while($clase = $clases->fetch_assoc()): 
                $puede_reducir = $clase['reservaciones_activas'] == 0;
            ?>
            <div class="clase-row">
                <div><strong><?php echo $clase['nombre_clase']; ?></strong></div>
                <div><?php echo $clase['nombre_entrenador'] ?? 'No asignado'; ?></div>
                <div><?php echo $clase['horario']; ?></div>
                <div><?php echo $clase['capacidad']; ?></div>
                <div>
                    <?php echo $clase['reservaciones_activas']; ?>
                    <?php if (!$puede_reducir): ?>
                        <div class="reserva-warning">✗ No reducir</div>
                    <?php else: ?>
                        <div class="reserva-ok">✓ Puede reducir</div>
                    <?php endif; ?>
                </div>
                <div>
                    <input type="number" 
                           name="cupos[<?php echo $clase['id_clase']; ?>]" 
                           value="<?php echo $clase['capacidad']; ?>" 
                           min="<?php echo $clase['reservaciones_activas']; ?>"
                           max="50"
                           class="cupo-input"
                           <?php echo !$puede_reducir ? 'title="No puedes reducir el cupo porque hay reservaciones activas"' : ''; ?>>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="btn-container">
            <button type="submit" name="actualizar_cupos" style="background: #002b5b; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                Guardar Cambios de Cupos
            </button>
        </div>
    </form>

  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Gestión de Cupos</p>
  </footer>
</body>
</html>