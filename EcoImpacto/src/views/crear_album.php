<?php
session_start();
require_once("../config/conexion_guardar.php");

if (!isset($_SESSION['id'])) {
    die("Debes iniciar sesión.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $usuario_id = $_SESSION['id'];

    if ($titulo !== '') {
        // Crear álbum
        $stmt = $conexion->prepare("INSERT INTO albumes (usuario_id, titulo, descripcion) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $usuario_id, $titulo, $descripcion);
        $stmt->execute();
        $album_id = $stmt->insert_id;
        $stmt->close();

        // Subir fotos
        if (!empty($_FILES['fotos']['name'][0])) {
            $carpetaDestino = "uploads/albumes/";
            if (!is_dir($carpetaDestino)) {
                mkdir($carpetaDestino, 0755, true);
            }

            foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                    $nombreArchivo = uniqid('foto_') . "_" . basename($_FILES['fotos']['name'][$key]);
                    $rutaCompleta = $carpetaDestino . $nombreArchivo;
                    if (move_uploaded_file($tmp_name, $rutaCompleta)) {
                        $stmtFoto = $conexion->prepare("INSERT INTO fotos (album_id, ruta_foto) VALUES (?, ?)");
                        $stmtFoto->bind_param("is", $album_id, $rutaCompleta);
                        $stmtFoto->execute();
                        $stmtFoto->close();
                    }
                }
            }
        }

        header("Location: perfil.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear Álbum</title>
<link rel="stylesheet" href="estilo_album.css?v=1">
</head>
<body>

<div class="form-contenedor">
    <h2>📂 Crear nuevo álbum</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="titulo">Título:</label>
        <input type="text" name="titulo" id="titulo" required>

        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion"></textarea>

        <label for="fotos">Fotos:</label>
        <input type="file" name="fotos[]" id="fotos" multiple accept="image/*">

        <button type="submit" class="btn btn-crear">Crear álbum</button>
    </form>
    <br>
    <a href="perfil.php" class="btn btn-eliminar">Volver</a>
</div>

</body>
</html>
