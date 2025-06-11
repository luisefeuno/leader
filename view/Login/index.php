<!doctype html>
<html lang="es" data-bs-theme="light">

<!--start head-->
<head>
<?php
$json_string = json_encode('ss');
$file = 'aabb.json';
file_put_contents($file, $json_string);
    session_start();
    if (isset($_SESSION['usu_id'])) {
        
        
        header('Location:../Home/index.php');
    }
?>
<?php include("../../config/templates/mainHead.php"); ?>
<style>

.material-symbols-outlined {
            font-size: 30px; /* Ajusta el tamaño según sea necesario */
        }

</style>
</head>
<!--end head-->

<body>


  <!--authentication-->
<!-- Form -->
<div>

  <div class="mx-3 mx-lg-0">

  <div class="card my-5 col-xl-9 col-xxl-8 mx-auto rounded-4 overflow-hidden border-3 p-3">
    <div class="row g-3">
      <div class="col-lg-6 d-flex">
        <div class="card-body p-5 w-100">
          <img src="../../public/assets/images/efeuno/logotipoWhite.png" class="mb-4" width="130" alt="">
          <h4 class="fw-bold">Inicia sesión</h4>
          <p class="mb-0">Introduce tus credenciales para entrar en tu cuenta</p>
          <div class="row g-3 my-4">
            <div class="col-12 col-lg-12">
              <button id="google-login" class="btn btn-light py-2 font-text1 fw-bold d-flex align-items-center justify-content-center w-100"><img 
              src="../../public/assets/images/icons/google-2.png" width="18" class="me-2" alt="">Iniciar Sesión con Google</button>
            </div>
            
          </div>
          <div class="separator">
            <div class="line"></div>
            <p class="mb-0 fw-bold">O</p>
            <div class="line"></div>
          </div>
          <div id="divLogin" class="form-body mt-4">
            <form class="row g-3" id="login_form">
              <div class="col-12">
                <label for="inputEmailAddress" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="usu_correo" placeholder="ejemplo@dominio.com">
              </div>
              <div class="col-12">
                <label for="inputChoosePassword" class="form-label">Contraseña</label>
                <div class="input-group" id="show_hide_password">
                  <input type="password" class="form-control border-end-0" id="usu_pass" 
                    placeholder="Introduzca contraseña">
                  <a href="javascript:;" class="input-group-text bg-transparent"><i
                      class="bi bi-eye-slash-fill"></i></a>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked">
                  <label class="form-check-label" for="flexSwitchCheckChecked">Recordar contraseña</label>
                </div>
              </div>
              <div class="col-md-6 text-end"> <a href="../../view/RecuperarPass/">¿Contraseña olvidada?</a>
              </div>
              <div class="col-12">
                <div class="d-grid">
                  <button type="submit" class="btn btn-primary" id="loginButton">Login</button>
                </div>
              </div>
              <div class="col-12">
                <div class="text-start">
                  <p class="mb-0">¿No tienes cuenta?<a href="../../view/Registro"> Registrate</a>
                  </p>
                </div>
              </div>
            </form>
            
          </div>
         
        </div>
        <div>
                <a class="nav-link dark-mode-icon" id="toggle-bs-theme" href="javascript:;"><span id="theme-icon" class="material-symbols-outlined">dark_mode</span></a>
              </div>
      </div>
      <div class="col-lg-6 d-lg-flex ">
        <div class="p-3 rounded-4 w-100 d-flex align-items-center justify-content-center border-3 bg-primary">
          <img src="../../public/assets/images/boxed-login.png" class="img-fluid" alt="">
        </div>
      </div>

    </div><!--end row-->
  </div>

</div>
  <!--BS Scripts-->
  <?php include("../../config/templates/mainJs.php"); ?>

    <!-- end BS Scripts-->

  <!--authentication-->

  <!--start plugins extra-->
  <script>
        $(document).ready(function() {
            $('#google-login').on('click', function() {
                window.location.href = '../../controller/googleLogin.php';
            });
        });
    </script>
       <script src="index.js"></script>
  <!--end plugins extra-->
  <?php include("../../config/templates/mainFooter.php"); ?>

</body>

</html>