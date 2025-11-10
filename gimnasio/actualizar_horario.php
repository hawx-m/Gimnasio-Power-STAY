<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Actualizar Horario de Clase</title>
  <link rel="stylesheet" href="estiloeditor.css">
</head>
<body>
  <header>Actualizar Horario de Clase</header>

  <div class="container">
    <!-- Barra lateral -->
    <div class="sidebar">
      <a href="editor.html">← Volver al panel</a>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
      <h2>Actualizar información de clase</h2>
      <div class="card">
        <form action="actualizar_horario.php" method="POST">
          <label for="id_clase">Selecciona la clase:</label><br>
          <select name="id_clase" required>
            <option value="">-- Selecciona una clase --</option>
            
            <?php
              include "conexion.php";
              $sql = "SELECT id_clase, nombre_clase FROM clases";
              $resultado = $conn->query($sql);
              while($fila = $resultado->fetch_assoc()){
                  echo "<option value='{$fila['id_clase']}'>{$fila['nombre_clase']}</option>";
              }
              $conn->close();
            ?>
          </select><br><br>

          <label for="horario">Nuevo horario:</label><br>
          <input type="text" name="horario" placeholder="Ejemplo: 08:00 - 09:00" required><br><br>

          <button type="submit">Actualizar Horario</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>

