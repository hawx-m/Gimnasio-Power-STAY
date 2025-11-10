
<?php
include "conexion.php"; // incluye la conexión a la base de datos

// Revisar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST['id_cliente'];
    $id_clase = $_POST['id_clase'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];      // Nueva variable
    $estado = $_POST['estado'];  // Nueva variable
    // Consulta para insertar la reservación
    $sql = "INSERT INTO reservaciones (id_cliente, id_clase, fecha_reservacion, hora_reservacion, estado) 
            VALUES ('$id_cliente', '$id_clase', '$fecha', '$hora', '$estado')";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Reservación registrada correctamente ✅</p>";
        echo "<a href='index.html'>Volver al panel</a>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
