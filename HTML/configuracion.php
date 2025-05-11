<?php
// Iniciar sesión
session_start();

// Conexión a la base de datos
$servidor = "192.168.1.100";  // Dirección IP del servidor de base de datos
$usuari = "safeuser";         // Usuario de la base de datos
$contrasenya_bd = "adie";     // Contraseña de la base de datos
$nom_base_dades = "SafeHolder"; 

$mysqli = new mysqli($servidor, $usuari, $contrasenya_bd, $nom_base_dades);

if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario'])) {
    die("No hay usuario conectado");
}

// Obtener el nombre del usuario desde la sesión
$nom_usuari = $_SESSION['usuario'];

// Si se recibe una petición POST, actualizar los datos del usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nom = $_POST['nom'];
    $cognoms = $_POST['cognoms'];
    $dni = $_POST['dni'];
    $telefon = $_POST['telefon'];
    $correu = $_POST['correu'];
    $contrasenya = $_POST['contrasenya'];

    // Actualizar los datos en la base de datos
    $consulta = "UPDATE usuaris SET nom = ?, cognoms = ?, dni = ?, telefon = ?, gmail = ?, contrasenya = ? WHERE nom = ?";
    $stmt = $mysqli->prepare($consulta);
    $stmt->bind_param("sssssss", $nom, $cognoms, $dni, $telefon, $correu, $contrasenya, $nom_usuari);

    if ($stmt->execute()) {

    } else {

    }

    $stmt->close();
}

// Obtener los datos del usuario para mostrar en el formulario
$consulta = "SELECT nom, cognoms, dni, telefon, gmail, contrasenya FROM usuaris WHERE nom = ?";
$stmt = $mysqli->prepare($consulta);
$stmt->bind_param("s", $nom_usuari);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($nom, $cognoms, $dni, $telefon, $correu, $contrasenya);

if ($stmt->fetch()) {
    // Los datos se obtienen correctamente y se asignan a las variables
} else {

}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SafeHolder</title>
    <link rel="stylesheet" href="../CSS/styleConfiguracion.css" />
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

      <div class="LoginCartera">
        <div class="valorCartera">
          <a href="./logout.php">
            <img src="../Images/salida.png" alt="VALOR CARTERA" />
          </a>
        </div>
        <div class="cuenta">
          <a href="../HTML/home.php">
            <img src="../Images/compra-una-casa.png" alt="CUENTA" />
          </a>
        </div>
      </div>
    </header>

    <div class="contenedor-principal">
      <div class="contenedor-editar">
        <h2>Editar Usuari</h2>
        <form action="configuracion.php" method="POST">
            <label for="nom">Nom:</label>
            <input
                type="text"
                id="nom"
                name="nom"
                value="<?php echo htmlspecialchars($nom); ?>"
                placeholder="El teu nom"
                required
            />

            <label for="cognoms">Cognoms:</label>
            <input
                type="text"
                id="cognoms"
                name="cognoms"
                value="<?php echo htmlspecialchars($cognoms); ?>"
                placeholder="Els teus cognoms"
                required
            />

            <label for="dni">DNI:</label>
            <input
                type="text"
                id="dni"
                name="dni"
                value="<?php echo htmlspecialchars($dni); ?>"
                placeholder="El teu DNI"
                required
            />

            <label for="telefon">Telèfon:</label>
            <input
                type="text"
                id="telefon"
                name="telefon"
                value="<?php echo htmlspecialchars($telefon); ?>"
                placeholder="El teu telèfon"
                required
            />

            <label for="correu">Correu:</label>
            <input
                type="email"
                id="correu"
                name="correu"
                value="<?php echo htmlspecialchars($correu); ?>"
                placeholder="El teu correu"
                required
            />

            <label for="contrasenya">Contrasenya:</label>
            <input
                type="text"
                id="contrasenya"
                name="contrasenya"
                value="<?php echo htmlspecialchars($contrasenya); ?>"
                placeholder="Nova contrasenya"
                required
            />

            <button type="submit">Desar canvis</button>
        </form>
      </div>

      <div class="contenedor-transacciones">
        <h2>Historial de Transaccions</h2>
        <ul>
          <li>Compra 1: Bitcoin - $500</li>
          <li>Compra 2: Oro - $300</li>
          <li>Compra 3: Euro - $200</li>
        </ul>
      </div>
    </div>
  </body>
</html>
