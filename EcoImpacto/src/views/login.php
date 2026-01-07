<?php
session_start();
// Salir de src/views para encontrar la carpeta config
require_once("../../config/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? '';
    $clave = $_POST["clave"] ?? '';

    // CORRECCIÓN CLAVE: Cambiamos 'email' por 'correo' porque así está en tu SQL
    $sql = "SELECT * FROM usuarios WHERE correo = ?"; 
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        // CORRECCIÓN CLAVE: Cambiamos 'contraseña' por 'password' porque así está en tu SQL
        if (password_verify($clave, $usuario["password"])) {
            // Mantenemos tus nombres de variables de sesión exactamente como los tenías
            $_SESSION["usuario"]   = $usuario["correo"]; 
            $_SESSION["nombre"]    = $usuario["nombre_usuario"]; 
            $_SESSION["id"]        = $usuario["id"];
            
            // Si en tu SQL no existen 'apellido' y 'provincia', estas líneas darán error, 
            // pero las dejo como las tenías por si las piensas agregar luego:
            $_SESSION["apellido"]  = $usuario["apellido"] ?? '';
            $_SESSION["provincia"] = $usuario["provincia"] ?? '';
            
            header("Location: ../../index.php");
            exit();
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="../../public/css/estilo_login.css">
  <script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js" type="module"></script>
  <script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js" nomodule></script>
</head>
<body>
<div class="wrapper">
  <div class="login-box">
    <form method="POST">
      <h2>Login</h2>
      <?php if (!empty($error)): ?>
        <p style="color:red; text-align:center;"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <div class="input-box">
        <span class="icon"><ion-icon name="mail"></ion-icon></span>
        <input type="email" name="email" required>
        <label>Email</label>
      </div>

      <div class="input-box">
        <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
        <input type="password" name="clave" required>
        <label>Contraseña</label>
      </div>

      <button type="submit">Iniciar sesión</button>

      <div class="register-link">
        <p>¿No tienes cuenta? <a href="../views/registro.php">Regístrate</a></p>
      </div>
    </form>
  </div>
</div>
</body>
</html>