<?php
// Ubicación: EcoImpacto/src/views/registro.php
session_start();
require_once("../../config/conexion.php"); 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre_u = $_POST["nombre_usuario"] ?? '';
    $email = $_POST["email"] ?? '';
    $clave = $_POST["clave"] ?? '';
    
    // 1. VERIFICACIÓN: Comprobar si el correo ya existe para evitar el Fatal Error
    $check_sql = "SELECT id FROM usuarios WHERE correo = ? OR nombre_usuario = ?";
    $check_stmt = $conexion->prepare($check_sql);
    $check_stmt->bind_param("ss", $email, $nombre_u);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $error = "El correo o el nombre de usuario ya están registrados.";
        $check_stmt->close();
    } else {
        $check_stmt->close();
        
        // 2. REGISTRO: Si no hay duplicados, procedemos a insertar
        $clave_hash = password_hash($clave, PASSWORD_BCRYPT);
        $sql = "INSERT INTO usuarios (nombre_usuario, correo, password) VALUES (?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sss", $nombre_u, $email, $clave_hash);

        if ($stmt->execute()) {
            header("Location: login.php?registro=exito");
            exit();
        } else {
            $error = "Error al registrar: " . $conexion->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - EcoJuegos</title>
    <link rel="stylesheet" href="../../public/css/estilo_login.css">
    <script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js" type="module"></script>
    <script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js" nomodule></script>
</head>
<body>
<div class="wrapper">
    <div class="login-box">
        <form method="POST">
            <h2>Crear Cuenta</h2>
            <?php if (!empty($error)): ?>
                <p style="color:red; text-align:center;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <div class="input-box">
                <span class="icon"><ion-icon name="person"></ion-icon></span>
                <input type="text" name="nombre_usuario" required>
                <label>Nombre de Usuario</label>
            </div>

            <div class="input-box">
                <span class="icon"><ion-icon name="mail"></ion-icon></span>
                <input type="email" name="email" required>
                <label>Email (Correo)</label>
            </div>

            <div class="input-box">
                <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                <input type="password" name="clave" required>
                <label>Contraseña</label>
            </div>

            <button type="submit" style="width: 100%; height: 45px; background: #28a745; color: #fff; border-radius: 6px; cursor: pointer; border: none; font-weight: bold;">
                Registrarse
            </button>

            <div class="register-link">
                <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
            </div>
        </form>
    </div>
</div>
</body>
</html>