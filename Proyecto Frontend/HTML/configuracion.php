<?php

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SafeHolder</title>
    <link rel="stylesheet" href="../CSS/styleConfiguracion.css  " />
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
          <a href="../index.php">
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
        <h2>Editar Usuario</h2>
        <form action="editar_usuario.php" method="POST">
          <label for="nombre">Nombre:</label>
          <input
            type="text"
            id="nombre"
            name="nombre"
            placeholder="Tu nombre"
            required
          />

          <label for="correo">Correo:</label>
          <input
            type="email"
            id="correo"
            name="correo"
            placeholder="Tu correo"
            required
          />

          <label for="contraseña">Contraseña:</label>
          <input
            type="password"
            id="contraseña"
            name="contraseña"
            placeholder="Nueva contraseña"
            required
          />

          <button type="submit">Guardar Cambios</button>
        </form>
      </div>

      <div class="contenedor-transacciones">
        <h2>Historial de Transacciones</h2>
        <ul>
          <li>Compra 1: Bitcoin - $500</li>
          <li>Compra 2: Oro - $300</li>
          <li>Compra 3: Euro - $200</li>
          <!-- Aquí puedes cargar dinámicamente el historial desde la base de datos -->
        </ul>
      </div>
    </div>
  </body>
</html>