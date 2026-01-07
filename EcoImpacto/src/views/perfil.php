<?php
// Ubicación: EcoImpacto/src/views/perfil.php
session_start();
require_once("../../config/conexion.php");

if (!isset($_SESSION['id'])) {
    die("Debes iniciar sesión.");
}

$id = $_SESSION['id'];

// Función para subir foto de perfil
function subirFotoPerfil($archivoFoto, $carpetaDestino = '../../uploads/') {
    if ($archivoFoto['error'] === UPLOAD_ERR_OK) {
        // Asegurarse de que la carpeta existe
        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0755, true);
        }
        $nombreArchivo = uniqid('perfil_') . '_' . basename($archivoFoto['name']);
        $rutaCompleta = $carpetaDestino . $nombreArchivo;
        if (move_uploaded_file($archivoFoto['tmp_name'], $rutaCompleta)) {
            // RETORNAMOS SOLO EL NOMBRE PARA LA BD (Sin los ../../)
            return $nombreArchivo; 
        }
    }
    return '';
}

// Procesar cambio de foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nueva_foto'])) {
    $nuevoNombreArchivo = subirFotoPerfil($_FILES['nueva_foto']);
    if ($nuevoNombreArchivo !== '') {
        $actualiza = $conexion->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
        $actualiza->bind_param("si", $nuevoNombreArchivo, $id);
        $actualiza->execute();
        $actualiza->close();
        header("Location: perfil.php");
        exit();
    }
}

// Procesar cambio de nombre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_nombre'])) {
    $nuevoNombre = trim($_POST['nuevo_nombre']);
    if ($nuevoNombre !== '') {
        $actualizaNombre = $conexion->prepare("UPDATE usuarios SET nombre_usuario = ? WHERE id = ?");
        $actualizaNombre->bind_param("si", $nuevoNombre, $id);
        $actualizaNombre->execute();
        $actualizaNombre->close();
        $_SESSION['nombre'] = $nuevoNombre;
        header("Location: perfil.php");
        exit();
    }
}

// Obtener datos del usuario (Ajustado a tu SQL)
$sql = "SELECT nombre_usuario, correo, foto_perfil FROM usuarios WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($nombre_usuario, $correo, $foto_perfil_bd);
$stmt->fetch();
$stmt->close();

// Definir la ruta de la foto para mostrarla AQUÍ en el perfil
$foto_mostrar = !empty($foto_perfil_bd) ? "../../uploads/" . $foto_perfil_bd : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Perfil del Usuario</title>
<link rel="stylesheet" href="../../public/css/estilo_perfil.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="perfil-fondo">
    <div class="perfil-contenedor" style="max-width: 800px; margin: auto; padding: 20px;">
        <h2>👤 Perfil de <span><?= htmlspecialchars($nombre_usuario) ?></span></h2>
        
        <div class="perfil-avatar" style="text-align: center;">
            <img src="<?= $foto_mostrar ?>" alt="Foto de perfil" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover;">
        </div>

        <div class="perfil-datos">
            <div><strong>Nombre de Usuario:</strong> <?= htmlspecialchars($nombre_usuario) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($correo) ?></div>
        </div>

        <hr>

        <form action="perfil.php" method="POST" class="perfil-form-nombre">
            <label for="nuevo_nombre">✏️ Cambiar nombre de usuario:</label>
            <input type="text" name="nuevo_nombre" id="nuevo_nombre" value="<?= htmlspecialchars($nombre_usuario) ?>" required>
            <button type="submit" class="btn btn-crear">Actualizar</button>
        </form>

        <form action="perfil.php" method="POST" enctype="multipart/form-data" class="perfil-form-foto">
            <label for="nueva_foto">📷 Cambiar foto de perfil:</label>
            <input type="file" name="nueva_foto" id="nueva_foto" accept="image/*" required>
            <button type="submit" class="btn btn-crear">Subir Foto</button>
        </form>

        <hr>

        <div class="perfil-botones">
            <a href="../../index.php" class="btn btn-eliminar" style="background: red; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">Volver al Inicio</a>
        </div>
    </div>
</div>
<script>
    const body = document.body;
    // Buscamos el botón si existe en esta página (en login/registro quizás no haya botón)
    const btn = document.querySelector('#dark-mode-toggle');

    // 1. FUNCIÓN PARA APLICAR EL TEMA
    const applyTheme = () => {
        const theme = localStorage.getItem('dark-mode');
        if (theme === 'enabled') {
            body.classList.add('dark-mode');
            if(btn) btn.innerHTML = '☀️';
        } else {
            body.classList.remove('dark-mode');
            if(btn) btn.innerHTML = '🌙';
        }
    };

    // 2. EJECUTAR AL CARGAR
    applyTheme();

    // 3. EVENTO PARA EL BOTÓN (Solo si la página lo tiene, como el Index)
    if (btn) {
        btn.addEventListener('click', () => {
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('dark-mode', 'disabled');
            } else {
                localStorage.setItem('dark-mode', 'enabled');
            }
            applyTheme();
        });
    }

    // 4. MAGIA: Escuchar cambios desde otras pestañas
    window.addEventListener('storage', (e) => {
        if (e.key === 'dark-mode') {
            applyTheme();
        }
    });
</script>
</body>
</html>