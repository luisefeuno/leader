// Función para mostrar el spinner en la zona de mensajes
function mostrarSpinner() {
    const zonaMensajes = document.getElementById("zonaMensajes");
    zonaMensajes.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';
}

// Función para ocultar el spinner y mostrar un mensaje
function ocultarSpinner(mensaje) {
    const zonaMensajes = document.getElementById("zonaMensajes");
    zonaMensajes.innerHTML = `<p>${mensaje}</p>`;
}

// Evento para el botón "descargar"
document.getElementById("descargar").addEventListener("click", function () {
    mostrarSpinner(); // Mostrar el spinner

    // Realizar la llamada AJAX al archivo descargarficheros.php
    fetch('../Ordenes/descargarficheros.php')
        .then(response => {
            if (response.ok) {
                return response.json(); // Parsear la respuesta como JSON
            } else {
                throw new Error("Error al descargar los ficheros.");
            }
        })
        .then(data => {
            if (data.status === 'success') {
                ocultarSpinner(data.message); // Mostrar mensaje de éxito
            } else {
                ocultarSpinner("Error en la descarga: " + data.message); // Mostrar mensaje de error
            }
        })
        .catch(error => {
            ocultarSpinner("Error al realizar la descarga."); // Mostrar mensaje de error
            console.error(error);
        });
});



//===<<!!>>=<<!!>>==<<!!>>==DROPZONE ===<<!!>>=<<!!>>==<<!!>>==//
//  var archivosAlmacenados = []; // Inicialmente no hay ningún archivo almacenado
  //======================<INICIALIZAMOS EL DROPZONE>=======================//
//   var myDropzone = new Dropzone("#dropzoneGesdoc", {
//         url: "../../controller/subirDocumentoJson.php?op=subirDocImport", // Ruta php que recibirá los archivos
//         paramName: "file", // Nombre del archivo en el formulario que será recibido en PHP
//         addRemoveLinks: true,
//         maxFiles: 5, // Puedes ajustar este número según el límite de archivos que quieras aceptar
//         parallelUploads: 5, // La cantidad de archivos que se pueden subir simultáneamente
//         maxFilesize: 5, // Tamaño máximo en MB aceptado por archivo
//         acceptedFiles: ".json", // Extensiones de archivo aceptadas
//         autoProcessQueue: false, // Evita que los archivos se suban automáticamente
//         uploadMultiple: true, // Permite la carga de varios archivos
//         dictDefaultMessage: "Arrastra o selecciona archivos .JSON, max 5 archivos",
//         dictRemoveFile: "Eliminar archivo",
//         init: function () {
//             this.on("error", function(file, errorMessage) {
//                 console.log(file);
//                 this.removeFile(file);
//                 toastr.error('El archivo '+errorMessage+' no está permitido. Se ha eliminado.'); // Mostrar mensaje de error con Toastr
//             });
//         }
//     });

  //=/=/=/=/=/=/=/=/=/=/=/=/=<FIN DROPZONE INICIALIZADO>=/=/=/=/=/=/=/=/=/=/=/=/=/=/=/
  
  
  
  //=/=/=/=/=/=/=/=/=/=/=/=/=<FIN DROPZONE>=/=/=/=/=/=/=/=/=/=/=/=/=/=/=/
//   $("#guardarIncidencia").on("click", function () {
//     cargando();
//         var allFiles = myDropzone.files;

//         if (allFiles.length !== 0) {
//             myDropzone.processQueue(); // Procesar la cola de archivos

//             myDropzone.on("successmultiple", function (files, response) {
//                 var parsedResponse = JSON.parse(response);
//                 console.log(parsedResponse);
//                 toastr.success('Archivos subidos exitosamente');
//                   setTimeout(function(){
//                 window.location.href = '../Ordenes/cargarOrdenesCron.php';
//             }, 2000); // Tiempo en milisegundos (2000ms = 2s)
//             });

//         } else {
//             descargando();
//             toastr.warning('Sube al menos un documento para la importación.');
//         }
//         descargando();
//     });
