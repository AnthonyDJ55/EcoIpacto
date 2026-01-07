<?php
session_start();
header('Content-Type: application/json');

// 1) Método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['ok'=>false, 'msg'=>'Método no permitido']); exit;
}

// 2) Cargar body JSON (o permitir x-www-form-urlencoded)
$raw  = file_get_contents('php://input');
$ct   = $_SERVER['CONTENT_TYPE'] ?? '';
$data = [];

if (stripos($ct, 'application/json') !== false) {
  $data = json_decode($raw, true);
  if (!is_array($data)) { echo json_encode(['ok'=>false,'msg'=>'JSON inválido']); exit; }
} else {
  $data = $_POST; // fallback
}

// 3) CSRF (si tu vista lo está generando en <meta name="csrf-token">)
if (empty($_SESSION['csrf'])) {
  // genera uno si no existe (primera carga)
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrfCliente = $data['csrf'] ?? '';
if ($csrfCliente !== $_SESSION['csrf']) {
  echo json_encode(['ok'=>false,'msg'=>'CSRF inválido']); exit;
}

// 4) Campos
$usuario = $_SESSION['usuario'] ?? 'Anónimo';
$juego   = 'Reto: ¿Son Combustible?';
$puntos  = (int)($data['puntos']  ?? 0);
$limpias = (int)($data['limpias'] ?? 0);
$errores = (int)($data['errores'] ?? 0);
$tiempo  = (int)($data['tiempo']  ?? 0);
$vidas   = (int)($data['vidas']   ?? 0);

// 5) Insert en la nueva tabla
try {
  require_once __DIR__ . '/config.php'; // Debe crear $pdo (PDO conectado a MySQL)

  $sql = "INSERT INTO puntuaciones_combustible (usuario, juego, puntos, limpias, errores, tiempo, vidas)
          VALUES (?, ?, ?, ?, ?, ?, ?)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario, $juego, $puntos, $limpias, $errores, $tiempo, $vidas]);

  // opcional: guardar mejor puntaje en sesión
  $_SESSION['puntos'] = max($_SESSION['puntos'] ?? 0, $puntos);

  echo json_encode(['ok'=>true, 'msg'=>'Guardado']);
} catch (Throwable $e) {
  // Log interno para depurar (no exponer en respuesta)
  error_log('guardar_combustible.php: ' . $e->getMessage());
  echo json_encode(['ok'=>false, 'msg'=>'BD no disponible o error de conexión.']);
}
