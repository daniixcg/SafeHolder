<?php
session_start(); // Inicia o continúa la sesión

// Eliminar todas las variables de sesión
$_SESSION = [];

// Destruir la sesión en el servidor
session_destroy();

// Redirigir al usuario al login
header("Location: ../index.php");
exit;
?>