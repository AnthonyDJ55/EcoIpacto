<?php
session_start();

// Supongamos que tienes la variable $puntos ya en sesión o necesitas capturarla desde alguna lógica
// Aquí simplemente reafirmamos lo acumulado
$puntos = isset($_SESSION['puntos']) ? $_SESSION['puntos'] : 0;

// Guardamos los puntos otra vez por si se actualizó
$_SESSION['puntos'] = $puntos;

// Redireccionamos al menú principal
header("Location: index.php");
exit();
?>
