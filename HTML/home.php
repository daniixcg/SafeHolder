<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

$usuario = $_SESSION['usuario'];

// --- FUNCIÓN PARA REGISTRAR TRANSACCIÓN ---
function registrarTransaccion($conn, $idportafoli, $idactiu, $tipo, $cantidad) {
    // Asegura que el tipo esté en mayúsculas para coincidir con la base de datos
    $tipo = strtoupper($tipo);
    $sql = "INSERT INTO transaccions (idportafoli, idactiu, tipustransaccio, quantitat, datatransaccio)
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisd", $idportafoli, $idactiu, $tipo, $cantidad);
    $stmt->execute();
    $stmt->close();
}

// --- PETICIÓN AJAX para cargar activos y dólares ---
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "DB connection failed"]);
        exit;
    }

    $sql = "SELECT idactiu, valor FROM actius WHERE idactiu IN (1, 2, 3)";
    $result = $conn->query($sql);
    $valores = [];

    while ($row = $result->fetch_assoc()) {
        if (!isset($row["idactiu"]) || !isset($row["valor"])) continue;
        $valores[$row["idactiu"]] = $row["valor"];
    }

    $sql = "SELECT dolars FROM usuaris WHERE nom = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($dollars);
    $stmt->fetch();
    $stmt->close();

    $conn->close();
    $valores['dollars'] = $dollars;
    echo json_encode($valores);
    exit;
}
// --- PETICIÓN AJAX PARA ACTIVOS DEL USUARIO (INCL. VALOR USD) ---
if (isset($_GET['portafolis_actius']) && $_GET['portafolis_actius'] === '1') {
    $conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "DB connection failed"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT u.idusuari, p.idportafoli FROM usuaris u JOIN portafolis p ON u.idusuari = p.idusuari WHERE u.nom = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($idusuari, $idportafoli);
    $stmt->fetch();
    $stmt->close();

    // Usa mayúsculas aquí
    $activos_posibles = ['BITCOIN', 'OR', 'EURO'];
    $valores = [];
    $sql = "SELECT nom, valor FROM actius WHERE nom IN ('BITCOIN', 'OR', 'EURO')";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $valores[$row['nom']] = $row['valor'];
    }

    // Inicializar todos los activos con cantidad 0
    $activos = [];
    foreach ($activos_posibles as $nombre) {
        $activos[$nombre] = [
            "activo" => strtolower($nombre), // para el frontend
            "cantidad" => 0,
            "valor_usd" => 0
        ];
    }

    // Obtener los activos reales del usuario
    $sql = "SELECT a.nom, pa.quantitat FROM portafolis_actius pa JOIN actius a ON pa.idactiu = a.idactiu WHERE pa.idportafoli = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idportafoli);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $nombre = $row['nom'];
        if (isset($activos[$nombre])) {
            $activos[$nombre]['cantidad'] = $row['quantitat'];
            $activos[$nombre]['valor_usd'] = round($row['quantitat'] * ($valores[$nombre] ?? 0), 2);
        }
    }
    $stmt->close();
    $conn->close();

    // Devolver como array indexado
    echo json_encode(array_values($activos));
    exit;
}

// --- CALCULAR VALOR TOTAL DE LA CARTERA ---
$conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
if ($conn->connect_error) {
    $valorCartera = 0;
} else {
    $stmt = $conn->prepare("SELECT u.idusuari, p.idportafoli, u.dolars
                            FROM usuaris u
                            JOIN portafolis p ON u.idusuari = p.idusuari
                            WHERE u.nom = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($idusuari, $idportafoli, $dollars);
    $stmt->fetch();
    $stmt->close();

    $totalActivos = 0;
    $sql = "SELECT pa.quantitat, a.valor FROM portafolis_actius pa JOIN actius a ON pa.idactiu = a.idactiu WHERE pa.idportafoli = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idportafoli);
    $stmt->execute();
    $stmt->bind_result($cantidad, $valor);
    while ($stmt->fetch()) {
        $totalActivos += $cantidad * $valor;
    }
    $stmt->close();
    $conn->close();

    $valorCartera = $dollars + $totalActivos;
}

// --- CARGAR DATOS PARA GRÁFICO DE ACTIVO ---
if (isset($_GET['grafico'])) {
    $tipo = $_GET['grafico'];
    $tabla = "";

    switch ($tipo) {
        case "bitcoin": $tabla = "bitcoinHistoric"; break;
        case "euro": $tabla = "euroHistoric"; break;
        case "oro": $tabla = "orHistoric"; break;
        default:
            http_response_code(400);
            echo json_encode(["error" => "Tipo de activo inválido"]);
            exit;
    }

    $conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "DB connection failed"]);
        exit;
    }

    $sql = "SELECT fecha, valor FROM $tabla ORDER BY fecha DESC LIMIT 10";
    $result = $conn->query($sql);
    $labels = [];
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $labels[] = date("H:i", strtotime($row["fecha"]));
        $data[] = floatval($row["valor"]);
    }

    $datos["labels"] = array_reverse($labels);
    $datos["data"] = array_reverse($data);
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode($datos);
    exit;
}

// --- DATOS PARA HEADER (valor total de cartera del usuario) ---
$conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Obtener idusuari y idportafoli del usuario logueado
$stmt = $conn->prepare("SELECT u.idusuari, p.idportafoli, u.dolars, u.inactivitat 
                        FROM usuaris u 
                        JOIN portafolis p ON u.idusuari = p.idusuari 
                        WHERE u.nom = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->bind_result($idusuari, $idportafoli, $dollars, $inactividad);
$stmt->fetch();
$stmt->close();

// Sumar el valor de todos los activos del usuario en dólares
$totalActivos = 0;
$stmt = $conn->prepare("SELECT pa.quantitat, a.valor 
                        FROM portafolis_actius pa 
                        JOIN actius a ON pa.idactiu = a.idactiu 
                        WHERE pa.idportafoli = ?");
$stmt->bind_param("i", $idportafoli);
$stmt->execute();
$stmt->bind_result($cantidad, $valor);

while ($stmt->fetch()) {
    $totalActivos += $cantidad * $valor;
}
$stmt->close();

$conn->close();

// Valor total de la cartera (dólares + activos)
$valorCartera = $dollars + $totalActivos;

// --- PROCESAR COMPRA ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'comprar') {
    header('Content-Type: application/json');

    $activo = $_POST['activo'] ?? '';
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $precio = floatval($_POST['precio'] ?? 0);

    if ($activo === 'oro') $activo = 'or';

    if (!$activo || $cantidad <= 0 || $precio <= 0) {
        echo json_encode(["success" => false, "message" => "❌ Datos inválidos"]);
        exit;
    }

    $totalUSD = $cantidad * $precio;

    $conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "❌ Error de conexión"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT idusuari, dolars FROM usuaris WHERE nom = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($idusuari, $saldo);
    $stmt->fetch();
    $stmt->close();

    if ($saldo < $totalUSD) {
        echo json_encode(["success" => false, "message" => "⚠️ No tienes suficiente saldo."]);
        exit;
    }

    $stmt = $conn->prepare("SELECT idportafoli FROM portafolis WHERE idusuari = ?");
    $stmt->bind_param("i", $idusuari);
    $stmt->execute();
    $stmt->bind_result($idportafoli);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT idactiu FROM actius WHERE nom = ?");
    $stmt->bind_param("s", $activo);
    $stmt->execute();
    $stmt->bind_result($idactiu);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE usuaris SET dolars = dolars - ? WHERE idusuari = ?");
    $stmt->bind_param("di", $totalUSD, $idusuari);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE portafolis_actius SET quantitat = quantitat + ? WHERE idportafoli = ? AND idactiu = ?");
    $stmt->bind_param("dii", $cantidad, $idportafoli, $idactiu);
    $stmt->execute();
    $stmt->close();

    // Registrar transacción de compra
    registrarTransaccion($conn, $idportafoli, $idactiu, 'COMPRA', $cantidad);

    $conn->close();

    echo json_encode(["success" => true, "message" => "✅ Compra realizada correctamente."]);
    exit;
    
}

// --- PROCESAR VENTA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'vender') {
    header('Content-Type: application/json');

    $activo = $_POST['activo'] ?? '';
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $precio = floatval($_POST['precio'] ?? 0);

    if ($activo === 'oro') $activo = 'or';

    if (!$activo || $cantidad <= 0 || $precio <= 0) {
        echo json_encode(["success" => false, "message" => "❌ Datos inválidos"]);
        exit;
    }

    $totalUSD = $cantidad * $precio;

    $conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "❌ Error de conexión"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT idusuari FROM usuaris WHERE nom = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($idusuari);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT idportafoli FROM portafolis WHERE idusuari = ?");
    $stmt->bind_param("i", $idusuari);
    $stmt->execute();
    $stmt->bind_result($idportafoli);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT idactiu FROM actius WHERE nom = ?");
    $stmt->bind_param("s", $activo);
    $stmt->execute();
    $stmt->bind_result($idactiu);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT quantitat FROM portafolis_actius WHERE idportafoli = ? AND idactiu = ?");
    $stmt->bind_param("ii", $idportafoli, $idactiu);
    $stmt->execute();
    $stmt->bind_result($cantidadDisponible);
    $stmt->fetch();
    $stmt->close();

    if ($cantidadDisponible < $cantidad) {
        echo json_encode(["success" => false, "message" => "⚠️ No tienes suficientes activos para vender."]);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare("UPDATE portafolis_actius SET quantitat = quantitat - ? WHERE idportafoli = ? AND idactiu = ?");
    $stmt->bind_param("dii", $cantidad, $idportafoli, $idactiu);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE usuaris SET dolars = dolars + ? WHERE idusuari = ?");
    $stmt->bind_param("di", $totalUSD, $idusuari);
    $stmt->execute();
    $stmt->close();

    // Registrar transacción de venta
    registrarTransaccion($conn, $idportafoli, $idactiu, 'VENTA', $cantidad);

    $conn->close();

    echo json_encode(["success" => true, "message" => "✅ Venta realizada correctamente."]);
    exit;
}

// --- PROCESAR SWAP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'swap') {
    header('Content-Type: application/json');

    $activoOrigen = $_POST['activoOrigen'] ?? '';
    $activoDestino = $_POST['activoDestino'] ?? '';
    $cantidadOrigen = floatval($_POST['cantidadOrigen'] ?? 0);
    $cantidadDestino = floatval($_POST['cantidadDestino'] ?? 0);

    if ($activoOrigen === 'oro') $activoOrigen = 'or';
    if ($activoDestino === 'oro') $activoDestino = 'or';

    if (!$activoOrigen || !$activoDestino || $activoOrigen === $activoDestino ||
        $cantidadOrigen <= 0 || $cantidadDestino <= 0) {
        echo json_encode(["success" => false, "message" => "❌ Datos inválidos para swap."]);
        exit;
    }

    $conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "❌ Error de conexión."]);
        exit;
    }

    $stmt = $conn->prepare("SELECT idusuari FROM usuaris WHERE nom = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($idusuari);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT idportafoli FROM portafolis WHERE idusuari = ?");
    $stmt->bind_param("i", $idusuari);
    $stmt->execute();
    $stmt->bind_result($idportafoli);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT idactiu FROM actius WHERE nom = ?");
    $stmt->bind_param("s", $activoOrigen);
    $stmt->execute();
    $stmt->bind_result($idOrigen);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT idactiu FROM actius WHERE nom = ?");
    $stmt->bind_param("s", $activoDestino);
    $stmt->execute();
    $stmt->bind_result($idDestino);
    $stmt->fetch();
    $stmt->close();

    // Verificar que tiene suficiente cantidad del activo origen
    $stmt = $conn->prepare("SELECT quantitat FROM portafolis_actius WHERE idportafoli = ? AND idactiu = ?");
    $stmt->bind_param("ii", $idportafoli, $idOrigen);
    $stmt->execute();
    $stmt->bind_result($cantidadDisponible);
    $stmt->fetch();
    $stmt->close();

    if ($cantidadDisponible < $cantidadOrigen) {
        echo json_encode(["success" => false, "message" => "⚠️ No tienes suficiente cantidad de $activoOrigen."]);
        $conn->close();
        exit;
    }

    // Restar activo origen
    $stmt = $conn->prepare("UPDATE portafolis_actius SET quantitat = quantitat - ? WHERE idportafoli = ? AND idactiu = ?");
    $stmt->bind_param("dii", $cantidadOrigen, $idportafoli, $idOrigen);
    $stmt->execute();
    $stmt->close();

    // Sumar activo destino (si existe lo actualiza, si no lo inserta)
    $stmt = $conn->prepare("SELECT quantitat FROM portafolis_actius WHERE idportafoli = ? AND idactiu = ?");
    $stmt->bind_param("ii", $idportafoli, $idDestino);
    $stmt->execute();
    $stmt->bind_result($cantidadActualDestino);
    $existe = $stmt->fetch();
    $stmt->close();

    if ($existe) {
        $stmt = $conn->prepare("UPDATE portafolis_actius SET quantitat = quantitat + ? WHERE idportafoli = ? AND idactiu = ?");
        $stmt->bind_param("dii", $cantidadDestino, $idportafoli, $idDestino);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO portafolis_actius (idportafoli, idactiu, quantitat) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $idportafoli, $idDestino, $cantidadDestino);
        $stmt->execute();
        $stmt->close();
    }

    // Registrar transacción de swap (resta origen)
    registrarTransaccion($conn, $idportafoli, $idOrigen, 'SWAP_ORIGEN', $cantidadOrigen);
    // Registrar transacción de swap (suma destino)
    registrarTransaccion($conn, $idportafoli, $idDestino, 'SWAP_DESTINO', $cantidadDestino);

    $conn->close();

    echo json_encode(["success" => true, "message" => "✅ Swap realizado correctamente."]);
    exit;
}

?>



<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SafeHolder</title>
    <link rel="stylesheet" href="../CSS/style.css" />
    <link rel="icon" href="../Images/favicon.png" type="image/x-icon" />
    <link
      href="https://fonts.googleapis.com/css2?family=Tektur:wght@400..900&display=swap"
      rel="stylesheet"
    />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </head>

  <body>
    <header class="headerContainer">
      <div>
        <img
          class="imagenHeader"
          src="../Images/logoSinFondo.png"
          alt="SafeHolder Logo"
        />
      </div>
      <div class="titulo">
        <h1>SafeHolder</h1>
      </div>
      <div class="bienvenida">
        <h2>
          Bienvenido,
          <?php echo htmlspecialchars($usuario); ?>!
        </h2>
        <p>
          Saldo en dólares:
          <?php echo number_format($dollars, 2); ?>
          USD
        </p>
      </div>

      <div class="LoginCartera">
        <div class="valorCartera">
          <a href="./logout.php">
            <img src="../Images/salida.png" alt="VALOR CARTERA" />
          </a>
        </div>

        <div class="cuenta">
          <a href="./configuracion.php">
            <img src="../Images/configuraciones.png" alt="CUENTA" />
          </a>
        </div>
      </div>
    </header>
    <div class="activos-usuario">
      <div id="box-bitcoin" class="activo-box">Cargando...</div>
      <div id="box-or" class="activo-box">Cargando...</div>
      <div id="box-euro" class="activo-box">Cargando...</div>
      <div id="cartera-total" class="cartera-total"> 
          $<?php echo number_format($valorCartera, 2); ?>
      </div>
    </div>
    <div class="grafico">
      <canvas id="performanceChart" width="400" height="200"></canvas>
    </div>

    <div class="compraVenta">
      <div class="compra">
        <button id="btnComprar" class="BtnCompra">Comprar</button>
      </div>
      <div class="cantidad">
        <h3>Cantidad</h3>
        <input type="number" id="cantidad" />
      </div>
      <div class="venta">
        <button id="btnVender" class="BtnVenta">Vender</button>
      </div>
    </div>

    <div class="contenedor2">
      <div class="activos">
        <h1>ACTIVOS</h1>
        <div class="container">
          <div class="bitcoin-container activo-btn" data-activo="bitcoin">
            <img src="../Images/bitcoin.png" alt="Bitcoin logo" width="50" />
            <div class="valor" id="valor-bitcoin">Cargando...</div>
          </div>
          <div class="gold-container activo-btn" data-activo="oro">
            <img src="../Images/oro.png" alt="Oro logo" width="50" />
            <div class="valor" id="valor-oro">Cargando...</div>
          </div>
          <div class="euro-container activo-btn" data-activo="euro">
            <img src="../Images/euro.png" alt="Euro logo" width="50" />
            <div class="valor" id="valor-euro">Cargando...</div>
          </div>
        </div>
      </div>

      <div class="cambio">
        <h1>SWAP</h1>
        <div class="container">
          <form id="swapForm">
            <label for="activoOrigen">Activo a intercambiar:</label>
            <select id="activoOrigen" name="activoOrigen" required>
                <option value="bitcoin">Bitcoin</option>
                <option value="oro">Oro</option>
                <option value="euro">Euro</option>
            </select>

            <label for="activoDestino">Activo a recibir:</label>
            <select id="activoDestino" name="activoDestino" required>
                <option value="bitcoin">Bitcoin</option>
                <option value="oro">Oro</option>
                <option value="euro">Euro</option>
            </select>

            <label for="cantidadSwap">Cantidad:</label>
            <input type="number" id="cantidadSwap" name="cantidadSwap" step="any" min="0.00000001" required />

            <button type="submit" class="boton">Realizar Swap</button>
          </form>
        </div>
      </div>
    </div>

    <script>
      const anterior = { 1: null, 2: null, 3: null, dollars: null };

      async function cargarValores() {
        try {
          const res = await fetch("?ajax=1");
          const datos = await res.json();

          if (datos[1] !== undefined && datos[1] !== anterior[1]) {
            document.getElementById("valor-bitcoin").textContent = `$ ${datos[1]}`;
            anterior[1] = datos[1];
          }
          if (datos[2] !== undefined && datos[2] !== anterior[2]) {
            document.getElementById("valor-oro").textContent = `$ ${datos[2]}`;
            anterior[2] = datos[2];
          }
          if (datos[3] !== undefined && datos[3] !== anterior[3]) {
            document.getElementById("valor-euro").textContent = `$ ${datos[3]}`;
            anterior[3] = datos[3];
          }

          if (datos.dollars !== undefined) {
            anterior.dollars = datos.dollars;
            // Actualiza el saldo en el header
            const saldoHeader = document.querySelector(".bienvenida p");
            if (saldoHeader) {
              saldoHeader.innerHTML = `Saldo en dólares: ${Number(datos.dollars).toFixed(2)} USD`;
            }
          }
        } catch (err) {
          console.error("Error al cargar valores:", err);
        }
      }

      cargarValores();
      setInterval(cargarValores, 2000);
    </script>

    <script>
      let chart;
      window.activoActual = "bitcoin";


      async function cargarGrafico(activo) {
    try {
        const res = await fetch("?grafico=" + activo);
        const datos = await res.json();
        const ctx = document.getElementById("performanceChart").getContext("2d");

        if (chart) {
            chart.data.labels = datos.labels;
            chart.data.datasets[0].data = datos.data;
            chart.data.datasets[0].label = `Histórico de ${activo.charAt(0).toUpperCase() + activo.slice(1)}`;
            chart.update();
        } else {
            chart = new Chart(ctx, {
                type: "line",
                data: {
                    labels: datos.labels,
                    datasets: [{
                        label: `Histórico de ${activo.charAt(0).toUpperCase() + activo.slice(1)}`,
                        data: datos.data,
                        backgroundColor: "rgba(75, 192, 192, 0.2)",
                        borderColor: "rgba(75, 192, 192, 1)",
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: "Gráfico Histórico",
                            color: "white",
                        },
                        legend: {
                            labels: {
                                color: "white",
                            },
                        },
                    },
                    scales: {
                        x: {
                            ticks: { color: "white" },
                            grid: { color: "rgba(255,255,255,0.2)" },
                        },
                        y: {
                            ticks: { color: "white" },
                            grid: { color: "rgba(255,255,255,0.2)" },
                        },
                    },
                },
            });
        }

        window.activoActual = activo; // ← 🔥 Este es el cambio clave
    } catch (err) {
        console.error("Error al cargar gráfico:", err);
    }
}


      // Inicia con Bitcoin
      cargarGrafico("bitcoin");

      document.querySelectorAll(".activo-btn").forEach((btn) => {
        btn.style.cursor = "pointer";
        btn.addEventListener("click", () => {
          const tipo = btn.dataset.activo;
          if (tipo) {
            cargarGrafico(tipo);
          }
        });
      });
    </script>
    <script>
        document.getElementById("swapForm").addEventListener("submit", async (e) => {
          e.preventDefault();

          const activoOrigen = document.getElementById("activoOrigen").value;
          const activoDestino = document.getElementById("activoDestino").value;
          const cantidadOrigen = parseFloat(document.getElementById("cantidadSwap").value);

          if (activoOrigen === activoDestino) {
            alert("⚠️ El activo de origen y destino no pueden ser iguales.");
            return;
          }

          if (isNaN(cantidadOrigen) || cantidadOrigen <= 0) {
            alert("⚠️ Ingresa una cantidad válida para intercambiar.");
            return;
          }

          try {
            // Obtener precios en tiempo real
            const res = await fetch("?ajax=1");
            const precios = await res.json();

            const nombres = { bitcoin: 1, oro: 2, euro: 3 };
            const mapNombreBD = { oro: "or", bitcoin: "bitcoin", euro: "euro" };

            const precioOrigen = precios[nombres[activoOrigen]];
            const precioDestino = precios[nombres[activoDestino]];

            if (!precioOrigen || !precioDestino) {
              alert("❌ Error al obtener precios actuales.");
              return;
            }

            const valorUSD = cantidadOrigen * precioOrigen;
            const cantidadDestino = valorUSD / precioDestino;

            const formData = new FormData();
            formData.append("accion", "swap");
            formData.append("activoOrigen", mapNombreBD[activoOrigen]);
            formData.append("activoDestino", mapNombreBD[activoDestino]);
            formData.append("cantidadOrigen", cantidadOrigen);
            formData.append("cantidadDestino", cantidadDestino);

            const resSwap = await fetch("home.php", {
              method: "POST",
              body: formData
            });

            const data = await resSwap.json();
            if (data.success) {
              alert("✅ Swap realizado con éxito.");
              location.reload();
            } else {
              alert("⛔ " + data.message);
            }

          } catch (err) {
            console.error("❌ Error en el swap:", err);
            alert("❌ Hubo un error realizando el swap.");
          }
        });
  </script>

    
    <script>
        const tiempoInactividad = <?= (int)$inactividad ?> * 1000; // en milisegundos
        let temporizadorInactividad;

        function reiniciarTemporizador() {
            clearTimeout(temporizadorInactividad);
            temporizadorInactividad = setTimeout(() => {
                alert("Has sido desconectado por inactividad.");
                window.location.href = "./logout.php";
            }, tiempoInactividad);
        }

        // Detectar actividad del usuario
        ['mousemove', 'keydown', 'click', 'touchstart'].forEach(evento => {
            document.addEventListener(evento, reiniciarTemporizador);
        });

        // Iniciar temporizador al cargar
        window.onload = reiniciarTemporizador;
    </script>
    <script>
window.onload = function () {
  document.getElementById("btnComprar").addEventListener("click", async () => {
    const cantidadInput = document.getElementById("cantidad");
    const cantidad = parseFloat(cantidadInput.value);
    const activo = window.activoActual;
    const valorDiv = document.getElementById(`valor-${activo}`);
    const precio = parseFloat(valorDiv?.textContent.replace("$", "").trim());

    console.log("🧪 EVENTO ACTIVADO:", {
      activo,
      cantidadInput: cantidadInput.value,
      cantidad,
      precio
    });

    if (!cantidad || cantidad <= 0) {
      alert("⚠️ Ingresa una cantidad válida.");
      return;
    }

    if (!precio || isNaN(precio)) {
      alert("❌ Precio inválido.");
      return;
    }

    const total = cantidad * precio;
    if (anterior.dollars < total) {
      alert(`⚠️ Saldo insuficiente. Necesitas $${total.toFixed(2)} USD`);
      return;
    }

    const formData = new FormData();
    formData.append("accion", "comprar");
    formData.append("activo", activo);
    formData.append("cantidad", cantidad);
    formData.append("precio", precio);

    try {
      const res = await fetch("home.php", {
        method: "POST",
        body: formData,
      });

      const text = await res.text();
      console.log("🧪 RAW RESPONSE:", text);

      const data = JSON.parse(text);
      if (data.success) {
        alert("✅ Compra realizada correctamente.");
        cargarActivosUsuario();
        cargarValores(); // Esto refresca el saldo automáticamente
        // location.reload(); // quita o comenta esta línea
      } else {
        alert("⛔ " + data.message);
      }
    } catch (err) {
      console.error("❌ Error JS", err);
      alert("❌ Error inesperado al realizar la compra.");
    }
  });
};
</script>

<script>
document.getElementById("btnVender").addEventListener("click", async () => {
  const cantidadInput = document.getElementById("cantidad");
  const cantidad = parseFloat(cantidadInput.value);
  const activo = window.activoActual;
  const valorDiv = document.getElementById(`valor-${activo}`);
  const precio = parseFloat(valorDiv?.textContent.replace("$", "").trim());

  if (!cantidad || cantidad <= 0) {
    alert("⚠️ Ingresa una cantidad válida.");
    return;
  }

  if (!precio || isNaN(precio)) {
    alert("❌ Precio inválido.");
    return;
  }

  const formData = new FormData();
  formData.append("accion", "vender");
  formData.append("activo", activo);
  formData.append("cantidad", cantidad);
  formData.append("precio", precio);

  try {
    const res = await fetch("home.php", {
      method: "POST",
      body: formData,
    });

    const text = await res.text();
    console.log("🧪 RAW RESPONSE (venta):", text);

    const data = JSON.parse(text);
    if (data.success) {
      alert("✅ Venta realizada correctamente.");
      cargarActivosUsuario();
      cargarValores(); // Esto refresca el saldo automáticamente
      // location.reload(); // quita o comenta esta línea
    } else {
      alert("⛔ " + data.message);
    }
  } catch (err) {
    console.error("❌ Error en la venta:", err);
    alert("❌ Error inesperado al realizar la venta.");
  }
});
</script>
<script>
async function cargarActivosUsuario() {
  try {
    const res = await fetch("home.php?portafolis_actius=1");
    const data = await res.json();

    const iconos = {
      bitcoin: { 
        src: "../Images/bitcoin.png", 
        sombra: "0 0 10px 2px orange", 
        alt: "Bitcoin",
        decimales: 9
      },
      or: { 
        src: "../Images/oro.png", 
        sombra: "0 0 10px 2px gold", 
        alt: "Oro",
        decimales: 4
      },
      euro: { 
        src: "../Images/euro.png", 
        sombra: "0 0 10px 2px #7d3cff", 
        alt: "Euro",
        decimales: 2
      }
    };

    data.forEach(activo => {
      const id = `box-${activo.activo}`;
      const el = document.getElementById(id);
      if (el) {
        const icono = iconos[activo.activo];
        const cantidadFormateada = Number(activo.cantidad).toFixed(icono.decimales);
        el.innerHTML = `
          <img class="icono-usuario" src="${icono.src}" alt="${icono.alt}" style="box-shadow:${icono.sombra};" />
          <span class="cantidad-usuario">${cantidadFormateada}</span>
          <span class="usd-usuario">≈ $${Number(activo.valor_usd).toFixed(2)}</span>
        `;
      }
    });
  } catch (err) {
    console.error("Error al cargar activos usuario:", err);
  }
}

cargarActivosUsuario();
setInterval(cargarActivosUsuario, 5000); // actualiza cada 5s
</script>

<?php
error_log("ID USUARIO: $usuario | ID PORTAFOLI: $idportafoli");
?>

  </body>
</html>