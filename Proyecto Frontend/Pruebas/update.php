<?php
$servername = "192.168.1.100";
$username = "safeuser";
$password = "adie";
$dbname = "SafeHolder";

// Conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// 1. Obtener precio de Bitcoin en USD
$url_btc = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd";
$response_btc = file_get_contents($url_btc);
if ($response_btc === false) {
    die("Error al pedir datos de Bitcoin");
}
$data_btc = json_decode($response_btc, true);

if (isset($data_btc['bitcoin']['usd'])) {
    $bitcoin_usd = $data_btc['bitcoin']['usd'];
} else {
    die("Error: No se encontró el precio de Bitcoin");
}

// 2. Obtener valor del Dólar en Euros
$url_usd = "https://api.coingecko.com/api/v3/simple/price?ids=usd&vs_currencies=eur";
$response_usd = file_get_contents($url_usd);
if ($response_usd === false) {
    die("Error al pedir datos del USD");
}
$data_usd = json_decode($response_usd, true);

if (isset($data_usd['usd']['eur'])) {
    $usd_to_eur = $data_usd['usd']['eur'];
    $eur_to_usd = 1 / $usd_to_eur;  // Invertir
} else {
    die("Error: No se encontró el precio del Euro");
}

// Actualizar precio de Bitcoin
$stmt = $conn->prepare("UPDATE actius SET valor = ? WHERE idactiu = 1");
$stmt->bind_param("d", $bitcoin_usd);
if ($stmt->execute()) {
    echo "Precio de Bitcoin actualizado correctamente<br>";
} else {
    echo "Error al actualizar Bitcoin: " . $stmt->error . "<br>";
}
$stmt->close();

// Actualizar precio del Euro
$stmt2 = $conn->prepare("UPDATE actius SET valor = ? WHERE idactiu = 3");
$stmt2->bind_param("d", $eur_to_usd);
if ($stmt2->execute()) {
    echo "Precio del Euro actualizado correctamente<br>";
} else {
    echo "Error al actualizar Euro: " . $stmt2->error . "<br>";
}
$stmt2->close();

$conn->close();
?>
