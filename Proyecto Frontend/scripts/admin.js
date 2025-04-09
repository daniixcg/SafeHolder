/* ESTA PARTE ES LA DEL ADMIN EL BOTON DE DESPLEGABLE */

// Evento para manejar el clic en el botón

document.querySelector('.dropbtn').addEventListener('click', function() {

    document.getElementById("myDropdown").classList.toggle("show");

});


// Cerrar el menú si se hace clic fuera de él

window.onclick = function(event) {

    if (!event.target.matches('.dropbtn')) {

        var dropdowns = document.getElementsByClassName("dropdown-content");

        for (var i = 0; i < dropdowns.length; i++) {

            var openDropdown = dropdowns[i];

            if (openDropdown.classList.contains('show')) {

                openDropdown.classList.remove('show');

            }

        }

    }

}

/* AQUI ACABA LA PARTE */