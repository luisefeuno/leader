<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

//==========================================//
// PROCESO KRON EL CUAL IMPORTA LAS ORDENES //
//==========================================//
// Ajustes de configuración para evitar límites de tiempo y memoria
// $json_string = json_encode('asdas');
// $file = 'PACONI.json';
// file_put_contents($file, $json_string);
// Establece la zona horaria de Madrid, Europa
date_default_timezone_set('Europe/Madrid');

// Directorio donde se encuentran los archivos .json
//$directorio = 'descargas/';

// Directorio local para guardar los archivos descargados
$directorio_localFtp = __DIR__ . '/descargas/';

// Obtiene la fecha actual
$fechaActual = new DateTime();
// Formatea la fecha para obtener solo la parte de la fecha
$fecha = $fechaActual->format('Y-m-d H:i:s');

// Se creara uno por cada vez que se ejecute el script
// Ruta del archivo JSON de registro
$nombreArchivoJson = $directorio_localFtp . "control_descarga_$fechaHoraActual.json";




// FUNCION LOG //
function validarCorreo($email)
{
    // Eliminar espacios en blanco al principio y al final
    $email = trim($email);

    // Validar el formato del correo electrónico
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    } else {
        return "Error: El campo CONDUCTOR_EMAIL no contiene una dirección de correo electrónico válida. CAMPO: $email";
    }
}

function validarIdentificador($identificador)
{
    // Eliminar espacios y convertir a mayúsculas
    $identificador = strtoupper(trim($identificador));

    // Verificar longitud
    if (strlen($identificador) != 9) {
        return false;
    }

    // Obtener la letra y los números
    $numero = substr($identificador, 0, -1);
    $control = substr($identificador, -1);

    // Verificar si es un DNI
    if (is_numeric($numero)) {
        return validarDNIOuNIF($numero, $control);
    }

    // Verificar si es un NIF
    $primeraLetra = substr($numero, 0, 1);
    $restoNumeros = substr($numero, 1);

    if (preg_match('/[XYZ]/', $primeraLetra)) {
        $numero = str_replace(['X', 'Y', 'Z'], ['0', '1', '2'], $numero);
        return validarDNIOuNIF($numero, $control);
    }

    if (preg_match('/[ABCDEFGHJKLMNPQRSUVW]/', $primeraLetra) && is_numeric($restoNumeros)) {
        return validarCIF($numero, $control);
    }

    return false;
}


function validarDNIOuNIF($numero, $letra)
{
    // Calcular la letra correcta
    $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
    $letraCalculada = $letras[intval($numero) % 23];

    // Comparar la letra calculada con la letra proporcionada
    return $letraCalculada === $letra;
}

function validarCIF($numero, $control)
{
    $letraInicial = substr($numero, 0, 1);
    $numeroCif = substr($numero, 1);
    $sumaPar = 0;
    $sumaImpar = 0;

    // Sumar valores pares e impares
    for ($i = 0; $i < strlen($numeroCif); $i++) {
        $n = intval($numeroCif[$i]);
        if ($i % 2 == 0) {
            $doble = $n * 2;
            $sumaImpar += $doble < 10 ? $doble : $doble - 9;
        } else {
            $sumaPar += $n;
        }
    }

    $sumaTotal = $sumaPar + $sumaImpar;
    $digitoControl = (10 - ($sumaTotal % 10)) % 10;

    // Verificar control
    if (preg_match('/[ABEH]/', $letraInicial)) {
        // El CIF debe tener un dígito de control
        return $control == $digitoControl;
    } elseif (preg_match('/[KPQS]/', $letraInicial)) {
        // El CIF debe tener una letra de control
        $letras = "JABCDEFGHI";
        return $control == $letras[$digitoControl];
    } else {
        // El CIF puede tener un dígito o letra de control
        $letras = "JABCDEFGHI";
        return $control == $digitoControl || $control == $letras[$digitoControl];
    }
}

function generarToken($longitud = 32)
{
    // Asegúrate de que la longitud sea siempre un número par
    if ($longitud % 2 !== 0) {
        $longitud++; // Si la longitud es impar, la incrementamos para que sea par
    }

    // Genera bytes aleatorios seguros
    $bytes = random_bytes($longitud / 2);

    // Convierte los bytes en una cadena hexadecimal
    $token = bin2hex($bytes);

    return $token;
}


$errorText = 0;
?>

<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga de JSON - Efeuno DEV</title>
    <style>
        body {
            display: grid;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .content {

            text-align: center;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <br>
    <div class="content">

        <?php
        // TRY CONEXION //
        try {

            $dominioCompleto = $_SERVER['HTTP_HOST'];

            // Guardar directamente el dominio o la IP completa
            $nombreDominio = $dominioCompleto;

            // Construir la ruta al archivo de configuración basado en el nombre del dominio
            $jsonContentSettings = file_get_contents(__DIR__ . '/../../config/settings/' . $nombreDominio . '.json');

            // Convertir el JSON a un arreglo asociativo de PHP
            $configJsonSetting = json_decode($jsonContentSettings, true);

            // Acceder a las variables de entorno de la base de datos
            $dbHost = $configJsonSetting['database']['host'];
            $dbPort = $configJsonSetting['database']['port'];
            $dbName = $configJsonSetting['database']['dbname'];
            $dbUser = $configJsonSetting['database']['username'];
            $dbPassword = $configJsonSetting['database']['password'];

            // CONEXION A LA BASE DE DATOS //
            $conectar = new PDO("mysql:host=" . $dbHost . ";port=" . $dbPort . ";dbname=" . $dbName . "", "" . $dbUser . "", "" . $dbPassword . "");

            // Establecer el modo de error de PDO para que lance excepciones en caso de error
            $conectar->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo "Conexión exitosa a la base de datos. <br>";

            //=================================================================================//
            //============           CARGA DE ORDENES FTP                       ==============//
            //============     AL SERVIDOR DESDE FTP DE LEADER TRANSPORT       ==============//
            //==============================================================================//

            // Acceder a las variables de entorno FTP encontradas en archivo JSON
            $ipFTP = $configJsonSetting['ftpConfig']['ipFTP'];
            $userFTP = $configJsonSetting['ftpConfig']['userFTP'];
            $passFTP = $configJsonSetting['ftpConfig']['passFTP'];
            $portFTP = $configJsonSetting['ftpConfig']['portFTP'];

            // Credenciales FTP del cliente
            $ftp_server = $ipFTP;
            $ftp_user = $userFTP;
            $ftp_pass = $passFTP;
            $ftp_port = $portFTP; // Ajusta el puerto si es necesario

            // Conectar al servidor FTP en el puerto especificado
            $ftp_conn = ftp_connect($ftp_server, $ftp_port) or die("No se pudo conectar a $ftp_server en el puerto $ftp_port");

            // Intentar iniciar sesión con manejo de errores
            if (!ftp_login($ftp_conn, $ftp_user, $ftp_pass)) {
                ftp_close($ftp_conn);
                echo "<h3>Error: No se pudo iniciar sesión con las credenciales proporcionadas. ERROR FTP</h3><br>";
                die("Error: No se pudo iniciar sesión con las credenciales proporcionadas.  ERROR FTP");
            }

            // Activar modo pasivo
            ftp_pasv($ftp_conn, true);

            echo "<b style='color:green;'>¡Conexión FTP exitosa!</b><br>";

            // Obtener lista de archivos en el directorio raíz
            $archivosFtp = ftp_nlist($ftp_conn, ".");


            // Crear el directorio local si no existe
            if (!is_dir($directorio_localFtp)) {
                mkdir($directorio_local, 0777, true);
            }

            // Crear un nombre único para el archivo JSON basado en la fecha y hora actual
            $fechaHoraActual = date('Y-m-d_H-i-s');
            $nombreArchivoJson = $directorio_localFtp . "control_descarga_$fechaHoraActual.json";

            // Inicializar el contenido del archivo JSON
            $controlDescarga = [
                'fecha_hora' => date('Y-m-d H:i:s'),
                'archivos' => []
            ];

            // Descargar cada archivo JSON
            foreach ($archivosFtp as $archivo) {
                if (pathinfo($archivo, PATHINFO_EXTENSION) === 'json') { // Filtrar solo archivos JSON
                    $ruta_local = $directorio_localFtp . $archivo;

                    // Obtener fecha de modificación del archivo en el servidor FTP
                    $fecha_modificacion_ftp = ftp_mdtm($ftp_conn, $archivo);

                    if (file_exists($ruta_local)) {
                        // Obtener fecha de modificación del archivo local
                        $fecha_modificacion_local = filemtime($ruta_local);

                        // Comparar las fechas
                        if ($fecha_modificacion_ftp <= $fecha_modificacion_local) {
                            echo "Archivo $archivo no ha cambiado. Se omite la descarga.<br>";
                            $controlDescarga['archivos'][] = [
                                'nombre' => $archivo,
                                'descargado' => false,
                                'razon' => 'No ha cambiado'
                            ];
                            continue;
                        }
                    }

                    // Descargar el archivo si es nuevo o ha sido modificado
                    if (ftp_get($ftp_conn, $ruta_local, $archivo, FTP_BINARY)) {
                        echo "Archivo $archivo descargado o actualizado exitosamente en $ruta_local.<br>";
                        $controlDescarga['archivos'][] = [
                            'nombre' => $archivo,
                            'descargado' => true
                        ];
                    } else {
                        echo "Error al descargar el archivo $archivo.<br>";
                        $controlDescarga['archivos'][] = [
                            'nombre' => $archivo,
                            'descargado' => false,
                            'razon' => 'Error en la descarga'
                        ];
                    }
                }
            }

            // Guardar el archivo JSON de control
            file_put_contents($nombreArchivoJson, json_encode($controlDescarga, JSON_PRETTY_PRINT));

            // Cerrar la conexión FTP
            ftp_close($ftp_conn);


            //========================================================================================//
            //========================================================================================//
            //========================================================================================//
            //========================================================================================//



            // Obtener todos los archivos .json en el directorio
            // $archivos = glob($directorio . '*.json');
            // echo '<hr>';
            // Obtener todos los archivos JSON en el directorio
            $jsonFiles = glob($directorio . "*.json");

            if ($jsonFiles === false) {
                die("Error al obtener la lista de archivos JSON.<br>");
            }

            // Recorrer cada archivo
            $contador = 1;
            foreach ($archivos as $archivo) {
                echo $contador;
                echo '<p style="font-family: Comic Sans MS, sans-serif; color: #0080ff; font-size: 20px; margin-bottom: 5px;">Cargando Archivo: ' . $archivo . '</p>';

                // Leer el contenido del archivo
                $contenido = file_get_contents($archivo);

                // Eliminar el BOM si está presente
                $contenido = preg_replace('/\x{FEFF}/u', '', $contenido);
                // Decodificar el contenido JSON
                $datos = json_decode($contenido, true);
                $CONDUCTOR_NIF = strtoupper(trim($datos['CONDUCTOR_NIF']));

                // SUPER VALIDACION: SI LA ORDEN CONTIENE UN DNI ERRONEO. //
                if (validarIdentificador($CONDUCTOR_NIF)) {
                } else {
                    echo 'EL DNI NO ES CORRECTO. ARCHIVO: ' . $archivo . ' DNI: ' . $CONDUCTOR_NIF;
                }


                // EL DNI DEBE DE TENER UN MINIMO DE 4 CARACTERES, NO SE CREA ORDEN
                if (strlen($CONDUCTOR_NIF) < 4) {
                    echo '<b style="color:red;"> ORDEN NO CREADA. EL DNI DEBE DE TENER MÁS DE 4 POSICIONES. EL DNI NO ES CORRECTO. ARCHIVO: ' . $archivo . ' DNI: ' . $CONDUCTOR_NIF . '</b>';
                    echo '<br><hr>';
                } else {
                    echo ' DNI CORRECTO: ' . $CONDUCTOR_NIF . '<br>';

                    //================================================//
                    //========= CREAR DATOS TRANSPORTISTA ============//
                    //================================================//

                    // GENERADOR DE TRANSPORTISTAS GRACIAS A LAS ORDENES.
                    echo '<br>--------------------------------------------------------------------<br>';
                    echo '▫︎ CREACIÓN DE USUARIO ORDEN  <br>';
                    echo '--------------------------------------------------------------------<br>';
                    // Datos Usuarios //
                    $TRANSPORTISTA_COD = $datos['TRANSPORTISTA_COD'];
                    $TRANSPORTISTA_NOMBRE = $datos['TRANSPORTISTA_NOMBRE'];
                    $TRANSPORTISTA_DIRECCION = $datos['TRANSPORTISTA_DIRECCION'];
                    $TRANSPORTISTA_CP = $datos['TRANSPORTISTA_CP'];
                    $TRANSPORTISTA_POBLACION = $datos['TRANSPORTISTA_POBLACION'];
                    $TRANSPORTISTA_PROVINCIA = $datos['TRANSPORTISTA_PROVINCIA'];
                    $CONDUCTOR_NIF = strtoupper(trim($datos['CONDUCTOR_NIF']));
                    $CONDUCTOR_NOMBRE = $datos['CONDUCTOR_NOMBRE'];
                    $CONDUCTOR_EMAIL = $datos['CONDUCTOR_EMAIL'];
                    $TRACTORA = $datos['TRACTORA'];

                    $correoCuenta = validarCorreo($CONDUCTOR_EMAIL);

                    if (strpos($correoCuenta, 'Error') === 0) {
                        // Manejar el error
                        echo $correoCuenta;
                    } else {
                        // El correo es válido, continuar con el proceso de inicio de sesión
                        echo "Correo válido: " . $correoCuenta . "<br>";

                        // Verificar si CUENTA CONDUCTOR ya existe
                        $usuarioConductorCreadoExist = "SELECT COUNT(*) as count FROM `tm_usuario` WHERE `idTransportista_transportistas-Transporte` = :idTransportistaLeader";
                        $usuarioConductorCreadoExist = $conectar->prepare($usuarioConductorCreadoExist);
                        $usuarioConductorCreadoExist->bindParam(':idTransportistaLeader', $CONDUCTOR_NIF);
                        $usuarioConductorCreadoExist->execute();
                        $usuarioConductorCreadoExist = $usuarioConductorCreadoExist->fetch(PDO::FETCH_ASSOC);

                        $exists = $usuarioConductorCreadoExist['count'] > 0;
                        if ($exists) {
                            echo 'CUENTA DE CONDUCTOR EXISTE YA <br>';

                            // Variables de ejemplo (deben ser definidas previamente en tu script)
                            $CONDUCTOR_NOMBRE = $TRANSPORTISTA_NOMBRE;
                            $correoCuenta = $CONDUCTOR_EMAIL;
                            $TRANSPORTISTA_PROVINCIA = $TRANSPORTISTA_PROVINCIA;
                            $TRANSPORTISTA_POBLACION = $TRANSPORTISTA_POBLACION;
                            $TRANSPORTISTA_CP = $TRANSPORTISTA_CP;
                            $TRANSPORTISTA_DIRECCION = $TRANSPORTISTA_DIRECCION;

                            // Consulta SQL para actualizar
                            $sql = "UPDATE `tm_usuario` 
                        SET `nombreUsu` = '$CONDUCTOR_NOMBRE', 
                            `correoUsu` = '$correoCuenta', 
                            `provinciaUsu` = '$TRANSPORTISTA_PROVINCIA', 
                            `ciudadPuebloUsu` = '$TRANSPORTISTA_POBLACION', 
                            `codigoPostalUsu` = '$TRANSPORTISTA_CP', 
                            `direccionFacturacionUsu` = '$TRANSPORTISTA_DIRECCION', 
                            `rolUsu` = 0, 
                            `estUsu` = 1, 
                            `fecAltaUsu` = now(), 
                            `identificacionFiscalUsu` = '$CONDUCTOR_NIF'
                        WHERE `idTransportista_transportistas-Transporte` = '$CONDUCTOR_NIF'"; // Ajusta esta condición según tu caso

                            $json_string = json_encode($sql);
                            $file = 'limunisinaa.json';
                            file_put_contents($file, $json_string);
                        } else {
                            //CREAR CUENTA DE USUARIO
                            echo 'CUENTA DEL CONDUCTOR NO EXISTE <br>';
                            $tokenUsu = generarToken(30);
                            $sql = "INSERT INTO `tm_usuario` (`nickUsu`,`nombreUsu`, `apellidosUsu`, `fechaNacimientoUsu`, `telefonoUsu`, `movilUsu`, `correoUsu`, `senaUsu`,`avatarUsu`, `paisUsu`, `provinciaUsu`, `ciudadPuebloUsu`, `codigoPostalUsu`, `direccionFacturacionUsu`, `rolUsu`, `estUsu`, `fecAltaUsu`, `identificacionFiscalUsu`, `tokenUsu`, `idTransportista_transportistas-Transporte`) 
                    VALUES ('','$CONDUCTOR_NOMBRE','','','','','$correoCuenta',md5('$CONDUCTOR_NIF'),'userLeader.png','','$TRANSPORTISTA_PROVINCIA','$TRANSPORTISTA_POBLACION','$TRANSPORTISTA_CP','$TRANSPORTISTA_DIRECCION',0, 1, now(),'$CONDUCTOR_NIF','$tokenUsu','$CONDUCTOR_NIF')";

                            $sql = $conectar->prepare($sql);
                            $sql->execute();
                            $json_string = json_encode($sql);
                            $file = 'moto.json';
                            file_put_contents($file, $json_string);
                            $resultadoUsu = $sql->fetch(PDO::FETCH_ASSOC);
                            echo "Usuario insertado correctamente.";
                        }

                        // Verificar si CONDUCTOR ya existe
                        $conductorCreadoExist = "SELECT idTransportista, COUNT(*) as count FROM `transportistas-Transporte`  WHERE idTransportistaLeader = :idTransportistaLeader GROUP BY idTransportista";

                        $conductorCreadoExist = $conectar->prepare($conductorCreadoExist);
                        $conductorCreadoExist->bindParam(':idTransportistaLeader', $CONDUCTOR_NIF);
                        $conductorCreadoExist->execute();
                        $conductorCreadoExist = $conductorCreadoExist->fetch(PDO::FETCH_ASSOC);

                        $idUsuarioTransportista = is_null($conductorCreadoExist['CONDUCTOR_NIF']) ? 0 : $conductorCreadoExist['CONDUCTOR_NIF'];

                        $exists = $conductorCreadoExist['count'] > 0;
                        $idTransportistaSelect = $conductorCreadoExist['CONDUCTOR_NIF'];

                        if ($exists) {
                            echo 'EXISTE EL CONDUCTOR <br>';


                            $sql = "UPDATE `transportistas-Transporte`
                SET 
                    `idTransportistaLeader` = '$CONDUCTOR_NIF',
                    `emailTransportista` = '$CONDUCTOR_EMAIL',
                    `nombreTransportista` = '$CONDUCTOR_NOMBRE',
                    `direccionTransportista` = '$TRANSPORTISTA_DIRECCION',
                    `poblacionTransportista` = '$TRANSPORTISTA_POBLACION',
                    `provinciaTransportista` = '$TRANSPORTISTA_PROVINCIA',
                    `cpDireccionTransportista` = '$TRANSPORTISTA_CP',
                    `nifTransportista` = '$CONDUCTOR_NIF',
                    `tractoraTransportista` = '$TRACTORA'
                WHERE 
                    `idTransportistaLeader` =  '$idTransportistaSelect';
                ";


                            $sql = $conectar->prepare($sql);
                            $sql->execute();

                            $resultado = $sql->fetch(PDO::FETCH_ASSOC);
                            echo "Transportista updateado correctamente.";
                        } else {

                            echo 'NO EXISTE EL CONDUCTOR <br>';
                            // Preparar la consulta

                            $sql = "INSERT INTO `transportistas-Transporte` (`idUsuario_Transportista`, `idTransportistaLeader`, `emailTransportista`, `nombreTransportista`, `direccionTransportista`, `poblacionTransportista`, `provinciaTransportista`, `cpDireccionTransportista`, `nifTransportista`, `tractoraTransportista`) VALUES ('$idUsuarioTransportista', '$CONDUCTOR_NIF',  '$CONDUCTOR_EMAIL', '$CONDUCTOR_NOMBRE','$TRANSPORTISTA_DIRECCION','$TRANSPORTISTA_POBLACION', '$TRANSPORTISTA_PROVINCIA', '$TRANSPORTISTA_CP', '$CONDUCTOR_NIF', '$TRACTORA')";

                            $sql = $conectar->prepare($sql);
                            $sql->execute();

                            $resultado = $sql->fetch(PDO::FETCH_ASSOC);
                            echo "Transportista insertado correctamente.";

                            /*    

                    echo "Usuario insertado correctamente."; */
                        }




                        //=====================================================//
                        //=====================================================//
                        //=====================================================//
                        //=====================================================//
                        echo '<br>--------------------------------------------------------------------<br>';
                        echo '--------------------------------------------------------------------<br>';
                        echo '--------------------------------------------------------------------<br>';
                        //================================================//
                        //================================================//
                        //================================================//

                        //================================================//
                        //  Recorrer cada objeto dentro del archivo JSON  //
                        //================================================//


                        /* foreach ($dato as $dato) { */

                        $TTE_COD = $datos['TTE_COD'];
                        $numeroTransporte = $datos['TTE_ORDEN'];
                        $textoNumeroOrden = $datos['TTE_ORDEN'];
                        $idTransportista = $CONDUCTOR_NIF;
                        $idCliente = 00;
                        $nombreTransportista = $datos['CONDUCTOR_NOMBRE'];
                        date_default_timezone_set('Europe/Madrid');
                        $fechaActual = date('Y-m-d H:i:s');


                        // Uso de la función para generar un token de 30 caracteres
                        $tokenOrden = generarToken(30);

                        // Verificar y mostrar el valor de 'TTE_TERRESTRE'
                        $TTE_TERRESTRE = $datos['TTE_TERRESTRE'];

                        // Verificar y mostrar el valor de 'TTE_MULTIMODAL'
                        $TTE_MULTIMODAL = $datos['TTE_MULTIMODAL'];

                        // Determinar el tipo de orden de transporte
                        // C = CONTENEDOR / T = TERRESTRE / M = MULTIMODAL / X = NO DEFINIDO
                        if ($TTE_TERRESTRE === false && $TTE_MULTIMODAL === false) {
                            $tipoOrdenTransporte = 'C';
                        } elseif ($TTE_TERRESTRE === true && $TTE_MULTIMODAL === false) {
                            $tipoOrdenTransporte = 'T';
                        } elseif ($TTE_TERRESTRE === false && $TTE_MULTIMODAL === true) {
                            $tipoOrdenTransporte = 'M';
                        } else {
                            $tipoOrdenTransporte = 'X'; // Caso de valor indefinido
                        }

                        echo 'El TIPO TRANSPORTE ES: ' . $tipoOrdenTransporte . '<br>';

                        // Mostrar el resultado para verificación
                        $contenedorActivo = $datos['MATRICULA'];
                        $precintoActivo = $datos['PRECINTO'];

                        $tipoContenedor = '';

                        if ($tipoOrdenTransporte == 'C') {

                            $fechaEstimada = $datos['TTE_FECHA_ESTIMADA_RECOGIDA'];

                            // Verificar si hay datos en LUGARES_CARGA
                            if (isset($datos['LUGARES']) && count($datos['LUGARES']) > 0) {
                                // Obtener el primer objeto de LUGARES_CARGA
                                $primerLugar = $datos['LUGARES'][0];

                                // Obtener el valor de LUGAR_NOMBRE
                                $puerto_origen = $primerLugar['LUGAR_NOMBRE'];

                                echo "El primer LUGAR_NOMBRE es: " . $puerto_origen;
                            } else {
                                $puerto_origen = 'No Indicado';
                                echo "No hay datos en LUGARES_CARGA.";
                            }
                        } else if ($tipoOrdenTransporte == 'T') {

                            $fechaEstimada = $datos['TTE_FECHA_CARGA'];

                            // Verificar si hay datos en LUGARES_CARGA
                            if (isset($datos['LUGARES_CARGA']) && count($datos['LUGARES_CARGA']) > 0) {
                                // Obtener el primer objeto de LUGARES_CARGA
                                $primerLugar = $datos['LUGARES_CARGA'][0];

                                // Obtener el valor de LUGAR_NOMBRE
                                $puerto_origen = $primerLugar['LUGAR_NOMBRE'];
                                echo "El primer LUGAR_NOMBRE es: " . $puerto_origen;
                            } else {
                                $puerto_origen = 'No Indicado';
                                echo "No hay datos en LUGARES_CARGA.";
                            }
                        } else {

                            $fechaEstimada = $datos['TTE_FECHA_CARGA'];
                            $puerto_origen = $datos['LUGAR_COMIENZO_NOMBRE'];
                            echo 'El primer LUGAR_NOMBRE: ' . $puerto_origen;
                        }



                        if ($TTE_COD == NULL || $TTE_COD == '') {

                            $errorText = 'Error: Faltan datos en el archivo: ' . $archivo . ' <br>';
                        } else {

                            // Convertir el array en formato JSON
                            $jsonOrdenTransporte = json_encode($datos);

                            // Verificar si TTE_COD ya existe
                            /*                     $sqlCheck = "SELECT idOrden, COUNT(*) as count FROM `orden-Transporte` WHERE `TTE_COD` = :tte_cod";
 */
                            $sqlCheck = "SELECT idOrden, COUNT(*) as count  FROM `orden-Transporte` WHERE `TTE_COD` = :tte_cod GROUP BY idOrden";

                            $stmtCheck = $conectar->prepare($sqlCheck);
                            $stmtCheck->bindParam(':tte_cod', $TTE_COD);
                            $stmtCheck->execute();
                            $selectOrdenExist = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                            $exists = $selectOrdenExist['count'];
                            $lastId = $selectOrdenExist['idOrden'];

                            echo 'EXISTE: ' . $exists . '<br>'; // Esto es solo para depuración, puedes eliminarlo después de verificar que funciona correctamente

                            //=====================================================//
                            //=====================================================//
                            //=============== AÑADIR ORDEN C Y T ==================//
                            //=====================================================//
                            //=====================================================//


                            if ($exists) {
                                // Realizar UPDATE

                                $sqlUpdateOrder = "UPDATE `orden-Transporte` 
                                SET `TTE_COD` = :TTE_COD, 
                                    `textoNumeroOrden` = :textoNumeroOrden, 
                                    `jsonOrdenTransporte` = :jsonOrdenTransporte, 
                                    `id_Transportista` = :idTransportista, 
                                    `nombreTransportista_ordenTransporte` = :nombreTransportista, 
                                    `puerto_origen` = :puerto_origen, 
                                    `fechaUpdate` = :fechaActual, 
                                    `tipoOrdenTransporte` = :tipoOrdenTransporte, 
                                    `tokenOrden` = :tokenOrden , 
                                    `LUGAR_COMIENZO` = :LUGAR_COMIENZO_NOMBRE , 
                                    `LUGAR_FIN` = :LUGAR_FIN_NOMBRE,
                                    `fechaOrdenViaje` = :fechaOrdenViaje  
                                WHERE `num_transporte` = :numeroTransporte";

                                $stmt = $conectar->prepare($sqlUpdateOrder);

                                // Bind de parámetros
                                $stmt->bindParam(':TTE_COD', $TTE_COD);
                                $stmt->bindParam(':textoNumeroOrden', $textoNumeroOrden);
                                $stmt->bindParam(':jsonOrdenTransporte', $jsonOrdenTransporte);
                                $stmt->bindParam(':idTransportista', $idTransportista);
                                $stmt->bindParam(':nombreTransportista', $nombreTransportista);
                                $stmt->bindParam(':puerto_origen', $puerto_origen);
                                $stmt->bindParam(':fechaActual', $fechaActual);
                                $stmt->bindParam(':tipoOrdenTransporte', $tipoOrdenTransporte); // Este valor debe ser definido correctamente
                                $stmt->bindParam(':tokenOrden', $tokenOrden);
                                $stmt->bindParam(':numeroTransporte', $numeroTransporte);
                                $stmt->bindParam(':LUGAR_COMIENZO_NOMBRE', $LUGAR_COMIENZO_NOMBRE);
                                $stmt->bindParam(':LUGAR_FIN_NOMBRE', $LUGAR_FIN_NOMBRE);
                                $stmt->bindParam(':fechaOrdenViaje', $fechaEstimada);

                                echo 'EDITANDO ORDEN NUMERO: ' . $TTE_COD . '<br>';
                                // Ejecutar la consulta
                                $stmt->execute();
                            } else {
                                // Realizar INSERT
                                // Realizar INSERT
                                $sqlInsertOrder = "INSERT INTO `orden-Transporte` 
                        (`TTE_COD`, `num_transporte`, `textoNumeroOrden`, `jsonOrdenTransporte`, `id_Transportista`, `contenedorActivo`, `precintoActivo`,`tipoContenedor`, `nombreTransportista_ordenTransporte`, `puerto_origen`, `fechaCreacion`, `fechaInactivo`, `fechaUpdate`, `tipoOrdenTransporte`, `estOrden`, `tokenOrden`, `LUGAR_COMIENZO`, `LUGAR_FIN`, `fechaOrdenViaje`, `nombreCliente`, `correoCliente`, `dniCliente`, `firmaCliente`) 
                        VALUES 
                        (:TTE_COD, :numeroTransporte, :textoNumeroOrden, :jsonOrdenTransporte, :idTransportista, :contenedorActivo, :precintoActivo, :tipoContenedor, :nombreTransportista, :puerto_origen, :fechaActual, null, null, :tipoOrdenTransporte, '1', :tokenOrden, :LUGAR_COMIENZO, :LUGAR_FIN, :fechaOrdenViaje, :nombreCliente, :correoCliente, :dniCliente, :firmaCliente)";

                                $stmt = $conectar->prepare($sqlInsertOrder);

                                // Bind de parámetros
                                $stmt->bindParam(':TTE_COD', $TTE_COD);
                                $stmt->bindParam(':numeroTransporte', $numeroTransporte);
                                $stmt->bindParam(':textoNumeroOrden', $textoNumeroOrden);
                                $stmt->bindParam(':jsonOrdenTransporte', $jsonOrdenTransporte);
                                $stmt->bindParam(':idTransportista', $idTransportista);
                                $stmt->bindParam(':contenedorActivo', $contenedorActivo);
                                $stmt->bindParam(':precintoActivo', $precintoActivo);
                                $stmt->bindParam(':tipoContenedor', $tipoContenedor);
                                $stmt->bindParam(':nombreTransportista', $nombreTransportista);
                                $stmt->bindParam(':puerto_origen', $puerto_origen);
                                $stmt->bindParam(':fechaActual', $fechaActual);
                                $stmt->bindParam(':tipoOrdenTransporte', $tipoOrdenTransporte);
                                $stmt->bindParam(':tokenOrden', $tokenOrden);
                                $stmt->bindParam(':LUGAR_COMIENZO', $LUGAR_COMIENZO_NOMBRE);
                                $stmt->bindParam(':LUGAR_FIN', $LUGAR_FIN_NOMBRE);
                                $stmt->bindParam(':fechaOrdenViaje', $fechaEstimada);
                                $stmt->bindValue(':nombreCliente', null, PDO::PARAM_STR);
                                $stmt->bindValue(':correoCliente', null, PDO::PARAM_STR);
                                $stmt->bindValue(':dniCliente', null, PDO::PARAM_STR);
                                $stmt->bindValue(':firmaCliente', null, PDO::PARAM_STR);

                                // Obtener el último ID insertado
                                echo ' INSERTANDO ORDEN NUEVA NUMERO: ' . $TTE_COD . '<br>';
                                // Ejecutar la consulta
                                $stmt->execute();
                                $lastId = $conectar->lastInsertId();
                            }
                            //=====================================================//
                            //=====================================================//
                            //=====================================================//
                            //=====================================================//


                            $contenedorActivo = '';
                            $precintoActivo = '';
                            $tipoContenedor = '';



                            // Imprimir el último ID para verificar
                            echo "El último ID insertado es: " . $lastId . '<br>';
                            $viajeOrden = 1; // ES INDICATIVO PARA SABER QUE NUMERO DE VIAJE ES, EL VIAJE 1..2..3..4

                            // ELIMINAMOS LOS VIAJES PARA IMPORTARLOS DE NUEVO

                            // Procesar los datos de LUGARES_CARGA
                            // DEPENDIENDO DEL TIPO DE ARCHIVO, LOS LUGARES ESTÁN ESCRITO DIFERENTES. 
                            if ($tipoOrdenTransporte == 'C') {

                                /* $sqlEliminarViajes = "DELETE FROM `viaje-Transporte` WHERE id_OrdenViajeTransporte = $lastId;";
                    $sqlEliminarViajes = $conectar->prepare($sqlEliminarViajes);
                    $sqlEliminarViajes->execute();
                    echo "Eliminando viaje de Orden ".$lastId; */




                                if (isset($datos['LUGARES']) && is_array($datos['LUGARES'])) {
                                    foreach ($datos['LUGARES'] as $lugar) {
                                        $LUGAR_COD = isset($lugar['LUGAR_COD']) ? $lugar['LUGAR_COD'] : null;
                                        $LUGAR_NOMBRE = isset($lugar['LUGAR_NOMBRE']) ? $lugar['LUGAR_NOMBRE'] : '';
                                        $LUGAR_DIRECCION = isset($lugar['LUGAR_DIRECCION']) ? $lugar['LUGAR_DIRECCION'] : '';
                                        $LUGAR_CP = isset($lugar['LUGAR_CP']) ? $lugar['LUGAR_CP'] : '';
                                        $LUGAR_POBLACION = isset($lugar['LUGAR_POBLACION']) ? $lugar['LUGAR_POBLACION'] : '';
                                        $LUGAR_PROVINCIA = isset($lugar['LUGAR_PROVINCIA']) ? $lugar['LUGAR_PROVINCIA'] : '';
                                        $LUGAR_TELEFONO = isset($lugar['LUGAR_TELEFONO']) ? $lugar['LUGAR_TELEFONO'] : '';

                                        // Verificar si el viaje ya existe
                                        $sqlCheckViaje = "SELECT COUNT(*) as count  FROM `viaje-Transporte` WHERE `LUGAR_COD` = :LUGAR_COD AND `id_OrdenViajeTransporte` = :id_OrdenViajeTransporte";
                                        $stmtCheckViaje = $conectar->prepare($sqlCheckViaje);
                                        $stmtCheckViaje->bindParam(':LUGAR_COD', $LUGAR_COD);
                                        $stmtCheckViaje->bindParam(':id_OrdenViajeTransporte', $lastId);
                                        $stmtCheckViaje->execute();
                                        $selectViajeExist = $stmtCheckViaje->fetch(PDO::FETCH_ASSOC);

                                        $viajeExists = $selectViajeExist['count'];

                                        if ($viajeExists) {
                                            // Realizar UPDATE
                                            $sqlUpdateViaje = "UPDATE `viaje-Transporte` 
                                                SET `LUGAR_NOMBRE` = :LUGAR_NOMBRE, 
                                                    `LUGAR_DIRECCION` = :LUGAR_DIRECCION, 
                                                    `LUGAR_CP` = :LUGAR_CP, 
                                                    `LUGAR_POBLACION` = :LUGAR_POBLACION, 
                                                    `LUGAR_PROVINCIA` = :LUGAR_PROVINCIA, 
                                                    `LUGAR_TELEFONO` = :LUGAR_TELEFONO 
                                                WHERE `LUGAR_COD` = :LUGAR_COD AND `id_OrdenViajeTransporte` = :id_OrdenViajeTransporte";

                                            $stmtUpdateViaje = $conectar->prepare($sqlUpdateViaje);
                                            $stmtUpdateViaje->bindParam(':LUGAR_NOMBRE', $LUGAR_NOMBRE);
                                            $stmtUpdateViaje->bindParam(':LUGAR_DIRECCION', $LUGAR_DIRECCION);
                                            $stmtUpdateViaje->bindParam(':LUGAR_CP', $LUGAR_CP);
                                            $stmtUpdateViaje->bindParam(':LUGAR_POBLACION', $LUGAR_POBLACION);
                                            $stmtUpdateViaje->bindParam(':LUGAR_PROVINCIA', $LUGAR_PROVINCIA);
                                            $stmtUpdateViaje->bindParam(':LUGAR_TELEFONO', $LUGAR_TELEFONO);
                                            $stmtUpdateViaje->bindParam(':LUGAR_COD', $LUGAR_COD);
                                            $stmtUpdateViaje->bindParam(':id_OrdenViajeTransporte', $lastId);

                                            $stmtUpdateViaje->execute();
                                            echo ' ACTUALIZANDO VIAJE<br>';
                                        } else {
                                            // Realizar INSERT
                                            $sqlInsertViaje = "INSERT INTO `viaje-Transporte`
                                                    (`id_OrdenViajeTransporte`,	`numeroOrdenViaje`, `fechaLlegadaViaje`, `fechaSalidaViaje`, `ObservacionViaje`, `FirmaViajeReceptor`, `documentoManual`, `LUGAR_COD`, `LUGAR_NOMBRE`, `LUGAR_DIRECCION`, `LUGAR_CP`, `LUGAR_POBLACION`, `LUGAR_PROVINCIA`, `LUGAR_TELEFONO`, `ordenViaje`, `tipoViaje`)
                                                    VALUES
                                                    (:id_OrdenViajeTransporte,:numeroTransporte, null, null, null, null, null, :LUGAR_COD, :LUGAR_NOMBRE, :LUGAR_DIRECCION, :LUGAR_CP, :LUGAR_POBLACION, :LUGAR_PROVINCIA, :LUGAR_TELEFONO, :ordenViaje, null)";

                                            $stmtInsertViaje = $conectar->prepare($sqlInsertViaje);
                                            $stmtInsertViaje->bindParam(':id_OrdenViajeTransporte', $lastId);
                                            $stmtInsertViaje->bindParam(':numeroTransporte', $numeroTransporte);
                                            $stmtInsertViaje->bindParam(':LUGAR_COD', $LUGAR_COD);
                                            $stmtInsertViaje->bindParam(':LUGAR_NOMBRE', $LUGAR_NOMBRE);
                                            $stmtInsertViaje->bindParam(':LUGAR_DIRECCION', $LUGAR_DIRECCION);
                                            $stmtInsertViaje->bindParam(':LUGAR_CP', $LUGAR_CP);
                                            $stmtInsertViaje->bindParam(':LUGAR_POBLACION', $LUGAR_POBLACION);
                                            $stmtInsertViaje->bindParam(':LUGAR_PROVINCIA', $LUGAR_PROVINCIA);
                                            $stmtInsertViaje->bindParam(':LUGAR_TELEFONO', $LUGAR_TELEFONO);
                                            $stmtInsertViaje->bindParam(':ordenViaje', $viajeOrden);

                                            $stmtInsertViaje->execute();
                                            echo ' INSERTANDO VIAJE<br> ';
                                        }

                                        $viajeOrden++;
                                    }
                                } else {
                                    echo 'El JSON no contiene el array "LUGARES" o no es válido.<br>';
                                }
                            } else if ($tipoOrdenTransporte == 'T' || $tipoOrdenTransporte == 'M') {

                                echo 'T DE TERRESTREE<br>';

                                /* $sqlEliminarViajes = "DELETE FROM `viaje-Transporte` WHERE id_OrdenViajeTransporte = $lastId;";
                    $sqlEliminarViajes = $conectar->prepare($sqlEliminarViajes);
                    $sqlEliminarViajes->execute();
                    echo "Eliminando viaje de Orden ".$lastId; */


                                if (isset($datos['LUGARES_CARGA']) && is_array($datos['LUGARES_CARGA'])) {
                                    foreach ($datos['LUGARES_CARGA'] as $lugar) {
                                        $LUGAR_COD = isset($lugar['LUGAR_COD']) ? $lugar['LUGAR_COD'] : null;
                                        $LUGAR_NOMBRE = isset($lugar['LUGAR_NOMBRE']) ? $lugar['LUGAR_NOMBRE'] : '';
                                        $LUGAR_DIRECCION = isset($lugar['LUGAR_DIRECCION']) ? $lugar['LUGAR_DIRECCION'] : '';
                                        $LUGAR_CP = isset($lugar['LUGAR_CP']) ? $lugar['LUGAR_CP'] : '';
                                        $LUGAR_POBLACION = isset($lugar['LUGAR_POBLACION']) ? $lugar['LUGAR_POBLACION'] : '';
                                        $LUGAR_PROVINCIA = isset($lugar['LUGAR_PROVINCIA']) ? $lugar['LUGAR_PROVINCIA'] : '';
                                        $LUGAR_TELEFONO = isset($lugar['LUGAR_TELEFONO']) ? $lugar['LUGAR_TELEFONO'] : '';

                                        // Verificar si el viaje ya existe
                                        $sqlCheckViaje = "SELECT COUNT(*) as count FROM `viaje-Transporte` WHERE `LUGAR_COD` = :LUGAR_COD AND `id_OrdenViajeTransporte` = :id_OrdenViajeTransporte";
                                        $stmtCheckViaje = $conectar->prepare($sqlCheckViaje);
                                        $stmtCheckViaje->bindParam(':LUGAR_COD', $LUGAR_COD);
                                        $stmtCheckViaje->bindParam(':id_OrdenViajeTransporte', $lastId);
                                        $stmtCheckViaje->execute();
                                        $selectViajeExist = $stmtCheckViaje->fetch(PDO::FETCH_ASSOC);

                                        $viajeExists = $selectViajeExist['count'];

                                        if ($viajeExists) {
                                            // Realizar UPDATE
                                            $sqlUpdateViaje = "UPDATE `viaje-Transporte` 
                                                SET `LUGAR_NOMBRE` = :LUGAR_NOMBRE, 
                                                    `LUGAR_DIRECCION` = :LUGAR_DIRECCION, 
                                                    `LUGAR_CP` = :LUGAR_CP, 
                                                    `LUGAR_POBLACION` = :LUGAR_POBLACION, 
                                                    `LUGAR_PROVINCIA` = :LUGAR_PROVINCIA, 
                                                    `LUGAR_TELEFONO` = :LUGAR_TELEFONO 
                                                WHERE `LUGAR_COD` = :LUGAR_COD AND `id_OrdenViajeTransporte` = :id_OrdenViajeTransporte";

                                            $stmtUpdateViaje = $conectar->prepare($sqlUpdateViaje);
                                            $stmtUpdateViaje->bindParam(':LUGAR_NOMBRE', $LUGAR_NOMBRE);
                                            $stmtUpdateViaje->bindParam(':LUGAR_DIRECCION', $LUGAR_DIRECCION);
                                            $stmtUpdateViaje->bindParam(':LUGAR_CP', $LUGAR_CP);
                                            $stmtUpdateViaje->bindParam(':LUGAR_POBLACION', $LUGAR_POBLACION);
                                            $stmtUpdateViaje->bindParam(':LUGAR_PROVINCIA', $LUGAR_PROVINCIA);
                                            $stmtUpdateViaje->bindParam(':LUGAR_TELEFONO', $LUGAR_TELEFONO);
                                            $stmtUpdateViaje->bindParam(':LUGAR_COD', $LUGAR_COD);
                                            $stmtUpdateViaje->bindParam(':id_OrdenViajeTransporte', $lastId);

                                            $stmtUpdateViaje->execute();
                                            echo ' ACTUALIZANDO VIAJE LUGARES_CARGA<br>';
                                        } else {
                                            // Realizar INSERT
                                            $sqlInsertViaje = "INSERT INTO `viaje-Transporte`
                                                    (`id_OrdenViajeTransporte`,`numeroOrdenViaje`, `fechaLlegadaViaje`, `fechaSalidaViaje`, `ObservacionViaje`, `FirmaViajeReceptor`, `documentoManual`, `LUGAR_COD`, `LUGAR_NOMBRE`, `LUGAR_DIRECCION`, `LUGAR_CP`, `LUGAR_POBLACION`, `LUGAR_PROVINCIA`, `LUGAR_TELEFONO`, `ordenViaje`, `tipoViaje`)
                                                    VALUES
                                                    (:id_OrdenViajeTransporte,:numeroTransporte, null, null, null, null, null, :LUGAR_COD, :LUGAR_NOMBRE, :LUGAR_DIRECCION, :LUGAR_CP, :LUGAR_POBLACION, :LUGAR_PROVINCIA, :LUGAR_TELEFONO, :ordenViaje, 'CARGA')";

                                            $stmtInsertViaje = $conectar->prepare($sqlInsertViaje);
                                            $stmtInsertViaje->bindParam(':id_OrdenViajeTransporte', $lastId);
                                            $stmtInsertViaje->bindParam(':numeroTransporte', $numeroTransporte);
                                            $stmtInsertViaje->bindParam(':LUGAR_COD', $LUGAR_COD);
                                            $stmtInsertViaje->bindParam(':LUGAR_NOMBRE', $LUGAR_NOMBRE);
                                            $stmtInsertViaje->bindParam(':LUGAR_DIRECCION', $LUGAR_DIRECCION);
                                            $stmtInsertViaje->bindParam(':LUGAR_CP', $LUGAR_CP);
                                            $stmtInsertViaje->bindParam(':LUGAR_POBLACION', $LUGAR_POBLACION);
                                            $stmtInsertViaje->bindParam(':LUGAR_PROVINCIA', $LUGAR_PROVINCIA);
                                            $stmtInsertViaje->bindParam(':LUGAR_TELEFONO', $LUGAR_TELEFONO);
                                            $stmtInsertViaje->bindParam(':ordenViaje', $viajeOrden);

                                            $stmtInsertViaje->execute();
                                            echo ' INSERTANDO VIAJE LUGARES_CARGA<br> ';
                                        }

                                        $viajeOrden++;
                                    }
                                } else {
                                    echo 'El JSON no contiene el array "LUGARES_CARGA" o no es válido.<br>';
                                }



                                if (isset($datos['LUGARES_DESCARGA']) && is_array($datos['LUGARES_DESCARGA'])) {
                                    foreach ($datos['LUGARES_DESCARGA'] as $lugar) {
                                        $LUGAR_COD = isset($lugar['LUGAR_COD']) ? $lugar['LUGAR_COD'] : null;
                                        $LUGAR_NOMBRE = isset($lugar['LUGAR_NOMBRE']) ? $lugar['LUGAR_NOMBRE'] : '';
                                        $LUGAR_DIRECCION = isset($lugar['LUGAR_DIRECCION']) ? $lugar['LUGAR_DIRECCION'] : '';
                                        $LUGAR_CP = isset($lugar['LUGAR_CP']) ? $lugar['LUGAR_CP'] : '';
                                        $LUGAR_POBLACION = isset($lugar['LUGAR_POBLACION']) ? $lugar['LUGAR_POBLACION'] : '';
                                        $LUGAR_PROVINCIA = isset($lugar['LUGAR_PROVINCIA']) ? $lugar['LUGAR_PROVINCIA'] : '';
                                        $LUGAR_TELEFONO = isset($lugar['LUGAR_TELEFONO']) ? $lugar['LUGAR_TELEFONO'] : '';

                                        // Verificar si el viaje ya existe
                                        $sqlCheckViaje = "SELECT COUNT(*) as count FROM `viaje-Transporte` WHERE `LUGAR_COD` = :LUGAR_COD AND `id_OrdenViajeTransporte` = :id_OrdenViajeTransporte";
                                        $stmtCheckViaje = $conectar->prepare($sqlCheckViaje);
                                        $stmtCheckViaje->bindParam(':LUGAR_COD', $LUGAR_COD);
                                        $stmtCheckViaje->bindParam(':id_OrdenViajeTransporte', $lastId);
                                        $stmtCheckViaje->execute();
                                        $selectViajeExist = $stmtCheckViaje->fetch(PDO::FETCH_ASSOC);

                                        $viajeExists = $selectViajeExist['count'];

                                        if ($viajeExists) {
                                            // Realizar UPDATE
                                            $sqlUpdateViaje = "UPDATE `viaje-Transporte` 
                                                SET `LUGAR_NOMBRE` = :LUGAR_NOMBRE, 
                                                    `LUGAR_DIRECCION` = :LUGAR_DIRECCION, 
                                                    `LUGAR_CP` = :LUGAR_CP, 
                                                    `LUGAR_POBLACION` = :LUGAR_POBLACION, 
                                                    `LUGAR_PROVINCIA` = :LUGAR_PROVINCIA, 
                                                    `LUGAR_TELEFONO` = :LUGAR_TELEFONO 
                                                WHERE `LUGAR_COD` = :LUGAR_COD AND `id_OrdenViajeTransporte` = :id_OrdenViajeTransporte";

                                            $stmtUpdateViaje = $conectar->prepare($sqlUpdateViaje);
                                            $stmtUpdateViaje->bindParam(':LUGAR_NOMBRE', $LUGAR_NOMBRE);
                                            $stmtUpdateViaje->bindParam(':LUGAR_DIRECCION', $LUGAR_DIRECCION);
                                            $stmtUpdateViaje->bindParam(':LUGAR_CP', $LUGAR_CP);
                                            $stmtUpdateViaje->bindParam(':LUGAR_POBLACION', $LUGAR_POBLACION);
                                            $stmtUpdateViaje->bindParam(':LUGAR_PROVINCIA', $LUGAR_PROVINCIA);
                                            $stmtUpdateViaje->bindParam(':LUGAR_TELEFONO', $LUGAR_TELEFONO);
                                            $stmtUpdateViaje->bindParam(':LUGAR_COD', $LUGAR_COD);
                                            $stmtUpdateViaje->bindParam(':id_OrdenViajeTransporte', $lastId);

                                            $stmtUpdateViaje->execute();
                                            echo ' ACTUALIZANDO VIAJE LUGARES_DESCARGA<br>';
                                        } else {
                                            // Realizar INSERT
                                            $sqlInsertViaje = "INSERT INTO `viaje-Transporte`
                                                    (`id_OrdenViajeTransporte`, `numeroOrdenViaje`, `fechaLlegadaViaje`, `fechaSalidaViaje`, `ObservacionViaje`, `FirmaViajeReceptor`, `documentoManual`, `LUGAR_COD`, `LUGAR_NOMBRE`, `LUGAR_DIRECCION`, `LUGAR_CP`, `LUGAR_POBLACION`, `LUGAR_PROVINCIA`, `LUGAR_TELEFONO`, `ordenViaje`, `tipoViaje`)
                                                    VALUES
                                                    (:id_OrdenViajeTransporte,:numeroOrdenViaje, null, null, null, null, null, :LUGAR_COD, :LUGAR_NOMBRE, :LUGAR_DIRECCION, :LUGAR_CP, :LUGAR_POBLACION, :LUGAR_PROVINCIA, :LUGAR_TELEFONO, :ordenViaje, 'DESCARGA')";

                                            $stmtInsertViaje = $conectar->prepare($sqlInsertViaje);
                                            $stmtInsertViaje->bindParam(':id_OrdenViajeTransporte', $lastId);
                                            $stmtInsertViaje->bindParam(':numeroOrdenViaje', $numeroTransporte);
                                            $stmtInsertViaje->bindParam(':LUGAR_COD', $LUGAR_COD);
                                            $stmtInsertViaje->bindParam(':LUGAR_NOMBRE', $LUGAR_NOMBRE);
                                            $stmtInsertViaje->bindParam(':LUGAR_DIRECCION', $LUGAR_DIRECCION);
                                            $stmtInsertViaje->bindParam(':LUGAR_CP', $LUGAR_CP);
                                            $stmtInsertViaje->bindParam(':LUGAR_POBLACION', $LUGAR_POBLACION);
                                            $stmtInsertViaje->bindParam(':LUGAR_PROVINCIA', $LUGAR_PROVINCIA);
                                            $stmtInsertViaje->bindParam(':LUGAR_TELEFONO', $LUGAR_TELEFONO);
                                            $stmtInsertViaje->bindParam(':ordenViaje', $viajeOrden);

                                            $stmtInsertViaje->execute();
                                            echo ' INSERTANDO VIAJE LUGARES_DESCARGA<br> ';
                                        }

                                        $viajeOrden++;
                                    }
                                } else {
                                    echo 'El JSON no contiene el array "LUGARES_CARGA" o no es válido.<br>';
                                }
                            }





                            echo '<p style="font-family: Arial, sans-serif; color: #800080; font-size: 15px; margin-bottom: 5px;">' . $numeroTransporte . ' - Orden Añadida BD: ' . $archivo . ' SQL: ' . $sqlInsertOrder . '</p><br>';
                        }


                        //==============================================================//
                        //==============================================================//
                        //==============================================================//

                        //==============================================================//
                        //  Mover el archivo a la carpeta de destino una vez finalizado //
                        //==============================================================//
                        /*   if (rename($archivo, 'processed/' . basename($archivo))) {

                    echo '<p style="font-family: Arial, sans-serif; color: #80ff00; font-size: 10px; margin-bottom: 5px;">El archivo '.$archivo.' fue movido exitosamente.</p>';

                } else {
                    echo '<p style="font-family: Arial, sans-serif; color: #ff0000; font-size: 10px; margin-bottom: 5px;">Hubo un error al mover el archivo '.$archivo.'.</p>';

                }   */
                        //==============================================================//
                        //==============================================================//
                        //==============================================================//


                        if ($errorText != '0') {
                            echo $errorText;
                            generarLog('ERROR: ', $errorText);
                        }
                        echo '<hr>';
                    }
                }
                $contador++;
            }

            echo '<hr>';

            //===========================================//
            // FUERA DE LA CONEXIÓN - SI FALLA CONEXION //
            //===========================================//

        } catch (PDOException $e) {
            // Captura y maneja cualquier excepción PDO
            echo "Error de conexión: " . $e->getMessage();
            generarLog('ERROR', $e->getMessage());
        }
        ?>

    </div>

</body>

</html>