<?php
    $formato_factura = isset($_GET['formato_factura']) ? $_GET['formato_factura'] : 'articulos';

   
    if($formato_factura == 'articulos' || $formato_factura == 'oro_inversion'){
        require_once 'fiskaly_actions/check_request.php';
    }else{
        require_once 'fiskaly_actions/check_request_renovaciones.php';
    }
    
    require_once 'fiskaly_actions/check_entorno.php';

    
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Generando factura <?php echo $url_completa_origen; ?></title>
<link rel="icon" type="image/x-icon" href="assets/img/icons/app/favicon.ico" />
    <link rel="stylesheet" href="assets/vendor/libs/spinkit/spinkit.css" />
    <style>
        #loaderContainer{
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #F7F8F8!important;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .sk-circle-fade {
            width: 120px !important;
            height: 120px !important;
        }

        :root {
        --sk-size: 30px;
        --sk-color:  #007bff !important;
        }
        
        @keyframes fadeInOut {
            0% { opacity: 1; }
            50% { opacity: 0.3; }
            100% { opacity: 1; }
        }

        #textLoader{
            font-size: 16px !important;
            font-weight: 600 !important;
            color: #007bff !important;
            margin-top: 60px !important;
            text-align: center !important;
            animation: fadeInOut 2s ease-in-out infinite;
            font-family: 'Roboto', sans-serif !important;
        }
    </style>
</head>

<body>
    <div class="loader-container" id="loaderContainer">
      <div class="sk-circle-fade sk-primary">
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        <div class="sk-circle-fade-dot"></div>
        
      </div>
      <div class="text-center" id="textLoader">Generando factura... aguarde</div>
    </div>
    <input type="hidden" id="url_completa_origen" value="<?php echo $url_completa_origen; ?>">
    <?php
        if ($url_api_fiskaly) {
            echo "<script>window.urlApiFiskaly = '{$url_api_fiskaly}';</script>";
          } else {
            echo '<div class="alert alert-danger">URL de la API de Fiskaly no encontrada</div>';
            $url_api_fiskaly = null;
          }
    ?>
    <?php if($formato_factura == 'articulos' || $formato_factura == 'oro_inversion'){ ?>
        <script src="fiskaly_actions/fiskaly_events.js?v=<?php echo time(); ?>"></script>
    <?php }else{ ?>
        <script src="fiskaly_actions/fiskaly_events_renovaciones.js?v=<?php echo time(); ?>"></script>
    <?php } ?>

    <script src="assets/js/pwa-install.js?v=<?php echo time(); ?>"></script>
</body>
</html>