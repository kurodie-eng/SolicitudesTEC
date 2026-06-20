<?php

if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Location: ../index.php');
    exit;
}

// Configuración SMTP para Gmail.
// Genera la contraseña de aplicación en: https://myaccount.google.com/apppasswords
// (requiere verificación en 2 pasos activada en tu cuenta Google)
// En Railway, define estas variables de entorno en el panel; localmente usa los valores de abajo.
define('MAIL_HOST',      getenv('MAIL_HOST')      ?: 'smtp.gmail.com');
define('MAIL_PORT', (int)(getenv('MAIL_PORT')      ?: 587));
define('MAIL_USERNAME',  getenv('MAIL_USERNAME')  ?: 'cardozacalderondaniel400@gmail.com');
define('MAIL_PASSWORD',  getenv('MAIL_PASSWORD')  ?: 'snqy ocgm tgtl lgig');
define('MAIL_FROM',      getenv('MAIL_FROM')      ?: 'cardozacalderondaniel400@gmail.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'ITSRV SOPORTEC');
