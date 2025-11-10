<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// Obtener clases para editar horarios
$clases = $conn->query("
    SELECT c.*, e.nombre as nombre_entrenador
    FROM clases c 
    LEFT JOIN entrenadores e ON c.id_entrenador = e.id_entrenador 
    ORDER BY c.horario, c.nombre_clase
");

// Actualizar horarios
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_horarios'])) {
    foreach ($_POST['horarios'] as $id_clase => $nuevo_horario) {
        $stmt = $conn->prepare("UPDATE clases SET horario = ? WHERE id_clase = ?");
        $stmt->bind_param("si", $nuevo_horario, $id_clase);
        $stmt->execute();
    }
    $mensaje = "<div class='success'>Horarios actualizados exitosamente</div>";
    header("Refresh:2");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Definir Horarios - Admin</title>
    <link rel="stylesheet" href="reservaciones.css">
    <style>
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .clase-row {
            display: grid;
            grid-template-columns: 2fr 2fr 2fr 2fr;
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
        .horario-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-container {
            text-align: center;
            margin: 20px 0;
        }
        .dias-semana {
            display: flex;
            gap: 10px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        .dia-btn {
            padding: 5px 10px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .dia-btn:hover {
            background: #e9ecef;
        }
        .horarios-predefinidos {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
  <header>
    <h1>Definir Horarios de Clases</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="lista_clases.php">Lista Clases</a> | 
      <a href="definir_cupos.php">Gestionar Cupos</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <?php echo $mensaje; ?>
    
    <div class="horarios-predefinidos">
        <h3>Formatos de Horario Recomendados</h3>
        <div class="dias-semana">
            <span class="dia-btn" onclick="document.getElementById('horario_ejemplo').value = 'Lun - Mié - Vie 7:00 AM'">Lun-Mié-Vie AM</span>
            <span class="dia-btn" onclick="document.getElementById('horario_ejemplo').value = 'Mar - Jue 6:00 PM'">Mar-Jue PM</span>
            <span class="dia-btn" onclick="document.getElementById('horario_ejemplo').value = 'Lunes a Viernes 8:00 AM'">Lun-Vie AM</span>
            <span class="dia-btn" onclick="document.getElementById('horario_ejemplo').value = 'Sábado 10:00 AM'">Sábado</span>
            <span class="dia-btn" onclick="document.getElementById('horario_ejemplo').value = 'Domingo 9:00 AM'">Domingo</span>
        </div>
        <input type="text" id="horario_ejemplo" placeholder="Haz clic en un formato arriba" readonly style="width: 100%; padding: 8px; margin-top: 10px; background: #e9ecef;">
    </div>

    <form method="post">
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div class="clase-row clase-header">
                <div>Nombre Clase</div>
                <div>Entrenador</div>
                <div>Horario Actual</div>
                <div>Nuevo Horario</div>
            </div>
            
            <?php while($clase = $clases->fetch_assoc()): ?>
            <div class="clase-row">
                <div><strong><?php echo $clase['nombre_clase']; ?></strong></div>
                <div><?php echo $clase['nombre_entrenador'] ?? 'No asignado'; ?></div>
                <div><?php echo $clase['horario']; ?></div>
                <div>
                    <input type="text" 
                           name="horarios[<?php echo $clase['id_clase']; ?>]" 
                           value="<?php echo $clase['horario']; ?>" 
                           class="horario-input"
                           placeholder="Ej: Lun - Mié - Vie 7:00 AM"
                           required>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="btn-container">
            <button type="submit" name="actualizar_horarios" style="background: #002b5b; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                Guardar Cambios de Horarios
            </button>
        </div>
    </form>

  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Configuración de Horarios</p>
  </footer>
</body>
</html>