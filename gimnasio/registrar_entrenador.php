<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $especialidad = $_POST['especialidad'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $experiencia = $_POST['experiencia'];
    $disponibilidad = $_POST['disponibilidad'];
    
    try {
        $sql = "INSERT INTO entrenadores (nombre, especialidad, telefono, correo) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $especialidad, $telefono, $correo);
        
        if ($stmt->execute()) {
            $mensaje = "<div class='success'>Entrenador registrado exitosamente</div>";
            
        } else {
            throw new Exception('Error al registrar el entrenador: ' . $stmt->error);
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
    <title>Registrar Entrenador - Admin</title>
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
            <a href="lista_entrenadores.php">Ver Lista de Entrenadores</a>
        </div>

        <div class="formulario">
            <h1>Registrar Nuevo Entrenador</h1>
            <?php echo $mensaje; ?>
            <form method="post">
                <div class="usuario">
                    <input type="text" name="nombre" required>
                    <label>Nombre completo *</label>
                </div>
                <div class="usuario">
                    <input type="text" name="especialidad" required placeholder="Ej: Yoga, Pesas, Cardio...">
                    <label>Especialidad *</label>
                </div>
                <div class="usuario">
                    <input type="tel" name="telefono" required>
                    <label>Teléfono *</label>
                </div>
                <div class="usuario">
                    <input type="email" name="correo" required>
                    <label>Correo electrónico *</label>
                </div>
                <div class="usuario">
                    <textarea name="experiencia" placeholder="Años de experiencia, certificaciones..."></textarea>
                    <label>Experiencia y Certificaciones</label>
                </div>
                <div class="usuario">
                    <input type="text" name="disponibilidad" placeholder="Ej: Lunes a Viernes 6:00 AM - 8:00 PM">
                    <label>Disponibilidad General</label>
                </div>
                <div>
                    <input type="submit" value="Registrar Entrenador">
                </div>
            </form>
            
        </div>
    </div>
</body>
</html>