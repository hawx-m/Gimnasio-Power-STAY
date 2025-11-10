

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Capturar Pago</title>
  <link rel="stylesheet" href="estiloeditor.css">
</head>
<body>
  <header>Capturar Pago</header>
  <div class="container">
    <div class="sidebar">
      <a href="editor.html"> Volver al panel</a>
    </div>

    <div class="main-content">
      <h2>Registrar Pago</h2>

      <?php
      $conn = new mysqli("localhost", "root", "", "gimnasio");
      if ($conn->connect_error) {
          die("<p>Error de conexión: " . $conn->connect_error . "</p>");
      }

      // Procesar formulario
      if ($_SERVER["REQUEST_METHOD"] === "POST") {
          $id_cliente = $_POST["id_cliente"];
          $monto = $_POST["monto"];
          $fecha_pago = $_POST["fecha_pago"];

          $sql = "INSERT INTO pagos (id_cliente, monto, fecha_pago) VALUES ('$id_cliente', '$monto', '$fecha_pago')";
          if ($conn->query($sql) === TRUE) {
              echo "<p style='color:green;'> Pago registrado correctamente.</p>";
          } else {
              echo "<p style='color:red;'> Error al registrar el pago: " . $conn->error . "</p>";
          }
      }
      ?>

      <form method="POST" action="">
        <label for="id_cliente">Cliente:</label><br>
        <select name="id_cliente" required>
          <option value="">-- Selecciona un cliente --</option>
          <?php
          $clientes = $conn->query("SELECT id_cliente, nombre FROM clientes");
          while ($fila = $clientes->fetch_assoc()) {
              echo "<option value='" . $fila["id_cliente"] . "'>" . $fila["nombre"] . "</option>";
          }
          ?>
        </select><br><br>

        <label for="monto">Monto:</label><br>
        <input type="number" name="monto" step="0.01" required><br><br>

        <label for="fecha_pago">Fecha de Pago:</label><br>
        <input type="date" name="fecha_pago" required><br><br>

        <button type="submit">Registrar Pago</button>
      </form>
    </div>
  </div>
</body>
</html>