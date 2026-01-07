<?php
require_once("../config/conexion_guardar.php");
$conn = getConexionGuardar();

$data = json_decode(file_get_contents("php://input"), true);
$jugador = $data['jugador'] ?? '';
$puntaje = intval($data['puntaje'] ?? 0);

if ($jugador && $puntaje >= 0) {
    $stmt = $conn->prepare("INSERT INTO polinizacion_veloz (jugador, puntaje, fecha) VALUES (?, ?, NOW())");
    $stmt->bind_param("si", $jugador, $puntaje);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(["success" => $success]);
} else {
    echo json_encode(["success" => false]);
}
$conn->close();
?>
