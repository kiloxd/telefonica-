<?php
// Conexion a la base de datos
$host = "localhost";
$user = "root";
$pass = "";
$db   = "dbhospital";

$con = mysqli_connect($host, $user, $pass, $db);

if (!$con) {
    die("Error de conexion: " . mysqli_connect_error());
}

mysqli_set_charset($con, "utf8");
?>
