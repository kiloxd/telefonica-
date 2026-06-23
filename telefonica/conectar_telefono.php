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

// Procesar las acciones de los telefonos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // Registrar un telefono nuevo
    if ($accion == 'agregar') {
        $numero   = mysqli_real_escape_string($con, trim($_POST['numero']));
        $gmail    = mysqli_real_escape_string($con, trim($_POST['gmail']));
        $marca    = mysqli_real_escape_string($con, trim($_POST['marca']));
        $modelo   = mysqli_real_escape_string($con, trim($_POST['modelo']));
        $operador = mysqli_real_escape_string($con, trim($_POST['operador']));

        if (empty($numero)) {
            $msg_err = "El numero es obligatorio.";
        } else {
            $chk = mysqli_query($con, "SELECT id_telefono FROM telefono WHERE numero='$numero'");
            if (mysqli_num_rows($chk) > 0) {
                $msg_err = "Ese numero ya esta registrado en el sistema.";
            } else {
                mysqli_query($con, "INSERT INTO telefono (id_usuario, numero, gmail, marca, modelo, operador, saldo, estado)
                                    VALUES ($uid, '$numero', '$gmail', '$marca', '$modelo', '$operador', 0.00, 'desconectado')");
                $id_t = mysqli_insert_id($con);
                mysqli_query($con, "INSERT INTO historial (id_telefono, tipo_operacion, descripcion)
                                    VALUES ($id_t, 'Registro', 'Telefono registrado por usuario')");
                $msg_ok = "Telefono $numero registrado correctamente.";
            }
        }
    }

    // Conectar el telefono
    if ($accion == 'conectar') {
        $id_t = (int)$_POST['id_tel'];
        $chk  = mysqli_query($con, "SELECT estado FROM telefono WHERE id_telefono=$id_t AND id_usuario=$uid");
        $row  = mysqli_fetch_assoc($chk);
        if (!$row) {
            $msg_err = "Telefono no encontrado.";
        } elseif ($row['estado']=='conectado') {
            $msg_err = "El telefono ya esta conectado.";
        } else {
            mysqli_query($con, "UPDATE telefono SET estado='conectado' WHERE id_telefono=$id_t");
            mysqli_query($con, "INSERT INTO historial (id_telefono,tipo_operacion,descripcion)
                                VALUES ($id_t,'Conexion','Telefono conectado al sistema')");
            $msg_ok = "Telefono conectado correctamente.";
        }
    }

    // Desconectar el telefono
    if ($accion == 'desconectar') {
        $id_t = (int)$_POST['id_tel'];
        $chk  = mysqli_query($con, "SELECT estado FROM telefono WHERE id_telefono=$id_t AND id_usuario=$uid");
        $row  = mysqli_fetch_assoc($chk);
        if (!$row) {
            $msg_err = "Telefono no encontrado.";
        } elseif ($row['estado']=='desconectado') {
            $msg_err = "El telefono ya esta desconectado.";
        } else {
            mysqli_query($con, "UPDATE telefono SET estado='desconectado' WHERE id_telefono=$id_t");
            mysqli_query($con, "INSERT INTO historial (id_telefono,tipo_operacion,descripcion)
                                VALUES ($id_t,'Desconexion','Telefono desconectado del sistema')");
            $msg_ok = "Telefono desconectado correctamente.";
        }
    }

    // Activar el desvio validando que no se haga un ciclo
    if ($accion == 'activar_desvio') {
        $id_t      = (int)$_POST['id_tel'];
        $destino   = mysqli_real_escape_string($con, trim($_POST['numero_desvio']));
        $tipo_desv = in_array($_POST['tipo_desvio'], array('siempre','ocupado','no_responde')) ? $_POST['tipo_desvio'] : 'siempre';
        $propio    = mysqli_fetch_assoc(mysqli_query($con, "SELECT numero FROM telefono WHERE id_telefono=$id_t"))['numero'];

        if ($destino == $propio) {
            $msg_err = "Error: no puedes desviar un telefono hacia si mismo.";
        } elseif (empty($destino)) {
            $msg_err = "Ingresa un numero de destino valido.";
        } else {
            $chk_d = mysqli_query($con, "SELECT id_telefono FROM telefono WHERE numero='$destino'");
            if (mysqli_num_rows($chk_d) == 0) {
                $msg_err = "El numero de destino no existe en el sistema.";
            } else {
                $ciclo = mysqli_fetch_assoc(mysqli_query($con, "SELECT numero_desvio FROM telefono WHERE numero='$destino'"));
                if ($ciclo && $ciclo['numero_desvio'] == $propio) {
                    $msg_err = "Error: configurar este desvio crearia un ciclo de llamadas.";
                } else {
                    mysqli_query($con, "UPDATE telefono SET numero_desvio='$destino', tipo_desvio='$tipo_desv'
                                       WHERE id_telefono=$id_t AND id_usuario=$uid");
                    mysqli_query($con, "INSERT INTO historial (id_telefono,tipo_operacion,descripcion)
                                        VALUES ($id_t,'Desvio activado','Desvio $tipo_desv hacia $destino')");
                    $msg_ok = "Desvio activado: $tipo_desv hacia $destino";
                }
            }
        }
    }

    // Desactivar el desvio
    if ($accion == 'desactivar_desvio') {
        $id_t = (int)$_POST['id_tel'];
        $chk  = mysqli_query($con, "SELECT numero_desvio FROM telefono WHERE id_telefono=$id_t AND id_usuario=$uid");
        $row  = mysqli_fetch_assoc($chk);
        if (!$row || !$row['numero_desvio']) {
            $msg_err = "No hay un desvio activo en ese telefono.";
        } else {
            mysqli_query($con, "UPDATE telefono SET numero_desvio=NULL WHERE id_telefono=$id_t AND id_usuario=$uid");
            mysqli_query($con, "INSERT INTO historial (id_telefono,tipo_operacion,descripcion)
                                VALUES ($id_t,'Desvio cancelado','Desvio desactivado por usuario')");
            $msg_ok = "Desvio cancelado correctamente.";
        }
    }

    // Eliminar el telefono
    if ($accion == 'eliminar') {
        $id_t = (int)$_POST['id_tel'];
        mysqli_query($con, "DELETE FROM telefono WHERE id_telefono=$id_t AND id_usuario=$uid");
        $msg_ok = "Telefono eliminado.";
    }
}

// Tema y foto del usuario
$res_cfg = mysqli_query($con, "SELECT tema, foto_perfil FROM usuario WHERE id_usuario = $uid");
$cfg = mysqli_fetch_assoc($res_cfg);
$tema = $cfg['tema'];
$foto = $cfg['foto_perfil'];

// Telefonos del usuario
$res_t = mysqli_query($con, "SELECT * FROM telefono WHERE id_usuario=$uid ORDER BY fecha_registro");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Telefonos | Compania Telefonica</title>
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
.badge { display:inline-block; padding:3px 10px; border-radius:99px; font-size:.78rem; font-weight:700; }
.badge-green { background:#d1fae5; color:#065f46; }
.badge-gray { background:#f1f5f9; color:#475569; }
.badge-blue { background:#dbeafe; color:#1d4ed8; }
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
    <a class="active" href="conectar_telefono.php">Mis Telefonos</a>
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
    <h1 class="page-title">Mis Telefonos</h1>

    <?php if ($msg_ok): ?>
      <div class="alert alert-success"><?= $msg_ok ?></div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
      <div class="alert alert-error"><?= $msg_err ?></div>
    <?php endif; ?>

    <!-- Formulario para registrar telefono -->
    <div class="card">
      <h3>Registrar Nuevo Telefono</h3>
      <form method="POST">
        <input type="hidden" name="accion" value="agregar">
        <div class="form-row">
          <div class="form-group">
            <label>Numero telefonico *</label>
            <input type="text" name="numero" placeholder="Ej. 5510001111" maxlength="20">
          </div>
          <div class="form-group">
            <label>Gmail del dispositivo</label>
            <input type="email" name="gmail" placeholder="dispositivo@gmail.com">
          </div>
          <div class="form-group">
            <label>Marca</label>
            <input type="text" name="marca" placeholder="Samsung, iPhone...">
          </div>
          <div class="form-group">
            <label>Modelo</label>
            <input type="text" name="modelo" placeholder="Galaxy A54...">
          </div>
          <div class="form-group">
            <label>Operador</label>
            <input type="text" name="operador" placeholder="Telcel, AT&amp;T...">
          </div>
        </div>
        <button type="submit" class="btn btn-blue">Registrar Telefono</button>
      </form>
    </div>

    <!-- Lista de telefonos -->
    <?php if (mysqli_num_rows($res_t) == 0): ?>
      <div class="card"><p style="color:#64748b;">No tienes telefonos registrados aun.</p></div>
    <?php endif; ?>

    <?php while ($t = mysqli_fetch_assoc($res_t)): ?>
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
        <div>
          <div style="font-size:1.15rem;font-weight:800;color:#0057B7;"><?= $t['numero'] ?></div>
          <?php if ($t['marca'] || $t['modelo']): ?>
            <div style="font-size:.85rem;color:#64748b;">
              <?= htmlspecialchars(trim($t['marca'].' '.$t['modelo'])) ?>
              <?php if ($t['operador']): ?> - <?= htmlspecialchars($t['operador']) ?><?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($t['gmail']): ?>
            <div style="font-size:.82rem;color:#94a3b8;"><?= htmlspecialchars($t['gmail']) ?></div>
          <?php endif; ?>
          <div style="margin-top:6px;">
            <?php if ($t['estado']=='conectado'): ?>
              <span class="badge badge-green">Conectado</span>
            <?php else: ?>
              <span class="badge badge-gray">Desconectado</span>
            <?php endif; ?>
            <span style="font-size:.85rem;color:#64748b;margin-left:10px;">
              Saldo: <strong>$<?= number_format($t['saldo'],2) ?></strong>
            </span>
          </div>
          <?php if ($t['numero_desvio']): ?>
            <div style="margin-top:5px;font-size:.85rem;">
              <span class="badge badge-blue">Desvio <?= $t['tipo_desvio'] ?> hacia <?= $t['numero_desvio'] ?></span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Botones de accion -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
          <?php if ($t['estado']!='conectado'): ?>
          <form method="POST">
            <input type="hidden" name="accion" value="conectar">
            <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
            <button class="btn btn-green" style="padding:7px 14px;font-size:.85rem;">Conectar</button>
          </form>
          <?php else: ?>
          <form method="POST">
            <input type="hidden" name="accion" value="desconectar">
            <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
            <button class="btn btn-gray" style="padding:7px 14px;font-size:.85rem;">Desconectar</button>
          </form>
          <?php endif; ?>

          <button class="btn btn-blue" style="padding:7px 14px;font-size:.85rem;"
                  onclick="toggleDesvio(<?= $t['id_telefono'] ?>)">Desvio</button>

          <form method="POST" onsubmit="return confirm('Eliminar el telefono <?= $t['numero'] ?>?');">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
            <button class="btn btn-red" style="padding:7px 14px;font-size:.85rem;">Eliminar</button>
          </form>
        </div>
      </div>

      <!-- Panel de configuracion de desvio -->
      <div id="desvio-<?= $t['id_telefono'] ?>" style="display:none;margin-top:16px;padding-top:16px;border-top:1.5px dashed #cbd5e1;">
        <div style="font-size:.95rem;font-weight:700;color:#1e3a5f;margin-bottom:12px;">Configuracion de Desvio</div>
        <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:10px;">
          <input type="hidden" name="accion" value="activar_desvio">
          <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
          <div class="form-group" style="margin:0;min-width:180px;flex:1;">
            <label>Numero destino del desvio</label>
            <input type="text" name="numero_desvio" placeholder="Numero destino" value="<?= htmlspecialchars($t['numero_desvio'] ?? '') ?>">
          </div>
          <div class="form-group" style="margin:0;min-width:200px;">
            <label>Tipo de desvio</label>
            <select name="tipo_desvio">
              <option value="siempre" <?= ($t['tipo_desvio']??'')=='siempre'?'selected':'' ?>>Siempre</option>
              <option value="ocupado" <?= ($t['tipo_desvio']??'')=='ocupado'?'selected':'' ?>>Cuando este ocupado</option>
              <option value="no_responde" <?= ($t['tipo_desvio']??'')=='no_responde'?'selected':'' ?>>Cuando no responda</option>
            </select>
          </div>
          <button type="submit" class="btn btn-green" style="padding:9px 18px;">Activar Desvio</button>
        </form>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="accion" value="desactivar_desvio">
          <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
          <button type="submit" class="btn btn-red" style="padding:9px 18px;font-size:.88rem;" onclick="return confirm('Desactivar desvio?');">Desactivar Desvio</button>
        </form>
        <button type="button" class="btn btn-gray" style="padding:9px 16px;font-size:.88rem;" onclick="toggleDesvio(<?= $t['id_telefono'] ?>)">Cancelar</button>
      </div>
    </div>
    <?php endwhile; ?>

  </main>
</div>

<script>
// Muestra u oculta el panel de desvio de cada telefono
function toggleDesvio(id) {
    var el = document.getElementById('desvio-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

</body>
</html>
