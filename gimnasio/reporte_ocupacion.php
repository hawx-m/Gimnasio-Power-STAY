<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

// Parámetros de filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
$id_clase = $_GET['id_clase'] ?? '';

// Obtener reporte de ocupación
$where_conditions = ["r.fecha_reservacion BETWEEN '$fecha_inicio' AND '$fecha_fin'"];
if ($id_clase) {
    $where_conditions[] = "c.id_clase = '$id_clase'";
}
$where_clause = implode(' AND ', $where_conditions);

$reporte = $conn->query("
    SELECT c.id_clase, c.nombre_clase, c.capacidad,
           COUNT(r.id_reservacion) as total_reservaciones,
           ROUND((COUNT(r.id_reservacion) / c.capacidad) * 100, 2) as porcentaje_ocupacion,
           e.nombre as entrenador
    FROM clases c
    LEFT JOIN reservaciones r ON c.id_clase = r.id_clase AND $where_clause
    LEFT JOIN entrenadores e ON c.id_entrenador = e.id_entrenador
    GROUP BY c.id_clase
    ORDER BY porcentaje_ocupacion DESC
");

// Obtener clases para el filtro
$clases = $conn->query("SELECT id_clase, nombre_clase FROM clases ORDER BY nombre_clase");

// Estadísticas generales
$stats = $conn->query("
    SELECT 
        COUNT(DISTINCT r.id_cliente) as clientes_activos,
        COUNT(r.id_reservacion) as total_reservaciones,
        AVG((SELECT COUNT(*) FROM reservaciones r2 WHERE r2.id_clase = c.id_clase AND r2.fecha_reservacion BETWEEN '$fecha_inicio' AND '$fecha_fin') / c.capacidad * 100) as ocupacion_promedio
    FROM reservaciones r
    LEFT JOIN clases c ON r.id_clase = c.id_clase
    WHERE r.fecha_reservacion BETWEEN '$fecha_inicio' AND '$fecha_fin'
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ocupación - Admin</title>
    <link rel="stylesheet" href="reservaciones.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #002b5b;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #002b5b;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .filtros {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filtro-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        .barra-ocupacion {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            margin: 5px 0;
            overflow: hidden;
        }
        .barra-progreso {
            height: 100%;
            border-radius: 10px;
            text-align: center;
            color: white;
            font-size: 0.7rem;
            line-height: 20px;
            font-weight: bold;
        }
        .ocupacion-baja { background: #28a745; }
        .ocupacion-media { background: #ffc107; }
        .ocupacion-alta { background: #dc3545; }
        .export-buttons {
            text-align: right;
            margin: 20px 0;
        }
    </style>
</head>
<body>
  <header>
    <h1>Reporte de Ocupación</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="reporte_pagos.php">Reporte Pagos</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <!-- Filtros -->
    <div class="filtros">
        <h3>Filtros del Reporte</h3>
        <form method="get">
            <div class="filtro-row">
                <div>
                    <label>Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" 
                           class="form-control" required>
                </div>
                <div>
                    <label>Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" 
                           class="form-control" required>
                </div>
                <div>
                    <label>Clase Específica</label>
                    <select name="id_clase" class="form-control">
                        <option value="">Todas las clases</option>
                        <?php while($clase = $clases->fetch_assoc()): ?>
                            <option value="<?php echo $clase['id_clase']; ?>" 
                                    <?php echo $id_clase == $clase['id_clase'] ? 'selected' : ''; ?>>
                                <?php echo $clase['nombre_clase']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" style="background: #002b5b; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">
                        Generar Reporte
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $reporte->num_rows; ?></div>
            <div class="stat-label">Clases Analizadas</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['clientes_activos'] ?? 0; ?></div>
            <div class="stat-label">Clientes Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_reservaciones'] ?? 0; ?></div>
            <div class="stat-label">Total Reservaciones</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo round($stats['ocupacion_promedio'] ?? 0, 1); ?>%</div>
            <div class="stat-label">Ocupación Promedio</div>
        </div>
    </div>

    <!-- Botones de exportación -->
    <div class="export-buttons">
        <button onclick="window.print()" style="background: #10c81cb6; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            Imprimir Reporte
        </button>
    </div>

    <!-- Tabla de resultados -->
    <table>
        <thead>
            <tr>
                <th>Clase</th>
                <th>Entrenador</th>
                <th>Capacidad</th>
                <th>Reservaciones</th>
                <th>Ocupación</th>
                <th>Barra de Ocupación</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php while($fila = $reporte->fetch_assoc()): 
                $porcentaje = $fila['porcentaje_ocupacion'] ?? 0;
                
                if ($porcentaje < 50) {
                    $nivel = 'baja';
                    $estado = '✅ Baja';
                } elseif ($porcentaje < 80) {
                    $nivel = 'media';
                    $estado = '⚠️ Media';
                } else {
                    $nivel = 'alta';
                    $estado = '🚨 Alta';
                }
            ?>
            <tr>
                <td><strong><?php echo $fila['nombre_clase']; ?></strong></td>
                <td><?php echo $fila['entrenador'] ?? 'No asignado'; ?></td>
                <td><?php echo $fila['capacidad']; ?></td>
                <td><?php echo $fila['total_reservaciones']; ?></td>
                <td><strong><?php echo $porcentaje; ?>%</strong></td>
                <td>
                    <div class="barra-ocupacion">
                        <div class="barra-progreso ocupacion-<?php echo $nivel; ?>" 
                             style="width: <?php echo min($porcentaje, 100); ?>%">
                            <?php echo $porcentaje; ?>%
                        </div>
                    </div>
                </td>
                <td><?php echo $estado; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if($reporte->num_rows == 0): ?>
        <p style="text-align: center; color: #666; margin-top: 20px; padding: 20px;">
            No hay datos de ocupación para el período seleccionado.
        </p>
    <?php endif; ?>

  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Reporte de Ocupación</p>
  </footer>
</body>
</html>