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

// Telefonos del usuario
$res_t = mysqli_query($con, "SELECT * FROM telefono WHERE id_usuario = $id ORDER BY fecha_registro");

// Contar mensajes recibidos
$res_m = mysqli_query($con, "SELECT COUNT(*) as total FROM mensaje m
                              INNER JOIN telefono t ON m.id_tel_receptor = t.id_telefono
                              WHERE t.id_usuario = $id AND m.eliminado_receptor = 0");
$total_msg = mysqli_fetch_assoc($res_m)['total'];

// Total recargado
$res_r = mysqli_query($con, "SELECT IFNULL(SUM(r.monto),0) as total FROM recarga r
                              INNER JOIN telefono t ON r.id_telefono = t.id_telefono
                              WHERE t.id_usuario = $id");
$total_rec = mysqli_fetch_assoc($res_r)['total'];

// Mensajes enviados
$res_ms = mysqli_query($con, "SELECT COUNT(*) as total FROM mensaje m
                               INNER JOIN telefono t ON m.id_tel_emisor = t.id_telefono
                               WHERE t.id_usuario = $id");
$total_env = mysqli_fetch_assoc($res_ms)['total'];

$total_tel = mysqli_num_rows($res_t);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Principal | Compania Telefonica</title>
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
.badge-red { background:#fee2e2; color:#b91c1c; }
.btn { padding:10px 24px; border:none; border-radius:7px; font-size:.9rem; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-blue { background:#0057B7; color:#fff; }
.btn-green { background:#059669; color:#fff; }
.btn-gray { background:#e2e8f0; color:#374151; }
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
    <a class="active" href="panel_usuario.php">Panel Principal</a>
    <a href="conectar_telefono.php">Mis Telefonos</a>
    <a href="gestion_saldo.php">Saldo</a>
    <a href="mensajes.php">Mensajes</a>
    <a href="contactos.php">Contactos</a>
    <div class="sep"></div>
    <a href="configuracion.php">Configuracion</a>
    <a href="sesiones.php">Sesiones</a>
    <div class="sep"></div>
    <a href="logout.php">Cerrar Sesion</a>
  </nav>

  <!-- CONTENIDO -->
  <main class="content">
    <h1 class="page-title">Panel Principal</h1>

    <!-- Tarjetas con totales -->
    <div class="stats-grid">
      <div class="stat-box">
        <div class="num"><?= $total_tel ?></div>
        <div class="lbl">Telefonos registrados</div>
      </div>
      <div class="stat-box">
        <div class="num"><?= $total_msg ?></div>
        <div class="lbl">Mensajes recibidos</div>
      </div>
      <div class="stat-box">
        <div class="num"><?= $total_env ?></div>
        <div class="lbl">Mensajes enviados</div>
      </div>
      <div class="stat-box">
        <div class="num">$<?= number_format($total_rec,2) ?></div>
        <div class="lbl">Total recargado</div>
      </div>
    </div>

    <!-- Tabla de telefonos -->
    <div class="card">
      <h3>Mis Telefonos</h3>
      <?php if ($total_tel == 0): ?>
        <p style="color:#64748b;font-size:.9rem;">No tienes telefonos registrados.
          <a href="conectar_telefono.php" style="color:#0057B7;">Agregar telefono</a></p>
      <?php else: ?>
      <table>
        <tr>
          <th>Numero</th>
          <th>Saldo</th>
          <th>Estado</th>
          <th>Desvio</th>
          <th>Accion</th>
        </tr>
        <?php while ($t = mysqli_fetch_assoc($res_t)): ?>
        <tr>
          <td><strong><?= $t['numero'] ?></strong></td>
          <td>$<?= number_format($t['saldo'],2) ?></td>
          <td>
            <?php if ($t['estado']=='conectado'): ?>
              <span class="badge badge-green">Conectado</span>
            <?php elseif ($t['estado']=='desconectado'): ?>
              <span class="badge badge-gray">Desconectado</span>
            <?php else: ?>
              <span class="badge badge-red">Inactivo</span>
            <?php endif; ?>
          </td>
          <td><?= $t['numero_desvio'] ? $t['numero_desvio'] : '---' ?></td>
          <td><a href="conectar_telefono.php" class="btn btn-gray" style="padding:5px 14px;font-size:.8rem;">Gestionar</a></td>
        </tr>
        <?php endwhile; ?>
      </table>
      <?php endif; ?>
    </div>

    <!-- Accesos rapidos -->
    <div class="card">
      <h3>Accesos Rapidos</h3>
      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="mensajes.php" class="btn btn-blue">Ver Mensajes</a>
        <a href="gestion_saldo.php" class="btn btn-green">Recargar Saldo</a>
        <a href="conectar_telefono.php" class="btn btn-gray">Gestionar Telefonos</a>
        <a href="contactos.php" class="btn btn-gray">Contactos</a>
      </div>
    </div>

  </main>
</div>

</body>
</html>
