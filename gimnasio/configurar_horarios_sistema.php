<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// Horarios predefinidos del gimnasio
$horarios_gimnasio = [
    'apertura' => '06:00',
    'cierre' => '22:00',
    'dias_apertura' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
    'horario_fin_semana' => '08:00-20:00'
];

// Actualizar horarios del sistema
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_horarios'])) {
    $horarios_gimnasio['apertura'] = $_POST['apertura'];
    $horarios_gimnasio['cierre'] = $_POST['cierre'];
    $horarios_gimnasio['dias_apertura'] = $_POST['dias_apertura'] ?? [];
    $horarios_gimnasio['horario_fin_semana'] = $_POST['horario_fin_semana'];
    
    $mensaje = "<div class='success'>Horarios del gimnasio actualizados exitosamente</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horarios del Gimnasio - Admin</title>
    <link rel="stylesheet" href="reservaciones.css">
    <style>
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .horario-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .horario-item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        .dias-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 10px 0;
        }
        .dia-checkbox {
            display: none;
        }
        .dia-label {
            padding: 8px 15px;
            border: 2px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        .dia-checkbox:checked + .dia-label {
            background: #002b5b;
            color: white;
            border-color: #002b5b;
        }
        .btn-container {
            text-align: center;
            margin: 20px 0;
        }
        .time-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 120px;
        }
        .horario-display {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
  <header>
    <h1>Horarios del Gimnasio</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="politicas_reservacion.php">Políticas</a> | 
      <a href="configurar_horarios.php">Horarios Clases</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <?php echo $mensaje; ?>
    
    <div class="horario-display">
        <h3>Horarios Actuales del Gimnasio</h3>
        <p><strong>Días de servicio:</strong> <?php echo implode(', ', $horarios_gimnasio['dias_apertura']); ?></p>
        <p><strong>Horario regular:</strong> <?php echo $horarios_gimnasio['apertura']; ?> - <?php echo $horarios_gimnasio['cierre']; ?></p>
        <p><strong>Fin de semana:</strong> <?php echo $horarios_gimnasio['horario_fin_semana']; ?></p>
    </div>

    <form method="post">
        <div class="horario-card">
            <h3>Horario Regular de Servicio</h3>
            
            <div class="horario-item">
                <div>
                    <strong>Hora de Apertura</strong>
                    <p style="font-size: 0.8rem; color: #666;">Hora en que abre el gimnasio</p>
                </div>
                <div>
                    <input type="time" name="apertura" value="<?php echo $horarios_gimnasio['apertura']; ?>" 
                           class="time-input" required>
                </div>
            </div>
            
            <div class="horario-item">
                <div>
                    <strong>Hora de Cierre</strong>
                    <p style="font-size: 0.8rem; color: #666;">Hora en que cierra el gimnasio</p>
                </div>
                <div>
                    <input type="time" name="cierre" value="<?php echo $horarios_gimnasio['cierre']; ?>" 
                           class="time-input" required>
                </div>
            </div>
        </div>

        <div class="horario-card">
            <h3>Días de Servicio</h3>
            <p style="margin-bottom: 15px;">Selecciona los días en que el gimnasio estará abierto:</p>
            
            <div class="dias-container">
                <?php 
                $dias_semana = [
                    'Lun' => 'Lunes',
                    'Mar' => 'Martes', 
                    'Mié' => 'Miércoles',
                    'Jue' => 'Jueves',
                    'Vie' => 'Viernes',
                    'Sáb' => 'Sábado',
                    'Dom' => 'Domingo'
                ];
                
                foreach ($dias_semana as $key => $dia):
                    $checked = in_array($key, $horarios_gimnasio['dias_apertura']) ? 'checked' : '';
                ?>
                <div>
                    <input type="checkbox" name="dias_apertura[]" value="<?php echo $key; ?>" 
                           id="dia_<?php echo $key; ?>" class="dia-checkbox" <?php echo $checked; ?>>
                    <label for="dia_<?php echo $key; ?>" class="dia-label"><?php echo $dia; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="horario-card">
            <h3>Horario Especial de Fin de Semana</h3>
            
            <div class="horario-item">
                <div>
                    <strong>Horario Fin de Semana</strong>
                    <p style="font-size: 0.8rem; color: #666;">Horario especial para sábados y domingos</p>
                </div>
                <div>
                    <input type="text" name="horario_fin_semana" value="<?php echo $horarios_gimnasio['horario_fin_semana']; ?>" 
                           placeholder="Ej: 08:00-20:00" required>
                </div>
            </div>
        </div>

        <div class="btn-container">
            <button type="submit" name="actualizar_horarios" style="background: #002b5b; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                Guardar Horarios del Gimnasio
            </button>
        </div>
    </form>

    <div style="margin-top: 30px; background: #fff3cd; padding: 20px; border-radius: 10px;">
        <h3>Consideraciones Importantes</h3>
        <ul>
            <li>Los horarios establecidos aquí afectarán la disponibilidad de todas las clases</li>
            <li>Las clases no pueden programarse fuera del horario de servicio del gimnasio</li>
            <li>Considera al menos 1 hora entre la apertura/cierre y la primera/última clase</li>
            <li>Los cambios en horarios afectarán las reservaciones futuras, no las existentes</li>
        </ul>
    </div>
  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Horarios del Gimnasio</p>
  </footer>
</body>
</html>