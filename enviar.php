<?php
function smtp_send($host, $port, $username, $password, $secure, $from, $to, $subject, $body, $additionalHeaders = []) {
    $transportHost = $host;
    $transportPort = $port;
    $protocol = '';
    if ($secure === 'ssl') {
        $protocol = 'ssl://';
    }

    $connection = stream_socket_client($protocol . $transportHost . ':' . $transportPort, $errno, $errstr, 30);
    if (!$connection) {
        return false;
    }
    stream_set_timeout($connection, 30);

    $getResponse = function () use ($connection) {
        $response = '';
        while (($line = fgets($connection, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };

    $sendCommand = function ($command, $expectedCodes = [250]) use ($connection, $getResponse) {
        fwrite($connection, $command . "\r\n");
        $response = $getResponse();
        $code = (int) substr($response, 0, 3);
        return in_array($code, $expectedCodes, true);
    };

    $initial = $getResponse();
    if (strpos($initial, '220') !== 0) {
        fclose($connection);
        return false;
    }

    $hostname = gethostname() ?: 'localhost';
    if (!$sendCommand("EHLO $hostname", [250])) {
        fclose($connection);
        return false;
    }

    if ($secure === 'tls') {
        if (!$sendCommand('STARTTLS', [220])) {
            fclose($connection);
            return false;
        }
        if (!stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($connection);
            return false;
        }
        if (!$sendCommand("EHLO $hostname", [250])) {
            fclose($connection);
            return false;
        }
    }

    if ($username !== '') {
        if (!$sendCommand('AUTH LOGIN', [334])) {
            fclose($connection);
            return false;
        }
        if (!$sendCommand(base64_encode($username), [334])) {
            fclose($connection);
            return false;
        }
        if (!$sendCommand(base64_encode($password), [235])) {
            fclose($connection);
            return false;
        }
    }

    if (!$sendCommand("MAIL FROM:<$from>", [250])) {
        fclose($connection);
        return false;
    }
    if (!$sendCommand("RCPT TO:<$to>", [250, 251])) {
        fclose($connection);
        return false;
    }
    if (!$sendCommand('DATA', [354])) {
        fclose($connection);
        return false;
    }

    $headers = array_merge([
        'From: ' . $from,
        'Reply-To: ' . $from,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Subject: ' . $subject,
    ], $additionalHeaders);

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    fwrite($connection, $message . "\r\n");
    $response = $getResponse();
    $code = (int) substr($response, 0, 3);
    $sendCommand('QUIT', [221]);
    fclose($connection);

    return $code === 250;
}

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

    $smtpHost = 'smtp.tuservidor.com';
    $smtpPort = 587;
    $smtpUser = 'usuario@tudominio.com';
    $smtpPass = 'tu_contraseña';
    $smtpSecure = 'tls'; // 'tls', 'ssl' o ''
    $smtpFrom = 'info@greenwash.es';

    $sent = smtp_send(
        $smtpHost,
        $smtpPort,
        $smtpUser,
        $smtpPass,
        $smtpSecure,
        $smtpFrom,
        $destinatario,
        $asunto,
        $cuerpo,
        [
            'Reply-To: ' . $email,
        ]
    );

    $mensajeEnviado = $sent ? '¡Mensaje enviado correctamente!' : 'No se pudo enviar el mensaje. Por favor, inténtalo más tarde.';

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
    echo '    <h2>' . htmlspecialchars($mensajeEnviado, ENT_QUOTES, 'UTF-8') . '</h2>';
    if (!$sent) {
        echo '    <p>Hubo un problema al enviar el correo. Por favor, revisa la configuración SMTP.</p>';
    } else {
        echo '    <p>Gracias por contactar con Green Wash. Nos pondremos en contacto contigo pronto.</p>';
    }
    echo '    <a class="btn" href="index.html">Volver al inicio</a>';
    echo '  </div>';
    echo '</body>';
    echo '</html>';
    exit;
}
?>

