<?php
// Directorio: EcoImpacto/config/conexion.php
$host = "localhost";
$user = "root";
$pass = ""; // En XAMPP suele estar vacío
$db = "ecoimpacto_db";

$conexion = mysqli_connect($host, $user, $pass, $db);

if (!$conexion) {
    die("Error crítico de conexión: " . mysqli_connect_error());
}
mysqli_set_charset($conexion, "utf8mb4");
?>