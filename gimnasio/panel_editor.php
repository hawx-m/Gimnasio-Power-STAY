<?php
session_start();
include("conexion.php");

// Verificar que sea editor
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'editor') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel del Editor - Gym</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
  <header>Panel del Editor - Gym</header>

  <div class="container">
    <!-- Barra  -->
    <div class="sidebar">
      <a href="#">Reservaciones</a>
      <a href="#">Horarios y Clases</a>
      <a href="#">Pagos / Membresías</a>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
      <h2>Bienvenido, Editor</h2>
      <p>Seleccione la opción que desea administrar:</p>

      <div class="card-container">
        <!-- Reservaciones -->
        <div class="card">
          <h3>Registrar Reservación</h3>
          <button id="btnRegistrar">Registrar</button>
        </div>

        <div class="card">
          <h3>Cancelar Reservación</h3>
          <button id="btnCancelar">Cancelar</button>
        </div>

        <!-- Horarios y Clases -->
        <div class="card">
          <h3>Actualizar horarios</h3>
          <button id="btnActualizarHorario">Actualizar</button>
        </div>

        <div class="card">
          <h3>Actualizar Cupos</h3>
          <button id="btnActualizarCupo">Actualizar</button>
        </div>

        <!-- Pagos / Membresías -->
        <div class="card">
          <h3>Capturar Pago</h3>
          <button id="btnCapturarPago">Capturar</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById("btnRegistrar").onclick = function() {
      window.location.href = "registrar_reservacion.html";
    };

    document.getElementById("btnCancelar").onclick = function() {
      window.location.href = "cancelar_reservacion.html";
    };

    document.getElementById("btnActualizarHorario").onclick = function() {
      window.location.href = "actualizar_horario.php";
    };

    document.getElementById("btnActualizarCupo").onclick = function() {
      window.location.href = "actualizar_cupos.php";
    };

    document.getElementById("btnCapturarPago").onclick = function() {
      window.location.href = "capturar_pago.php";
    };
  </script>

</body>
</html>

