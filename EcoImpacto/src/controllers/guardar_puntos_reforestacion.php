<?php
require_once("../config/conexion_guardar.php");
$conn = getConexionGuardar();

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);
$puntos = intval($data['puntos'] ?? 0);
$historial = $data['historial'] ?? [];

if ($id > 0) {
    $historial_json = json_encode($historial);

    $stmt = $conn->prepare("INSERT INTO puntaje_reforestacion (id, puntos, historial) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE puntos = VALUES(puntos), historial = VALUES(historial)");
    $stmt->bind_param("iis", $id, $puntos, $historial_json);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "ID inválido"]);
}

$conn->close();
?>
