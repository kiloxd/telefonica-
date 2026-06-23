<?php
session_start();
require 'conexion.php';

// Cerrar la sesion del usuario en la base de datos
if (isset($_SESSION['id_usuario'])) {
    $uid = (int)$_SESSION['id_usuario'];
    mysqli_query($con, "UPDATE sesion SET estado='cerrada', fin_sesion=NOW()
                        WHERE id_usuario=$uid AND estado='activa'");
}

session_destroy();
header("Location: index.php");
exit();
?>
