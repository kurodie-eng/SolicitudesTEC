<?php

if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Location: ../index.php');
    exit;
}

date_default_timezone_set('America/Mexico_City');

// Conexión a la BD SQL de pruebas,
                    //  Server     Usuario  Contraseña     NombreBD
$conexion=new mysqli("localhost",  "root",   "12345",   "solicitudes");
$conexion->set_charset("utf8");

?>