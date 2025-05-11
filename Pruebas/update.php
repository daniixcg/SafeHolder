<?php
// Configuración de la base de datos
$servername = "192.168.1.100";
$username = "safeuser";
$password = "adie";
$dbname = "SafeHolder";

// Conectar a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: Conexión fallida: " . $conn->connect_error . "\n";
    exit;
}

// =============================================
// 1. Conseguir precio de Bitcoin (idactiu = 1)
// =============================================
$bitcoin_url = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd";
$bitcoin_response = file_get_contents($bitcoin_url);
$bitcoin_data = json_decode($bitcoin_response, true);
$bitcoin_price = $bitcoin_data['bitcoin']['usd'] ?? null;

if ($bitcoin_price !== null) {
    $sql_bitcoin = "UPDATE actius SET valor = $bitcoin_price WHERE idactiu = 1";
    if ($conn->query($sql_bitcoin) === TRUE) {
        echo "[" . date("Y-m-d H:i:s") . "] INFO: Precio del Bitcoin actualizado correctamente a $bitcoin_price USD.\n";
    } else {
        echo "[" . date("Y-m-d H:i:s") . "] ERROR: Error al actualizar Bitcoin: " . $conn->error . "\n";
    }
} else {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: No se encontró el precio del Bitcoin.\n";
}

// =============================================
// 2. Conseguir precio del Oro (PAXG a USD) (idactiu = 2)
// =============================================
$gold_url = "https://api.coingecko.com/api/v3/simple/price?ids=pax-gold&vs_currencies=usd";
$gold_response = file_get_contents($gold_url);
$gold_data = json_decode($gold_response, true);
$gold_price = $gold_data['pax-gold']['usd'] ?? null;

if ($gold_price !== null) {
    $sql_gold = "UPDATE actius SET valor = $gold_price WHERE idactiu = 2";
    if ($conn->query($sql_gold) === TRUE) {
        echo "[" . date("Y-m-d H:i:s") . "] INFO: Precio del Oro (PAXG) actualizado correctamente a $gold_price USD.\n";
    } else {
        echo "[" . date("Y-m-d H:i:s") . "] ERROR: Error al actualizar Oro (PAXG): " . $conn->error . "\n";
    }
} else {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: No se encontró el precio del Oro (PAXG).\n";
}

// =============================================
// 3. Conseguir precio del Euro (idactiu = 3) - Solución alternativa
// =============================================
$url_usd = "https://api.coingecko.com/api/v3/simple/price?ids=usd&vs_currencies=eur";
$response_usd = file_get_contents($url_usd);
if ($response_usd === false) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: Error al pedir datos del USD.\n";
    exit;
}
$data_usd = json_decode($response_usd, true);

if (isset($data_usd['usd']['eur'])) {
    $usd_to_eur = $data_usd['usd']['eur'];
    $eur_to_usd = 1 / $usd_to_eur;  // Invertir
    $sql_euro = "UPDATE actius SET valor = $eur_to_usd WHERE idactiu = 3";
    if ($conn->query($sql_euro) === TRUE) {
        echo "[" . date("Y-m-d H:i:s") . "] INFO: Precio del Euro actualizado correctamente a $eur_to_usd USD.\n";
    } else {
        echo "[" . date("Y-m-d H:i:s") . "] ERROR: Error al actualizar Euro: " . $conn->error . "\n";
    }
} else {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: No se encontró el precio del Euro.\n";
}

// =============================================

$conn->close();
?>
