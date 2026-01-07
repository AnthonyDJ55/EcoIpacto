<?php
session_start();
require_once("../config/conexion_guardar.php");
header('Content-Type: application/json');

$conn = getConexionGuardar();
if (!$conn || $conn->connect_error) {
    echo json_encode(["estado" => "error", "mensaje" => "Conexión fallida"]);
    exit;
}

// Recuperar datos desde POST
$puntos = intval($_POST['score'] ?? 0);
$ecoCoins = intval($_POST['ecoCoins'] ?? 0);
$email = $_SESSION['email'] ?? '';

if ($puntos <= 0 && $ecoCoins <= 0) {
    echo json_encode(["estado" => "error", "mensaje" => "Nada que guardar"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO puntaje_reciclaje (email, puntos, ecoCoins) VALUES (?, ?, ?)");
$stmt->bind_param("sii", $email, $puntos, $ecoCoins);

if ($stmt->execute()) {
    echo json_encode(["estado" => "ok", "mensaje" => "✅ Guardado"]);
} else {
    echo json_encode(["estado" => "error", "mensaje" => "❌ Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
