<?php

if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Location: ../index.php');
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../lib/phpmailer/Exception.php';
require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../lib/phpmailer/SMTP.php';
require_once __DIR__ . '/config_correo.php';

function crearMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->Timeout    = 5;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    return $mail;
}

function enviarCorreo(string $destinatario, string $nombreDest, string $asunto, string $cuerpoHTML): bool {
    $brevoKey = getenv('BREVO_API_KEY') ?: '';
    if ($brevoKey !== '') {
        return _enviarBrevo($brevoKey, $destinatario, $nombreDest, $asunto, $cuerpoHTML);
    }
    try {
        $mail = crearMailer();
        $mail->addAddress($destinatario, $nombreDest);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHTML;
        $mail->AltBody = strip_tags($cuerpoHTML);
        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('[ITSRV-Correo] No enviado a ' . $destinatario . ': ' . $e->getMessage());
        return false;
    }
}

function _enviarBrevo(string $key, string $dest, string $nombre, string $asunto, string $html): bool {
    $payload = json_encode([
        'sender'      => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM],
        'to'          => [['email' => $dest, 'name' => $nombre]],
        'subject'     => $asunto,
        'htmlContent' => $html,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . $key,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($code !== 200 && $code !== 201) {
        error_log('[ITSRV-Correo/Brevo] Error ' . $code . ' al enviar a ' . $dest . ': ' . ($err ?: $response));
        return false;
    }
    return true;
}

function plantillaCorreo(string $titulo, string $cuerpo): string {
    return <<<HTML
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f0f2f8;font-family:Arial,sans-serif;">
        <div style="max-width:560px;margin:32px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
            <div style="background:#1b552d;padding:24px 32px;">
                <div style="color:#fff;font-size:20px;font-weight:bold;letter-spacing:1px;">ITSRV <span style="color:#ff893a;">SOPORTEC</span></div>
                <div style="color:#8f98b2;font-size:12px;margin-top:4px;">Instituto Tecnológico Superior de Rioverde</div>
            </div>
            <div style="padding:32px;">
                <h2 style="margin:0 0 16px;font-size:18px;color:#1a2340;">$titulo</h2>
                $cuerpo
            </div>
            <div style="background:#f0f2f8;padding:16px 32px;font-size:11px;color:#8f98b2;text-align:center;">
                Este es un mensaje automático. No respondas a este correo.
            </div>
        </div>
    </body>
    </html>
    HTML;
}

// --- Eventos de correo ---

function correoSolicitudCreada(mysqli $db, string $encabezado, string $descripcion): void {
    $stmt = $db->prepare(
        "SELECT correo, nombre, app FROM usuario WHERE id_rol = 2 AND disponible = 1"
    );
    $stmt->execute();
    $trabajadores = $stmt->get_result();
    $stmt->close();

    $cuerpo = <<<HTML
    <p style="color:#4a5568;line-height:1.6;">
        Se ha registrado una nueva solicitud de soporte técnico que requiere atención.
    </p>
    <div style="background:#f0f2f8;border-radius:8px;padding:16px;margin:16px 0;">
        <div style="font-weight:bold;color:#1a2340;margin-bottom:4px;">$encabezado</div>
        <div style="color:#4a5568;font-size:14px;">$descripcion</div>
    </div>
    <p style="color:#4a5568;line-height:1.6;">Ingresa al sistema para revisar y aceptar la solicitud.</p>
    HTML;

    $html   = plantillaCorreo("Nueva solicitud de soporte", $cuerpo);
    $asunto = "Nueva solicitud: $encabezado";

    while ($t = $trabajadores->fetch_object()) {
        enviarCorreo($t->correo, $t->nombre . ' ' . $t->app, $asunto, $html);
    }
}

function correoSolicitudAceptada(mysqli $db, int $id_sol): void {
    $stmt = $db->prepare(
        "SELECT u.correo, u.nombre, u.app, s.encabezado, s.prioridad, s.fecha_limite
         FROM solicitud s
         JOIN usuario u ON u.id_us = s.id_us
         WHERE s.id_sol = ?"
    );
    $stmt->bind_param("i", $id_sol);
    $stmt->execute();
    $sol = $stmt->get_result()->fetch_object();
    $stmt->close();

    if (!$sol) return;

    $cuerpo = <<<HTML
    <p style="color:#4a5568;line-height:1.6;">
        Tu solicitud ha sido aceptada por un técnico y está en proceso de atención.
    </p>
    <div style="background:#f0f2f8;border-radius:8px;padding:16px;margin:16px 0;">
        <div style="font-weight:bold;color:#1a2340;margin-bottom:8px;">{$sol->encabezado}</div>
        <div style="font-size:13px;color:#4a5568;">
            <strong>Prioridad:</strong> {$sol->prioridad}<br>
            <strong>Fecha límite:</strong> {$sol->fecha_limite}
        </div>
    </div>
    <p style="color:#4a5568;line-height:1.6;">Te notificaremos cuando el técnico haya enviado el reporte de solución.</p>
    HTML;

    enviarCorreo(
        $sol->correo,
        $sol->nombre . ' ' . $sol->app,
        "Tu solicitud ha sido aceptada — ITSRV SOPORTEC",
        plantillaCorreo("Solicitud aceptada", $cuerpo)
    );
}

function correoReporteEnviado(mysqli $db, int $id_sol): void {
    $stmt = $db->prepare(
        "SELECT u.correo, u.nombre, u.app, s.encabezado
         FROM solicitud s
         JOIN usuario u ON u.id_us = s.id_us
         WHERE s.id_sol = ?"
    );
    $stmt->bind_param("i", $id_sol);
    $stmt->execute();
    $sol = $stmt->get_result()->fetch_object();
    $stmt->close();

    if (!$sol) return;

    $cuerpo = <<<HTML
    <p style="color:#4a5568;line-height:1.6;">
        El técnico ha enviado el reporte de solución para tu solicitud.
    </p>
    <div style="background:#f0f2f8;border-radius:8px;padding:16px;margin:16px 0;">
        <div style="font-weight:bold;color:#1a2340;">{$sol->encabezado}</div>
    </div>
    <p style="color:#4a5568;line-height:1.6;">
        Ingresa al sistema para revisar el reporte y aprobarlo o rechazarlo.
    </p>
    HTML;

    enviarCorreo(
        $sol->correo,
        $sol->nombre . ' ' . $sol->app,
        "Reporte listo para revisión — ITSRV SOPORTEC",
        plantillaCorreo("Reporte enviado por el técnico", $cuerpo)
    );
}

function correoReporteAprobado(mysqli $db, int $id_sol): void {
    $stmt = $db->prepare(
        "SELECT u.correo, u.nombre, u.app, s.encabezado
         FROM asignacion a
         JOIN usuario u ON u.id_us = a.id_trabajador
         JOIN solicitud s ON s.id_sol = a.id_sol
         WHERE a.id_sol = ? AND a.estado_asignacion = 'activa'
         LIMIT 1"
    );
    $stmt->bind_param("i", $id_sol);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_object();
    $stmt->close();

    if (!$row) return;

    $cuerpo = <<<HTML
    <p style="color:#4a5568;line-height:1.6;">
        El solicitante ha aprobado tu reporte. La solicitud queda marcada como finalizada.
    </p>
    <div style="background:#f0f2f8;border-radius:8px;padding:16px;margin:16px 0;">
        <div style="font-weight:bold;color:#1a2340;">{$row->encabezado}</div>
    </div>
    <p style="color:#4a5568;line-height:1.6;">¡Buen trabajo!</p>
    HTML;

    enviarCorreo(
        $row->correo,
        $row->nombre . ' ' . $row->app,
        "Reporte aprobado — ITSRV SOPORTEC",
        plantillaCorreo("Tu reporte fue aprobado", $cuerpo)
    );
}

function correoReporteRechazado(mysqli $db, int $id_sol, string $razon): void {
    $stmt = $db->prepare(
        "SELECT u.correo, u.nombre, u.app, s.encabezado
         FROM asignacion a
         JOIN usuario u ON u.id_us = a.id_trabajador
         JOIN solicitud s ON s.id_sol = a.id_sol
         WHERE a.id_sol = ? AND a.estado_asignacion = 'activa'
         LIMIT 1"
    );
    $stmt->bind_param("i", $id_sol);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_object();
    $stmt->close();

    if (!$row) return;

    $cuerpo = <<<HTML
    <p style="color:#4a5568;line-height:1.6;">
        El solicitante ha rechazado tu reporte y solicita que lo reenvíes.
    </p>
    <div style="background:#f0f2f8;border-radius:8px;padding:16px;margin:16px 0;">
        <div style="font-weight:bold;color:#1a2340;margin-bottom:8px;">{$row->encabezado}</div>
        <div style="font-size:13px;color:#e74c3c;"><strong>Motivo:</strong> $razon</div>
    </div>
    <p style="color:#4a5568;line-height:1.6;">Ingresa al sistema para enviar un nuevo reporte.</p>
    HTML;

    enviarCorreo(
        $row->correo,
        $row->nombre . ' ' . $row->app,
        "Reporte rechazado — debes reenviarlo — ITSRV SOPORTEC",
        plantillaCorreo("Tu reporte fue rechazado", $cuerpo)
    );
}
