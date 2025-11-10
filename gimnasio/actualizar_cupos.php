<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Cupo de Clase</title>
    <link rel="stylesheet" href="estiloeditor.css">
</head>
<body>
<header>Actualizar Cupo de Clase</header>

<div class="main-content">
    <?php
    // Mostrar errores para depurar si algo falla
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Conexión a la base de datos
    $conn = new mysqli("localhost", "root", "", "gimnasio");

    if ($conn->connect_error) {
        die("<p>Error de conexión: " . $conn->connect_error . "</p>");
    }

    // Si el formulario se envió
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_clase = $_POST["id_clase"];
        $nuevo_cupo = $_POST["nuevo_cupo"];

        if (!empty($id_clase) && !empty($nuevo_cupo)) {
            $sql = "UPDATE clases SET capacidad = '$nuevo_cupo' WHERE id_clase = '$id_clase'";
            if ($conn->query($sql) === TRUE) {
                echo "<p><b>✅ Cupo actualizado correctamente.</b></p>";
            } else {
                echo "<p>Error al actualizar: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>⚠️ Por favor selecciona una clase y escribe el nuevo cupo.</p>";
        }
    }

    // Obtener las clases existentes
    $resultado = $conn->query("SELECT id_clase, nombre_clase FROM clases");
    ?>

    <div class="card" style="max-width:400px;margin:auto;">
        <h3>Actualizar Cupo de Clase</h3>
        <form action="" method="POST">
            <label for="id_clase">Seleccionar clase:</label><br>
            <select name="id_clase" required>
                <option value="">-- Selecciona una clase --</option>
                <?php
                if ($resultado && $resultado->num_rows > 0) {
                    while ($fila = $resultado->fetch_assoc()) {
                        echo "<option value='" . $fila['id_clase'] . "'>" . $fila['nombre_clase'] . "</option>";
                    }
                } else {
                    echo "<option disabled>No hay clases registradas</option>";
                }
                ?>
            </select><br><br>

            <label for="nuevo_cupo">Nuevo cupo:</label><br>
            <input type="number" name="nuevo_cupo" min="1" required><br><br>

            <button type="submit">Actualizar Cupo</button>
        </form>

        <br>
        <a href="editor.html">← Volver al panel</a>
    </div>

    <?php $conn->close(); ?>
</div>
</body>
</html>
