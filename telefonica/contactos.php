<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['id_usuario'];
$msg_ok  = "";
$msg_err = "";

// Telefonos del usuario
$res_t = mysqli_query($con, "SELECT * FROM telefono WHERE id_usuario=$uid");
$tels  = array();
while ($r = mysqli_fetch_assoc($res_t)) { $tels[] = $r; }
$ids_t = !empty($tels) ? implode(',', array_map(function($t){ return $t['id_telefono']; }, $tels)) : '0';

// Procesar acciones de contactos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $accion  = $_POST['accion'];
    $id_tel  = (int)$_POST['id_tel'];

    // Verificar que el telefono sea del usuario
    $ok = false;
    foreach ($tels as $t) { if ($t['id_telefono'] == $id_tel) { $ok = true; break; } }

    if (!$ok) {
        $msg_err = "Telefono no valido.";
    } elseif ($accion == 'agregar') {
        $numero = mysqli_real_escape_string($con, trim($_POST['numero_contacto']));
        $nombre = mysqli_real_escape_string($con, trim($_POST['nombre_contacto']));
        $chk_c = mysqli_query($con, "SELECT id_telefono FROM telefono WHERE numero='$numero'");
        if (mysqli_num_rows($chk_c) == 0) {
            $msg_err = "El numero $numero no existe en el sistema.";
        } else {
            $id_contacto_tel = mysqli_fetch_assoc($chk_c)['id_telefono'];
            $chk_ex = mysqli_query($con, "SELECT id_contacto FROM contacto WHERE id_tel_dueno=$id_tel AND id_tel_contacto=$id_contacto_tel");
            if (mysqli_num_rows($chk_ex) > 0) {
                $msg_err = "Ese contacto ya existe.";
            } else {
                mysqli_query($con, "INSERT INTO contacto (id_tel_dueno, id_tel_contacto, nombre_contacto)
                                    VALUES ($id_tel, $id_contacto_tel, '$nombre')");
                $msg_ok = "Contacto agregado correctamente.";
            }
        }
    } elseif ($accion == 'bloquear' || $accion == 'desbloquear') {
        // Bloquear o desbloquear un contacto
        $id_c  = (int)$_POST['id_contacto'];
        $nuevo = $accion == 'bloquear' ? 1 : 0;
        $texto = $accion == 'bloquear' ? 'bloqueado' : 'desbloqueado';
        mysqli_query($con, "UPDATE contacto SET bloqueado=$nuevo WHERE id_contacto=$id_c AND id_tel_dueno=$id_tel");
        $msg_ok = "Contacto $texto correctamente.";
    } elseif ($accion == 'eliminar') {
        $id_c = (int)$_POST['id_contacto'];
        mysqli_query($con, "DELETE FROM contacto WHERE id_contacto=$id_c AND id_tel_dueno=$id_tel");
        $msg_ok = "Contacto eliminado.";
    }
}

// Tema y foto
$res_cfg = mysqli_query($con, "SELECT tema, foto_perfil FROM usuario WHERE id_usuario = $uid");
$cfg = mysqli_fetch_assoc($res_cfg);
$tema = $cfg['tema'];
$foto = $cfg['foto_perfil'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contactos | Compania Telefonica</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; background:<?= $tema=='oscuro'?'#0f172a':'#EFF4FB' ?>; min-height:100vh; display:flex; flex-direction:column; }
.topbar { background:<?= $tema=='oscuro'?'#0f2742':'#0057B7' ?>; height:56px; display:flex; align-items:center; justify-content:space-between; padding:0 24px; }
.topbar .logo { color:#fff; font-weight:700; font-size:1.1rem; text-decoration:none; }
.topbar .user { color:#bfdbfe; font-size:.88rem; display:flex; align-items:center; gap:10px; }
.topbar .user strong { color:#fff; }
.avatar { width:32px; height:32px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.4); }
.avatar-letra { width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; }
.layout { display:flex; flex:1; }
.sidebar { width:210px; background:<?= $tema=='oscuro'?'#0a1628':'#1e3a5f' ?>; padding:20px 0; }
.sidebar a { display:block; padding:11px 20px; color:#94b8d4; text-decoration:none; font-size:.9rem; border-left:3px solid transparent; }
.sidebar a:hover, .sidebar a.active { color:#fff; background:rgba(255,255,255,.08); border-left-color:#7dd3fc; }
.sidebar .sep { border-top:1px solid rgba(255,255,255,.1); margin:8px 0; }
.content { flex:1; padding:28px 32px; }
.page-title { font-size:1.5rem; font-weight:700; color:<?= $tema=='oscuro'?'#7dd3fc':'#1e3a5f' ?>; margin-bottom:20px; }
.card { background:<?= $tema=='oscuro'?'#1e2d3d':'#fff' ?>; border-radius:12px; box-shadow:0 2px 10px rgba(0,87,183,.07); padding:22px 24px; margin-bottom:20px; }
.card h3 { color:<?= $tema=='oscuro'?'#7dd3fc':'#1e3a5f' ?>; font-size:1.05rem; margin-bottom:14px; border-bottom:2px solid <?= $tema=='oscuro'?'#1e3a5f':'#eff4fb' ?>; padding-bottom:10px; }
.form-group { margin-bottom:16px; flex:1; min-width:160px; }
label { display:block; font-size:.83rem; font-weight:600; color:<?= $tema=='oscuro'?'#94a3b8':'#374151' ?>; margin-bottom:5px; }
input, select { width:100%; padding:9px 12px; border:1.5px solid <?= $tema=='oscuro'?'#334155':'#cbd5e1' ?>; border-radius:7px; font-size:.9rem; font-family:inherit; background:<?= $tema=='oscuro'?'#0f2742':'#fff' ?>; color:<?= $tema=='oscuro'?'#e2e8f0':'#374151' ?>; }
table { width:100%; border-collapse:collapse; font-size:.9rem; }
th { background:<?= $tema=='oscuro'?'#0f2742':'#eff4fb' ?>; color:<?= $tema=='oscuro'?'#7dd3fc':'#1e3a5f' ?>; font-weight:700; padding:10px 12px; text-align:left; }
td { padding:9px 12px; border-bottom:1px solid <?= $tema=='oscuro'?'#1e3a5f':'#f1f5f9' ?>; color:<?= $tema=='oscuro'?'#e2e8f0':'#374151' ?>; }
.badge { display:inline-block; padding:3px 10px; border-radius:99px; font-size:.78rem; font-weight:700; }
.badge-green { background:#d1fae5; color:#065f46; }
.badge-red { background:#fee2e2; color:#b91c1c; }
.btn { padding:10px 24px; border:none; border-radius:7px; font-size:.9rem; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-blue { background:#0057B7; color:#fff; }
.btn-green { background:#059669; color:#fff; }
.btn-red { background:#e53e3e; color:#fff; }
.btn-gray { background:#e2e8f0; color:#374151; }
.alert { padding:11px 16px; border-radius:8px; font-size:.88rem; margin-bottom:16px; }
.alert-error { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
</style>
</head>
<body>

<!-- BARRA SUPERIOR -->
<div class="topbar">
  <a class="logo" href="panel_usuario.php">Compania Telefonica</a>
  <div class="user">
    <?php if ($foto && file_exists('uploads/fotos/'.$foto)): ?>
      <img src="uploads/fotos/<?= htmlspecialchars($foto) ?>" class="avatar" alt="foto">
    <?php else: ?>
      <div class="avatar-letra"><?= strtoupper(substr($_SESSION['nombre'],0,1)) ?></div>
    <?php endif; ?>
    Hola, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
  </div>
</div>

<div class="layout">

  <!-- MENU LATERAL -->
  <nav class="sidebar">
    <a href="panel_usuario.php">Panel Principal</a>
    <a href="conectar_telefono.php">Mis Telefonos</a>
    <a href="gestion_saldo.php">Saldo</a>
    <a href="mensajes.php">Mensajes</a>
    <a class="active" href="contactos.php">Contactos</a>
    <div class="sep"></div>
    <a href="configuracion.php">Configuracion</a>
    <a href="sesiones.php">Sesiones</a>
    <div class="sep"></div>
    <a href="logout.php">Cerrar Sesion</a>
  </nav>

  <!-- CONTENIDO -->
  <main class="content">
    <h1 class="page-title">Contactos</h1>

    <?php if ($msg_ok): ?>
      <div class="alert alert-success"><?= $msg_ok ?></div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
      <div class="alert alert-error"><?= $msg_err ?></div>
    <?php endif; ?>

    <!-- Agregar contacto -->
    <div class="card">
      <h3>Agregar Contacto</h3>
      <?php if (empty($tels)): ?>
        <p style="color:#64748b;">Necesitas al menos un telefono registrado.</p>
      <?php else: ?>
      <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="accion" value="agregar">
        <div class="form-group" style="margin:0;">
          <label>Mi telefono</label>
          <select name="id_tel">
            <?php foreach ($tels as $t): ?>
              <option value="<?= $t['id_telefono'] ?>"><?= $t['numero'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;">
          <label>Numero a agregar</label>
          <input type="text" name="numero_contacto" placeholder="5510001111">
        </div>
        <div class="form-group" style="margin:0;">
          <label>Nombre</label>
          <input type="text" name="nombre_contacto" placeholder="Ej. Amigo">
        </div>
        <button type="submit" class="btn btn-blue">Agregar</button>
      </form>
      <?php endif; ?>
    </div>

    <!-- Lista de contactos por telefono -->
    <?php foreach ($tels as $t): ?>
    <?php
    $res_c = mysqli_query($con, "SELECT c.*, tf.numero AS num_contacto FROM contacto c
                                  INNER JOIN telefono tf ON c.id_tel_contacto = tf.id_telefono
                                  WHERE c.id_tel_dueno = {$t['id_telefono']}
                                  ORDER BY c.bloqueado, c.nombre_contacto");
    if (mysqli_num_rows($res_c) == 0) continue;
    ?>
    <div class="card">
      <h3>Contactos de <?= htmlspecialchars($t['numero']) ?></h3>
      <table>
        <tr>
          <th>Nombre</th>
          <th>Numero</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
        <?php while ($c = mysqli_fetch_assoc($res_c)): ?>
        <tr>
          <td><?= htmlspecialchars($c['nombre_contacto'] ?: '---') ?></td>
          <td><?= htmlspecialchars($c['num_contacto']) ?></td>
          <td>
            <?php if ($c['bloqueado']): ?>
              <span class="badge badge-red">Bloqueado</span>
            <?php else: ?>
              <span class="badge badge-green">Activo</span>
            <?php endif; ?>
          </td>
          <td style="display:flex;gap:6px;padding:8px 12px;flex-wrap:wrap;">
            <?php if ($c['bloqueado']): ?>
            <form method="POST">
              <input type="hidden" name="accion" value="desbloquear">
              <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
              <input type="hidden" name="id_contacto" value="<?= $c['id_contacto'] ?>">
              <button class="btn btn-green" style="padding:5px 12px;font-size:.8rem;">Desbloquear</button>
            </form>
            <?php else: ?>
            <form method="POST">
              <input type="hidden" name="accion" value="bloquear">
              <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
              <input type="hidden" name="id_contacto" value="<?= $c['id_contacto'] ?>">
              <button class="btn btn-red" style="padding:5px 12px;font-size:.8rem;">Bloquear</button>
            </form>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('Eliminar contacto?');">
              <input type="hidden" name="accion" value="eliminar">
              <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
              <input type="hidden" name="id_contacto" value="<?= $c['id_contacto'] ?>">
              <button class="btn btn-gray" style="padding:5px 12px;font-size:.8rem;">Eliminar</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </table>
    </div>
    <?php endforeach; ?>

    <?php if (empty($tels)): ?>
    <div class="card"><p style="color:#64748b;">No tienes telefonos ni contactos registrados.</p></div>
    <?php endif; ?>

  </main>
</div>

</body>
</html>
