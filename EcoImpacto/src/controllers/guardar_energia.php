<?php
session_start();
require_once("../config/conexion_guardar.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = getConexionGuardar();
$conn->set_charset("utf8mb4");

// Detectar formato de entrada
$raw = file_get_contents('php://input');
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
$data = [];

if (stripos($ct, 'application/json') !== false) {
  $data = json_decode($raw, true) ?: [];
} else {
  $data = $_POST;
}

// Jugador: prioriza sesión, luego payload, luego 'Anonimo'
$jugador  = $_SESSION['usuario'] ?? ($data['jugador'] ?? 'Anonimo');

$ahorro   = intval($data['ahorro_watts'] ?? 0);
$apagados = intval($data['dispositivos_apagados'] ?? 0);
$tiempo   = intval($data['tiempo_restante'] ?? 0);
$vidas    = intval($data['vidas_restantes'] ?? 0);

// Nota: ON DUPLICATE KEY requiere índice UNIQUE en 'jugador'
$stmt = $conn->prepare("
  INSERT INTO puntaje_energia (jugador, ahorro_watts, dispositivos_apagados, tiempo_restante, vidas_restantes)
  VALUES (?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
    ahorro_watts = VALUES(ahorro_watts),
    dispositivos_apagados = VALUES(dispositivos_apagados),
    tiempo_restante = VALUES(tiempo_restante),
    vidas_restantes = VALUES(vidas_restantes),
    fecha = CURRENT_TIMESTAMP
");
$stmt->bind_param("siiii", $jugador, $ahorro, $apagados, $tiempo, $vidas);
$stmt->execute();

header('Content-Type: application/json');
echo json_encode(['success' => true]);
