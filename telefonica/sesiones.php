<?php
session_start();
require 'conexion.php';

// Si no hay sesion de usuario se manda al login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['id_usuario'];

// Tema y foto del usuario
$res_cfg = mysqli_query($con, "SELECT tema, foto_perfil FROM usuario WHERE id_usuario = $id");
$cfg = mysqli_fetch_assoc($res_cfg);
$tema = $cfg['tema'];
$foto = $cfg['foto_perfil'];

// Cerrar sesiones viejas de mas de 24 horas
mysqli_query($con, "UPDATE sesion SET estado='cerrada', fin_sesion=NOW()
                    WHERE id_usuario=$id AND estado='activa'
                      AND inicio_sesion < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

$res_s = mysqli_query($con, "SELECT * FROM sesion WHERE id_usuario=$id ORDER BY inicio_sesion DESC LIMIT 30");
$total_activas = mysqli_num_rows(mysqli_query($con, "SELECT id_sesion FROM sesion WHERE id_usuario=$id AND estado='activa'"));
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Sesiones | Compania Telefonica</title>
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
table { width:100%; border-collapse:collapse; font-size:.9rem; }
th { background:<?= $tema=='oscuro'?'#0f2742':'#eff4fb' ?>; color:<?= $tema=='oscuro'?'#7dd3fc':'#1e3a5f' ?>; font-weight:700; padding:10px 12px; text-align:left; }
td { padding:9px 12px; border-bottom:1px solid <?= $tema=='oscuro'?'#1e3a5f':'#f1f5f9' ?>; color:<?= $tema=='oscuro'?'#e2e8f0':'#374151' ?>; }
.badge { display:inline-block; padding:3px 10px; border-radius:99px; font-size:.78rem; font-weight:700; }
.badge-green { background:#d1fae5; color:#065f46; }
.badge-gray { background:#f1f5f9; color:#475569; }
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:20px; }
.stat-box { background:<?= $tema=='oscuro'?'#1e2d3d':'#fff' ?>; border-radius:12px; padding:18px 20px; box-shadow:0 2px 10px rgba(0,87,183,.07); }
.stat-box .num { font-size:1.9rem; font-weight:800; color:#0057B7; }
.stat-box .lbl { font-size:.82rem; color:#64748b; margin-top:4px; }
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
    <a href="contactos.php">Contactos</a>
    <div class="sep"></div>
    <a href="configuracion.php">Configuracion</a>
    <a class="active" href="sesiones.php">Sesiones</a>
    <div class="sep"></div>
    <a href="logout.php">Cerrar Sesion</a>
  </nav>

  <!-- CONTENIDO -->
  <main class="content">
    <h1 class="page-title">Mis Sesiones</h1>

    <div class="stats-grid">
      <div class="stat-box">
        <div class="num"><?= $total_activas ?></div>
        <div class="lbl">Sesiones activas</div>
      </div>
      <div class="stat-box">
        <div class="num"><?= mysqli_num_rows($res_s) ?></div>
        <div class="lbl">Sesiones recientes</div>
      </div>
    </div>

    <div class="card">
      <h3>Historial de Sesiones</h3>
      <?php if (mysqli_num_rows($res_s) == 0): ?>
        <p style="color:#64748b;">Sin sesiones registradas.</p>
      <?php else: ?>
      <table>
        <tr>
          <th>Inicio de sesion</th>
          <th>Cierre de sesion</th>
          <th>IP</th>
          <th>Estado</th>
        </tr>
        <?php while ($s = mysqli_fetch_assoc($res_s)): ?>
        <tr>
          <td><?= $s['inicio_sesion'] ?></td>
          <td><?= $s['fin_sesion'] ? $s['fin_sesion'] : 'En curso' ?></td>
          <td><?= $s['ip_cliente'] ? $s['ip_cliente'] : '---' ?></td>
          <td>
            <?php if ($s['estado']=='activa'): ?>
              <span class="badge badge-green">Activa</span>
            <?php else: ?>
              <span class="badge badge-gray">Cerrada</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </table>
      <?php endif; ?>
    </div>

  </main>
</div>

</body>
</html>
