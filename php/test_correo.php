<?php
session_start();
if (empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

require_once "correo.php";

$ok = enviarCorreo(
    $_SESSION["correo"],
    $_SESSION["nombre"] . ' ' . $_SESSION["app"],
    "Prueba de correo — ITSRV SOPORTEC",
    plantillaCorreo(
        "Correo de prueba",
        '<p style="color:#4a5568;line-height:1.6;">
            Este es un correo de prueba del sistema <strong>ITSRV SOPORTEC</strong>.<br>
            Si lo recibes, la configuración de correo funciona correctamente.
        </p>'
    )
);

if ($ok) {
    echo "✅ Correo enviado correctamente a <strong>" . htmlspecialchars($_SESSION["correo"]) . "</strong>.";
} else {
    echo "❌ Error al enviar el correo. Revisa <code>php/config_correo.php</code> y el error_log de PHP.";
}
