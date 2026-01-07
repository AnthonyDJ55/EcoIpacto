<?php
session_start();
require_once("../config/conexion_guardar.php");

if (!isset($_SESSION['id'])) {
    die("Debes iniciar sesión.");
}

if (isset($_GET['id'])) {
    $album_id = intval($_GET['id']);
    $usuario_id = $_SESSION['id'];

    // Obtener rutas de fotos
    $stmt = $conexion->prepare("SELECT ruta_foto FROM fotos WHERE album_id = ?");
    $stmt->bind_param("i", $album_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($foto = $result->fetch_assoc()) {
        if (file_exists($foto['ruta_foto'])) {
            unlink($foto['ruta_foto']);
        }
    }
    $stmt->close();

    // Eliminar álbum
    $del = $conexion->prepare("DELETE FROM albumes WHERE id = ? AND usuario_id = ?");
    $del->bind_param("ii", $album_id, $usuario_id);
    $del->execute();
    $del->close();

    header("Location: perfil.php");
    exit();
}
?>
