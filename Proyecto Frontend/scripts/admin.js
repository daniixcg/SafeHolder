// Selecciona los elementos necesarios
const editarBotones = document.querySelectorAll(".editar-btn"); // Todos los botones "Editar"
const sidebar = document.getElementById("sidebar");
const cerrarSidebar = document.getElementById("cerrarSidebar");
const usuariosContainer = document.querySelector(".usuarios"); // Contenedor de la tabla

let filaSeleccionada = null; // Variable para almacenar la fila seleccionada

// Función para abrir el sidebar
function abrirSidebar(event) {
  // Obtén la fila correspondiente al botón clicado
  filaSeleccionada = event.target.closest("tr");
  const nombre = filaSeleccionada.children[0].textContent; // Primera columna (Nombre)
  const apellidos = filaSeleccionada.children[1].textContent; // Segunda columna (Apellidos)
  const dni = filaSeleccionada.children[2].textContent; // Tercera columna (DNI)
  const telefono = filaSeleccionada.children[3].textContent; // Cuarta columna (Teléfono)
  const correo = filaSeleccionada.children[4].textContent; // Quinta columna (Correo)

  // Rellena los campos del formulario con los datos de la fila
  document.getElementById("nombre").value = nombre;
  document.getElementById("apellidos").value = apellidos;
  document.getElementById("dni").value = dni;
  document.getElementById("telefono").value = telefono;
  document.getElementById("correo").value = correo;

  // Posiciona el sidebar al lado del contenedor
  const containerRect = usuariosContainer.getBoundingClientRect();
  sidebar.style.top = `${containerRect.top}px`;
  sidebar.style.left = `${containerRect.right}px`;
  sidebar.style.height = `${containerRect.height}px`;

  // Muestra el sidebar
  sidebar.classList.add("active");
}

// Función para cerrar el sidebar
function cerrarSidebarFunc() {
  sidebar.classList.remove("active");
}

// Asocia el evento de clic a cada botón "Editar"
editarBotones.forEach((boton) => {
  boton.addEventListener("click", abrirSidebar);
});

// Asocia el evento de clic al botón "Cerrar"
cerrarSidebar.addEventListener("click", cerrarSidebarFunc);