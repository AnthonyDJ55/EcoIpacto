<?php
// Directorio: config/db.php
$host = "localhost";
$user = "root";
$pass = "";
$db = "ecoimpacto_db";

$conexion = mysqli_connect($host, $user, $pass, $db);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
// Ajuste de caracteres para evitar errores con tildes
mysqli_set_charset($conexion, "utf8");
?>