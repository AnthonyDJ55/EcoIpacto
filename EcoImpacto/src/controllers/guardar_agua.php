<?php
require_once("../config/conexion_guardar.php");
$conn = getConexionGuardar();

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);
$agua_ahorrada = intval($data['agua_ahorrada'] ?? 0);
$decisiones = $data['decisiones'] ?? [];

if ($id > 0) {
    $decisiones_json = json_encode($decisiones);

    $stmt = $conn->prepare("INSERT INTO ahorro_agua (id, agua_ahorrada, decisiones) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE agua_ahorrada = VALUES(agua_ahorrada), decisiones = VALUES(decisiones)");
    $stmt->bind_param("iis", $id, $agua_ahorrada, $decisiones_json);

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
