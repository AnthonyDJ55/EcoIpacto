<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(["error" => "Método no permitido"]);
  exit;
}

if (!isset($_SESSION['usuario'])) {
  http_response_code(401);
  echo json_encode(["error" => "Sesión no iniciada"]);
  exit;
}

// CSRF
$tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!$tokenHeader || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $tokenHeader)) {
  http_response_code(403);
  echo json_encode(["error" => "CSRF inválido"]);
  exit;
}

$puntos = filter_input(INPUT_POST, 'puntos', FILTER_VALIDATE_INT);
if ($puntos === false || $puntos < 0) {
  http_response_code(400);
  echo json_encode(["error" => "Puntos inválidos"]);
  exit;
}

require 'conexion.php';

$usuario = $_SESSION['usuario'];
$sql = "INSERT INTO puntuaciones_guardacostas (usuario, puntos) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $usuario, $puntos);
if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(["error" => "No se pudo guardar"]);
  exit;
}

echo json_encode(["ok" => true, "mensaje" => "¡Puntaje guardado!"]);
