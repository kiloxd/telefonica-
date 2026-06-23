<?php
session_start();
require 'conexion.php';

$error = "";
$exito = "";

// Procesar el registro cuando se envia el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre    = mysqli_real_escape_string($con, trim($_POST['nombre']));
    $apellidos = mysqli_real_escape_string($con, trim($_POST['apellidos']));
    $direccion = mysqli_real_escape_string($con, trim($_POST['direccion']));
    $correo    = mysqli_real_escape_string($con, trim($_POST['correo']));
    $numero    = mysqli_real_escape_string($con, trim($_POST['numero']));
    $pass      = $_POST['contrasena'];
    $pass2     = $_POST['contrasena2'];

    // Validaciones de los campos
    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($numero) || empty($pass)) {
        $error = "Todos los campos obligatorios deben estar completos.";
    } elseif ($pass != $pass2) {
        $error = "Las contrasenas no coinciden.";
    } elseif (strlen($pass) < 4) {
        $error = "La contrasena debe tener al menos 4 caracteres.";
    } else {

        // Revisar que el correo no este repetido
        $chk = mysqli_query($con, "SELECT id_usuario FROM usuario WHERE correo='$correo'");

        if (mysqli_num_rows($chk) > 0) {
            $error = "El correo ya esta registrado.";
        } else {

            // Revisar que el numero no este repetido
            $chk2 = mysqli_query($con, "SELECT id_telefono FROM telefono WHERE numero='$numero'");

            if (mysqli_num_rows($chk2) > 0) {
                $error = "El numero telefonico ya esta registrado.";
            } else {

                // Guardar la persona, el usuario y su telefono
                $hash = md5($pass);

                mysqli_query($con, "INSERT INTO persona (nombre, apellidos, direccion)
                                    VALUES ('$nombre','$apellidos','$direccion')");
                $id_persona = mysqli_insert_id($con);

                mysqli_query($con, "INSERT INTO usuario (id_persona, correo, contrasena)
                                    VALUES ($id_persona, '$correo', '$hash')");
                $id_usuario = mysqli_insert_id($con);

                mysqli_query($con, "INSERT INTO telefono (id_usuario, numero, saldo, estado)
                                    VALUES ($id_usuario, '$numero', 0.00, 'desconectado')");

                $exito = "Cuenta creada correctamente. Ya puedes iniciar sesion.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro - Telefonica</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; background:#EFF4FB; min-height:100vh;
       display:flex; flex-direction:column; }
header { background:#0057B7; padding:16px 32px;
         display:flex; justify-content:space-between; align-items:center; }
header .logo { color:#fff; font-size:1.3rem; font-weight:700; text-decoration:none; }
header a.nav { color:#bfdbfe; font-size:.9rem; text-decoration:none; }
header a.nav:hover { color:#fff; }
.container { flex:1; display:flex; align-items:center; justify-content:center; padding:40px 16px; }
.card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,87,183,.1);
        width:100%; max-width:520px; padding:36px 40px; }
.card h2 { color:#0057B7; font-size:1.6rem; margin-bottom:6px; }
.card p  { color:#64748b; font-size:.9rem; margin-bottom:28px; }
.row { display:flex; gap:14px; }
.row .form-group { flex:1; }
.form-group { margin-bottom:18px; }
label { display:block; font-size:.85rem; font-weight:600; color:#374151;
        margin-bottom:6px; }
input { width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:8px;
        font-size:.95rem; color:#1e293b; transition:.2s; }
input:focus { outline:none; border-color:#0057B7; box-shadow:0 0 0 3px rgba(0,87,183,.12); }
.req { color:#e53e3e; }
.btn { width:100%; padding:13px; background:#0057B7; color:#fff; font-size:1rem;
       font-weight:700; border:none; border-radius:8px; cursor:pointer;
       margin-top:8px; transition:.2s; }
.btn:hover { background:#0046a3; }
.alert { padding:12px 16px; border-radius:8px; font-size:.9rem; margin-bottom:18px; }
.alert-error   { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
.link-row { text-align:center; margin-top:16px; font-size:.88rem; color:#64748b; }
.link-row a { color:#0057B7; text-decoration:none; font-weight:600; }
</style>
</head>
<body>

<header>
  <a class="logo" href="index.php">Telefonica</a>
  <a class="nav" href="login.php">Ya tengo cuenta</a>
</header>

<div class="container">
  <div class="card">
    <h2>Crear Cuenta</h2>
    <p>Completa los datos para registrarte en el sistema.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($exito): ?>
      <div class="alert alert-success"><?= $exito ?>
        <br><a href="login.php" style="color:#065f46;font-weight:700;">Ir al inicio de sesion</a>
      </div>
    <?php endif; ?>

    <?php if (!$exito): ?>
    <form method="POST">
      <div class="row">
        <div class="form-group">
          <label>Nombre <span class="req">*</span></label>
          <input type="text" name="nombre" placeholder="Juan" required
                 value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
        </div>
        <div class="form-group">
          <label>Apellidos <span class="req">*</span></label>
          <input type="text" name="apellidos" placeholder="Perez Garcia" required
                 value="<?= isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : '' ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Direccion</label>
        <input type="text" name="direccion" placeholder="Calle y numero"
               value="<?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?>">
      </div>
      <div class="form-group">
        <label>Correo electronico <span class="req">*</span></label>
        <input type="email" name="correo" placeholder="correo@ejemplo.com" required
               value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>">
      </div>
      <div class="form-group">
        <label>Numero telefonico <span class="req">*</span></label>
        <input type="text" name="numero" placeholder="5510001111" maxlength="20" required
               value="<?= isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : '' ?>">
      </div>
      <div class="row">
        <div class="form-group">
          <label>Contrasena <span class="req">*</span></label>
          <input type="password" name="contrasena" placeholder="Min. 4 caracteres" required>
        </div>
        <div class="form-group">
          <label>Confirmar contrasena <span class="req">*</span></label>
          <input type="password" name="contrasena2" placeholder="Repite tu contrasena" required>
        </div>
      </div>
      <button type="submit" class="btn">Registrarse</button>
    </form>
    <?php endif; ?>

    <div class="link-row">
      Ya tienes cuenta? <a href="login.php">Inicia sesion</a>
    </div>
  </div>
</div>

</body>
</html>
