<?php
function smtp_send($host, $port, $username, $password, $secure, $from, $fromName, $replyTo, $to, $subject, $body, $additionalHeaders = []) {
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

    $fromHeader = $fromName ? ($fromName . ' <' . $from . '>') : $from;
    $headers = array_merge([
        'From: ' . $fromHeader,
        'Reply-To: ' . ($replyTo ?: $from),
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
    function normalizeText($value) {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
    }

    function buildMailBody(array $post) {
        $fieldLabels = [
            'nombre' => 'Nombre',
            'apellidos' => 'Apellidos',
            'franquicia' => 'Modalidad de Franquicia',
            'zona' => 'Zona',
            'provincia' => 'Provincia',
            'poblacion' => 'Población',
            'ciudad' => 'Ciudad',
            'pais' => 'País',
            'email' => 'Email',
            'codigo_pais' => 'Código de país',
            'telefono' => 'Teléfono',
            'mensaje' => 'Mensaje',
        ];

        $bodyLines = [];
        foreach ($fieldLabels as $key => $label) {
            if (!isset($post[$key])) {
                continue;
            }
            $value = trim($post[$key]);
            if ($value === '') {
                continue;
            }
            $bodyLines[] = $label . ': ' . normalizeText($value);
        }

        foreach ($post as $key => $value) {
            if (array_key_exists($key, $fieldLabels)) {
                continue;
            }
            $value = trim($value);
            if ($value === '') {
                continue;
            }
            $label = ucwords(str_replace(['_', '-'], [' ', ' '], $key));
            $bodyLines[] = $label . ': ' . normalizeText($value);
        }

        return implode("\n", $bodyLines);
    }

    $zonaRaw = trim($_POST['zona'] ?? '');
    $zona = normalizeText($zonaRaw);
    $zonaLabel = [
        'espana' => 'España',
        'internacional' => 'Internacional',
    ][$zonaRaw] ?? $zona;

    $provincia = normalizeText($_POST['provincia'] ?? '');
    $poblacion = normalizeText($_POST['poblacion'] ?? $_POST['ciudad'] ?? '');
    $pais = normalizeText($_POST['pais'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';

    $destinatario = 'tyeast8217@gmail.com';
    $asunto = 'Nuevo Candidato GW: ' . $zonaLabel . ' - ' . $provincia . ' - ' . $poblacion . ' - ' . $pais;
    $cuerpo = buildMailBody($_POST);

    if ($cuerpo === '') {
        $cuerpo = "Formulario enviado desde la web.\n\n" . print_r($_POST, true);
    }

    $smtpHost = 'live.smtp.mailtrap.io';
    $smtpPort = 587;
    $smtpUser = 'api';
    $smtpPass = '637d9590095561795eb197f80c1f96ee'; // Pega aquí el token que generaste
    $smtpSecure = 'tls'; 
    $smtpFrom = 'info@greenwash.es';
    $smtpFromName = 'Nuevo Expediente Greenwash';

    $sent = smtp_send(
        $smtpHost,
        $smtpPort,
        $smtpUser,
        $smtpPass,
        $smtpSecure,
        $smtpFrom,
        $smtpFromName,
        $email,
        $destinatario,
        $asunto,
        $cuerpo
    );

    if ($sent) {
        header('Location: gracias.html');
        exit;
    }

    header('Location: mensaje-rechazado.html');
    exit;
}
?>

