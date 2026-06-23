<?php
session_start();
require 'conexion.php';

// Si ya hay sesion iniciada se manda al panel correspondiente
if (isset($_SESSION['id_usuario']) || isset($_SESSION['id_admin'])) {
    header("Location: " . (isset($_SESSION['id_admin']) ? "panel_admin.php" : "panel_usuario.php"));
    exit();
}

$error = "";

// Procesar el inicio de sesion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $tipo = $_POST['tipo'];
    $cred = mysqli_real_escape_string($con, trim($_POST['credencial']));
    $pass = md5($_POST['contrasena']);

    // Login para administrador
    if ($tipo == 'admin') {

        $sql = "SELECT a.id_admin, p.nombre, a.rol
                FROM administrador a
                INNER JOIN persona p ON a.id_persona = p.id_persona
                WHERE a.usuario_admin = '$cred' AND a.contrasena = '$pass'";
        $res = mysqli_query($con, $sql);

        if (mysqli_num_rows($res) == 1) {
            $row = mysqli_fetch_assoc($res);
            $_SESSION['id_admin'] = $row['id_admin'];
            $_SESSION['nombre']   = $row['nombre'];
            $_SESSION['rol']      = $row['rol'];
            header("Location: panel_admin.php");
            exit();
        } else {
            $error = "Credenciales de administrador incorrectas.";
        }

    } else {

        // Login para usuario normal con su correo
        $sql = "SELECT u.id_usuario, p.nombre, u.estado
                FROM usuario u
                INNER JOIN persona p ON u.id_persona = p.id_persona
                WHERE u.correo = '$cred' AND u.contrasena = '$pass'";
        $res = mysqli_query($con, $sql);

        if (mysqli_num_rows($res) == 1) {
            $row = mysqli_fetch_assoc($res);

            if ($row['estado'] == 'bloqueado') {
                $error = "Tu cuenta esta bloqueada. Contacta al administrador.";
            } else {
                $_SESSION['id_usuario'] = $row['id_usuario'];
                $_SESSION['nombre']     = $row['nombre'];

                // Guardar la sesion en la base de datos
                $ip = $_SERVER['REMOTE_ADDR'];
                mysqli_query($con, "INSERT INTO sesion (id_usuario, ip_cliente)
                                    VALUES ({$row['id_usuario']}, '$ip')");

                header("Location: panel_usuario.php");
                exit();
            }
        } else {
            $error = "Correo o contrasena incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Iniciar Sesion - Telefonica</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; background:#EFF4FB;
       min-height:100vh; display:flex; flex-direction:column; }
header { background:#0057B7; padding:16px 32px;
         display:flex; justify-content:space-between; align-items:center; }
header .logo { color:#fff; font-size:1.3rem; font-weight:700; text-decoration:none; }
header a.nav { color:#bfdbfe; font-size:.9rem; text-decoration:none; }
header a.nav:hover { color:#fff; }
.container { flex:1; display:flex; align-items:center; justify-content:center; padding:40px 16px; }
.card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,87,183,.1);
        width:100%; max-width:420px; padding:36px 40px; }
.card h2 { color:#0057B7; font-size:1.6rem; margin-bottom:6px; }
.card p  { color:#64748b; font-size:.9rem; margin-bottom:28px; }
.tabs { display:flex; gap:0; margin-bottom:24px; border:1.5px solid #cbd5e1;
        border-radius:8px; overflow:hidden; }
.tab { flex:1; padding:10px; text-align:center; font-size:.9rem; font-weight:600;
       cursor:pointer; background:#f8fafc; color:#64748b; border:none;
       transition:.2s; }
.tab.active { background:#0057B7; color:#fff; }
.form-group { margin-bottom:18px; }
label { display:block; font-size:.85rem; font-weight:600; color:#374151; margin-bottom:6px; }
input { width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:8px;
        font-size:.95rem; transition:.2s; }
input:focus { outline:none; border-color:#0057B7; box-shadow:0 0 0 3px rgba(0,87,183,.12); }
.btn { width:100%; padding:13px; background:#0057B7; color:#fff; font-size:1rem;
       font-weight:700; border:none; border-radius:8px; cursor:pointer; margin-top:4px;
       transition:.2s; }
.btn:hover { background:#0046a3; }
.alert-error { padding:12px 16px; border-radius:8px; font-size:.9rem; margin-bottom:18px;
               background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }
.link-row { text-align:center; margin-top:16px; font-size:.88rem; color:#64748b; }
.link-row a { color:#0057B7; text-decoration:none; font-weight:600; }
</style>
</head>
<body>

<header>
  <a class="logo" href="index.php">Telefonica</a>
  <a class="nav" href="registro.php">Crear cuenta</a>
</header>

<div class="container">
  <div class="card">
    <h2>Iniciar Sesion</h2>
    <p>Accede con tu correo o como administrador.</p>

    <?php if ($error): ?>
      <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <!-- Pestanas para elegir tipo de login -->
    <div class="tabs">
      <button class="tab active" id="tab-user" onclick="switchTab('usuario')">Usuario</button>
      <button class="tab"        id="tab-admin" onclick="switchTab('admin')">Administrador</button>
    </div>

    <form method="POST" id="login-form">
      <input type="hidden" name="tipo" id="tipo-input" value="usuario">

      <div class="form-group">
        <label id="label-cred">Correo electronico</label>
        <input type="text" name="credencial" id="input-cred"
               placeholder="correo@ejemplo.com" required>
      </div>
      <div class="form-group">
        <label>Contrasena</label>
        <input type="password" name="contrasena" placeholder="Tu contrasena" required>
      </div>
      <button type="submit" class="btn">Entrar</button>
    </form>

    <div class="link-row">
      <a href="recuperar.php">Olvide mi contrasena</a>
      &nbsp;|&nbsp;
      <a href="registro.php">Registrarme</a>
    </div>
  </div>
</div>

<script>
// Cambia entre login de usuario y administrador
function switchTab(tipo) {
    document.getElementById('tipo-input').value = tipo;
    var isAdmin = tipo === 'admin';
    document.getElementById('tab-user').classList.toggle('active', !isAdmin);
    document.getElementById('tab-admin').classList.toggle('active', isAdmin);
    document.getElementById('label-cred').textContent = isAdmin ? 'Usuario administrador' : 'Correo electronico';
    document.getElementById('input-cred').placeholder = isAdmin ? 'Ej: admin' : 'correo@ejemplo.com';
}
<?php if (isset($_POST['tipo']) && $_POST['tipo'] == 'admin'): ?>
switchTab('admin');
<?php endif; ?>
</script>

</body>
</html>
