<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

function smtp_send($host, $port, $username, $password, $secure, $from, $fromName, $replyTo, $to, $subject, $body, &$errorMessage = null, $additionalHeaders = []) {
    $transportHost = $host;
    $transportPort = $port;
    $protocol = '';
    if ($secure === 'ssl') {
        $protocol = 'ssl://';
    }

    $connection = stream_socket_client($protocol . $transportHost . ':' . $transportPort, $errno, $errstr, 30);
    if (!$connection) {
        $errorMessage = 'Connection failed: ' . $errno . ' - ' . $errstr;
        return false;
    }
    stream_set_timeout($connection, 30);

    $lastResponse = '';
    $getResponse = function () use ($connection, &$lastResponse) {
        $response = '';
        while (($line = fgets($connection, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $lastResponse = $response;
        return $response;
    };

    $sendCommand = function ($command, $expectedCodes = [250]) use ($connection, $getResponse, &$lastResponse) {
        fwrite($connection, $command . "\r\n");
        $response = $getResponse();
        $code = (int) substr($response, 0, 3);
        $lastResponse = $response;
        return in_array($code, $expectedCodes, true);
    };

    $initial = $getResponse();
    if (strpos($initial, '220') !== 0) {
        $errorMessage = 'SMTP banner not received or invalid: ' . trim($initial);
        fclose($connection);
        return false;
    }

    $hostname = gethostname() ?: 'localhost';
    if (!$sendCommand("EHLO $hostname", [250])) {
        $errorMessage = 'EHLO failed: ' . trim($lastResponse);
        fclose($connection);
        return false;
    }

    if ($secure === 'tls') {
        if (!$sendCommand('STARTTLS', [220])) {
            $errorMessage = 'STARTTLS failed: ' . trim($lastResponse);
            fclose($connection);
            return false;
        }
        if (!stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $errorMessage = 'Unable to enable TLS crypto';
            fclose($connection);
            return false;
        }
        if (!$sendCommand("EHLO $hostname", [250])) {
            $errorMessage = 'EHLO after STARTTLS failed: ' . trim($lastResponse);
            fclose($connection);
            return false;
        }
    }

    if ($username !== '') {
        if (!$sendCommand('AUTH LOGIN', [334])) {
            $errorMessage = 'AUTH LOGIN failed: ' . trim($lastResponse);
            fclose($connection);
            return false;
        }
        if (!$sendCommand(base64_encode($username), [334])) {
            $errorMessage = 'AUTH username failed: ' . trim($lastResponse);
            fclose($connection);
            return false;
        }
        if (!$sendCommand(base64_encode($password), [235])) {
            $errorMessage = 'AUTH password failed: ' . trim($lastResponse);
            fclose($connection);
            return false;
        }
    }

    if (!$sendCommand("MAIL FROM:<$from>", [250])) {
        $errorMessage = 'MAIL FROM failed: ' . trim($lastResponse);
        fclose($connection);
        return false;
    }
    if (!$sendCommand("RCPT TO:<$to>", [250, 251])) {
        $errorMessage = 'RCPT TO failed: ' . trim($lastResponse);
        fclose($connection);
        return false;
    }
    if (!$sendCommand('DATA', [354])) {
        $errorMessage = 'DATA command failed: ' . trim($lastResponse);
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

    if ($code !== 250) {
        $errorMessage = 'Message sending failed: ' . trim($response);
        return false;
    }

    return true;
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

    $smtpHost = 'greenwash-es.correoseguro.dinaserver.com';
    $smtpPort = 465;
    $smtpUser = 'smtp@greenwash.es';
    $smtpPass = 'BC8zH3:3*1]6'; // Pega aquí el token que generaste
    $smtpSecure = 'ssl'; 
    $smtpFrom = 'smtp@greenwash.es';
    $smtpFromName = 'Nuevo Expediente Greenwash';

    $errorMessage = '';
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
        $cuerpo,
        $errorMessage
    );

    if ($sent) {
        header('Location: gracias.html');
        exit;
    }

    @file_put_contents(__DIR__ . '/enviar-error.log', date('[Y-m-d H:i:s] ') . $errorMessage . ' | POST: ' . json_encode($_POST, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    header('Location: mensaje-rechazado.html');
    exit;
}
?>

