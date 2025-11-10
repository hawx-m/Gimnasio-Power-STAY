<?php
session_start();
include("conexion.php");

// Verificar que sea administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

// Obtener estadísticas para el dashboard
$stats = [];
$stats['total_usuarios'] = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
$stats['total_clientes'] = $conn->query("SELECT COUNT(*) as total FROM clientes")->fetch_assoc()['total'];
$stats['total_entrenadores'] = $conn->query("SELECT COUNT(*) as total FROM entrenadores")->fetch_assoc()['total'];
$stats['total_clases'] = $conn->query("SELECT COUNT(*) as total FROM clases")->fetch_assoc()['total'];
$stats['reservaciones_hoy'] = $conn->query("SELECT COUNT(*) as total FROM reservaciones WHERE fecha_reservacion = CURDATE()")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Administrador - Gimnasio Power STAY</title>
  <link rel="stylesheet" href="panel.css">
  <style>
    .dashboard-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
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
    .admin-panel {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      padding: 2rem;
    }
    .admin-card {
      background: white;
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      border-left: 4px solid #002b5b;
    }
    .admin-card h3 {
      color: #002b5b;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .admin-card ul {
      list-style: none;
      padding: 0;
    }
    .admin-card li {
      padding: 0.5rem 0;
      border-bottom: 1px solid #eee;
    }
    .admin-card a {
      color: #002b5b;
      text-decoration: none;
      display: block;
      padding: 0.3rem 0;
      transition: color 0.3s ease;
    }
    .admin-card a:hover {
      color: #005fa3;
      text-decoration: underline;
    }
    .user-info {
      margin-top: 10px;
      font-size: 14px;
    }
    .user-info a {
      color: white;
      text-decoration: none;
      margin: 0 10px;
    }
    .user-info a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <header>
    <h1>Panel de Administración - Gimnasio Power STAY</h1>
    <div class="user-info">
      <strong>Administrador:</strong> <?php echo $_SESSION['usuario']; ?> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <!-- Estadísticas del Dashboard -->
  <div class="container">
    <br> <h2>Dashboard - Resumen General</h2> <br>
    <div class="dashboard-stats">
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_usuarios']; ?></div>
        <div class="stat-label">Usuarios Totales</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_clientes']; ?></div>
        <div class="stat-label">Clientes Registrados</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_entrenadores']; ?></div>
        <div class="stat-label">Entrenadores</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_clases']; ?></div>
        <div class="stat-label">Clases Activas</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['reservaciones_hoy']; ?></div>
        <div class="stat-label">Reservaciones Hoy</div>
      </div>
    </div>
  </div>

  <main class="admin-panel">
    
    <!-- Gestión de Usuarios -->
    <div class="admin-card">
      <h3>Gestión de Usuarios</h3>
      <ul>
        <li><a href="gestion_usuarios.php">Registrar nuevo usuario</a></li>
        <li><a href="lista_usuarios.php">Lista de usuarios</a></li>
        <li><a href="editar_permisos.php">Editar permisos y roles</a></li>
      </ul>
    </div>

    <!-- Gestión de Entrenadores -->
    <div class="admin-card">
      <h3>Gestión de Entrenadores</h3>
      <ul>
        <li><a href="registrar_entrenador.php">Registrar entrenador</a></li>
        <li><a href="lista_entrenadores.php">Lista de entrenadores</a></li>
        <li><a href="asignar_clases.php">Asignar clases</a></li>
      </ul>
    </div>

    <!-- Gestión de Clases -->
    <div class="admin-card">
      <h3>Gestión de Clases</h3>
      <ul>
        <li><a href="crear_clase.php">Crear nueva clase</a></li>
        <li><a href="lista_clases.php">Lista de clases</a></li>
        <li><a href="configurar_horarios.php">Definir horarios</a></li>
        <li><a href="definir_cupos.php">Definir cupos máximos</a></li>
      </ul>
    </div>

    <!-- Políticas -->
    <div class="admin-card">
      <h3>Políticas y Horarios</h3>
      <ul>
        <li><a href="politicas_reservacion.php">Políticas de reservación</a></li>
        <li><a href="configurar_horarios_sistema.php">Horarios del gimnasio</a></li>
      </ul>
    </div>

    <!-- Reportes -->
    <div class="admin-card">
      <h3>Reportes y Análisis</h3>
      <ul>
        <li><a href="reporte_ocupacion.php">Reporte de ocupación</a></li>
        <li><a href="reporte_pagos.php">Reporte de pagos/membresías</a></li>
      </ul>
    </div>

  </main>

  <footer>
    <p>© 2025 Gimnasio Power STAY | Panel de Administración</p>
  </footer>
</body>
</html>