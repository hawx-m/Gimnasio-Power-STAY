<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$servername = "localhost";     
$username = "root";         
$password = "";             
$database = "gimnasio";   

$mysqli = new mysqli($servername, $username, $password, $database);
if ($mysqli->connect_error) {
    die(json_encode(["error" => "Error de conexión: " . $mysqli->connect_error]));
}

$mysqli->set_charset("utf8mb4");

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {

    // RESERVACIONES POR FECHA 
    case 'reservacionesPorFecha':
        $fecha = $_GET['fecha'] ?? null;
        if (!$fecha) { 
            echo json_encode(["error"=>"Se requiere fecha"]); 
            exit; 
        }

        $entrenador_id = $_GET['entrenador'] ?? null;
        $clase_id = $_GET['clase'] ?? null;

        // Consulta para estructura de BD
        $sql = "SELECT r.*, c.nombre as nombre_cliente, cl.nombre_clase, e.nombre as nombre_entrenador
                FROM reservaciones r
                JOIN clientes c ON r.id_cliente = c.id_cliente
                JOIN clases cl ON r.id_clase = cl.id_clase
                LEFT JOIN entrenadores e ON cl.id_entrenador = e.id_entrenador
                WHERE r.fecha_reservacion = ?";
        
        $types = "s";
        $params = [$fecha];

        if ($entrenador_id) { 
            $sql .= " AND cl.id_entrenador = ?"; 
            $types .= "i"; 
            $params[] = $entrenador_id; 
        }
        if ($clase_id) { 
            $sql .= " AND r.id_clase = ?"; 
            $types .= "i"; 
            $params[] = $clase_id; 
        }

        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res->fetch_all(MYSQLI_ASSOC);
            echo json_encode(["count" => count($rows), "list" => $rows]);
        } else {
            echo json_encode(["error" => "Error en consulta: " . $mysqli->error]);
        }
        exit;
        break;

    // VERIFICAR RESERVA 
    case 'verificarReserva':
        $cliente = isset($_GET['cliente']) ? intval($_GET['cliente']) : 0;
        $fecha = $_GET['fecha'] ?? null;
        $clase_id = $_GET['clase'] ?? null;

        if (!$cliente || !$fecha || !$clase_id) {
            echo json_encode(["error"=>"Faltan parámetros (cliente, fecha, clase)"]); 
            exit;
        }

        $sql = "SELECT COUNT(*) AS c FROM reservaciones WHERE id_cliente = ? AND fecha_reservacion = ? AND id_clase = ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isi", $cliente, $fecha, $clase_id);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            echo json_encode(["exists" => ($r['c'] > 0)]);
        } else {
            echo json_encode(["error" => "Error en consulta"]);
        }
        exit;
        break;

    // RESERVAR 
    case 'reservar':
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload) { 
            echo json_encode(["ok"=>false,"error"=>"Payload inválido"]); 
            exit; 
        }

        $cliente = isset($payload['cliente']) ? intval($payload['cliente']) : 0;
        $fecha = $payload['fecha'] ?? '';
        $clase_id = isset($payload['clase']) ? intval($payload['clase']) : 0;

        if (!$cliente || !$fecha || !$clase_id) {
            echo json_encode(["ok"=>false,"error"=>"Faltan datos obligatorios"]); 
            exit;
        }

        // 1) Verificar si ya existe reserva
        $sql = "SELECT COUNT(*) AS c FROM reservaciones WHERE id_cliente=? AND fecha_reservacion=? AND id_clase=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isi", $cliente, $fecha, $clase_id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r['c'] > 0) {
            echo json_encode(["ok"=>false,"error"=>"Ya existe una reservación para este cliente en la misma clase"]); 
            exit;
        }

        // 2) Verificar capacidad de la clase
        $stmt = $mysqli->prepare("SELECT capacidad FROM clases WHERE id_clase = ?");
        $capacity = null;
        if ($stmt) {
            $stmt->bind_param("i", $clase_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $capacity = intval($row['capacidad']);
            }
        }

        // 3) Contar reservaciones actuales
        $sql = "SELECT COUNT(*) AS c FROM reservaciones WHERE fecha_reservacion=? AND id_clase=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("si", $fecha, $clase_id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $current = intval($r['c']);

        if ($capacity !== null && $current >= $capacity) {
            echo json_encode(["ok"=>false,"error"=>"Capacidad máxima alcanzada"]); 
            exit;
        }

        // 4) Insertar reservación
        $ins = $mysqli->prepare("INSERT INTO reservaciones (id_cliente, id_clase, fecha_reservacion, estado) VALUES (?, ?, ?, 'Confirmada')");
        if ($ins) {
            $ins->bind_param("iis", $cliente, $clase_id, $fecha);
            if ($ins->execute()) {
                echo json_encode(["ok"=>true]);
            } else {
                echo json_encode(["ok"=>false,"error"=>"Error al insertar: " . $ins->error]);
            }
        } else {
            echo json_encode(["ok"=>false,"error"=>"Error en preparación: " . $mysqli->error]);
        }
        exit;
        break;

    // CANCELAR RESERVA 
    case 'cancelarReserva':
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload) { 
            echo json_encode(["ok"=>false,"error"=>"Payload inválido"]); 
            exit; 
        }
        
        $cliente = isset($payload['cliente']) ? intval($payload['cliente']) : 0;
        $fecha = $payload['fecha'] ?? '';
        $clase_id = isset($payload['clase']) ? intval($payload['clase']) : 0;

        if (!$cliente || !$fecha || !$clase_id) {
            echo json_encode(["ok"=>false,"error"=>"Faltan datos"]); 
            exit;
        }

        // Verificar regla de cancelación (1 hora antes)
        $stmt = $mysqli->prepare("SELECT c.horario FROM clases c WHERE c.id_clase = ?");
        $hora_clase = null;
        if ($stmt) {
            $stmt->bind_param("i", $clase_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                // Extraer hora del horario 
                $hora_clase = "18:00"; 
            }
        }

        if ($hora_clase !== null) {
            $classDateTime = strtotime($fecha . ' ' . $hora_clase);
            $now = time();
            $diffMin = ($classDateTime - $now) / 60;
            if ($diffMin <= 60) {
                echo json_encode(["ok"=>false,"error"=>"No se puede cancelar: falta menos de 1 hora"]); 
                exit;
            }
        }

        // Eliminar reservación
        $del = $mysqli->prepare("DELETE FROM reservaciones WHERE id_cliente = ? AND fecha_reservacion = ? AND id_clase = ? LIMIT 1");
        if ($del) {
            $del->bind_param("isi", $cliente, $fecha, $clase_id);
            if ($del->execute()) {
                echo json_encode(["ok"=>true]);
            } else {
                echo json_encode(["ok"=>false,"error"=>"Error al cancelar: ".$del->error]);
            }
        } else {
            echo json_encode(["ok"=>false,"error"=>"Error en preparación: ".$mysqli->error]);
        }
        exit;
        break;

    // ESTADÍSTICAS DEL MES
    case 'statsMes':
        $month = isset($_GET['month']) ? intval($_GET['month']) : 0;
        $year = isset($_GET['year']) ? intval($_GET['year']) : 0;
        if (!$month || !$year) { 
            echo json_encode(["error"=>"month y year son requeridos"]); 
            exit; 
        }

        // Total de clases (count de tabla clases)
        $totalClases = 0;
        $r = $mysqli->query("SELECT COUNT(*) AS c FROM clases");
        if ($r && $row = $r->fetch_assoc()) {
            $totalClases = intval($row['c']);
        }

        // Reservas en el mes
        $reservas = 0;
        $sql = "SELECT COUNT(*) AS c FROM reservaciones WHERE YEAR(fecha_reservacion)=? AND MONTH(fecha_reservacion)=?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $year, $month);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $reservas = intval($res['c']);
        }

        // Asistencias 
        $asistencias = 0;
        $sql = "SELECT COUNT(*) AS c FROM reservaciones WHERE YEAR(fecha_reservacion)=? AND MONTH(fecha_reservacion)=? AND estado = 'Confirmada'";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $year, $month);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $asistencias = intval($res['c']);
        }

        echo json_encode([
            "totalClases" => $totalClases,
            "reservas" => $reservas, 
            "asistencias" => $asistencias
        ]);
        exit;
        break;

    default:
        echo json_encode(["error"=>"Acción no válida: $action"]);
        exit;
}
?>