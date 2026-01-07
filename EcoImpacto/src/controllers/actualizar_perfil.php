<?php
session_start();
require_once("../config/conexion_guardar.php");

// Mostrar errores para depurar (solo mientras desarrollas)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar sesión activa
if (!isset($_SESSION['id_usuario'])) {
    die("Acceso no autorizado");
}

$id = $_SESSION['id_usuario'];

// Validar entrada POST
$nombre    = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$apellido  = isset($_POST['apellido']) ? $_POST['apellido'] : '';
$email     = isset($_POST['email']) ? $_POST['email'] : '';

// Verificar si se subió imagen
$foto_perfil = isset($_FILES['foto_perfil']) ? $_FILES['foto_perfil'] : null;
$ruta_foto = isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : '';

if ($foto_perfil && $foto_perfil['size'] > 0 && $foto_perfil['error'] === 0) {
    // Validar tipo de imagen (solo JPG o PNG)
    $tipos_permitidos = ['image/jpeg', 'image/png'];
    if (!in_array($foto_perfil['type'], $tipos_permitidos)) {
        die("Formato de imagen no permitido. Usa JPG o PNG.");
    }

    // Nombre único para imagen
    $extension = pathinfo($foto_perfil['name'], PATHINFO_EXTENSION);
    $nombre_foto = 'foto_' . $id . '_' . time() . '.' . $extension;
    $ruta_destino = 'foto_perfil/' . $nombre_foto;

    // Mover imagen a carpeta destino
    if (!move_uploaded_file($foto_perfil['tmp_name'], $ruta_destino)) {
        die("Error al subir la imagen.");
    }

    // Actualizar ruta de imagen
    $ruta_foto = $ruta_destino;
}

// Preparar consulta SQL
$stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, foto_perfil = ? WHERE id = ?");
if (!$stmt) {
    die("Error en la preparación de consulta: " . $conexion->error);
}

// Enviar datos de forma segura
$stmt->bind_param("ssssi", $nombre, $apellido, $email, $ruta_foto, $id);
if ($stmt->execute()) {
    // Actualizar datos de sesión
    $_SESSION['nombre'] = $nombre;
    $_SESSION['apellido'] = $apellido;
    $_SESSION['email'] = $email;
    $_SESSION['foto_perfil'] = $ruta_foto;

    // Redirigir al perfil con mensaje de éxito
    header("Location: index.php?perfil=actualizado");
    exit();
} else {
    die("Error al actualizar datos: " . $stmt->error);
}
?>
