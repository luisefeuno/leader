<!doctype html>
<html lang="es" data-bs-theme="light">
<!--start head-->
<head>
<?php include("../../config/templates/mainHead.php"); ?>
<?php
    // 3 es USER y 1 es ADMIN. 2 JEFE DE ESTUDIOS 0 PROFESOR
    //checkAccess(['0', '1', '2', '3']);
     checkAccess(['1','0','3']);

    ?>
<!--end head-->
</head>



<body>

  <!--start mainHeader-->
  <?php include("../../config/templates/mainHeader.php"); ?>
  <!--end mainHeader-->


  <!--start sidebar-->
  <?php include("../../config/templates/mainSidebar.php"); ?>
  <!--end sidebar-->


  <!--start main content-->
  <main class="page-content">

  </main>
  <!--end main content-->


  <!--start overlay-->
  <div class="overlay btn-toggle-menu"></div>
  <!--end overlay-->

  <!-- Search Modal -->
  <?php include("../../config/templates/searchModal.php"); ?>
<?php include("../../config/templates/mainFooter.php"); ?>



  <!--start theme customization-->
  <?php include("../../config/templates/mainThemeCustomization.php"); ?>

  <!--end theme customization-->



  <!--BS Scripts-->
  <?php include("../../config/templates/mainJs.php"); ?>

    <!-- end BS Scripts-->



  <!--start plugins extra-->
   <script src="../../public/assets/plugins/metismenu/js/metisMenu.min.js"></script>
   <script src="../../public/assets/plugins/simplebar/js/simplebar.min.js"></script>
  <!--end plugins extra-->



</body>

</html>