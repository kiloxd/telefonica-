<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

// Rango de fechas para el reporte
$fecha_ini = isset($_GET['fecha_ini']) ? $_GET['fecha_ini'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$f_ini = mysqli_real_escape_string($con, $fecha_ini . ' 00:00:00');
$f_fin = mysqli_real_escape_string($con, $fecha_fin . ' 23:59:59');

// Estadisticas del periodo
$msgs_periodo = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t FROM mensaje WHERE fecha_hora BETWEEN '$f_ini' AND '$f_fin'"))['t'];
$recargas_p   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t, IFNULL(SUM(monto),0) as total FROM recarga WHERE fecha_hora BETWEEN '$f_ini' AND '$f_fin'"));
$nuevos_users = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t FROM usuario WHERE fecha_registro BETWEEN '$f_ini' AND '$f_fin'"))['t'];
$errores_p    = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t FROM incidencia WHERE fecha_hora BETWEEN '$f_ini' AND '$f_fin'"))['t'];
$saldo_prom   = mysqli_fetch_assoc(mysqli_query($con, "SELECT ROUND(AVG(saldo),2) as prom FROM telefono"))['prom'];
$tel_conectados = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as t FROM telefono WHERE estado='conectado'"))['t'];

// Mensajes por dia
$res_dias = mysqli_query($con, "SELECT DATE(fecha_hora) as dia, COUNT(*) as total FROM mensaje WHERE fecha_hora BETWEEN '$f_ini' AND '$f_fin' GROUP BY DATE(fecha_hora) ORDER BY dia");
$dias_labels = array();
$dias_msgs   = array();
while ($d = mysqli_fetch_assoc($res_dias)) { $dias_labels[] = $d['dia']; $dias_msgs[] = (int)$d['total']; }

// Recargas por dia
$res_rec_dias = mysqli_query($con, "SELECT DATE(fecha_hora) as dia, COUNT(*) as total FROM recarga WHERE fecha_hora BETWEEN '$f_ini' AND '$f_fin' GROUP BY DATE(fecha_hora) ORDER BY dia");
$rec_por_dia = array();
while ($rd = mysqli_fetch_assoc($res_rec_dias)) { $rec_por_dia[$rd['dia']] = (int)$rd['total']; }

// Recargas por metodo
$res_metodos = mysqli_query($con, "SELECT metodo_pago, COUNT(*) as total, SUM(monto) as suma FROM recarga WHERE fecha_hora BETWEEN '$f_ini' AND '$f_fin' GROUP BY metodo_pago ORDER BY total DESC");
$metodos = array();
while ($m = mysqli_fetch_assoc($res_metodos)) { $metodos[] = $m; }

// Top 5 usuarios
$res_top = mysqli_query($con, "SELECT p.nombre, p.apellidos, COUNT(m.id_mensaje) as total_msgs FROM mensaje m
                               INNER JOIN telefono t ON m.id_tel_emisor = t.id_telefono
                               INNER JOIN usuario u ON t.id_usuario = u.id_usuario
                               INNER JOIN persona p ON u.id_persona = p.id_persona
                               WHERE m.fecha_hora BETWEEN '$f_ini' AND '$f_fin'
                               GROUP BY u.id_usuario ORDER BY total_msgs DESC LIMIT 5");
$top_users = array();
while ($r = mysqli_fetch_assoc($res_top)) { $top_users[] = $r; }

// Historial de operaciones
$tipo_hist = isset($_GET['tipo']) ? mysqli_real_escape_string($con, $_GET['tipo']) : '';
$where_h   = $tipo_hist ? "AND h.tipo_operacion LIKE '%$tipo_hist%'" : '';
$res_hist  = mysqli_query($con, "SELECT h.tipo_operacion, h.descripcion, h.fecha_hora, t.numero FROM historial h
                                 LEFT JOIN telefono t ON h.id_telefono = t.id_telefono
                                 WHERE h.fecha_hora BETWEEN '$f_ini' AND '$f_fin' $where_h
                                 ORDER BY h.fecha_hora DESC LIMIT 50");

// Calcular escala de la grafica
$max_grafica = max(array_merge($dias_msgs, array(1)));
$rec_vals = array_values($rec_por_dia);
$max_rec  = count($rec_vals) ? max(array_merge($rec_vals, array(1))) : 1;
$max_grafica_total = max($max_grafica, $max_rec, 1);

$colores_metodo = array('tarjeta'=>'#0057B7','transferencia'=>'#7c3aed','deposito'=>'#059669','oxxo'=>'#f59e0b');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reportes y Estadisticas | Compania Telefonica</title>
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
.form-group { margin-bottom:16px; }
label { display:block; font-size:.83rem; font-weight:600; color:#374151; margin-bottom:5px; }
input { width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:.9rem; font-family:inherit; }
table { width:100%; border-collapse:collapse; font-size:.9rem; }
th { background:#eff4fb; color:#1e3a5f; font-weight:700; padding:10px 12px; text-align:left; }
td { padding:9px 12px; border-bottom:1px solid #f1f5f9; color:#374151; }
.badge { display:inline-block; padding:3px 10px; border-radius:99px; font-size:.78rem; font-weight:700; }
.badge-blue { background:#dbeafe; color:#1d4ed8; }
.btn { padding:10px 24px; border:none; border-radius:7px; font-size:.9rem; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-blue { background:#0057B7; color:#fff; }
.btn-green { background:#059669; color:#fff; }
.btn-gray { background:#e2e8f0; color:#374151; }
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
    <a href="panel_admin.php">Dashboard</a>
    <a href="gestion_usuarios.php">Usuarios</a>
    <a href="gestion_telefonos.php">Telefonos</a>
    <a class="active" href="reportes.php">Reportes</a>
    <div class="sep"></div>
    <a href="logout.php">Cerrar Sesion</a>
  </nav>

  <!-- CONTENIDO -->
  <main class="content">
    <h1 class="page-title">Reportes y Estadisticas</h1>

    <!-- Filtros del reporte -->
    <div class="card">
      <form method="GET" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0;"><label>Desde</label><input type="date" name="fecha_ini" value="<?= $fecha_ini ?>" style="width:160px;"></div>
        <div class="form-group" style="margin:0;"><label>Hasta</label><input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" style="width:160px;"></div>
        <div class="form-group" style="margin:0;"><label>Tipo operacion</label><input type="text" name="tipo" placeholder="Ej: Recarga, Envio..." style="width:180px;" value="<?= isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : '' ?>"></div>
        <button type="submit" class="btn btn-blue">Generar</button>
        <a href="reportes.php" class="btn btn-gray">Restablecer</a>
        <button type="button" class="btn btn-green" onclick="abrirVistaPrevia()">Imprimir Reporte</button>
      </form>
    </div>

    <!-- Indicadores -->
    <div class="stats-grid">
      <div class="stat-box"><div class="num"><?= $nuevos_users ?></div><div class="lbl">Nuevos usuarios</div></div>
      <div class="stat-box"><div class="num"><?= $msgs_periodo ?></div><div class="lbl">Mensajes enviados</div></div>
      <div class="stat-box"><div class="num"><?= $recargas_p['t'] ?></div><div class="lbl">Recargas realizadas</div></div>
      <div class="stat-box"><div class="num">$<?= number_format($recargas_p['total'],2) ?></div><div class="lbl">Ingresos del periodo</div></div>
      <div class="stat-box"><div class="num">$<?= number_format($saldo_prom,2) ?></div><div class="lbl">Saldo promedio</div></div>
      <div class="stat-box"><div class="num"><?= $tel_conectados ?></div><div class="lbl">Telefonos conectados</div></div>
    </div>

    <!-- Grafica comparativa -->
    <div class="card">
      <h3>Comparativa: Mensajes vs Recargas por Dia</h3>
      <?php if (empty($dias_labels) && empty($rec_por_dia)): ?>
        <p style="color:#64748b;font-size:.9rem;">Sin datos para el periodo seleccionado.</p>
      <?php else: ?>
      <div style="display:flex;gap:20px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;"><div style="width:16px;height:16px;border-radius:4px;background:#0057B7;"></div>Mensajes enviados</div>
        <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;"><div style="width:16px;height:16px;border-radius:4px;background:#059669;"></div>Recargas realizadas</div>
      </div>
      <div style="overflow-x:auto;">
        <div style="display:flex;align-items:flex-end;gap:8px;min-height:180px;padding:0 8px 0 0;border-left:2px solid #e2e8f0;border-bottom:2px solid #e2e8f0;min-width:<?= max(count($dias_labels)*60,300) ?>px;">
          <?php
          $todas_fechas = array_unique(array_merge($dias_labels, array_keys($rec_por_dia)));
          sort($todas_fechas);
          foreach ($todas_fechas as $fecha):
            $msgs_val = in_array($fecha, $dias_labels) ? $dias_msgs[array_search($fecha, $dias_labels)] : 0;
            $recs_val = isset($rec_por_dia[$fecha]) ? $rec_por_dia[$fecha] : 0;
            $h_msg = $max_grafica_total > 0 ? round(($msgs_val / $max_grafica_total) * 160) : 0;
            $h_rec = $max_grafica_total > 0 ? round(($recs_val / $max_grafica_total) * 160) : 0;
            $dia_corto = date('d/m', strtotime($fecha));
          ?>
          <div style="display:flex;flex-direction:column;align-items:center;gap:3px;flex:1;min-width:48px;">
            <div style="display:flex;gap:3px;font-size:.7rem;color:#64748b;">
              <span style="color:#0057B7;font-weight:700;"><?= $msgs_val ?></span><span>/</span><span style="color:#059669;font-weight:700;"><?= $recs_val ?></span>
            </div>
            <div style="display:flex;gap:3px;align-items:flex-end;height:160px;">
              <div style="width:18px;height:<?= $h_msg ?>px;background:#0057B7;border-radius:4px 4px 0 0;"></div>
              <div style="width:18px;height:<?= $h_rec ?>px;background:#059669;border-radius:4px 4px 0 0;"></div>
            </div>
            <div style="font-size:.72rem;color:#94a3b8;text-align:center;"><?= $dia_corto ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div style="display:flex;gap:20px;flex-wrap:wrap;">
      <!-- Recargas por metodo -->
      <div class="card" style="flex:1;min-width:240px;">
        <h3>Recargas por Metodo de Pago</h3>
        <?php if (empty($metodos)): ?>
          <p style="color:#64748b;font-size:.9rem;">Sin recargas en el periodo.</p>
        <?php else: ?>
          <?php
          $max_met = max(array_map(function($m){ return $m['total']; }, $metodos));
          foreach ($metodos as $m):
            $pct = $max_met > 0 ? round(($m['total'] / $max_met) * 100) : 0;
            $color = isset($colores_metodo[$m['metodo_pago']]) ? $colores_metodo[$m['metodo_pago']] : '#64748b';
          ?>
          <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:5px;">
              <span style="font-weight:600;text-transform:capitalize;"><?= $m['metodo_pago'] ?></span>
              <span style="color:#64748b;"><?= $m['total'] ?> rec. - <strong>$<?= number_format($m['suma'],2) ?></strong></span>
            </div>
            <div style="background:#e2e8f0;border-radius:99px;height:10px;"><div style="background:<?= $color ?>;height:10px;border-radius:99px;width:<?= $pct ?>%;"></div></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Top usuarios -->
      <div class="card" style="flex:1;min-width:220px;">
        <h3>Top Usuarios Activos</h3>
        <?php if (empty($top_users)): ?>
          <p style="color:#64748b;font-size:.9rem;">Sin datos.</p>
        <?php else: ?>
        <table>
          <tr><th>#</th><th>Usuario</th><th>Mensajes</th></tr>
          <?php $i=1; foreach ($top_users as $r): ?>
          <tr>
            <td><strong><?= $i++ ?></strong></td>
            <td><?= htmlspecialchars($r['nombre'].' '.$r['apellidos']) ?></td>
            <td><span class="badge badge-blue"><?= $r['total_msgs'] ?></span></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Historial de operaciones -->
    <div class="card">
      <h3>Historial de Operaciones</h3>
      <?php if (mysqli_num_rows($res_hist) == 0): ?>
        <p style="color:#64748b;">Sin registros para el periodo.</p>
      <?php else: ?>
      <table>
        <tr><th>Fecha</th><th>Telefono</th><th>Operacion</th><th>Descripcion</th></tr>
        <?php while ($h = mysqli_fetch_assoc($res_hist)): ?>
        <tr>
          <td style="font-size:.82rem;"><?= $h['fecha_hora'] ?></td>
          <td><?= $h['numero'] ?: '---' ?></td>
          <td><span class="badge badge-blue" style="font-size:.75rem;"><?= htmlspecialchars($h['tipo_operacion']) ?></span></td>
          <td style="font-size:.85rem;color:#64748b;"><?= htmlspecialchars($h['descripcion']) ?></td>
        </tr>
        <?php endwhile; ?>
      </table>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- Ventana de vista previa de impresion -->
<div id="modal-impresion" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;width:520px;max-width:95vw;max-height:88vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <div style="background:#0057B7;padding:16px 22px;border-radius:14px 14px 0 0;display:flex;justify-content:space-between;align-items:center;">
      <span style="color:#fff;font-weight:700;font-size:1rem;">Vista Previa del Reporte</span>
      <button onclick="cerrarModal()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:1rem;">X</button>
    </div>
    <div style="padding:24px;">
      <div style="text-align:center;border-bottom:2px solid #e2e8f0;padding-bottom:16px;margin-bottom:20px;">
        <div style="font-size:1.4rem;font-weight:800;color:#0057B7;">Compania Telefonica</div>
        <div style="font-size:.9rem;color:#64748b;margin-top:4px;">Reporte del Sistema</div>
        <div style="font-size:.82rem;color:#94a3b8;margin-top:4px;">Periodo: <strong><?= $fecha_ini ?></strong> al <strong><?= $fecha_fin ?></strong></div>
        <div style="font-size:.78rem;color:#94a3b8;">Generado el: <?= date('d/m/Y H:i') ?></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
        <div style="background:#eff6ff;border-radius:8px;padding:12px;text-align:center;"><div style="font-size:1.5rem;font-weight:800;color:#0057B7;"><?= $nuevos_users ?></div><div style="font-size:.78rem;color:#64748b;">Nuevos usuarios</div></div>
        <div style="background:#eff6ff;border-radius:8px;padding:12px;text-align:center;"><div style="font-size:1.5rem;font-weight:800;color:#0057B7;"><?= $msgs_periodo ?></div><div style="font-size:.78rem;color:#64748b;">Mensajes enviados</div></div>
        <div style="background:#f0fdf4;border-radius:8px;padding:12px;text-align:center;"><div style="font-size:1.5rem;font-weight:800;color:#059669;"><?= $recargas_p['t'] ?></div><div style="font-size:.78rem;color:#64748b;">Recargas realizadas</div></div>
        <div style="background:#f0fdf4;border-radius:8px;padding:12px;text-align:center;"><div style="font-size:1.5rem;font-weight:800;color:#059669;">$<?= number_format($recargas_p['total'],2) ?></div><div style="font-size:.78rem;color:#64748b;">Total ingresos</div></div>
        <div style="background:#fefce8;border-radius:8px;padding:12px;text-align:center;"><div style="font-size:1.5rem;font-weight:800;color:#92400e;">$<?= number_format($saldo_prom,2) ?></div><div style="font-size:.78rem;color:#64748b;">Saldo promedio</div></div>
        <div style="background:#fef2f2;border-radius:8px;padding:12px;text-align:center;"><div style="font-size:1.5rem;font-weight:800;color:#b91c1c;"><?= $errores_p ?></div><div style="font-size:.78rem;color:#64748b;">Incidencias</div></div>
      </div>
      <?php if (!empty($top_users)): ?>
      <div style="margin-bottom:16px;">
        <div style="font-weight:700;color:#1e3a5f;font-size:.9rem;margin-bottom:8px;">Top Usuarios Activos</div>
        <?php foreach ($top_users as $i => $r): ?>
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:.85rem;">
          <span><?= ($i+1).'. '.htmlspecialchars($r['nombre'].' '.$r['apellidos']) ?></span>
          <span style="font-weight:700;color:#0057B7;"><?= $r['total_msgs'] ?> msgs</span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($metodos)): ?>
      <div>
        <div style="font-weight:700;color:#1e3a5f;font-size:.9rem;margin-bottom:8px;">Recargas por Metodo</div>
        <?php foreach ($metodos as $m): ?>
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:.85rem;">
          <span style="text-transform:capitalize;"><?= $m['metodo_pago'] ?></span>
          <span style="font-weight:700;"><?= $m['total'] ?> - $<?= number_format($m['suma'],2) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div style="padding:16px 22px;border-top:1px solid #e2e8f0;text-align:center;">
      <button id="btn-imprimir" onclick="simularImpresion()" style="background:#0057B7;color:#fff;border:none;padding:12px 36px;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;width:100%;">Imprimir</button>
    </div>
  </div>
</div>

<script>
// Abre la ventana de vista previa
function abrirVistaPrevia() {
    document.getElementById('modal-impresion').style.display = 'flex';
}

// Cierra la ventana
function cerrarModal() {
    document.getElementById('modal-impresion').style.display = 'none';
    var btn = document.getElementById('btn-imprimir');
    btn.textContent = 'Imprimir';
    btn.style.background = '#0057B7';
    btn.disabled = false;
}

// Simula la impresion con un mensaje
function simularImpresion() {
    var btn = document.getElementById('btn-imprimir');
    btn.textContent = 'Imprimiendo...';
    btn.style.background = '#059669';
    btn.disabled = true;
    setTimeout(function() {
        btn.textContent = 'Reporte impreso correctamente';
        btn.style.background = '#047857';
        setTimeout(function() { cerrarModal(); }, 1800);
    }, 2000);
}
</script>

</body>
</html>
