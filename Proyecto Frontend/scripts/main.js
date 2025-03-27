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


