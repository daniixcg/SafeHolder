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
    $sql_bitcoin = "INSERT INTO bitcoinHistoric (fecha, valor) VALUES (NOW(), $bitcoin_price)";
    if ($conn->query($sql_bitcoin) === TRUE) {
        echo "[" . date("Y-m-d H:i:s") . "] INFO: Precio de Bitcoin insertado correctamente: $bitcoin_price USD.\n";
        
        // Eliminar el registro más antiguo si hay más de 48 registros
        $sql_count = "SELECT COUNT(*) AS total FROM bitcoinHistoric";
        $result = $conn->query($sql_count);
        $row = $result->fetch_assoc();
        if ($row['total'] > 48) {
            $sql_delete = "DELETE FROM bitcoinHistoric ORDER BY fecha ASC LIMIT 1";
            $conn->query($sql_delete);
            echo "[" . date("Y-m-d H:i:s") . "] INFO: El registro más antiguo de Bitcoin fue eliminado.\n";
        }
    } else {
        echo "[" . date("Y-m-d H:i:s") . "] ERROR: Error al insertar precio de Bitcoin: " . $conn->error . "\n";
    }
} else {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: No se encontró el precio de Bitcoin.\n";
}

// =============================================
// 2. Conseguir precio del Oro (PAXG a USD) (idactiu = 2)
// =============================================

$gold_url = "https://api.coingecko.com/api/v3/simple/price?ids=pax-gold&vs_currencies=usd";
$gold_response = file_get_contents($gold_url);
$gold_data = json_decode($gold_response, true);
$gold_price = $gold_data['pax-gold']['usd'] ?? null;

if ($gold_price !== null) {
    $sql_gold = "INSERT INTO orHistoric (fecha, valor) VALUES (NOW(), $gold_price)";
    if ($conn->query($sql_gold) === TRUE) {
        echo "[" . date("Y-m-d H:i:s") . "] INFO: Precio de Oro (PAXG) insertado correctamente: $gold_price USD.\n";
        
        // Eliminar el registro más antiguo si hay más de 48 registros
        $sql_count = "SELECT COUNT(*) AS total FROM orHistoric";
        $result = $conn->query($sql_count);
        $row = $result->fetch_assoc();
        if ($row['total'] > 48) {
            $sql_delete = "DELETE FROM orHistoric ORDER BY fecha ASC LIMIT 1";
            $conn->query($sql_delete);
            echo "[" . date("Y-m-d H:i:s") . "] INFO: El registro más antiguo de Oro (PAXG) fue eliminado.\n";
        }
    } else {
        echo "[" . date("Y-m-d H:i:s") . "] ERROR: Error al insertar precio de Oro (PAXG): " . $conn->error . "\n";
    }
} else {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: No se encontró el precio de Oro (PAXG).\n";
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
    $sql_euro = "INSERT INTO euroHistoric (fecha, valor) VALUES (NOW(), $eur_to_usd)";
    if ($conn->query($sql_euro) === TRUE) {
        echo "[" . date("Y-m-d H:i:s") . "] INFO: Precio del Euro insertado correctamente: $eur_to_usd USD.\n";
        
        // Eliminar el registro más antiguo si hay más de 48 registros
        $sql_count = "SELECT COUNT(*) AS total FROM euroHistoric";
        $result = $conn->query($sql_count);
        $row = $result->fetch_assoc();
        if ($row['total'] > 48) {
            $sql_delete = "DELETE FROM euroHistoric ORDER BY fecha ASC LIMIT 1";
            $conn->query($sql_delete);
            echo "[" . date("Y-m-d H:i:s") . "] INFO: El registro más antiguo de Euro fue eliminado.\n";
        }
    } else {
        echo "[" . date("Y-m-d H:i:s") . "] ERROR: Error al insertar precio del Euro: " . $conn->error . "\n";
    }
} else {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: No se encontró el precio del Euro.\n";
}

// =============================================

$conn->close();
?>
