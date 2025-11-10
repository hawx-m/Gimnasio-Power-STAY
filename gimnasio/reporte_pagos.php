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
$tipo_membresia = $_GET['tipo_membresia'] ?? '';

// Obtener reporte de pagos
$where_conditions = ["p.fecha_pago BETWEEN '$fecha_inicio' AND '$fecha_fin'"];
if ($tipo_membresia) {
    $where_conditions[] = "c.membresia = '$tipo_membresia'";
}
$where_clause = implode(' AND ', $where_conditions);

$reporte = $conn->query("
    SELECT p.*, c.nombre, c.membresia, c.correo
    FROM pagos p
    LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
    WHERE $where_clause
    ORDER BY p.fecha_pago DESC
");

// Estadísticas de pagos
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_pagos,
        SUM(p.monto) as ingresos_totales,
        AVG(p.monto) as promedio_pago,
        COUNT(DISTINCT p.id_cliente) as clientes_activos,
        (SELECT COUNT(*) FROM clientes WHERE membresia = 'VIP') as total_vip,
        (SELECT COUNT(*) FROM clientes WHERE membresia = 'Básica') as total_basica
    FROM pagos p
    WHERE $where_clause
")->fetch_assoc();

// Distribución por membresía
$distribucion = $conn->query("
    SELECT c.membresia, COUNT(*) as total, SUM(p.monto) as ingresos
    FROM pagos p
    LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
    WHERE $where_clause
    GROUP BY c.membresia
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pagos - Admin</title>
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
        .distribucion {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .distribucion-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .membresia-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 10px;
        }
        .vip { background: #ffc107; color: black; }
        .basica { background: #6c757d; color: white; }
        .export-buttons {
            text-align: right;
            margin: 20px 0;
        }
        .ingreso {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
  <header>
    <h1>Reporte de Pagos y Membresías</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="reporte_ocupacion.php">Reporte Ocupación</a> | 
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
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
                </div>
                <div>
                    <label>Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
                </div>
                <div>
                    <label>Tipo de Membresía</label>
                    <select name="tipo_membresia">
                        <option value="">Todas las membresías</option>
                        <option value="VIP" <?php echo $tipo_membresia == 'VIP' ? 'selected' : ''; ?>>VIP</option>
                        <option value="Básica" <?php echo $tipo_membresia == 'Básica' ? 'selected' : ''; ?>>Básica</option>
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
            <div class="stat-number">$<?php echo number_format($stats['ingresos_totales'] ?? 0, 2); ?></div>
            <div class="stat-label">Ingresos Totales</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_pagos'] ?? 0; ?></div>
            <div class="stat-label">Pagos Procesados</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">$<?php echo number_format($stats['promedio_pago'] ?? 0, 2); ?></div>
            <div class="stat-label">Promedio por Pago</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['clientes_activos'] ?? 0; ?></div>
            <div class="stat-label">Clientes Activos</div>
        </div>
    </div>

    <!-- Distribución por membresía -->
    <div class="distribucion">
        <div class="distribucion-card">
            <h3>Distribución por Membresía</h3>
            <?php while($dist = $distribucion->fetch_assoc()): ?>
                <div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <strong><?php echo $dist['membresia']; ?></strong>
                    <span class="membresia-badge <?php echo strtolower($dist['membresia']); ?>">
                        <?php echo $dist['total']; ?> pagos
                    </span>
                    <div style="color: #28a745; font-weight: bold;">
                        $<?php echo number_format($dist['ingresos'], 2); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="distribucion-card">
            <h3>Resumen de Membresías</h3>
            <div style="margin: 10px 0; padding: 15px; background: #fff3cd; border-radius: 5px;">
                <strong>Total Clientes VIP:</strong> 
                <span style="color: #ffc107; font-weight: bold;"><?php echo $stats['total_vip'] ?? 0; ?></span>
            </div>
            <div style="margin: 10px 0; padding: 15px; background: #e2e3e5; border-radius: 5px;">
                <strong>Total Clientes Básica:</strong> 
                <span style="color: #6c757d; font-weight: bold;"><?php echo $stats['total_basica'] ?? 0; ?></span>
            </div>
            <div style="margin: 10px 0; padding: 15px; background: #d1ecf1; border-radius: 5px;">
                <strong>Total Clientes Activos:</strong> 
                <span style="color: #0c5460; font-weight: bold;"><?php echo ($stats['total_vip'] + $stats['total_basica']) ?? 0; ?></span>
            </div>
        </div>
    </div>

    <!-- Botones de exportación -->
    <div class="export-buttons">
        <button onclick="window.print()" style="background: #10c81cb6; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            Imprimir Reporte
        </button>
    </div>

    <!-- Tabla de pagos -->
    <table>
        <thead>
            <tr>
                <th>ID Pago</th>
                <th>Cliente</th>
                <th>Membresía</th>
                <th>Monto</th>
                <th>Fecha Pago</th>
                <th>Correo</th>
            </tr>
        </thead>
        <tbody>
            <?php while($pago = $reporte->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo $pago['id_pago']; ?></td>
                <td><strong><?php echo $pago['nombre']; ?></strong></td>
                <td>
                    <span class="membresia-badge <?php echo strtolower($pago['membresia']); ?>">
                        <?php echo $pago['membresia']; ?>
                    </span>
                </td>
                <td class="ingreso">$<?php echo number_format($pago['monto'], 2); ?></td>
                <td><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></td>
                <td><?php echo $pago['correo']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if($reporte->num_rows == 0): ?>
        <p style="text-align: center; color: #666; margin-top: 20px; padding: 20px;">
            No hay pagos registrados para el período seleccionado.
        </p>
    <?php endif; ?>

    <!-- Análisis financiero -->
    <div style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 10px;">
        <h3>Análisis Financiero</h3>
        
        <?php
        $ingresos_vip = 0;
        $ingresos_basica = 0;
        $distribucion->data_seek(0);
        
        while($dist = $distribucion->fetch_assoc()) {
            if ($dist['membresia'] == 'VIP') $ingresos_vip = $dist['ingresos'];
            if ($dist['membresia'] == 'Básica') $ingresos_basica = $dist['ingresos'];
        }
        
        $total_ingresos = $ingresos_vip + $ingresos_basica;
        $porcentaje_vip = $total_ingresos > 0 ? round(($ingresos_vip / $total_ingresos) * 100) : 0;
        $porcentaje_basica = $total_ingresos > 0 ? round(($ingresos_basica / $total_ingresos) * 100) : 0;
        ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
            <div>
                <h4>Composición de Ingresos</h4>
                <p><strong>Membresía VIP:</strong> $<?php echo number_format($ingresos_vip, 2); ?> (<?php echo $porcentaje_vip; ?>%)</p>
                <p><strong>Membresía Básica:</strong> $<?php echo number_format($ingresos_basica, 2); ?> (<?php echo $porcentaje_basica; ?>%)</p>
                
                <div style="background: #e9ecef; height: 20px; border-radius: 10px; margin: 10px 0; overflow: hidden;">
                    <div style="background: #ffc107; height: 100%; width: <?php echo $porcentaje_vip; ?>%; float: left;"></div>
                    <div style="background: #6c757d; height: 100%; width: <?php echo $porcentaje_basica; ?>%; float: left;"></div>
                </div>
            </div>
            
        </div>
    </div>
  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Reporte de Pagos</p>
  </footer>
</body>
</html>