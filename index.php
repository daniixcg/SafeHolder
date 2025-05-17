<?php
// Configuración de la base de datos
$servername = "192.168.1.100";
$username = "safeuser";
$password = "adie";
$dbname = "SafeHolder";

// Conectar a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

session_start();
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $contrasenya = trim($_POST['password'] ?? '');

    // Validaciones en el servidor
    if (empty($email) || empty($contrasenya)) {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
    } elseif (strpos($email, '@') === false) {
        $mensaje = "⚠️ El correo debe contener el símbolo @.";
    } else {
        // Incluimos rol y operatiu en la consulta
        $stmt = $conn->prepare("SELECT nom, rol FROM usuaris WHERE gmail = ? AND contrasenya = ? AND operatiu = 1");
        $stmt->bind_param("ss", $email, $contrasenya);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $fila = $resultado->fetch_assoc();
            $_SESSION['usuario'] = $fila['nom']; // Guardamos el nombre en la sesión

            // Redirección según el rol
            if ($fila['rol'] == 1) {
                header("Location: ./HTML/home.php");
            } elseif ($fila['rol'] == 0) {
                header("Location: ./HTML/admin.php");
            } else {
                $mensaje = "❌ Rol desconocido.";
            }
            exit();
        } else {
            $mensaje = "❌ Correo, contraseña incorrectos o cuenta desactivada.";
        }

        $stmt->close();
    }
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeHolder Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tektur:wght@400..900&display=swap" rel="stylesheet">
    <link rel="icon" href="./Images/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="./CSS/styleLogin.css">
</head>
<body>
    <div class="flotante">
        <div class="logo">
            <img src="./Images/logoSinFondo.png" alt="LOGO">
            <h1>SafeHolder</h1>
        </div>
        <div class="formulario">
            <?php if ($mensaje): ?>
                <p style="color: white; font-weight: bold;"><?php echo $mensaje; ?></p>
            <?php endif; ?>
            <form id="loginForm" action="index.php" method="POST">
                <div class="input">
                    <label for="email">Email</label> <br>
                    <input type="email" name="email" id="email" placeholder="Introduce tu correo electrónico" required>
                </div>
                <div class="input">
                    <label for="password">Contraseña</label> <br>
                    <input type="password" name="password" id="password" placeholder="Introduce tu contraseña" required> 
                </div>
                <div class="botonC">
                    <input id="button" class="boton" type="submit" value="Iniciar Sesión">
                </div>
            </form>
            <p id="errorJS" style="color: yellow; font-weight: bold;"></p>
        </div>
    </div>
    <script>
        document.getElementById("loginForm").addEventListener("submit", function (e) {
            const email = document.getElementById("email").value;
            const errorJS = document.getElementById("errorJS");
            errorJS.textContent = "";

            if (!email.includes("@")) {
                e.preventDefault();
                errorJS.textContent = "⚠️ El correo debe contener el símbolo @.";
            }
        });
    </script>
</body>
</html>
