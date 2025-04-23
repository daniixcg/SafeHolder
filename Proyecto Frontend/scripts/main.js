// Función para inicializar el DOM y asignar eventos
function init() {
    // Obtener referencias a los elementos del DOM
    const boton = document.getElementById('boton');
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
    window.location.href = '../HTML/index.html';
}

// Ejecutar la función init cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', init);


/* AQUI SERA LA PARTE DE LAS CRIPTOMONEDAS */

 // BITCOIN
function actualizarPrecioBitcoin() {
    fetch('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=sats')
        .then(response => response.json())
        .then(data => {
            const satoshis = data.bitcoin.sats;
            document.getElementById('bitcoin-price').innerHTML = 
                `Bitcoin: ${satoshis} sats`;
            document.getElementById('bitcoin-image').src = 
                'https://assets.coingecko.com/coins/images/1/small/bitcoin.png?1696501400';
        })
        .catch(error => {
            document.getElementById('bitcoin-price').innerHTML = 
                'Error al cargar el precio';
        });
}

actualizarPrecioBitcoin();
setInterval(actualizarPrecioBitcoin, 60000);