<?php
session_start();
require 'conexion.php';

// Si no hay sesion de usuario se manda al login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['id_usuario'];
$msg_ok  = "";
$msg_err = "";

// Procesar la recarga de saldo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'recargar') {
    $id_tel = (int)$_POST['id_tel'];
    $monto  = (float)$_POST['monto'];
    $metodo = mysqli_real_escape_string($con, $_POST['metodo']);

    $chk = mysqli_query($con, "SELECT id_telefono FROM telefono WHERE id_telefono=$id_tel AND id_usuario=$uid");

    if (mysqli_num_rows($chk) == 0) {
        $msg_err = "Telefono no valido.";
    } elseif ($monto <= 0) {
        $msg_err = "El monto debe ser mayor a cero.";
    } elseif (!in_array($metodo, array('tarjeta','transferencia','deposito','oxxo'))) {
        $msg_err = "Metodo de pago no valido.";
    } else {

        $id_tarjeta_usar = null;

        // Si paga con tarjeta hay que revisar si es nueva o guardada
        if ($metodo == 'tarjeta') {
            $opcion_tarjeta = $_POST['id_tarjeta'];

            if ($opcion_tarjeta == 'nueva') {
                // Datos de una tarjeta nueva que se va a guardar
                $numero_raw  = preg_replace('/\s+/', '', $_POST['numero_tarjeta']);
                $titular     = mysqli_real_escape_string($con, trim($_POST['titular']));
                $vencimiento = mysqli_real_escape_string($con, trim($_POST['vencimiento']));
                $nuevo_nip   = trim($_POST['nuevo_nip']);

                if (!preg_match('/^\d{13,19}$/', $numero_raw)) {
                    $msg_err = "El numero de tarjeta no es valido.";
                } elseif (empty($titular)) {
                    $msg_err = "Ingresa el nombre del titular de la tarjeta.";
                } elseif (!preg_match('/^\d{2}\/\d{2}$/', $vencimiento)) {
                    $msg_err = "La fecha de vencimiento debe tener el formato MM/AA.";
                } elseif (!preg_match('/^\d{3}$/', $nuevo_nip)) {
                    $msg_err = "El NIP de seguridad debe ser de 3 digitos.";
                } else {
                    $ultimos4 = substr($numero_raw, -4);
                    $enmascarado = "**** **** **** " . $ultimos4;
                    $nip_hash = md5($nuevo_nip);
                    mysqli_query($con, "INSERT INTO tarjeta_guardada (id_usuario, titular, numero_enmascarado, vencimiento, nip_hash)
                                        VALUES ($uid, '$titular', '$enmascarado', '$vencimiento', '$nip_hash')");
                    $id_tarjeta_usar = mysqli_insert_id($con);
                }
            } else {
                // Usar una tarjeta ya guardada pidiendo el NIP
                $id_tarjeta_sel = (int)$opcion_tarjeta;
                $nip_ingresado  = trim($_POST['nip']);
                $chk_t = mysqli_query($con, "SELECT nip_hash FROM tarjeta_guardada WHERE id_tarjeta=$id_tarjeta_sel AND id_usuario=$uid");
                $tarjeta = mysqli_fetch_assoc($chk_t);

                if (!$tarjeta) {
                    $msg_err = "Tarjeta no valida.";
                } elseif (!preg_match('/^\d{3}$/', $nip_ingresado)) {
                    $msg_err = "Ingresa el NIP de 3 digitos.";
                } elseif (md5($nip_ingresado) != $tarjeta['nip_hash']) {
                    $msg_err = "NIP incorrecto.";
                } else {
                    $id_tarjeta_usar = $id_tarjeta_sel;
                }
            }
        }

        // Si todo salio bien se hace la recarga
        if (empty($msg_err)) {
            $valor_tarjeta = $id_tarjeta_usar ? $id_tarjeta_usar : "NULL";
            mysqli_query($con, "UPDATE telefono SET saldo = saldo + $monto WHERE id_telefono=$id_tel");
            mysqli_query($con, "INSERT INTO recarga (id_telefono, monto, metodo_pago, id_tarjeta)
                                VALUES ($id_tel, $monto, '$metodo', $valor_tarjeta)");
            mysqli_query($con, "INSERT INTO historial (id_telefono, tipo_operacion, descripcion)
                                VALUES ($id_tel, 'Recarga', 'Recarga de \$$monto via $metodo')");
            $msg_ok = "Recarga de \$" . number_format($monto, 2) . " realizada correctamente.";
        }
    }
}

// Eliminar una tarjeta guardada
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'eliminar_tarjeta') {
    $id_t = (int)$_POST['id_tarjeta'];
    mysqli_query($con, "UPDATE recarga SET id_tarjeta=NULL WHERE id_tarjeta=$id_t");
    mysqli_query($con, "DELETE FROM tarjeta_guardada WHERE id_tarjeta=$id_t AND id_usuario=$uid");
    $msg_ok = "Tarjeta eliminada.";
}

// Tema y foto del usuario
$res_cfg = mysqli_query($con, "SELECT tema, foto_perfil FROM usuario WHERE id_usuario = $uid");
$cfg = mysqli_fetch_assoc($res_cfg);
$tema = $cfg['tema'];
$foto = $cfg['foto_perfil'];

// Telefonos del usuario
$res_t = mysqli_query($con, "SELECT * FROM telefono WHERE id_usuario=$uid ORDER BY fecha_registro");
$telefonos = array();
while ($r = mysqli_fetch_assoc($res_t)) { $telefonos[] = $r; }

// Tarjetas guardadas del usuario
$res_tarj = mysqli_query($con, "SELECT * FROM tarjeta_guardada WHERE id_usuario=$uid ORDER BY fecha_registro");
$tarjetas = array();
while ($r = mysqli_fetch_assoc($res_tarj)) { $tarjetas[] = $r; }
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion de Saldo | Compania Telefonica</title>
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
input, select { width:100%; padding:9px 12px; border:1.5px solid <?= $tema=='oscuro'?'#334155':'#cbd5e1' ?>; border-radius:7px; font-size:.9rem; font-family:inherit; background:<?= $tema=='oscuro'?'#0f2742':'#fff' ?>; color:<?= $tema=='oscuro'?'#e2e8f0':'#374151' ?>; }
table { width:100%; border-collapse:collapse; font-size:.9rem; }
th { background:<?= $tema=='oscuro'?'#0f2742':'#eff4fb' ?>; color:<?= $tema=='oscuro'?'#7dd3fc':'#1e3a5f' ?>; font-weight:700; padding:10px 12px; text-align:left; }
td { padding:9px 12px; border-bottom:1px solid <?= $tema=='oscuro'?'#1e3a5f':'#f1f5f9' ?>; color:<?= $tema=='oscuro'?'#e2e8f0':'#374151' ?>; }
.badge { display:inline-block; padding:3px 10px; border-radius:99px; font-size:.78rem; font-weight:700; }
.badge-green { background:#d1fae5; color:#065f46; }
.badge-gray { background:#f1f5f9; color:#475569; }
.badge-blue { background:#dbeafe; color:#1d4ed8; }
.btn { padding:10px 24px; border:none; border-radius:7px; font-size:.9rem; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-green { background:#059669; color:#fff; }
.btn-red { background:#e53e3e; color:#fff; }
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
    <a class="active" href="gestion_saldo.php">Saldo</a>
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
    <h1 class="page-title">Gestion de Saldo</h1>

    <?php if ($msg_ok): ?>
      <div class="alert alert-success"><?= $msg_ok ?></div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
      <div class="alert alert-error"><?= $msg_err ?></div>
    <?php endif; ?>

    <!-- Saldo actual por telefono -->
    <div class="card">
      <h3>Saldo Actual por Telefono</h3>
      <?php if (empty($telefonos)): ?>
        <p style="color:#64748b;">No tienes telefonos registrados.</p>
      <?php else: ?>
      <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <?php foreach ($telefonos as $t): ?>
        <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:16px 20px;min-width:180px;text-align:center;">
          <div style="font-size:1.6rem;font-weight:800;color:#0057B7;">$<?= number_format($t['saldo'],2) ?></div>
          <div style="font-size:.88rem;color:#374151;margin-top:4px;"><?= htmlspecialchars($t['numero']) ?></div>
          <div style="margin-top:8px;">
            <?php if ($t['estado']=='conectado'): ?>
              <span class="badge badge-green">Conectado</span>
            <?php else: ?>
              <span class="badge badge-gray">Desconectado</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Formulario de recarga -->
    <div class="card">
      <h3>Recargar Saldo</h3>
      <?php if (empty($telefonos)): ?>
        <p style="color:#64748b;font-size:.9rem;">Necesitas al menos un telefono registrado.
          <a href="conectar_telefono.php" style="color:#0057B7;">Registrar telefono</a></p>
      <?php else: ?>
      <form method="POST">
        <input type="hidden" name="accion" value="recargar">
        <div class="form-row">
          <div class="form-group">
            <label>Telefono</label>
            <select name="id_tel">
              <?php foreach ($telefonos as $t): ?>
                <option value="<?= $t['id_telefono'] ?>"><?= $t['numero'] ?> - $<?= number_format($t['saldo'],2) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Monto ($)</label>
            <input type="number" name="monto" step="0.01" min="1" placeholder="50.00">
          </div>
          <div class="form-group">
            <label>Metodo de pago</label>
            <select name="metodo" id="select-metodo" onchange="mostrarPanelMetodo()">
              <option value="tarjeta">Tarjeta</option>
              <option value="transferencia">Transferencia</option>
              <option value="deposito">Deposito</option>
              <option value="oxxo">OXXO</option>
            </select>
          </div>
        </div>

        <!-- Panel que aparece solo cuando se paga con tarjeta -->
        <div id="panel-tarjeta" style="display:none;border-top:1.5px dashed #cbd5e1;padding-top:16px;margin-top:6px;">
          <?php if (!empty($tarjetas)): ?>
          <div class="form-group" style="max-width:360px;">
            <label>Selecciona una tarjeta</label>
            <select name="id_tarjeta" id="select-tarjeta" onchange="mostrarSubPanelTarjeta()">
              <?php foreach ($tarjetas as $tc): ?>
                <option value="<?= $tc['id_tarjeta'] ?>"><?= $tc['numero_enmascarado'] ?> - vence <?= $tc['vencimiento'] ?></option>
              <?php endforeach; ?>
              <option value="nueva">Agregar otra tarjeta</option>
            </select>
          </div>
          <?php else: ?>
            <input type="hidden" name="id_tarjeta" value="nueva">
            <p style="font-size:.88rem;color:#64748b;margin-bottom:10px;">No tienes tarjetas guardadas. Ingresa los datos para guardar una nueva.</p>
          <?php endif; ?>

          <!-- Pide el NIP cuando se usa una tarjeta guardada -->
          <div id="panel-nip" style="display:none;max-width:220px;">
            <div class="form-group">
              <label>NIP de seguridad</label>
              <input type="text" name="nip" maxlength="3" placeholder="Ej. 800">
            </div>
          </div>

          <!-- Datos para guardar una tarjeta nueva -->
          <div id="panel-nueva-tarjeta" style="<?= !empty($tarjetas) ? 'display:none;' : '' ?>">
            <div class="form-row">
              <div class="form-group">
                <label>Numero de tarjeta</label>
                <input type="text" name="numero_tarjeta" maxlength="19" placeholder="1234 5678 9012 3456">
              </div>
              <div class="form-group">
                <label>Nombre del titular</label>
                <input type="text" name="titular" placeholder="Como aparece en la tarjeta">
              </div>
              <div class="form-group">
                <label>Vencimiento (MM/AA)</label>
                <input type="text" name="vencimiento" maxlength="5" placeholder="08/27">
              </div>
              <div class="form-group">
                <label>Crea un NIP de seguridad (3 digitos)</label>
                <input type="text" name="nuevo_nip" maxlength="3" placeholder="Ej. 800">
              </div>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-green">Recargar</button>
      </form>
      <?php endif; ?>
    </div>

    <!-- Tarjetas guardadas -->
    <?php if (!empty($tarjetas)): ?>
    <div class="card">
      <h3>Tarjetas Guardadas</h3>
      <table>
        <tr>
          <th>Tarjeta</th>
          <th>Titular</th>
          <th>Vencimiento</th>
          <th>Accion</th>
        </tr>
        <?php foreach ($tarjetas as $tc): ?>
        <tr>
          <td><strong><?= $tc['numero_enmascarado'] ?></strong></td>
          <td><?= htmlspecialchars($tc['titular']) ?></td>
          <td><?= $tc['vencimiento'] ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Eliminar esta tarjeta guardada?');">
              <input type="hidden" name="accion" value="eliminar_tarjeta">
              <input type="hidden" name="id_tarjeta" value="<?= $tc['id_tarjeta'] ?>">
              <button class="btn btn-red" style="padding:5px 12px;font-size:.8rem;">Eliminar</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php endif; ?>

    <!-- Historial de recargas -->
    <div class="card">
      <h3>Historial de Recargas</h3>
      <?php
      if (!empty($telefonos)):
        $ids = implode(',', array_map(function($t){ return $t['id_telefono']; }, $telefonos));
        $res_r = mysqli_query($con, "SELECT r.*, t.numero, tg.numero_enmascarado FROM recarga r
                                      INNER JOIN telefono t ON r.id_telefono = t.id_telefono
                                      LEFT JOIN tarjeta_guardada tg ON r.id_tarjeta = tg.id_tarjeta
                                      WHERE r.id_telefono IN ($ids)
                                      ORDER BY r.fecha_hora DESC LIMIT 30");
      ?>
      <?php if (mysqli_num_rows($res_r) == 0): ?>
        <p style="color:#64748b;font-size:.9rem;">Sin recargas registradas.</p>
      <?php else: ?>
      <table>
        <tr>
          <th>Fecha</th>
          <th>Telefono</th>
          <th>Monto</th>
          <th>Metodo</th>
        </tr>
        <?php while ($r = mysqli_fetch_assoc($res_r)): ?>
        <tr>
          <td><?= $r['fecha_hora'] ?></td>
          <td><?= $r['numero'] ?></td>
          <td><strong style="color:#059669;">+$<?= number_format($r['monto'],2) ?></strong></td>
          <td>
            <span class="badge badge-blue"><?= $r['metodo_pago'] ?></span>
            <?php if ($r['numero_enmascarado']): ?>
              <span style="font-size:.78rem;color:#64748b;"><?= $r['numero_enmascarado'] ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </table>
      <?php endif; ?>
      <?php endif; ?>
    </div>

  </main>
</div>

<script>
// Muestra el panel de tarjeta solo cuando el metodo es tarjeta
function mostrarPanelMetodo() {
    var metodo = document.getElementById('select-metodo').value;
    var panel = document.getElementById('panel-tarjeta');
    panel.style.display = metodo === 'tarjeta' ? 'block' : 'none';
    if (metodo === 'tarjeta') {
        mostrarSubPanelTarjeta();
    }
}

// Cambia entre pedir NIP (tarjeta guardada) o datos nuevos
function mostrarSubPanelTarjeta() {
    var select = document.getElementById('select-tarjeta');
    var valor = select ? select.value : 'nueva';
    var panelNip = document.getElementById('panel-nip');
    var panelNueva = document.getElementById('panel-nueva-tarjeta');

    if (valor === 'nueva') {
        panelNip.style.display = 'none';
        panelNueva.style.display = 'block';
    } else {
        panelNip.style.display = 'block';
        panelNueva.style.display = 'none';
    }
}

mostrarPanelMetodo();
</script>

</body>
</html>
