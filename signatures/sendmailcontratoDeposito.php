<?php 
// ENVIO EL EMAIL CON EL PDF ADJUNTO
	$dia = date("d.m.Y");
	$hora = date("H:i:s");
    $currentyear = date("Y");
	$email = "jorgedeboli@gmail.com";
    $appSuportEmail = "cashsegunda@appsegundamano.com";
    $titulodocumento = "Contrato-deposito";
    $appName = "Recycle company";

	$message = "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'> 
    <meta name='viewport' content='width=device-width'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='x-apple-disable-message-reformatting'> 
    <title></title> 
    <style>

        html,
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }
		h3{
			font-family:  Helvetica, Arial, sans-serif;
		}

        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        div[style*='margin: 16px 0'] {
            margin: 0 !important;
        }

        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }
        table table table {
            table-layout: auto;
        }

        img {
            -ms-interpolation-mode:bicubic;
        }

        *[x-apple-data-detectors], 
        .x-gmail-data-detectors,    
        .x-gmail-data-detectors *,
        .aBn {
            border-bottom: 0 !important;
            cursor: default !important;
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        .a6S {
            display: none !important;
            opacity: 0.01 !important;
        }
        img.g-img + div {
            display: none !important;
        }

        .button-link {
            text-decoration: none !important;
        }

        
        @media only screen and (min-device-width: 375px) and (max-device-width: 413px) { 
            .email-container {
                min-width: 375px !important;
            }
        }

    </style>
    <style>

    .button-td,
    .button-a {
        transition: all 100ms ease-in;
    }
    .button-td:hover,
    .button-a:hover {
        background: #555555 !important;
        border-color: #555555 !important;
    }

    @media screen and (max-width: 600px) {

        .email-container p {
            font-size: 17px !important;
            line-height: 22px !important;
        }

    }

    </style>

    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->

</head>
<body width='100%' bgcolor='#eeeeee' style='margin: 0; mso-line-height-rule: exactly;'>
   <h1>pdf enviado por email</h1>
</body>
</html>"; 

	$asunto = '&#x2605; Tu '.$titulodocumento.' de '.$appName;
	$subject=  mb_convert_encoding($asunto, "UTF-8", "HTML-ENTITIES");

    $email_to = $email;
    $email_from = $appSuportEmail;
	
	$separator = md5(time());

    $eol = PHP_EOL;

    $filename = "../pdfs/docs/".$titulodocumento."-".$currentyear."-".$iditem.".pdf";

	$filenameSubject = $titulodocumento."-".$currentyear."-".$iditem.".pdf";

    $pdfdoc = file_get_contents($filename);
    $attachment = chunk_split(base64_encode($pdfdoc));

    //$headers  = "From: \"TuEmpresa\"<" . $email_from . ">".$from.$eol;
	$headers  = 'From: '.$appName.' <'.$appSuportEmail.'>' . "\r\n";
    $headers .= "MIME-Version: 1.0".$eol; 
    $headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"";

    $body = "--".$separator.$eol;

    $body .= "Content-Type: text/html; charset=\"utf-8\"".$eol;
    $body .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
    $body .= $message.$eol;

    // adjunto
    $body .= "--".$separator.$eol;
    $body .= "Content-Type: application/octet-stream; name=\"".$filenameSubject."\"".$eol;
    $body .= "Content-Transfer-Encoding: base64".$eol;
    $body .= "Content-Disposition: attachment".$eol.$eol;
    $body .= $attachment.$eol;
    $body .= "--".$separator."--";

    mail($email_to, $subject, $body, $headers);
	
	//ELIMINO EL NUMERO DE ORDEN
	//unlink('../pdfs/docs/'.$titulodocumento."-".$currentyear.'-'.$iditem.'.pdf');
?>