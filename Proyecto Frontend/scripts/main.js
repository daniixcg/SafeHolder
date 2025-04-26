// Función para inicializar el DOM y asignar eventos
function init() {
    // Obtener referencias a los elementos del DOM
    const boton = document.getElementById('button');
    const email = document.getElementById('email');
    const password = document.getElementById('password');

    // Asignar evento al botón
    boton.addEventListener('click', () => validarCampos(email, password));
}

// Función para validar los campos de email y contraseña
function validarCampos(email, password) {
    // Validar si el campo de email está vacío
    if (!email.value.trim()) {
        alert('Debe ingresar un correo');
        return;
    }

    // Validar si el campo de contraseña está vacío
    if (!password.value.trim()) {
        alert('Debe ingresar una contraseña');
        return;
    }

    // Si ambos campos están rellenos, redirigir a index.html
    redirigirPagina();
}

// Función para redirigir a la página index.html
function redirigirPagina() {
    window.location.href = '../index.html';
}

// Ejecutar la función init cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', init);


/* AQUI SERA LA PARTE DE LAS CRIPTOMONEDAS */

 // BITCOIN
// Función para obtener el precio del Bitcoin desde la API de CoinGecko y actualizar el DOM
function obtenerPrecioBitcoinUSD() {
    fetch('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd&include_last_updated_at=true')
        .then(response => response.json())
        .then(data => {
            const usdPrice = data.bitcoin.usd;

            // Actualizar el DOM con el precio obtenido
            document.getElementById('bitcoin-price').innerHTML = 
                `Bitcoin: $${usdPrice} USD`;
            document.getElementById('bitcoin-image').src = 
                'https://assets.coingecko.com/coins/images/1/small/bitcoin.png?1696501400';
        })
        .catch(error => {
            console.error('Error al obtener el precio del Bitcoin:', error);
            document.getElementById('bitcoin-price').innerHTML = 
                'Error al cargar el precio';
        });
}

// Función para ejecutar el archivo PHP y actualizar la base de datos
function ejecutarArchivoPHP() {
    fetch('../php/update.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al ejecutar el archivo PHP');
            }
            return response.text(); // Leer la respuesta del PHP
        })
        .then(data => {
            console.log('Respuesta del servidor:', data); // Mostrar respuesta del PHP en la consola
        })
        .catch(error => {
            console.error('Error al ejecutar el archivo PHP:', error);
        });
}

// Función para actualizar el precio del Bitcoin
function actualizarPrecioBitcoin() {
    // Llamar a ambas funciones de forma independiente
    obtenerPrecioBitcoinUSD(); // Actualizar el precio en el DOM
    ejecutarArchivoPHP(); // Actualizar el precio en la base de datos
}

// Llamar a la función para actualizar el precio del Bitcoin cada 60 segundos
actualizarPrecioBitcoin();
setInterval(actualizarPrecioBitcoin, 60000);