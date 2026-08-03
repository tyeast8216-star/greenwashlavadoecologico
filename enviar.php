<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $franquicia = trim($_POST['franquicia'] ?? '');
    $zona = trim($_POST['zona'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $pais = trim($_POST['pais'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $codigoPais = trim($_POST['codigo_pais'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    $destinatario = 'info@greenwash.es';
    $asunto = 'Nuevo mensaje de contacto desde la web';

    $cuerpo = "Nombre: $nombre $apellidos\n";
    $cuerpo .= "Email: $email\n";
    $cuerpo .= "Teléfono: $codigoPais $telefono\n";
    $cuerpo .= "Modalidad de Franquicia: $franquicia\n";
    $cuerpo .= "Zona: $zona\n";
    if ($provincia !== '') {
        $cuerpo .= "Provincia: $provincia\n";
    }
    if ($ciudad !== '') {
        $cuerpo .= "Ciudad: $ciudad\n";
    }
    if ($pais !== '') {
        $cuerpo .= "País: $pais\n";
    }
    $cuerpo .= "Mensaje:\n$mensaje\n";

    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($destinatario, $asunto, $cuerpo, $headers);

    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head>';
    echo '  <meta charset="utf-8">';
    echo '  <meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '  <title>Mensaje enviado</title>';
    echo '  <style>body{font-family:Arial,sans-serif;background:#f4f8f7;padding:40px;} .box{max-width:600px;margin:0 auto;background:#fff;padding:30px;border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,.1);} .btn{display:inline-block;margin-top:20px;padding:10px 16px;background:#1f7164;color:#fff;text-decoration:none;border-radius:6px;}</style>';
    echo '</head>';
    echo '<body>';
    echo '  <div class="box">';
    echo '    <h2>¡Mensaje enviado correctamente!</h2>';
    echo '    <p>Gracias por contactar con Green Wash. Nos pondremos en contacto contigo pronto.</p>';
    echo '    <a class="btn" href="index.html">Volver al inicio</a>';
    echo '  </div>';
    echo '</body>';
    echo '</html>';
    exit;
}
?>
