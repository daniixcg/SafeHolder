// BOTON PARA EDITAR USUARIO
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



// BOTON DE AÑADIR USUARIO
document.addEventListener("DOMContentLoaded", () => {
  const btnAgregarUsuario = document.querySelector(".agregarUsuario"); // Botón para abrir el sidebar2
  const sidebar2 = document.getElementById("sidebar2"); // Sidebar para agregar usuario
  const cerrarModal = document.getElementById("cerrarModal"); // Botón para cerrar el sidebar2
  const formAgregarUsuario = document.getElementById("formAgregarUsuario"); // Formulario de agregar usuario
  const tablaUsuarios = document.querySelector(".usuarios-tabla"); // Tabla de usuarios

  // Abrir el sidebar2 para agregar usuario
  btnAgregarUsuario.addEventListener("click", () => {
      sidebar2.style.display = "block"; // Muestra el sidebar2
  });

  // Cerrar el sidebar2
  cerrarModal.addEventListener("click", () => {
      sidebar2.style.display = "none"; // Oculta el sidebar2
  });

  // Agregar un nuevo usuario
  formAgregarUsuario.addEventListener("submit", (e) => {
      e.preventDefault(); // Evita el envío del formulario

      // Obtener los valores de los campos del formulario de agregar usuario
      const nuevoNombre = document.querySelector("#sidebar2 #nombre").value;
      const nuevoApellidos = document.querySelector("#sidebar2 #apellidos").value;
      const nuevoDni = document.querySelector("#sidebar2 #dni").value;
      const nuevoTelefono = document.querySelector("#sidebar2 #telefono").value;
      const nuevoCorreo = document.querySelector("#sidebar2 #correo").value;

      // Crear una nueva fila en la tabla
      const nuevaFila = document.createElement("tr");
      nuevaFila.innerHTML = `
          <td>${nuevoNombre}</td>
          <td>${nuevoApellidos}</td>
          <td>${nuevoDni}</td>
          <td>${nuevoTelefono}</td>
          <td>${nuevoCorreo}</td>
          <td>
              <button class="btn-accion editar-btn">
                  <span>Editar</span>
              </button>
          </td>
      `;

      // Agregar la nueva fila a la tabla
      tablaUsuarios.appendChild(nuevaFila);

      // Limpiar el formulario y cerrar el sidebar2
      formAgregarUsuario.reset();
      sidebar2.style.display = "none";
  });
});