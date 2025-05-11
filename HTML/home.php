<?php
session_start(); // Iniciar la sesión

// Verificar si el usuario está logeado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php"); // Redirigir al login si no está logeado
    exit;
}

$usuario = $_SESSION['usuario']; // Obtener el nombre de usuario de la sesión

// --- SI ES UNA PETICIÓN AJAX DEVOLVEMOS LOS DATOS DE LOS ACTIVOS ---
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

// --- PETICIÓN PARA DATOS DEL GRÁFICO POR ACTIVO ---
if (isset($_GET['grafico'])) {
    $tipo = $_GET['grafico'];
    $tabla = "";

    switch ($tipo) {
        case "bitcoin":
            $tabla = "bitcoinHistoric";
            break;
        case "euro":
            $tabla = "euroHistoric";
            break;
        case "oro":
            $tabla = "orHistoric";
            break;
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

// Si no es una petición AJAX, se continúa con la carga de la página normal
$conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT dolars, inactivitat FROM usuaris WHERE nom = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->bind_result($dollars, $inactividad);
$stmt->fetch();
$stmt->close();
$conn->close();

// PHP para el SWAP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    if ($input['accion'] === 'swap') {
        $activoOrigen = $input['activoOrigen'];
        $activoDestino = $input['activoDestino'];
        $cantidad = (int)$input['cantidad'];

        if ($cantidad <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Cantidad inválida."]);
            exit;
        }

        $conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos."]);
            exit;
        }

        // Verificar si el usuario tiene suficiente del activo de origen
        $sql = "SELECT valor FROM actius WHERE idactiu = (SELECT id FROM actius WHERE nom = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $activoOrigen);
        $stmt->execute();
        $stmt->bind_result($valorOrigen);
        $stmt->fetch();
        $stmt->close();

        if ($valorOrigen < $cantidad) {
            echo json_encode(["success" => false, "message" => "Saldo insuficiente en el activo de origen."]);
            exit;
        }

        // Realizar el intercambio
        $sql = "UPDATE actius SET valor = valor - ? WHERE idactiu = (SELECT id FROM actius WHERE nom = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $cantidad, $activoOrigen);
        $stmt->execute();

        $sql = "UPDATE actius SET valor = valor + ? WHERE idactiu = (SELECT id FROM actius WHERE nom = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $cantidad, $activoDestino);
        $stmt->execute();

        $stmt->close();
        $conn->close();

        echo json_encode(["success" => true]);
        exit;
    }
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
          <a href="../index.php">
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

    <div class="grafico">
      <canvas id="performanceChart" width="400" height="200"></canvas>
    </div>

    <div class="compraVenta">
      <div class="compra">
        <button id="btnComprar" class="BtnCompra">Comprar</button>
      </div>
      <div class="cantidad">
        <h3>Cantidad</h3>
        <input type="number" id="cantidad" min="1" />
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
            <input type="number" id="cantidadSwap" name="cantidadSwap" min="1" required />

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
            document.getElementById(
              "valor-bitcoin"
            ).textContent = `$ ${datos[1]}`;
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

          if (
            datos.dollars !== undefined &&
            datos.dollars !== anterior.dollars
          ) {
            anterior.dollars = datos.dollars;
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
      let activoActual = "bitcoin";

      async function cargarGrafico(activo) {
        try {
            const res = await fetch("?grafico=" + activo);
            const datos = await res.json();
            const ctx = document
                .getElementById("performanceChart")
                .getContext("2d");

            if (chart) {
                chart.data.labels = datos.labels;
                chart.data.datasets[0].data = datos.data;
                chart.data.datasets[0].label = `Histórico de ${
                  activo.charAt(0).toUpperCase() + activo.slice(1)
                }`;
                chart.update();
            } else {
                chart = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: datos.labels,
                        datasets: [
                            {
                                label: `Histórico de ${
                                  activo.charAt(0).toUpperCase() + activo.slice(1)
                                }`,
                                data: datos.data,
                                backgroundColor: "rgba(75, 192, 192, 0.2)",
                                borderColor: "rgba(75, 192, 192, 1)",
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: "Gráfico Histórico",
                                color: "white", // Título en blanco
                            },
                            legend: {
                                labels: {
                                    color: "white", // Leyenda en blanco
                                },
                            },
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: "white", // Etiquetas del eje X en blanco
                                },
                                grid: {
                                    color: "rgba(255, 255, 255, 0.2)", // Líneas de la cuadrícula en blanco semitransparente
                                },
                            },
                            y: {
                                ticks: {
                                    color: "white", // Etiquetas del eje Y en blanco
                                },
                                grid: {
                                    color: "rgba(255, 255, 255, 0.2)", // Líneas de la cuadrícula en blanco semitransparente
                                },
                            },
                        },
                    },
                });
            }

            activoActual = activo;
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
   
    <script>  // Funcion para el SWAP
      document.getElementById("swapForm").addEventListener("submit", async (e) => { e.preventDefault();

      const activoOrigen = document.getElementById("activoOrigen").value;
      const activoDestino = document.getElementById("activoDestino").value;
      const cantidad = document.getElementById("cantidadSwap").value;

      if (activoOrigen === activoDestino) {
          alert("El activo de origen y destino no pueden ser iguales.");
          return;
      }

      try {
          const res = await fetch("home.php", {
              method: "POST",
              headers: {
                  "Content-Type": "application/json",
              },
              body: JSON.stringify({
                  accion: "swap",
                  activoOrigen: activoOrigen,
                  activoDestino: activoDestino,
                  cantidad: cantidad,
              }),
          });

          const data = await res.json();
          if (data.success) {
              alert("Swap realizado con éxito.");
              location.reload(); // Recarga la página para actualizar los datos
          } else {
              alert("Error al realizar el swap: " + data.message);
          }
      } catch (err) {
          console.error("Error en el swap:", err);
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
  </body>
</html>
