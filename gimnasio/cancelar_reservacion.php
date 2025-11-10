
<?php
include "conexion.php"; // Conexión a la base de datos

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_reservacion = $_POST['id_reservacion'];

    // Primero verificamos si existe la reservación
    $verificar = "SELECT * FROM reservaciones WHERE id_reservacion = '$id_reservacion'";
    $resultado = $conn->query($verificar);

    if ($resultado->num_rows > 0) {
        // Si existe, la eliminamos o marcamos como cancelada
        $sql = "UPDATE reservaciones SET estado = 'Cancelada' WHERE id_reservacion = '$id_reservacion'";

        if ($conn->query($sql) === TRUE) {
            echo "<p>Reservación cancelada correctamente ✅</p>";
            echo "<a href='panel_editor.html'>Volver al panel</a>";
        } else {
            echo "Error al cancelar: " . $conn->error;
        }
    } else {
        echo "<p>No se encontró ninguna reservación con ese ID </p>";
        echo "<a href='panel_editor.html'>Volver al panel</a>";
    }
}

$conn->close();
?>
