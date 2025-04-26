<?php
// 🔹 CONFIGURACIÓN DE LA BASE DE DATOS
$host = "192.168.1.100"; // 🔹 MODIFICAR: IP del servidor que tiene la BD
$usuario = "safeuser"; // 🔹 MODIFICAR: Usuario de la BD
$password = "adie"; // 🔹 MODIFICAR: Contraseña del usuario
$base_datos = "SafeHolder"; // 🔹 MODIFICAR: Nombre de la base de datos

// 🔹 CONEXIÓN A LA BASE DE DATOS
$conn = new mysqli($host, $usuario, $password, $base_datos);

// 🔹 COMPROBAR CONEXIÓN
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// 🔹 CONSULTA DE PRUEBA
$sql = "SELECT idusuari , nom , cognoms FROM usuaris"; // 🔹 MODIFICAR: Nombre de la tabla
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a BD Remota</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        table { width: 50%; margin: auto; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid black; }
        th { background-color: #007BFF; color: white; }
    </style>
</head>
<body>
    <h1>Datos de la Base de Datos Remota</h1>
    <table>
        <tr><th>ID</th><th>Nombre</th><th>Apellidos</th></tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>{$row['idusuari']}</td><td>{$row['nom']}</td><td>{$row['cognoms']}</td></tr>";
            }
        } else {
            echo "<tr><td colspan='2'>No hay datos</td></tr>";
        }
        ?>
    </table>
</body>
</html>

<?php
// 🔹 CERRAR CONEXIÓN
$conn->close();
?>
