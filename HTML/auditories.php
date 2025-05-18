<?php
$mysqli = new mysqli("192.168.1.100", "safeuser", "adie", "SafeHolder");
if ($mysqli->connect_error) {
    die("Error de connexió: " . $mysqli->connect_error);
}

// AJAX per recarregar només les taules
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_start();
    // Tabla Auditorias (sin idusuari)
    echo '<div class="tabla-scroll tabla-auditoria">
        <table>
            <caption>Auditories</caption>
            <thead>
                <tr>
                    <th>idauditoria</th>
                    <th>taula</th>
                    <th>idregistre</th>
                    <th>accio</th>
                    <th>campModificat</th>
                    <th>valorAntic</th>
                    <th>valorNou</th>
                    <th>dataauditoria</th>
                </tr>
            </thead>
        </table>
        <div class="scroll-tbody">
            <table>
                <tbody>';
    $sql = "SELECT idauditoria, taula, idregistre, accio, campModificat, valorAntic, valorNou, dataauditoria FROM auditories ORDER BY dataauditoria DESC";
    $result = $mysqli->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['idauditoria']) . "</td>";
            echo "<td>" . htmlspecialchars($row['taula']) . "</td>";
            echo "<td>" . htmlspecialchars($row['idregistre']) . "</td>";
            echo "<td>" . htmlspecialchars($row['accio']) . "</td>";
            echo "<td>" . htmlspecialchars($row['campModificat']) . "</td>";
            echo "<td>" . htmlspecialchars($row['valorAntic']) . "</td>";
            echo "<td>" . htmlspecialchars($row['valorNou']) . "</td>";
            echo "<td>" . htmlspecialchars($row['dataauditoria']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="8">No hi ha dades d\'auditoria.</td></tr>';
    }
    echo '      </tbody>
            </table>
        </div>
    </div>';

    // Tabla Transaccions
    echo '<div class="tabla-scroll tabla-transaccions">
        <table>
            <caption>Transaccions</caption>
            <thead>
                <tr>
                    <th>idtransaccio</th>
                    <th>idusuari</th>
                    <th>idportafoli</th>
                    <th>idactiu</th>
                    <th>tipustransaccio</th>
                    <th>quantitat</th>
                    <th>datatransaccio</th>
                </tr>
            </thead>
        </table>
        <div class="scroll-tbody">
            <table>
                <tbody>';
    $where = [];
    $params = [];
    $types = '';

    $campo = $_GET['campo'] ?? '';
    $valor = $_GET['valor'] ?? '';

    if ($campo && $valor !== '') {
        if ($campo === 'datatransaccio') {
            $where[] = "DATE(t.datatransaccio) = ?";
            $params[] = $valor;
            $types .= 's';
        } elseif ($campo === 'idusuari') {
            $where[] = "p.idusuari = ?";
            $params[] = $valor;
            $types .= 'i';
        } elseif ($campo === 'idactiu') {
            $where[] = "t.idactiu = ?";
            $params[] = $valor;
            $types .= 'i';
        } elseif ($campo === 'quantitat') {
            $where[] = "t.quantitat = ?";
            $params[] = $valor;
            $types .= 'd';
        }
    }

    $sqlTrans = "SELECT t.idtransaccio, p.idusuari, t.idportafoli, t.idactiu, t.tipustransaccio, t.quantitat, t.datatransaccio
                 FROM transaccions t
                 JOIN portafolis p ON t.idportafoli = p.idportafoli";
    if ($where) {
        $sqlTrans .= " WHERE " . implode(' AND ', $where);
    }
    $sqlTrans .= " ORDER BY t.datatransaccio DESC";

    if ($params) {
        $stmt = $mysqli->prepare($sqlTrans);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $resultTrans = $stmt->get_result();
    } else {
        $resultTrans = $mysqli->query($sqlTrans);
    }
    if ($resultTrans && $resultTrans->num_rows > 0) {
        while ($row = $resultTrans->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['idtransaccio']) . "</td>";
            echo "<td>" . htmlspecialchars($row['idusuari']) . "</td>";
            echo "<td>" . htmlspecialchars($row['idportafoli']) . "</td>";
            echo "<td>" . htmlspecialchars($row['idactiu']) . "</td>";
            echo "<td>" . htmlspecialchars($row['tipustransaccio']) . "</td>";
            echo "<td>" . htmlspecialchars($row['quantitat']) . "</td>";
            echo "<td>" . htmlspecialchars($row['datatransaccio']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="7">No hi ha transaccions.</td></tr>';
    }
    echo '      </tbody>
            </table>
        </div>
    </div>';
    $mysqli->close();
    echo ob_get_clean();
    exit;
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
    <link rel="stylesheet" href="../CSS/styleAuditoria.css" />
</head>
<body>
    <header class="headerContainer">
        <div><img class="imagenHeader" src="../Images/logoSinFondo.png" alt="SafeHolder Logo" /></div>
        <div class="titulo"><h1>SafeHolder</h1></div>
        <div class="filtros-header" style="display:flex;align-items:center;gap:18px;margin-left:40px;">
            <span style="font-weight:bold;color:#fff;">Filtrat per transaccions:</span>
            <form id="filtro-transaccions" onsubmit="filtrarTransacciones();return false;" style="display:flex;gap:10px;align-items:center;">
                <select name="campo" id="campo" onchange="actualizaTipoInput()" style="padding: 4px 8px;">
                    <option value="datatransaccio" <?php if(($_GET['campo']??'')=='datatransaccio') echo 'selected'; ?>>Data</option>
                    <option value="idusuari" <?php if(($_GET['campo']??'')=='idusuari') echo 'selected'; ?>>Usuari</option>
                    <option value="idactiu" <?php if(($_GET['campo']??'')=='idactiu') echo 'selected'; ?>>Tipus d'actiu</option>
                    <option value="quantitat" <?php if(($_GET['campo']??'')=='quantitat') echo 'selected'; ?>>Import</option>
                </select>
                <input type="text" name="valor" id="valorFiltro"
                    value="<?php echo isset($_GET['valor']) ? htmlspecialchars($_GET['valor']) : ''; ?>"
                    placeholder="Valor a buscar" style="padding: 4px 8px;">
                <button type="button" onclick="filtrarTransacciones()">Filtrar</button>
                <button type="button" onclick="limpiarFiltro()">Reset</button>
            </form>
            <button class="recargar-btn" onclick="recargarTablas()" style="margin-left:18px;height:40px;">&#x21bb; Recarregar</button>
        </div>
        <div class="LoginCartera">
            <div class="valorCartera">
                <a href="../HTML/admin.php"><img src="../Images/admin.png" alt="VALOR CARTERA"></a>
            </div>
            <div class="cuenta">
                <a href="../HTML/logout.php"><img src="../Images/salida.png" alt="CUENTA"></a>
            </div>
        </div>
    </header>
    <div class="table-container" id="tablas-dinamicas">
        <!-- Las tablas se cargan aquí por PHP/AJAX -->
        <?php
        // --- Tabla Auditorias ---
        echo '<div class="tabla-scroll tabla-auditoria">
            <table>
                <caption>Auditories</caption>
                <thead>
                    <tr>
                        <th>idauditoria</th>
                        <th>taula</th>
                        <th>idregistre</th>
                        <th>accio</th>
                        <th>campModificat</th>
                        <th>valorAntic</th>
                        <th>valorNou</th>
                        <th>dataauditoria</th>
                    </tr>
                </thead>
            </table>
            <div class="scroll-tbody">
                <table>
                    <tbody>';
        $sql = "SELECT idauditoria, taula, idregistre, accio, campModificat, valorAntic, valorNou, dataauditoria FROM auditories ORDER BY dataauditoria DESC";
        $result = $mysqli->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['idauditoria']) . "</td>";
                echo "<td>" . htmlspecialchars($row['taula']) . "</td>";
                echo "<td>" . htmlspecialchars($row['idregistre']) . "</td>";
                echo "<td>" . htmlspecialchars($row['accio']) . "</td>";
                echo "<td>" . htmlspecialchars($row['campModificat']) . "</td>";
                echo "<td>" . htmlspecialchars($row['valorAntic']) . "</td>";
                echo "<td>" . htmlspecialchars($row['valorNou']) . "</td>";
                echo "<td>" . htmlspecialchars($row['dataauditoria']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo '<tr><td colspan="8">No hi ha dades d\'auditoria.</td></tr>';
        }
        echo '      </tbody>
                </table>
            </div>
        </div>';

        // --- Tabla Transaccions ---
        $where = [];
        $params = [];
        $types = '';

        $campo = $_GET['campo'] ?? '';
        $valor = $_GET['valor'] ?? '';

        if ($campo && $valor !== '') {
            if ($campo === 'datatransaccio') {
                $where[] = "DATE(t.datatransaccio) = ?";
                $params[] = $valor;
                $types .= 's';
            } elseif ($campo === 'idusuari') {
                $where[] = "p.idusuari = ?";
                $params[] = $valor;
                $types .= 'i';
            } elseif ($campo === 'idactiu') {
                $where[] = "t.idactiu = ?";
                $params[] = $valor;
                $types .= 'i';
            } elseif ($campo === 'quantitat') {
                $where[] = "t.quantitat = ?";
                $params[] = $valor;
                $types .= 'd';
            }
        }

        echo '<div class="tabla-scroll tabla-transaccions">
            <table>
                <caption>Transaccions</caption>
                <thead>
                    <tr>
                        <th>idtransaccio</th>
                        <th>idusuari</th>
                        <th>idportafoli</th>
                        <th>idactiu</th>
                        <th>tipustransaccio</th>
                        <th>quantitat</th>
                        <th>datatransaccio</th>
                    </tr>
                </thead>
            </table>
            <div class="scroll-tbody">
                <table>
                    <tbody>';
        $sqlTrans = "SELECT t.idtransaccio, p.idusuari, t.idportafoli, t.idactiu, t.tipustransaccio, t.quantitat, t.datatransaccio
                     FROM transaccions t
                     JOIN portafolis p ON t.idportafoli = p.idportafoli";
        if ($where) {
            $sqlTrans .= " WHERE " . implode(' AND ', $where);
        }
        $sqlTrans .= " ORDER BY t.datatransaccio DESC";

        if ($params) {
            $stmt = $mysqli->prepare($sqlTrans);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $resultTrans = $stmt->get_result();
        } else {
            $resultTrans = $mysqli->query($sqlTrans);
        }
        if ($resultTrans && $resultTrans->num_rows > 0) {
            while ($row = $resultTrans->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['idtransaccio']) . "</td>";
                echo "<td>" . htmlspecialchars($row['idusuari']) . "</td>";
                echo "<td>" . htmlspecialchars($row['idportafoli']) . "</td>";
                echo "<td>" . htmlspecialchars($row['idactiu']) . "</td>";
                echo "<td>" . htmlspecialchars($row['tipustransaccio']) . "</td>";
                echo "<td>" . htmlspecialchars($row['quantitat']) . "</td>";
                echo "<td>" . htmlspecialchars($row['datatransaccio']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo '<tr><td colspan="7">No hi ha transaccions.</td></tr>';
        }
        echo '      </tbody>
                </table>
            </div>
        </div>';
        ?>
    </div>
    <script>
    function actualizaTipoInput() {
        const campo = document.getElementById('campo').value;
        const input = document.getElementById('valorFiltro');
        if (campo === 'datatransaccio') {
            input.type = 'date';
            input.placeholder = '';
        } else if (campo === 'quantitat') {
            input.type = 'number';
            input.step = 'any';
            input.placeholder = '';
        } else {
            input.type = 'text';
            input.placeholder = 'Valor a buscar';
        }
    }
    function filtrarTransacciones() {
        const campo = document.getElementById('campo').value;
        const valor = document.getElementById('valorFiltro').value;
        const params = new URLSearchParams(window.location.search);
        params.set('ajax', '1');
        params.set('campo', campo);
        params.set('valor', valor);
        fetch('auditories.php?' + params.toString())
            .then(res => res.text())
            .then(html => {
                document.getElementById('tablas-dinamicas').innerHTML = html;
            });
    }
    function limpiarFiltro() {
        document.getElementById('campo').selectedIndex = 0;
        document.getElementById('valorFiltro').value = '';
        filtrarTransacciones();
    }
    function recargarTablas() {
        // Recarga manteniendo el filtro actual
        filtrarTransacciones();
    }
    window.onload = actualizaTipoInput;
    </script>
</body>
</html>