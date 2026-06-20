<?php

if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Location: ../index.php');
    exit;
}

date_default_timezone_set('America/Mexico_City');

$conexion=new mysqli("mysql.railway.internal","root","mZUjUViLXfUTyZEEpysTMgcKGxpfQlzb","railway");

$conexion->set_charset("utf8");

?>