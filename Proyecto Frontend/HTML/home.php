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

    // Obtener los valores de los activos (Bitcoin, Oro, Euro)
    $sql = "SELECT idactiu, valor FROM actius WHERE idactiu IN (1, 2, 3)";
    $result = $conn->query($sql);
    $valores = [];

    while ($row = $result->fetch_assoc()) {
        $valores[$row["idactiu"]] = $row["valor"];
    }

    // Obtener el saldo de dólares del usuario
    $sql = "SELECT dolars FROM usuaris WHERE nom = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($dollars);
    $stmt->fetch();
    $stmt->close();

    // Cerrar conexión a la base de datos
    $conn->close();

    // Añadir los valores de activos y el saldo de dólares al array de respuesta
    $valores['dollars'] = $dollars;

    // Devolver los datos en formato JSON
    echo json_encode($valores);
    exit;
}

// Si no es una petición AJAX, se continúa con la carga de la página normal

// Obtener el saldo de dólares para mostrar en el header
$conn = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT dolars FROM usuaris WHERE nom = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->bind_result($dollars);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeHolder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tektur:wght@400..900&display=swap" rel="stylesheet">
    <link rel="icon" href="../Images/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../CSS/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <header class="headerContainer">
        <div>
            <img class="imagenHeader" src="../Images/logoSinFondo.png" alt="SafeHolder Logo">
        </div>
        
        <div class="titulo">
            <h1>SafeHolder</h1>
        </div>

        <!-- Mostrar el mensaje de bienvenida y el saldo en dólares -->
        <div class="bienvenida">
            <h2>Bienvenido, <?php echo htmlspecialchars($usuario); ?>!</h2>
            <p>Saldo en dólares: <?php echo number_format($dollars, 2); ?> USD</p> <!-- Mostrar el saldo -->
        </div>

        <div class="LoginCartera">
            <div class="valorCartera">   
                <img src="../Images/valorCartera.png" alt="VALOR CARTERA">
            </div>
            <div class="cuenta">
                <a href="../index.php">
                    <img src="../Images/cuenta.png" alt="CUENTA">
                </a>
            </div> 
        </div>
    </header>
    <div class="grafico"> 
        <canvas id="performanceChart" width="400" height="200"></canvas>
        <script>
            const ctx = document.getElementById('performanceChart').getContext('2d');

            const performanceChart = new Chart(ctx, {
                type: 'line', // tipo de gráfico
                data: {
                    labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48'],                    
                    datasets: [{
                        label: 'Performance',
                        data: [65, 59, 80, 81, 56, 75, 99, 88, 76, 1, 34, 67, 92, 43, 78, 60, 85, 47, 90, 72, 39, 54, 66, 29, 70, 82, 93, 24, 50, 61, 19, 44, 38, 73, 87, 31, 69, 95, 62, 18, 22, 40, 13, 84, 100, 36, 15, 58],
                        backgroundColor: 'rgba(75, 192, 192, 0.2)', // color de fondo
                        borderColor: 'rgba(75, 192, 192, 1)', // color de línea
                        borderWidth: 2,
                        fill: true, // rellena el área debajo de la línea
                        tension: 0.3, // suaviza la línea
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Gráfico de Performance'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true // empieza desde 0
                        }
                    }
                }
            });
        </script>
    </div>

    <div class="compraVenta">
        <div class="compra">
            <button class="BtnCompra">Comprar</button>
        </div>
        <div class="cantidad">
            <h3>Cantidad</h3>
            <input type="number">
        </div>
        <div class="venta">
            <button class="BtnVenta">Vender</button>
        </div>
    </div>

    <div class="contenedor2">
        <div class="activos">
            <h1>ACTIVOS </h1>
            <div class="container">
                <div class="bitcoin-container">
                    <img src="../Images/bitcoin.png" alt="Bitcoin logo" width="50">
                    <div class="valor" id="valor-bitcoin">Cargando...</div>
                </div>
                <div class="gold-container">
                    <img src="../Images/oro.png" alt="Oro logo" width="50">
                    <div class="valor" id="valor-oro">Cargando...</div>
                </div>
                <div class="euro-container">
                    <img src="../Images/euro.png" alt="Euro logo" width="50">
                    <div class="valor" id="valor-euro">Cargando...</div>
                </div>
            </div>
        </div>

        <div class="cambio">
            <h1>SWAP</h1>
            <div class="container">
                <div class="Activos"></div>
                <div class="Activos"></div>
                <div class="Activos"></div>
                <div class="botonC">
                    <button class="boton">Swap</button>
                </div>
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
                    document.getElementById("valor-bitcoin").textContent = datos[1];
                    anterior[1] = datos[1];
                }
                if (datos[2] !== undefined && datos[2] !== anterior[2]) {
                    document.getElementById("valor-oro").textContent = datos[2];
                    anterior[2] = datos[2];
                }
                if (datos[3] !== undefined && datos[3] !== anterior[3]) {
                    document.getElementById("valor-euro").textContent = datos[3];
                    anterior[3] = datos[3];
                }

                // Mostrar el saldo en dólares
                if (datos.dollars !== undefined && datos.dollars !== anterior.dollars) {
                    console.log("Saldo en dólares:", datos.dollars); // Para depurar el valor de dólares
                    anterior.dollars = datos.dollars;
                }
            } catch (err) {
                console.error("Error al cargar valores:", err);
            }
        }

        cargarValores();
        setInterval(cargarValores, 2000);
    </script>

</body>
</html>
