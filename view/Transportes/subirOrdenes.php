<!doctype html>
<html lang="es" data-bs-theme="light">
<!--start head-->
<?php
session_start();

?>

<head>
    <?php include("../../config/templates/mainHead.php"); ?>

    <?php
    // 3 es USER y 1 es ADMIN. 2 JEFE DE ESTUDIOS 0 PROFESOR
    //checkAccess(['0', '1', '2', '3']);
    checkAccess(['0', '1']);

    ?>
    <!--end head-->
    <style>
        #accordionButton1.accordion-button:not(.collapsed) {
            background-color: var(--bs-cyan);
            color: var(--bs-black);
        }

        #accordionButton2.accordion-button:not(.collapsed) {
            background-color: var(--bs-orange);
            color: var(--bs-black);
        }


        /* Ajusta la barra de progreso para que sea más estilizada */
        .dropzone .dz-progress .dz-upload {
            background-color: #28a745 !important;
            /* Cambia el color de la barra de progreso */
            height: 5px;
            /* Ajusta el grosor de la barra */
            border-radius: 3px;
        }
    </style>
</head>



<body>
    <input type="hidden" id="usuRol" value="<?php echo $_SESSION['usu_rol']; ?>">
    <!--start mainHeader-->
    <?php include("../../config/templates/mainHeader.php"); ?>
    <!--end mainHeader-->


    <!--start sidebar-->
    <?php include("../../config/templates/mainSidebar.php"); ?>
    <!--end sidebar-->

    <!-- **************************************** -->
    <!--                BREADCUM                  -->
    <!-- **************************************** -->
    <!-- <span class="breadcrumb-item active">Mantenimiento</span> -->
    <!-- **************************************** -->
    <!--                FIN DEL BREADCUM                  -->
    <!-- **************************************** -->

    <!-- ***************************************************** -->
    <!--                CABECERA DE LA PAGINA                  -->
    <!-- ***************************************************** -->

    <!--start main content-->
    <main class="page-content">
        <div class="page-breadcrumb d-sm-flex align-items-center">
            <div class="breadcrumb-title pe-3"><a href="../../view/Home/index.php" class="text-reset">Inicio</a></div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item" aria-current="page">Transportes</li>
                        <li class="breadcrumb-item active" aria-current="page">Subir-Descargar Ordenes</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">

            <div class="col-12 card mg-t-20-force">
                <div class="card-body">
                    <h2 class="card-title">Subida-Descarga de Ordenes</h2>
                    <div class="my-3 border-top"></div>

                    <div class="row">
                        <!-- Form Validation Form -->
                        <div id="form-SubirImg" class="card-body d-flex justify-content-center  row ">
                            <div class="col-12">
                                <p class="tx-bold mg-b-20 tx-center">Subir-Descargar Ordenes masiva con un archivo .JSON.</p>
                            </div>

                            <!-- Este es el DROPZONE - Sin funcionaba -->
                            <!-- <div class="col-12 tx-center" id="">
                                <div class="row mg-b-20" style="margin:20px;">
                                    <div class="col-12 dropzone" id="dropzoneGesdoc"></div>
                                </div>
                                <label for=""><b class="tx-danger">*</b> La extension de archivo permitida es .JSON</label>

                            </div> -->



                            <!-- <div class="col-12 d-flex justify-content-center mg-t-10 " style="display:flex;justify-content:space-between;margin:20px;">
                            </div> -->
                            <!-- <a href='../Ordenes/cargarOrdenesCron.php'><button type='button' class='btn btn-warning tx-right'>Forzar Carga</button></a> -->

                            <div class="col-12 d-flex justify-content-center mg-t-10 " style="display:flex;justify-content:space-between; margin:20px;">

                                <!-- <a onclick=''><button type='button' id="guardarIncidencia" class='btn btn-success' style="margin-right: 10px">IMPORTACION Archivos DROPZONE</button></a> -->

                                <!-- <a href='../Ordenes/descargarficheros.php'><button type='button' class='btn btn-warning tx-right' style="margin-right: 10px" id="descargar">DESCARGAR FICHEROS del FTP</button></a> -->


                                <button type='button' class='btn btn-warning tx-right' style="margin-right: 10px" id="descargar">DESCARGAR FICHEROS del FTP</button>


                                <a href='../Ordenes/subirficheros.php'><button type='button' class='btn btn-danger'>SUBIR FICHEROS AL FTP</button></a>
                            </div>

                            <!-- <div class="col-12 d-flex justify-content-center mg-t-10 " style="display:flex;justify-content:space-between; ">
                                <p> IMPORTACION Archivos DROPZONE: Se refiere a la incorporación a la base de datos del servidor de un archivo JSON fuera del circuito FTP-Remoto (DropZone)</p>
                            </div> -->

                            <div class="col-12 d-flex justify-content-center mg-t-10 " style="display:flex;justify-content:space-between; ">
                                <p> DESCARGA FICHEROS DEL FTP Se conecta al FTP-remoto de Leader, se descarga los ficheros a la carpeta-local "/ordenes/Uploads" y las trabaja <br> Archivo: "Ordenes/cargarOrdenesCron.php</p>
                            </div>

                            <div class="col-12 d-flex justify-content-center mg-t-10 " style="display:flex;justify-content:space-between; margin:20px;">
                                <p> CARGAR FICHEROS AL FTP: Se conecta al FTP de Leader, e intenta cargar los ficheros de la carpeta local "Ordenes/envios" al la carpeta FTP-remota "/responsesEfeuno" <br> Archivo: "Ordenes/subirficheros.php"</p>

                            </div>


                            <div class="col-12 d-flex justify-content-center mg-t-10 " style="display:flex;justify-content:space-between; margin:20px;" id="zonaMensajes">
                                <p> "Zona de mensajes"</p>
                            </div>

                        </div>
                    </div>

    </main>
    <?php include("../../config/templates/mainFooter.php"); ?> <!--end main content-->



    <!--start overlay-->
    <div class="overlay btn-toggle-menu"></div>
    <!--end overlay-->

    <!-- Search Modal -->
    <?php include_once 'modalAgregar.php' ?>
    <?php include_once 'modalEditar.php' ?>
    <?php include_once 'modalInformacion.php' ?>


    <?php include("../../config/templates/searchModal.php"); ?>


    <!--start theme customization-->
    <?php include("../../config/templates/mainThemeCustomization.php"); ?>

    <!--end theme customization-->



    <!--BS Scripts-->
    <?php include("../../config/templates/mainJs.php"); ?>

    <!-- end BS Scripts-->



    <!--start plugins extra-->
    <script src="../../public/assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="../../public/assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="subirArchivoOrdenes.js"></script>
    <!--end plugins extra-->



</body>

</html>