<?php
// Conexión a la base de datos
$servername = "192.168.1.100";  // Dirección IP del servidor de base de datos
$username = "safeuser";         // Usuario de la base de datos
$password = "adie";             // Contraseña de la base de datos
$dbname = "SafeHolder";         // Nombre de la base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verificar si el formulario de agregar usuario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar'])) {
    $nom = $_POST['nom'];
    $cognoms = $_POST['cognoms'];
    $dni = $_POST['dni'];
    $telefon = $_POST['telefon'];
    $gmail = $_POST['gmail'];
    $contrasenya = $_POST['contrasenya'];

    // Validar los datos
    if (empty($nom) || empty($cognoms) || empty($dni) || empty($telefon) || empty($gmail) || empty($contrasenya)) {
        echo "Per favor, completa tots els camps.";
    } else {
        // Transacción para seguridad
        $conn->begin_transaction();

        try {
            // Insertar usuario
            $stmt = $conn->prepare("INSERT INTO usuaris (nom, cognoms, dni, telefon, gmail, contrasenya, dolars, rol, inactivitat, operatiu, dataCreacio) 
                                    VALUES (?, ?, ?, ?, ?, ?, 500, 1, 60, 1, NOW())");
            $stmt->bind_param("ssssss", $nom, $cognoms, $dni, $telefon, $gmail, $contrasenya);
            $stmt->execute();
            $idusuari = $conn->insert_id;

            // Insertar portafoli
            $stmt = $conn->prepare("INSERT INTO portafolis (idusuari) VALUES (?)");
            $stmt->bind_param("i", $idusuari);
            $stmt->execute();
            $idportafoli = $conn->insert_id;

            // Insertar portafolis_actius (3 activos)
            $stmt = $conn->prepare("INSERT INTO portafolis_actius (idportafoli, idactiu, quantitat) VALUES (?, ?, 0)");
            for ($idactiu = 1; $idactiu <= 3; $idactiu++) {
                $stmt->bind_param("ii", $idportafoli, $idactiu);
                $stmt->execute();
            }

            // Confirmar transacción
            $conn->commit();
            

        } catch (Exception $e) {
            // Si hay error, hacer rollback
            $conn->rollback();
            echo "Error: " . $e->getMessage();
        }
    }
}


// Verificar si el botón de eliminar ha sido presionado
if (isset($_POST['eliminar'])) {
    $idusuari = $_POST['idusuari'];

    // Obtener el idportafoli del usuario
    $sqlPortafoli = "SELECT idportafoli FROM portafolis WHERE idusuari = '$idusuari'";
    $resultPortafoli = $conn->query($sqlPortafoli);

    if ($resultPortafoli && $resultPortafoli->num_rows > 0) {
        $row = $resultPortafoli->fetch_assoc();
        $idportafoli = $row['idportafoli'];

        // 1. Eliminar transacciones asociadas a ese portafolio
        $sqlDeleteTrans = "DELETE FROM transaccions WHERE idportafoli = '$idportafoli'";
        $conn->query($sqlDeleteTrans);

        // 2. Eliminar portafolis_actius
        $sqlDeleteActius = "DELETE FROM portafolis_actius WHERE idportafoli = '$idportafoli'";
        $conn->query($sqlDeleteActius);

        // 3. Eliminar el portafoli
        $sqlDeletePortafoli = "DELETE FROM portafolis WHERE idportafoli = '$idportafoli'";
        $conn->query($sqlDeletePortafoli);
    }

    // 4. Finalmente eliminar el usuario
    $sqlDeleteUsuari = "DELETE FROM usuaris WHERE idusuari = '$idusuari'";
    $conn->query($sqlDeleteUsuari);
}

// Verificar si se ha enviado el formulario de editar usuario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar'])) {
    $idusuari = $_POST['idusuari'];
    $nom = $_POST['nom'];
    $cognoms = $_POST['cognoms'];
    $dni = $_POST['dni'];
    $telefon = $_POST['telefon'];
    $gmail = $_POST['gmail'];
    $contrasenya = $_POST['contrasenya'];

    // Validar los datos antes de hacer el UPDATE
    if (empty($nom) || empty($cognoms) || empty($dni) || empty($telefon) || empty($gmail) || empty($contrasenya)) {
        echo "Per favor, completa tots els camps.";
    } else {
        // Consulta UPDATE para actualizar el usuario
        $sql = "UPDATE usuaris SET 
                nom='$nom', 
                cognoms='$cognoms', 
                dni='$dni', 
                telefon='$telefon', 
                gmail='$gmail', 
                contrasenya='$contrasenya'
                WHERE idusuari='$idusuari'";

        if ($conn->query($sql) === TRUE) {

        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SafeHolder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Tektur:wght@400..900&display=swap" rel="stylesheet" />
    <link rel="icon" href="../Images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../CSS/styleAdmin.css" />
</head>
<body>
    <header class="headerContainer">
        <div><img class="imagenHeader" src="../Images/logoSinFondo.png" alt="SafeHolder Logo" /></div>
        <div class="titulo"><h1>SafeHolder</h1></div>
        <div class="LoginCartera">
            <div class="valorCartera">
                <a href="../HTML/auditories.php">
                    <img src="../Images/auditoria.png" alt="VALOR CARTERA" />
                </a>
            </div>
            <div class="cuenta">
                <a href="../index.php">
                    <img src="../Images/salida.png" alt="VALOR CARTERA" />
                </a>
            </div>
        </div>
    </header>

    <div class="conjunto">
        <div class="usuarios">
            <h1>USUARIS</h1>
            <table class="usuarios-tabla">
                <tr>
                    <th>Nom</th>
                    <th>Cognoms</th>
                    <th>DNI</th>
                    <th>Telèfon</th>
                    <th>Correu</th>
                    <th>Contrasenya</th>
                    <th>Acció</th>
                </tr>
                <?php
                // Consulta para obtener los usuarios
                $sql = "SELECT idusuari, nom, cognoms, dni, telefon, gmail , contrasenya FROM usuaris WHERE rol = 1";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . $row['nom'] . "</td>
                                <td>" . $row['cognoms'] . "</td>
                                <td>" . $row['dni'] . "</td>
                                <td>" . $row['telefon'] . "</td>
                                <td>" . $row['gmail'] . "</td>
                                <td>" . $row['contrasenya'] . "</td>
                                <td>
                                    <form method='POST' style='display:inline;'>
                                        <input type='hidden' name='idusuari' value='" . $row['idusuari'] . "' />
                                    </form>
                                    <button class='btn-accion editar-btn' onclick='editarUsuario(" . $row['idusuari'] . ", `" . $row['nom'] . "`, `" . $row['cognoms'] . "`, `" . $row['dni'] . "`, `" . $row['telefon'] . "`, `" . $row['gmail'] . "`, `" . $row['contrasenya'] . "`)'>Editar</button>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No es van trobar usuaris</td></tr>";
                }
                ?>
            </table>

            <!-- Sidebar para editar y eliminar -->
            <div class="sidebar" id="sidebar" style="display: none;">
                <h2>Editar Usuari</h2>
                <form id="editarUsuarioForm" method="POST">
                    <input type="hidden" id="idusuari" name="idusuari" />
                    <label for="nom">Nom:</label>
                    <input type="text" id="nom" name="nom" placeholder="Nom de l'usuari" />

                    <label for="cognoms">Cognoms:</label>
                    <input type="text" id="cognoms" name="cognoms" placeholder="Cognoms de l'usuari" />

                    <label for="dni">DNI:</label>
                    <input type="text" id="dni" name="dni" placeholder="DNI de l'usuari" />

                    <label for="telefon">Telèfon:</label>
                    <input type="text" id="telefon" name="telefon" placeholder="Telèfon de l'usuari" />

                    <label for="gmail">Correu:</label>
                    <input type="email" id="gmail" name="gmail" placeholder="Correu de l'usuari" />

                    <label for="contrasenya">Contrasenya:</label>
                    <input type="text" id="contrasenya" name="contrasenya" placeholder="Contrasenya" />

                    <button type="submit" name="guardar">Guardar Canvis</button>
                    <button type="submit" name="eliminar" id="eliminarUsuario">Eliminar</button>
                    <button type="button" id="cerrarSidebar">Tancar</button>
                </form>
            </div>
        </div>

            <!-- BOTON AGREGAR USUARIO -->
            <button class="agregarUsuario" onclick="document.getElementById('sidebar2').style.display='block'">
                <span>Afegeix Nou Usuari</span>
            </button>


        <!-- Sidebar para agregar usuario -->
        <div class="sidebar2" id="sidebar2" style="display: none;">
            <h2>Afegeix Usuari</h2>
            <form method="POST" id="formAgregarUsuario">
                <label for="nom">Nom:</label>
                <input type="text" id="nom" name="nom" placeholder="Nom de l'usuari" required />

                <label for="cognoms">Cognoms:</label>
                <input type="text" id="cognoms" name="cognoms" placeholder="Cognoms de l'usuari" required />

                <label for="dni">DNI:</label>
                <input type="text" id="dni" name="dni" placeholder="DNI de l'usuari" required />

                <label for="telefon">Telèfon:</label>
                <input type="text" id="telefon" name="telefon" placeholder="Telèfon de l'usuari" required />

                <label for="gmail">Correu:</label>
                <input type="email" id="gmail" name="gmail" placeholder="Correu de l'usuari" required />

                <label for="contrasenya">Contrasenya:</label>
                <input type="text" id="contrasenya" name="contrasenya" placeholder="Contrasenya" required />

                <button type="submit" class="btn-accion" name="agregar">Afegeix Usuari</button>
                <button type="button" id="cerrarModal" class="btn-accion" onclick="document.getElementById('sidebar2').style.display='none'">Tancar</button>
            </form>
        </div>
    </div>

    <script>
        function editarUsuario(id, nom, cognoms, dni, telefon, gmail , contrasenya) {
            // Mostrar el sidebar con la información del usuario a editar
            document.getElementById('sidebar').style.display = 'block';
            document.getElementById('idusuari').value = id;
            document.getElementById('nom').value = nom;
            document.getElementById('cognoms').value = cognoms;
            document.getElementById('dni').value = dni;
            document.getElementById('telefon').value = telefon;
            document.getElementById('gmail').value = gmail;
            document.getElementById('contrasenya').value = contrasenya;
        }

        document.getElementById('cerrarSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').style.display = 'none';
        });
    </script>

</body>
</html>

<?php
// Cerrar conexión
$conn->close();
?>
