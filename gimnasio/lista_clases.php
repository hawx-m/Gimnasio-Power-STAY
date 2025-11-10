<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

// Obtener lista de clases con información de entrenadores
$clases = $conn->query("
    SELECT c.*, e.nombre as nombre_entrenador, 
           COUNT(r.id_reservacion) as reservaciones_activas
    FROM clases c 
    LEFT JOIN entrenadores e ON c.id_entrenador = e.id_entrenador 
    LEFT JOIN reservaciones r ON c.id_clase = r.id_clase AND r.estado IN ('Pendiente', 'Confirmada')
    GROUP BY c.id_clase 
    ORDER BY c.nombre_clase
");

// Procesar eliminación de clase
if (isset($_GET['eliminar'])) {
    $id_eliminar = $_GET['eliminar'];
    
    // Verificar si hay reservaciones activas
    $reservas_activas = $conn->query("
        SELECT COUNT(*) as total 
        FROM reservaciones 
        WHERE id_clase = $id_eliminar AND estado IN ('Pendiente', 'Confirmada')
    ")->fetch_assoc()['total'];
    
    if ($reservas_activas > 0) {
        $mensaje_error = "No se puede eliminar la clase porque tiene $reservas_activas reservación(es) activa(s)";
    } else {
        // Eliminar la clase
        if ($conn->query("DELETE FROM clases WHERE id_clase = $id_eliminar")) {
            $mensaje_exito = "Clase eliminada exitosamente";
            header("Refresh:2; url=lista_clases.php");
        } else {
            $mensaje_error = "Error al eliminar la clase: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clases - Admin</title>
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
        .edit-btn { 
            background: #ffc107; 
            color: black; 
            border: 1px solid #ffc107;
        }
        .edit-btn:hover {
            background: #e0a800;
            border-color: #e0a800;
        }
        .delete-btn { 
            background: #dc3545; 
            color: white; 
            border: 1px solid #dc3545;
        }
        .delete-btn:hover {
            background: #c82333;
            border-color: #c82333;
        }
        .stats-btn { 
            background: #0d6efd; 
            color: white; 
            border: 1px solid #0d6efd;
        }
        .stats-btn:hover {
            background: #0056b3;
            border-color: #0056b3;
        }
        .assign-btn { 
            background: #20c997; 
            color: white; 
            border: 1px solid #20c997;
        }
        .assign-btn:hover {
            background: #199d76;
            border-color: #199d76;
        }
        .ocupacion-baja { 
            color: black; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 0.7rem;
            font-weight: bold;
        }
        .ocupacion-media { 
            color: black; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 0.7rem;
            font-weight: bold;
        }
        .ocupacion-alta { 
            color: black; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 0.7rem;
            font-weight: bold;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .action-buttons a {
            background: #002b5b;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s ease;
        }
        .action-buttons a:hover {
            background: #005fa3;
        }
        .no-entrenador {
            color: #6c757d;
            font-style: italic;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .clase-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
  <header>
    <h1>Lista de Clases</h1>
    <div class="user-info">
      <a href="panel_admin.php">Panel Admin</a> | 
      <a href="crear_clase.php">Nueva Clase</a> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <!-- Mensajes de éxito/error -->
    <?php if (isset($mensaje_exito)): ?>
        <div class="success-message">
            ✅ <?php echo $mensaje_exito; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($mensaje_error)): ?>
        <div class="error-message">
            ❌ <?php echo $mensaje_error; ?>
        </div>
    <?php endif; ?>


    <!-- Tabla de clases -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Clase</th>
                    <th>Horario</th>
                    <th>Entrenador</th>
                    <th>Capacidad</th>
                    <th>Reservaciones</th>
                    <th>Ocupación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($clases->num_rows > 0): ?>
                    <?php while($clase = $clases->fetch_assoc()): 
                        $porcentaje_ocupacion = $clase['capacidad'] > 0 ? round(($clase['reservaciones_activas'] / $clase['capacidad']) * 100) : 0;
                        
                        if ($porcentaje_ocupacion < 50) {
                            $nivel_ocupacion = 'baja';
                            $texto_ocupacion = 'Baja';
                            $icono_ocupacion = '✅';
                        } elseif ($porcentaje_ocupacion < 80) {
                            $nivel_ocupacion = 'media';
                            $texto_ocupacion = 'Media';
                            $icono_ocupacion = '⚠️';
                        } else {
                            $nivel_ocupacion = 'alta';
                            $texto_ocupacion = 'Alta';
                            $icono_ocupacion = '🚨';
                        }
                    ?>
                    <tr>
                        <td><strong>#<?php echo $clase['id_clase']; ?></strong></td>
                        <td>
                            <div class="clase-info">
                                <div>
                                    <strong><?php echo $clase['nombre_clase']; ?></strong>
                                    <?php if (!empty($clase['descripcion'])): ?>
                                        <br><small style="color: #666;"><?php echo $clase['descripcion']; ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $clase['horario']; ?></td>
                        <td>
                            <?php if ($clase['nombre_entrenador']): ?>
                                <?php echo $clase['nombre_entrenador']; ?>
                            <?php else: ?>
                                <span class="no-entrenador">No asignado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $clase['capacidad']; ?></strong>
                            <br><small>personas</small>
                        </td>
                        <td>
                            <strong><?php echo $clase['reservaciones_activas']; ?></strong>
                            <br><small>activas</small>
                        </td>
                        <td>
                            <span class="ocupacion-<?php echo $nivel_ocupacion; ?>">
                                <?php echo $icono_ocupacion; ?> <?php echo $texto_ocupacion; ?>
                                <br>
                                <small>(<?php echo $porcentaje_ocupacion; ?>%)</small>
                            </span>
                        </td>
                        <td class="actions">
                            <?php if ($clase['nombre_entrenador']): ?>
                                <a href="asignar_clases.php?id=<?php echo $clase['id_entrenador']; ?>" class="assign-btn" title="Gestionar entrenador">
                                    Entrenador
                                </a>
                            <?php else: ?>
                                <a href="asignar_clases.php" class="assign-btn" title="Asignar entrenador">
                                    Asignar
                                </a>
                            <?php endif; ?>
                            <a href="lista_clases.php?eliminar=<?php echo $clase['id_clase']; ?>" 
                               class="delete-btn" 
                               title="Eliminar clase"
                               onclick="return confirm('¿Estás seguro de eliminar la clase \'<?php echo $clase['nombre_clase']; ?>\'?\n\nEsta acción no se puede deshacer.');">
                                Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                            <div style="font-size: 3rem; margin-bottom: 10px;">🏋️‍♀️</div>
                            <h3>No hay clases registradas</h3>
                            <p>Comienza creando tu primera clase para el gimnasio.</p>
                            <a href="crear_clase.php" style="display: inline-block; margin-top: 15px; background: #002b5b; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">
                                Crear Primera Clase
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Resumen estadístico -->
    <?php if ($clases->num_rows > 0): ?>
        <?php
        // Calcular estadísticas
        $clases->data_seek(0); // Reset pointer para recalcular
        $total_clases = $clases->num_rows;
        $clases_con_entrenador = 0;
        $clases_sin_entrenador = 0;
        $total_capacidad = 0;
        $total_reservaciones = 0;
        
        while($clase = $clases->fetch_assoc()) {
            if ($clase['nombre_entrenador']) {
                $clases_con_entrenador++;
            } else {
                $clases_sin_entrenador++;
            }
            $total_capacidad += $clase['capacidad'];
            $total_reservaciones += $clase['reservaciones_activas'];
        }
        
        $porcentaje_con_entrenador = round(($clases_con_entrenador / $total_clases) * 100);
        $ocupacion_promedio = $total_capacidad > 0 ? round(($total_reservaciones / $total_capacidad) * 100) : 0;
        ?>
        
        <div style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 10px;">
            <h3>Resumen de Clases</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #002b5b;"><?php echo $total_clases; ?></div>
                    <div style="color: #666; font-size: 0.9rem;">Total Clases</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;"><?php echo $clases_con_entrenador; ?></div>
                    <div style="color: #666; font-size: 0.9rem;">Con Entrenador (<?php echo $porcentaje_con_entrenador; ?>%)</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #ffc107;"><?php echo $clases_sin_entrenador; ?></div>
                    <div style="color: #666; font-size: 0.9rem;">Sin Entrenador</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #0d6efd;"><?php echo $ocupacion_promedio; ?>%</div>
                    <div style="color: #666; font-size: 0.9rem;">Ocupación Promedio</div>
                </div>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #e8f4f8; border-radius: 8px;">
                
                <?php if ($ocupacion_promedio < 30): ?>
                    <p>📉 La ocupación promedio es <strong>baja (<?php echo $ocupacion_promedio; ?>%)</strong>. Podrías considerar promocionar las clases o ajustar horarios.</p>
                <?php elseif ($ocupacion_promedio > 80): ?>
                    <p>📈 La ocupación promedio es <strong>alta (<?php echo $ocupacion_promedio; ?>%)</strong>. Excelente desempeño. Considera aumentar cupos o crear nuevas clases.</p>
                <?php else: ?>
                    <p>✅ La ocupación promedio es <strong>óptima (<?php echo $ocupacion_promedio; ?>%)</strong>. Buen balance entre demanda y capacidad.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Lista de Clases - <?php echo $total_clases ?? 0; ?> clases registradas</p>
  </footer>

  <script>
    // Confirmación para eliminación
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de que deseas eliminar esta clase?\n\nEsta acción eliminará permanentemente la clase y no se podrá deshacer.')) {
                    e.preventDefault();
                }
            });
        });
    });
  </script>
</body>
</html>