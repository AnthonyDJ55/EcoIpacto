<?php
header('Content-Type: application/json; charset=utf-8');
require_once("../config/conexion_guardar.php");

$sql = "SELECT usuario, puntos, fecha FROM puntuaciones_guardacostas ORDER BY puntos DESC, fecha ASC LIMIT 10";
$res = $conn->query($sql);

$data = [];
while ($row = $res->fetch_assoc()) {
  $data[] = $row;
}
echo json_encode(["ok" => true, "top" => $data]);
