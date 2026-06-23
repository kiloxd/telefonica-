<?php
session_start();
require 'conexion.php';

// Si no hay sesion de administrador se manda al login
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

// Estadisticas generales del sistema
$total_usuarios  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t FROM usuario"))['t'];
$total_telefonos = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t FROM telefono"))['t'];
$tel_conectados  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t FROM telefono WHERE estado='conectado'"))['t'];
$total_mensajes  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t FROM mensaje"))['t'];
$total_recargas  = mysqli_fetch_assoc(mysqli_query($con, "SELECT IFNULL(SUM(monto),0) as t FROM recarga"))['t'];
$sesiones_activas= mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t FROM sesion WHERE estado='activa'"))['t'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Administrador | Compania Telefonica</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; background:#EFF4FB; min-height:100vh; display:flex; flex-direction:column; }
.topbar { background:#1e3a5f; height:56px; display:flex; align-items:center; justify-content:space-between; padding:0 24px; }
.topbar .logo { color:#fff; font-weight:700; font-size:1.1rem; text-decoration:none; }
.topbar .badge-admin { background:#f59e0b; color:#1e3a5f; font-size:.75rem; font-weight:800; padding:3px 8px; border-radius:99px; margin-left:8px; }
.topbar .user { color:#94b8d4; font-size:.88rem; }
.topbar .user strong { color:#fff; }
.layout { display:flex; flex:1; }
.sidebar { width:210px; background:#0f2742; padding:20px 0; }
.sidebar a { display:block; padding:11px 20px; color:#94b8d4; text-decoration:none; font-size:.9rem; border-left:3px solid transparent; }
.sidebar a:hover, .sidebar a.active { color:#fff; background:rgba(255,255,255,.07); border-left-color:#f59e0b; }
.sidebar .sep { border-top:1px solid rgba(255,255,255,.08); margin:8px 0; }
.content { flex:1; padding:28px 32px; }
.page-title { font-size:1.5rem; font-weight:700; color:#1e3a5f; margin-bottom:20px; }
.card { background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,87,183,.07); padding:22px 24px; margin-bottom:20px; }
.card h3 { color:#1e3a5f; font-size:1.05rem; margin-bottom:14px; border-bottom:2px solid #eff4fb; padding-bottom:10px; }
table { width:100%; border-collapse:collapse; font-size:.9rem; }
th { background:#eff4fb; color:#1e3a5f; font-weight:700; padding:10px 12px; text-align:left; }
td { padding:9px 12px; border-bottom:1px solid #f1f5f9; color:#374151; }
.badge { display:inline-block; padding:3px 10px; border-radius:99px; font-size:.78rem; font-weight:700; }
.badge-green { background:#d1fae5; color:#065f46; }
.badge-red { background:#fee2e2; color:#b91c1c; }
.badge-blue { background:#dbeafe; color:#1d4ed8; }
.btn { padding:10px 24px; border:none; border-radius:7px; font-size:.9rem; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-blue { background:#0057B7; color:#fff; }
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:20px; }
.stat-box { background:#fff; border-radius:12px; padding:18px 20px; box-shadow:0 2px 10px rgba(0,87,183,.07); }
.stat-box .num { font-size:1.9rem; font-weight:800; color:#0057B7; }
.stat-box .lbl { font-size:.82rem; color:#64748b; margin-top:4px; }
</style>
</head>
<body>

<!-- BARRA SUPERIOR -->
<div class="topbar">
  <div>
    <a class="logo" href="panel_admin.php">Compania Telefonica</a>
    <span class="badge-admin">ADMIN</span>
  </div>
  <div class="user">Hola, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong></div>
</div>

<div class="layout">

  <!-- MENU LATERAL -->
  <nav class="sidebar">
    <a class="active" href="panel_admin.php">Dashboard</a>
    <a href="gestion_usuarios.php">Usuarios</a>
    <a href="gestion_telefonos.php">Telefonos</a>
    <a href="reportes.php">Reportes</a>
    <div class="sep"></div>
    <a href="logout.php">Cerrar Sesion</a>
  </nav>

  <!-- CONTENIDO -->
  <main class="content">
    <h1 class="page-title">Panel Administrador</h1>

    <!-- Indicadores generales -->
    <div class="stats-grid">
      <div class="stat-box"><div class="num"><?= $total_usuarios ?></div><div class="lbl">Usuarios registrados</div></div>
      <div class="stat-box"><div class="num"><?= $total_telefonos ?></div><div class="lbl">Telefonos</div></div>
      <div class="stat-box"><div class="num"><?= $tel_conectados ?></div><div class="lbl">Telefonos conectados</div></div>
      <div class="stat-box"><div class="num"><?= $total_mensajes ?></div><div class="lbl">Mensajes enviados</div></div>
      <div class="stat-box"><div class="num">$<?= number_format($total_recargas,2) ?></div><div class="lbl">Total recargado</div></div>
      <div class="stat-box"><div class="num"><?= $sesiones_activas ?></div><div class="lbl">Sesiones activas</div></div>
    </div>

    <div style="display:flex;gap:20px;flex-wrap:wrap;">
      <!-- Ultimos usuarios -->
      <div class="card" style="flex:1;min-width:280px;">
        <h3>Ultimos Usuarios Registrados</h3>
        <?php
        $res = mysqli_query($con, "SELECT p.nombre, p.apellidos, u.correo, u.estado FROM usuario u
                                    INNER JOIN persona p ON u.id_persona=p.id_persona
                                    ORDER BY u.fecha_registro DESC LIMIT 6");
        ?>
        <table>
          <tr><th>Nombre</th><th>Correo</th><th>Estado</th></tr>
          <?php while ($r = mysqli_fetch_assoc($res)): ?>
          <tr>
            <td><?= htmlspecialchars($r['nombre'].' '.$r['apellidos']) ?></td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($r['correo']) ?></td>
            <td><span class="badge <?= $r['estado']=='activo'?'badge-green':'badge-red' ?>"><?= $r['estado'] ?></span></td>
          </tr>
          <?php endwhile; ?>
        </table>
        <div style="margin-top:12px;"><a href="gestion_usuarios.php" class="btn btn-blue" style="padding:7px 18px;font-size:.85rem;">Ver todos</a></div>
      </div>

      <!-- Telefonos conectados -->
      <div class="card" style="flex:1;min-width:260px;">
        <h3>Telefonos Conectados</h3>
        <?php
        $res2 = mysqli_query($con, "SELECT t.numero, t.saldo, p.nombre FROM telefono t
                                     INNER JOIN usuario u ON t.id_usuario = u.id_usuario
                                     INNER JOIN persona p ON u.id_persona = p.id_persona
                                     WHERE t.estado = 'conectado' ORDER BY t.fecha_registro DESC LIMIT 8");
        ?>
        <?php if (mysqli_num_rows($res2) == 0): ?>
          <p style="color:#64748b;font-size:.9rem;">Ningun telefono conectado actualmente.</p>
        <?php else: ?>
        <table>
          <tr><th>Numero</th><th>Usuario</th><th>Saldo</th></tr>
          <?php while ($r = mysqli_fetch_assoc($res2)): ?>
          <tr>
            <td><strong><?= $r['numero'] ?></strong></td>
            <td><?= htmlspecialchars($r['nombre']) ?></td>
            <td>$<?= number_format($r['saldo'],2) ?></td>
          </tr>
          <?php endwhile; ?>
        </table>
        <?php endif; ?>
        <div style="margin-top:12px;"><a href="gestion_telefonos.php" class="btn btn-blue" style="padding:7px 18px;font-size:.85rem;">Ver todos</a></div>
      </div>
    </div>

    <!-- Ultimas recargas -->
    <div class="card">
      <h3>Ultimas Recargas</h3>
      <?php
      $res3 = mysqli_query($con, "SELECT r.monto, r.metodo_pago, r.fecha_hora, t.numero, p.nombre FROM recarga r
                                   INNER JOIN telefono t ON r.id_telefono = t.id_telefono
                                   INNER JOIN usuario u ON t.id_usuario = u.id_usuario
                                   INNER JOIN persona p ON u.id_persona = p.id_persona
                                   ORDER BY r.fecha_hora DESC LIMIT 8");
      ?>
      <?php if (mysqli_num_rows($res3) == 0): ?>
        <p style="color:#64748b;">Sin recargas registradas.</p>
      <?php else: ?>
      <table>
        <tr><th>Usuario</th><th>Telefono</th><th>Monto</th><th>Metodo</th><th>Fecha</th></tr>
        <?php while ($r = mysqli_fetch_assoc($res3)): ?>
        <tr>
          <td><?= htmlspecialchars($r['nombre']) ?></td>
          <td><?= $r['numero'] ?></td>
          <td><strong style="color:#059669;">+$<?= number_format($r['monto'],2) ?></strong></td>
          <td><span class="badge badge-blue"><?= $r['metodo_pago'] ?></span></td>
          <td style="font-size:.82rem;"><?= $r['fecha_hora'] ?></td>
        </tr>
        <?php endwhile; ?>
      </table>
      <?php endif; ?>
    </div>

  </main>
</div>

</body>
</html>
