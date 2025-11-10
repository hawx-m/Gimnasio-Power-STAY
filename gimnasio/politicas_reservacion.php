<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// Obtener políticas actuales
$politicas = [
    'max_reservas_dia' => 2,
    'max_reservas_semana' => 8,
    'cancelacion_minima' => 2, // horas
    'tolerancia_retraso' => 15, // minutos
    'suspension_intempestiva' => 3 // número de faltas para suspensión
];

// Actualizar políticas
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_politicas'])) {
    // En una implementación real, guardarías esto en una tabla de configuración
    $politicas['max_reservas_dia'] = $_POST['max_reservas_dia'];
    $politicas['max_reservas_semana'] = $_POST['max_reservas_semana'];
    $politicas['cancelacion_minima'] = $_POST['cancelacion_minima'];
    $politicas['tolerancia_retraso'] = $_POST['tolerancia_retraso'];
    $politicas['suspension_intempestiva'] = $_POST['suspension_intempestiva'];
    
    $mensaje = "<div class='success'>Políticas actualizadas exitosamente</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Políticas de Reservación - Admin</title>
    <link rel="stylesheet" href="reservaciones.css">
    <style>
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .politica-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .politica-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        .politica-header {
            font-weight: bold;
            background: #f8f9fa;
            border-bottom: 2px solid #002b5b;
        }
        .input-small {
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
        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
  <header>
    <h1>Políticas de Reservación</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="configurar_horarios_sistema.php">Horarios Gimnasio</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <?php echo $mensaje; ?>
    
    <div class="info-box">
        <strong>Configuración del Sistema de Reservaciones</strong>
        <p>Establece las reglas y límites para las reservaciones de clases en el gimnasio.</p>
    </div>

    <form method="post">
        <div class="politica-card">
            <h3>Límites de Reservación</h3>
            <div class="politica-item politica-header">
                <div>Descripción</div>
                <div>Valor Actual</div>
                <div>Nuevo Valor</div>
            </div>
            
            <div class="politica-item">
                <div>
                    <strong>Máximo de reservaciones por día</strong>
                    <p style="font-size: 0.8rem; color: #666;">Límite de clases que un cliente puede reservar en un mismo día</p>
                </div>
                <div><?php echo $politicas['max_reservas_dia']; ?></div>
                <div>
                    <input type="number" name="max_reservas_dia" value="<?php echo $politicas['max_reservas_dia']; ?>" 
                           min="1" max="5" class="input-small" required>
                </div>
            </div>
            
            <div class="politica-item">
                <div>
                    <strong>Máximo de reservaciones por semana</strong>
                    <p style="font-size: 0.8rem; color: #666;">Límite total de clases por semana por cliente</p>
                </div>
                <div><?php echo $politicas['max_reservas_semana']; ?></div>
                <div>
                    <input type="number" name="max_reservas_semana" value="<?php echo $politicas['max_reservas_semana']; ?>" 
                           min="1" max="20" class="input-small" required>
                </div>
            </div>
        </div>

        <div class="politica-card">
            <h3>Políticas de Tiempo</h3>
            
            <div class="politica-item">
                <div>
                    <strong>Cancelación mínima (horas)</strong>
                    <p style="font-size: 0.8rem; color: #666;">Tiempo mínimo para cancelar una reservación sin penalización</p>
                </div>
                <div><?php echo $politicas['cancelacion_minima']; ?> horas</div>
                <div>
                    <input type="number" name="cancelacion_minima" value="<?php echo $politicas['cancelacion_minima']; ?>" 
                           min="1" max="24" class="input-small" required> horas
                </div>
            </div>
            
            <div class="politica-item">
                <div>
                    <strong>Tolerancia a retraso (minutos)</strong>
                    <p style="font-size: 0.8rem; color: #666;">Tiempo máximo de retraso permitido para no perder la reservación</p>
                </div>
                <div><?php echo $politicas['tolerancia_retraso']; ?> minutos</div>
                <div>
                    <input type="number" name="tolerancia_retraso" value="<?php echo $politicas['tolerancia_retraso']; ?>" 
                           min="5" max="30" class="input-small" required> minutos
                </div>
            </div>
        </div>

        <div class="politica-card">
            <h3>Sanciones y Penalizaciones</h3>
            
            <div class="politica-item">
                <div>
                    <strong>Faltas para suspensión</strong>
                    <p style="font-size: 0.8rem; color: #666;">Número de faltas sin aviso para suspender temporalmente al cliente</p>
                </div>
                <div><?php echo $politicas['suspension_intempestiva']; ?></div>
                <div>
                    <input type="number" name="suspension_intempestiva" value="<?php echo $politicas['suspension_intempestiva']; ?>" 
                           min="1" max="10" class="input-small" required> faltas
                </div>
            </div>
        </div>

        <div class="btn-container">
            <button type="submit" name="actualizar_politicas" style="background: #002b5b; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                Guardar Políticas
            </button>
        </div>
    </form>

    <div style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 10px;">
        <h3>Resumen de Políticas Actuales</h3>
        <ul>
            <li>Los clientes pueden reservar máximo <strong><?php echo $politicas['max_reservas_dia']; ?> clases por día</strong></li>
            <li>Límite semanal: <strong><?php echo $politicas['max_reservas_semana']; ?> clases por semana</strong></li>
            <li>Cancelación permitida hasta <strong><?php echo $politicas['cancelacion_minima']; ?> horas antes</strong> de la clase</li>
            <li>Tolerancia de <strong><?php echo $politicas['tolerancia_retraso']; ?> minutos</strong> de retraso</li>
            <li>Suspensión temporal después de <strong><?php echo $politicas['suspension_intempestiva']; ?> faltas</strong> sin aviso</li>
        </ul>
    </div>
  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Políticas de Reservación</p>
  </footer>
</body>
</html>