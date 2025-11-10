<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// Obtener entrenadores
$entrenadores = $conn->query("SELECT id_entrenador, nombre FROM entrenadores ORDER BY nombre");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_clase = $_POST['nombre_clase'];
    $horario = $_POST['horario'];
    $id_entrenador = $_POST['id_entrenador'];
    $capacidad = $_POST['capacidad'];
    
    try {
        $sql = "INSERT INTO clases (nombre_clase, horario, id_entrenador, capacidad) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $nombre_clase, $horario, $id_entrenador, $capacidad);
        
        if ($stmt->execute()) {
            $mensaje = "<div class='success'>Clase creada exitosamente</div>";
        } else {
            throw new Exception('Error al crear la clase: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        $mensaje = "<div class='error'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Clase - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .nav-links {
            text-align: center;
            margin: 20px 0;
        }
        .nav-links a {
            color: #002b5b;
            text-decoration: none;
            margin: 0 10px;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 80px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="nav-links">
            <a href="panel_admin.php">← Volver al Panel</a> | 
            <a href="lista_clases.php">Ver Lista de Clases</a>
        </div>

        <div class="formulario">
            <h1>Crear Nueva Clase</h1>
            <?php echo $mensaje; ?>
            <form method="post">
                <div class="usuario">
                    <input type="text" name="nombre_clase" required>
                    <label>Nombre de la clase *</label>
                </div>
                <div class="usuario">
                    <input type="text" name="horario" required placeholder="Ej: Lun - Mié - Vie 7:00 AM">
                    <label>Horario *</label>
                </div>
                <div class="usuario">
                    <select name="id_entrenador" required>
                        <option value="">Seleccionar entrenador *</option>
                        <?php while($entrenador = $entrenadores->fetch_assoc()): ?>
                            <option value="<?php echo $entrenador['id_entrenador']; ?>">
                                <?php echo $entrenador['nombre']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label>Entrenador asignado</label>
                </div>
                <div class="usuario">
                    <input type="number" name="capacidad" required min="1" max="50">
                    <label>Capacidad máxima *</label>
                </div>
                <div>
                    <input type="submit" value="Crear Clase">
                </div>
            </form>
        </div>
    </div>
</body>
</html>