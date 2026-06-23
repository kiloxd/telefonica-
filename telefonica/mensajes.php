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

// Costo del mensaje segun la tarifa
$res_tar = mysqli_query($con, "SELECT costo_mensaje FROM tarifa ORDER BY id_tarifa LIMIT 1");
$costo   = mysqli_fetch_assoc($res_tar)['costo_mensaje'];
$costo   = $costo ?: 1.00;

// Telefonos del usuario
$res_t = mysqli_query($con, "SELECT * FROM telefono WHERE id_usuario=$uid ORDER BY fecha_registro");
$tels  = array();
while ($r = mysqli_fetch_assoc($res_t)) { $tels[] = $r; }

// Enviar un mensaje
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'enviar') {
    $id_emisor = isset($_POST['id_emisor']) ? (int)$_POST['id_emisor'] : 0;
    $num_dest  = mysqli_real_escape_string($con, trim($_POST['numero_destino']));
    $contenido = mysqli_real_escape_string($con, trim($_POST['contenido']));

    if (!isset($_POST['id_emisor'])) {
        $msg_err = "No tienes telefonos conectados para enviar mensajes.";
    } else {
        // Revisar estado del telefono y que tenga saldo
        $chk_e = mysqli_query($con, "SELECT estado, saldo FROM telefono WHERE id_telefono=$id_emisor AND id_usuario=$uid");
        $emisor = mysqli_fetch_assoc($chk_e);

        if (!$emisor) {
            $msg_err = "Telefono emisor no valido.";
        } elseif ($emisor['estado'] != 'conectado') {
            $msg_err = "El telefono emisor debe estar conectado para enviar mensajes.";
        } elseif ($emisor['saldo'] < $costo) {
            $msg_err = "Saldo insuficiente. Necesitas \$$costo. Saldo actual: \$" . number_format($emisor['saldo'],2);
        } elseif (empty($contenido)) {
            $msg_err = "El mensaje no puede estar vacio.";
        } else {
            $chk_r = mysqli_query($con, "SELECT id_telefono FROM telefono WHERE numero='$num_dest'");
            if (mysqli_num_rows($chk_r) == 0) {
                $msg_err = "El numero destino no existe en el sistema.";
            } else {
                $receptor = mysqli_fetch_assoc($chk_r);
                $id_receptor = $receptor['id_telefono'];
                // Revisar que el receptor no tenga bloqueado al emisor
                $chk_blq = mysqli_query($con, "SELECT id_contacto FROM contacto WHERE id_tel_dueno=$id_receptor AND id_tel_contacto=$id_emisor AND bloqueado=1");
                if (mysqli_num_rows($chk_blq) > 0) {
                    $msg_err = "No puedes enviar mensajes a ese numero (bloqueado).";
                } else {
                    mysqli_query($con, "INSERT INTO mensaje (id_tel_emisor, id_tel_receptor, contenido, costo, estado)
                                        VALUES ($id_emisor, $id_receptor, '$contenido', $costo, 'enviado')");
                    mysqli_query($con, "UPDATE telefono SET saldo = saldo - $costo WHERE id_telefono=$id_emisor");
                    mysqli_query($con, "INSERT INTO historial (id_telefono, tipo_operacion, descripcion)
                                        VALUES ($id_emisor, 'Envio mensaje', 'Mensaje a $num_dest, costo -\$$costo')");
                    $msg_ok = "Mensaje enviado correctamente a $num_dest. Se descontaron \$$costo de tu saldo.";
                }
            }
        }
    }
}

// Eliminar un mensaje
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'eliminar') {
    $id_msg  = (int)$_POST['id_msg'];
    $tipo_el = $_POST['tipo_el'];
    $ids_t = implode(',', array_map(function($t){ return $t['id_telefono']; }, $tels));
    if (!empty($ids_t)) {
        if ($tipo_el == 'enviado') {
            mysqli_query($con, "UPDATE mensaje SET eliminado_emisor=1 WHERE id_mensaje=$id_msg AND id_tel_emisor IN ($ids_t)");
        } else {
            mysqli_query($con, "UPDATE mensaje SET eliminado_receptor=1 WHERE id_mensaje=$id_msg AND id_tel_receptor IN ($ids_t)");
        }
    }
    $msg_ok = "Mensaje eliminado.";
}

// Tema y foto
$res_cfg = mysqli_query($con, "SELECT tema, foto_perfil FROM usuario WHERE id_usuario = $uid");
$cfg = mysqli_fetch_assoc($res_cfg);
$tema = $cfg['tema'];
$foto = $cfg['foto_perfil'];

$ids_t = !empty($tels) ? implode(',', array_map(function($t){ return $t['id_telefono']; }, $tels)) : '0';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'recibidos';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mensajes | Compania Telefonica</title>
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
.form-row { display:flex; gap:14px; flex-wrap:wrap; }
.form-group { margin-bottom:16px; flex:1; min-width:180px; }
label { display:block; font-size:.83rem; font-weight:600; color:<?= $tema=='oscuro'?'#94a3b8':'#374151' ?>; margin-bottom:5px; }
input, select, textarea { width:100%; padding:9px 12px; border:1.5px solid <?= $tema=='oscuro'?'#334155':'#cbd5e1' ?>; border-radius:7px; font-size:.9rem; font-family:inherit; background:<?= $tema=='oscuro'?'#0f2742':'#fff' ?>; color:<?= $tema=='oscuro'?'#e2e8f0':'#374151' ?>; }
table { width:100%; border-collapse:collapse; font-size:.9rem; }
th { background:<?= $tema=='oscuro'?'#0f2742':'#eff4fb' ?>; color:<?= $tema=='oscuro'?'#7dd3fc':'#1e3a5f' ?>; font-weight:700; padding:10px 12px; text-align:left; }
td { padding:9px 12px; border-bottom:1px solid <?= $tema=='oscuro'?'#1e3a5f':'#f1f5f9' ?>; color:<?= $tema=='oscuro'?'#e2e8f0':'#374151' ?>; }
.btn { padding:10px 24px; border:none; border-radius:7px; font-size:.9rem; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-green { background:#059669; color:#fff; }
.btn-red { background:#e53e3e; color:#fff; }
.alert { padding:11px 16px; border-radius:8px; font-size:.88rem; margin-bottom:16px; }
.alert-error { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
.tabs { display:flex; gap:0; margin-bottom:20px; border:1.5px solid #cbd5e1; border-radius:9px; overflow:hidden; max-width:420px; }
.tabs a { flex:1; text-align:center; padding:10px; font-weight:600; font-size:.9rem; text-decoration:none; color:#64748b; background:#f8fafc; }
.tabs a.act-b { background:#0057B7; color:#fff; }
.tabs a.act-g { background:#059669; color:#fff; }
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
    <a class="active" href="mensajes.php">Mensajes</a>
    <a href="contactos.php">Contactos</a>
    <div class="sep"></div>
    <a href="configuracion.php">Configuracion</a>
    <a href="sesiones.php">Sesiones</a>
    <div class="sep"></div>
    <a href="logout.php">Cerrar Sesion</a>
  </nav>

  <!-- CONTENIDO -->
  <main class="content">
    <h1 class="page-title">Mensajes</h1>

    <?php if ($msg_ok): ?>
      <div class="alert alert-success"><?= $msg_ok ?></div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
      <div class="alert alert-error"><?= $msg_err ?></div>
    <?php endif; ?>

    <!-- Pestanas -->
    <div class="tabs">
      <a href="?tab=recibidos" class="<?= $tab=='recibidos'?'act-b':'' ?>">Recibidos</a>
      <a href="?tab=enviados" class="<?= $tab=='enviados'?'act-b':'' ?>">Enviados</a>
      <a href="?tab=enviar" class="<?= $tab=='enviar'?'act-g':'' ?>">Enviar</a>
    </div>

    <?php if ($tab == 'enviar'): ?>
    <!-- Formulario para enviar mensaje -->
    <div class="card">
      <h3>Nuevo Mensaje (costo: $<?= $costo ?> por mensaje)</h3>
      <?php if (empty($tels)): ?>
        <p style="color:#64748b;">No tienes telefonos registrados. <a href="conectar_telefono.php" style="color:#0057B7;">Registrar</a></p>
      <?php else: ?>
      <form method="POST">
        <input type="hidden" name="accion" value="enviar">
        <div class="form-row">
          <div class="form-group">
            <label>Telefono emisor</label>
            <select name="id_emisor">
              <?php foreach ($tels as $t): ?>
                <option value="<?= $t['id_telefono'] ?>" <?= $t['estado']!='conectado'?'disabled':'' ?>>
                  <?= $t['numero'] ?> - $<?= number_format($t['saldo'],2) ?> <?= $t['estado']!='conectado'?'(desconectado)':'' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Numero destino</label>
            <input type="text" name="numero_destino" placeholder="5510001111">
          </div>
        </div>
        <div class="form-group">
          <label>Mensaje</label>
          <textarea name="contenido" rows="4" placeholder="Escribe tu mensaje aqui..."></textarea>
        </div>
        <button type="submit" class="btn btn-green">Enviar Mensaje</button>
      </form>
      <?php endif; ?>
    </div>

    <?php elseif ($tab == 'recibidos'): ?>
    <!-- Mensajes recibidos -->
    <div class="card">
      <h3>Mensajes Recibidos</h3>
      <?php
      $res_m = mysqli_query($con, "SELECT m.*, te.numero AS num_emisor FROM mensaje m
                                    INNER JOIN telefono te ON m.id_tel_emisor = te.id_telefono
                                    WHERE m.id_tel_receptor IN ($ids_t) AND m.eliminado_receptor = 0
                                    ORDER BY m.fecha_hora DESC LIMIT 50");
      ?>
      <?php if (mysqli_num_rows($res_m) == 0): ?>
        <p style="color:#64748b;font-size:.9rem;">Sin mensajes recibidos.</p>
      <?php else: ?>
      <table>
        <tr>
          <th>De</th>
          <th>Mensaje</th>
          <th>Fecha</th>
          <th></th>
        </tr>
        <?php while ($m = mysqli_fetch_assoc($res_m)): ?>
        <tr>
          <td><strong><?= $m['num_emisor'] ?></strong></td>
          <td style="max-width:300px;"><?= htmlspecialchars($m['contenido']) ?></td>
          <td style="font-size:.8rem;color:#64748b;"><?= $m['fecha_hora'] ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Eliminar mensaje?');">
              <input type="hidden" name="accion" value="eliminar">
              <input type="hidden" name="id_msg" value="<?= $m['id_mensaje'] ?>">
              <input type="hidden" name="tipo_el" value="recibido">
              <button class="btn btn-red" style="padding:4px 10px;font-size:.78rem;">Eliminar</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </table>
      <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- Mensajes enviados -->
    <div class="card">
      <h3>Mensajes Enviados</h3>
      <?php
      $res_ms = mysqli_query($con, "SELECT m.*, tr.numero AS num_receptor FROM mensaje m
                                     INNER JOIN telefono tr ON m.id_tel_receptor = tr.id_telefono
                                     WHERE m.id_tel_emisor IN ($ids_t) AND m.eliminado_emisor = 0
                                     ORDER BY m.fecha_hora DESC LIMIT 50");
      ?>
      <?php if (mysqli_num_rows($res_ms) == 0): ?>
        <p style="color:#64748b;font-size:.9rem;">Sin mensajes enviados.</p>
      <?php else: ?>
      <table>
        <tr>
          <th>Para</th>
          <th>Mensaje</th>
          <th>Costo</th>
          <th>Fecha</th>
          <th></th>
        </tr>
        <?php while ($m = mysqli_fetch_assoc($res_ms)): ?>
        <tr>
          <td><strong><?= $m['num_receptor'] ?></strong></td>
          <td style="max-width:260px;"><?= htmlspecialchars($m['contenido']) ?></td>
          <td style="color:#e53e3e;">-$<?= $m['costo'] ?></td>
          <td style="font-size:.8rem;color:#64748b;"><?= $m['fecha_hora'] ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Eliminar mensaje?');">
              <input type="hidden" name="accion" value="eliminar">
              <input type="hidden" name="id_msg" value="<?= $m['id_mensaje'] ?>">
              <input type="hidden" name="tipo_el" value="enviado">
              <button class="btn btn-red" style="padding:4px 10px;font-size:.78rem;">Eliminar</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </table>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>
</div>

</body>
</html>
