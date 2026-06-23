<?php
session_start();
require 'conexion.php';

$paso   = 1;
$error  = "";
$exito  = "";
$correo_ok = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['correo'])) {
    $correo = mysqli_real_escape_string($con, trim($_POST['correo']));
    $res = mysqli_query($con, "SELECT id_usuario FROM usuario WHERE correo='$correo'");
    if (mysqli_num_rows($res) == 1) {
        $_SESSION['recuperar_correo'] = $correo;
        $paso = 2;
        $correo_ok = $correo;
    } else {
        $error = "No existe una cuenta con ese correo.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nueva_pass'])) {
    $nueva   = $_POST['nueva_pass'];
    $confirma = $_POST['confirma_pass'];
    if (strlen($nueva) < 4) {
        $error = "La contrasena debe tener al menos 4 caracteres.";
        $paso = 2;
        $correo_ok = $_SESSION['recuperar_correo'];
    } elseif ($nueva != $confirma) {
        $error = "Las contrasenas no coinciden.";
        $paso = 2;
        $correo_ok = $_SESSION['recuperar_correo'];
    } else {
        $correo_s = mysqli_real_escape_string($con, $_SESSION['recuperar_correo']);
        $hash = md5($nueva);
        mysqli_query($con, "UPDATE usuario SET contrasena='$hash' WHERE correo='$correo_s'");
        unset($_SESSION['recuperar_correo']);
        $exito = "Contrasena actualizada correctamente. Ya puedes iniciar sesion.";
    }
}

if (isset($_SESSION['recuperar_correo']) && empty($_POST)) {
    $paso = 2;
    $correo_ok = $_SESSION['recuperar_correo'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recuperar Contrasena - Telefonica</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; background:#EFF4FB;
       min-height:100vh; display:flex; flex-direction:column; }
header { background:#0057B7; padding:16px 32px;
         display:flex; justify-content:space-between; align-items:center; }
header .logo { color:#fff; font-size:1.3rem; font-weight:700; text-decoration:none; }
header a.nav { color:#bfdbfe; font-size:.9rem; text-decoration:none; }
.container { flex:1; display:flex; align-items:center; justify-content:center; padding:40px 16px; }
.card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,87,183,.1);
        width:100%; max-width:420px; padding:36px 40px; }
.card h2 { color:#0057B7; font-size:1.5rem; margin-bottom:6px; }
.card p  { color:#64748b; font-size:.9rem; margin-bottom:24px; }
.steps  { display:flex; gap:8px; margin-bottom:28px; }
.step   { flex:1; height:5px; border-radius:99px; background:#e2e8f0; }
.step.done { background:#0057B7; }
.form-group { margin-bottom:18px; }
label { display:block; font-size:.85rem; font-weight:600; color:#374151; margin-bottom:6px; }
input { width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:8px;
        font-size:.95rem; transition:.2s; }
input:focus { outline:none; border-color:#0057B7; box-shadow:0 0 0 3px rgba(0,87,183,.12); }
.btn { width:100%; padding:13px; background:#0057B7; color:#fff; font-size:1rem;
       font-weight:700; border:none; border-radius:8px; cursor:pointer; margin-top:4px; }
.btn:hover { background:#0046a3; }
.alert { padding:12px 16px; border-radius:8px; font-size:.9rem; margin-bottom:18px; }
.alert-error   { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
.link-row { text-align:center; margin-top:16px; font-size:.88rem; color:#64748b; }
.link-row a { color:#0057B7; text-decoration:none; font-weight:600; }
.email-badge { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;
               padding:10px 14px; color:#1d4ed8; font-weight:600; margin-bottom:18px;
               font-size:.9rem; }
</style>
</head>
<body>

<header>
  <a class="logo" href="index.php">Telefonica</a>
  <a class="nav" href="login.php">Volver al login</a>
</header>

<div class="container">
  <div class="card">
    <h2>Recuperar Contrasena</h2>

    <div class="steps">
      <div class="step done"></div>
      <div class="step <?php echo $paso >= 2 ? 'done' : ''; ?>"></div>
    </div>

    <?php if ($exito): ?>
      <div class="alert alert-success"><?php echo $exito; ?></div>
      <div class="link-row"><a href="login.php">Ir al inicio de sesion</a></div>

    <?php elseif ($paso == 1): ?>
      <p>Ingresa tu correo registrado para continuar.</p>
      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label>Correo electronico</label>
          <input type="email" name="correo" placeholder="correo@ejemplo.com" required>
        </div>
        <button type="submit" class="btn">Continuar</button>
      </form>

    <?php elseif ($paso == 2): ?>
      <p>Establece tu nueva contrasena para la cuenta:</p>
      <div class="email-badge"><?php echo htmlspecialchars($correo_ok); ?></div>
      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label>Nueva contrasena</label>
          <input type="password" name="nueva_pass" placeholder="Min. 4 caracteres" required>
        </div>
        <div class="form-group">
          <label>Confirmar contrasena</label>
          <input type="password" name="confirma_pass" placeholder="Repite la contrasena" required>
        </div>
        <button type="submit" class="btn">Cambiar Contrasena</button>
      </form>
    <?php endif; ?>

    <?php if (!$exito): ?>
    <div class="link-row"><a href="login.php">Volver al inicio de sesion</a></div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
