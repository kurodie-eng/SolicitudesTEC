<?php

// Regreso al login tras cerrar sesión
session_start();
session_destroy();
header("location: ../index.php");
exit();

?>