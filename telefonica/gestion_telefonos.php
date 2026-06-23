<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

$msg_ok  = "";
$msg_err = "";

// Procesar acciones de telefonos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // Registrar un telefono y asignarlo a un usuario
    if ($accion == 'registrar') {
        $id_usuario = (int)$_POST['id_usuario'];
        $numero     = mysqli_real_escape_string($con, trim($_POST['numero']));
        $gmail      = mysqli_real_escape_string($con, trim($_POST['gmail']));
        $marca      = mysqli_real_escape_string($con, trim($_POST['marca']));
        $modelo     = mysqli_real_escape_string($con, trim($_POST['modelo']));
        $operador   = mysqli_real_escape_string($con, trim($_POST['operador']));
        $estado     = in_array($_POST['estado'], array('conectado','desconectado','inactivo')) ? $_POST['estado'] : 'desconectado';

        if (empty($numero)) {
            $msg_err = "El numero es obligatorio.";
        } else {
            $chk = mysqli_query($con, "SELECT id_telefono FROM telefono WHERE numero='$numero'");
            if (mysqli_num_rows($chk) > 0) {
                $msg_err = "Ese numero ya esta registrado.";
            } else {
                mysqli_query($con, "INSERT INTO telefono (id_usuario, numero, gmail, marca, modelo, operador, saldo, estado)
                                    VALUES ($id_usuario, '$numero', '$gmail', '$marca', '$modelo', '$operador', 0.00, '$estado')");
                $id_t = mysqli_insert_id($con);
                mysqli_query($con, "INSERT INTO historial (id_telefono, tipo_operacion, descripcion) VALUES ($id_t, 'Registro admin', 'Telefono registrado por administrador')");
                $msg_ok = "Telefono $numero registrado correctamente.";
            }
        }
    }

    // Editar datos del telefono
    if ($accion == 'editar') {
        $id_t     = (int)$_POST['id_tel'];
        $gmail    = mysqli_real_escape_string($con, trim($_POST['gmail']));
        $marca    = mysqli_real_escape_string($con, trim($_POST['marca']));
        $modelo   = mysqli_real_escape_string($con, trim($_POST['modelo']));
        $operador = mysqli_real_escape_string($con, trim($_POST['operador']));
        $estado   = in_array($_POST['estado'], array('conectado','desconectado','inactivo')) ? $_POST['estado'] : 'desconectado';
        mysqli_query($con, "UPDATE telefono SET gmail='$gmail', marca='$marca', modelo='$modelo', operador='$operador', estado='$estado' WHERE id_telefono=$id_t");
        $msg_ok = "Telefono actualizado.";
    }

    // Ajustar el saldo del telefono
    if ($accion == 'ajustar_saldo') {
        $id_t  = (int)$_POST['id_tel'];
        $monto = (float)$_POST['monto'];
        $tipo  = $_POST['tipo'];
        if ($tipo == 'agregar') {
            mysqli_query($con, "UPDATE telefono SET saldo = saldo + $monto WHERE id_telefono=$id_t");
        } else {
            mysqli_query($con, "UPDATE telefono SET saldo = GREATEST(0, saldo - $monto) WHERE id_telefono=$id_t");
        }
        $msg_ok = "Saldo ajustado.";
    }

    // Quitar el desvio
    if ($accion == 'limpiar_desvio') {
        $id_t = (int)$_POST['id_tel'];
        mysqli_query($con, "UPDATE telefono SET numero_desvio=NULL WHERE id_telefono=$id_t");
        $msg_ok = "Desvio cancelado.";
    }

    // Eliminar el telefono y sus datos
    if ($accion == 'eliminar') {
        $id_t = (int)$_POST['id_tel'];
        mysqli_query($con, "DELETE FROM mensaje   WHERE id_tel_emisor=$id_t OR id_tel_receptor=$id_t");
        mysqli_query($con, "DELETE FROM recarga   WHERE id_telefono=$id_t");
        mysqli_query($con, "DELETE FROM historial WHERE id_telefono=$id_t");
        mysqli_query($con, "DELETE FROM contacto  WHERE id_tel_dueno=$id_t OR id_tel_contacto=$id_t");
        mysqli_query($con, "DELETE FROM incidencia WHERE id_telefono=$id_t");
        mysqli_query($con, "DELETE FROM telefono  WHERE id_telefono=$id_t");
        $msg_ok = "Telefono eliminado.";
    }
}

// Filtros de busqueda
$buscar        = isset($_GET['buscar']) ? mysqli_real_escape_string($con, $_GET['buscar']) : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$where_parts   = array();
if ($buscar) $where_parts[] = "(t.numero LIKE '%$buscar%' OR p.nombre LIKE '%$buscar%' OR t.marca LIKE '%$buscar%')";
if ($filtro_estado && in_array($filtro_estado, array('conectado','desconectado','inactivo'))) $where_parts[] = "t.estado='$filtro_estado'";
$where = count($where_parts) ? "WHERE ".implode(' AND ',$where_parts) : '';

$res_t = mysqli_query($con, "SELECT t.*, p.nombre, p.apellidos, u.id_usuario FROM telefono t
                             INNER JOIN usuario u ON t.id_usuario = u.id_usuario
                             INNER JOIN persona p ON u.id_persona = p.id_persona
                             $where ORDER BY t.estado, t.fecha_registro DESC");

$res_users = mysqli_query($con, "SELECT u.id_usuario, p.nombre, p.apellidos FROM usuario u INNER JOIN persona p ON u.id_persona=p.id_persona ORDER BY p.nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion de Telefonos | Compania Telefonica</title>
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
.form-row { display:flex; gap:14px; flex-wrap:wrap; }
.form-group { margin-bottom:16px; flex:1; min-width:180px; }
label { display:block; font-size:.83rem; font-weight:600; color:#374151; margin-bottom:5px; }
input, select { width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:.9rem; font-family:inherit; }
table { width:100%; border-collapse:collapse; font-size:.9rem; }
th { background:#eff4fb; color:#1e3a5f; font-weight:700; padding:10px 12px; text-align:left; }
td { padding:9px 12px; border-bottom:1px solid #f1f5f9; color:#374151; }
.badge { display:inline-block; padding:3px 10px; border-radius:99px; font-size:.78rem; font-weight:700; }
.badge-green { background:#d1fae5; color:#065f46; }
.badge-gray { background:#f1f5f9; color:#475569; }
.badge-red { background:#fee2e2; color:#b91c1c; }
.btn { padding:10px 24px; border:none; border-radius:7px; font-size:.9rem; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-blue { background:#0057B7; color:#fff; }
.btn-green { background:#059669; color:#fff; }
.btn-red { background:#e53e3e; color:#fff; }
.btn-gray { background:#e2e8f0; color:#374151; }
.btn-yellow { background:#f59e0b; color:#1e3a5f; }
.alert { padding:11px 16px; border-radius:8px; font-size:.88rem; margin-bottom:16px; }
.alert-error { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
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
    <a class="active" href="gestion_telefonos.php">Telefonos</a>
    <a href="reportes.php">Reportes</a>
    <div class="sep"></div>
    <a href="logout.php">Cerrar Sesion</a>
  </nav>

  <!-- CONTENIDO -->
  <main class="content">
    <h1 class="page-title">Gestion de Telefonos</h1>

    <?php if ($msg_ok): ?>
      <div class="alert alert-success"><?= $msg_ok ?></div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
      <div class="alert alert-error"><?= $msg_err ?></div>
    <?php endif; ?>

    <!-- Filtros y boton registrar -->
    <div class="card">
      <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <form method="GET" style="display:flex;gap:12px;flex:1;align-items:flex-end;flex-wrap:wrap;">
          <div class="form-group" style="margin:0;flex:1;min-width:180px;">
            <label>Buscar</label>
            <input type="text" name="buscar" placeholder="Numero, nombre, marca..." value="<?= htmlspecialchars($buscar) ?>">
          </div>
          <div class="form-group" style="margin:0;min-width:150px;">
            <label>Estado</label>
            <select name="estado">
              <option value="">Todos</option>
              <option value="conectado" <?= $filtro_estado=='conectado'?'selected':'' ?>>Conectados</option>
              <option value="desconectado" <?= $filtro_estado=='desconectado'?'selected':'' ?>>Desconectados</option>
              <option value="inactivo" <?= $filtro_estado=='inactivo'?'selected':'' ?>>Inactivos</option>
            </select>
          </div>
          <button type="submit" class="btn btn-blue">Filtrar</button>
          <a href="gestion_telefonos.php" class="btn btn-gray">Limpiar</a>
        </form>
        <button onclick="toggleForm('form-nuevo')" class="btn btn-green">Registrar Telefono</button>
      </div>
    </div>

    <!-- Formulario registrar telefono -->
    <div id="form-nuevo" style="display:none;">
      <div class="card" style="border:2px solid #059669;">
        <h3 style="color:#059669;">Registro de Telefono</h3>
        <form method="POST">
          <input type="hidden" name="accion" value="registrar">
          <div class="form-row">
            <div class="form-group">
              <label>Usuario propietario *</label>
              <select name="id_usuario">
                <?php while ($usr = mysqli_fetch_assoc($res_users)): ?>
                  <option value="<?= $usr['id_usuario'] ?>"><?= htmlspecialchars($usr['nombre'].' '.$usr['apellidos']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group"><label>Numero telefonico *</label><input type="text" name="numero" placeholder="5510001111" maxlength="20"></div>
            <div class="form-group"><label>Gmail / Correo del dispositivo</label><input type="email" name="gmail" placeholder="dispositivo@gmail.com"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Marca</label><input type="text" name="marca" placeholder="Samsung, iPhone..."></div>
            <div class="form-group"><label>Modelo</label><input type="text" name="modelo" placeholder="Galaxy A54..."></div>
            <div class="form-group"><label>Operador</label><input type="text" name="operador" placeholder="Telcel, AT&amp;T..."></div>
            <div class="form-group"><label>Estado inicial</label>
              <select name="estado"><option value="desconectado">Desconectado</option><option value="conectado">Conectado</option><option value="inactivo">Inactivo</option></select>
            </div>
          </div>
          <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-green">Registrar</button>
            <button type="button" onclick="toggleForm('form-nuevo')" class="btn btn-gray">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabla de telefonos -->
    <div class="card">
      <h3>Telefonos Registrados (<?= mysqli_num_rows($res_t) ?>)</h3>
      <table>
        <tr>
          <th>Numero</th>
          <th>Usuario</th>
          <th>Marca/Modelo</th>
          <th>Operador</th>
          <th>Saldo</th>
          <th>Estado</th>
          <th>Desvio</th>
          <th>Acciones</th>
        </tr>
        <?php while ($t = mysqli_fetch_assoc($res_t)): ?>
        <tr>
          <td><strong><?= $t['numero'] ?></strong><?php if ($t['gmail']): ?><br><span style="font-size:.78rem;color:#64748b;"><?= htmlspecialchars($t['gmail']) ?></span><?php endif; ?></td>
          <td><?= htmlspecialchars($t['nombre'].' '.$t['apellidos']) ?></td>
          <td style="font-size:.85rem;"><?= htmlspecialchars(($t['marca']?$t['marca']:'---').' '.($t['modelo']?$t['modelo']:'')) ?></td>
          <td style="font-size:.85rem;"><?= htmlspecialchars($t['operador'] ?: '---') ?></td>
          <td>$<?= number_format($t['saldo'],2) ?></td>
          <td><span class="badge <?= $t['estado']=='conectado'?'badge-green':($t['estado']=='inactivo'?'badge-red':'badge-gray') ?>"><?= $t['estado'] ?></span></td>
          <td style="font-size:.82rem;"><?= $t['numero_desvio'] ?: '---' ?></td>
          <td>
            <div style="display:flex;gap:4px;flex-wrap:wrap;">
              <button onclick="toggleForm('edit-t-<?= $t['id_telefono'] ?>')" class="btn btn-blue" style="padding:4px 9px;font-size:.78rem;">Editar</button>
              <button onclick="toggleForm('saldo-<?= $t['id_telefono'] ?>')" class="btn btn-yellow" style="padding:4px 9px;font-size:.78rem;">Saldo</button>
              <?php if ($t['numero_desvio']): ?>
              <form method="POST">
                <input type="hidden" name="accion" value="limpiar_desvio">
                <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
                <button class="btn btn-gray" style="padding:4px 9px;font-size:.78rem;">Sin desvio</button>
              </form>
              <?php endif; ?>
              <form method="POST" onsubmit="return confirm('Eliminar telefono?');">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
                <button class="btn btn-red" style="padding:4px 9px;font-size:.78rem;">Eliminar</button>
              </form>
            </div>
            <!-- Editar telefono -->
            <div id="edit-t-<?= $t['id_telefono'] ?>" style="display:none;margin-top:8px;background:#f8faff;border-radius:8px;padding:12px;">
              <form method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                  <div style="flex:1;min-width:120px;"><label style="font-size:.78rem;font-weight:600;">Gmail</label><input type="text" name="gmail" value="<?= htmlspecialchars($t['gmail']) ?>" style="padding:5px 8px;font-size:.82rem;"></div>
                  <div style="flex:1;min-width:100px;"><label style="font-size:.78rem;font-weight:600;">Marca</label><input type="text" name="marca" value="<?= htmlspecialchars($t['marca']) ?>" style="padding:5px 8px;font-size:.82rem;"></div>
                  <div style="flex:1;min-width:100px;"><label style="font-size:.78rem;font-weight:600;">Modelo</label><input type="text" name="modelo" value="<?= htmlspecialchars($t['modelo']) ?>" style="padding:5px 8px;font-size:.82rem;"></div>
                  <div style="flex:1;min-width:100px;"><label style="font-size:.78rem;font-weight:600;">Operador</label><input type="text" name="operador" value="<?= htmlspecialchars($t['operador']) ?>" style="padding:5px 8px;font-size:.82rem;"></div>
                  <div style="min-width:110px;"><label style="font-size:.78rem;font-weight:600;">Estado</label>
                    <select name="estado" style="padding:5px 8px;font-size:.82rem;">
                      <option value="conectado" <?= $t['estado']=='conectado'?'selected':'' ?>>Conectado</option>
                      <option value="desconectado" <?= $t['estado']=='desconectado'?'selected':'' ?>>Desconectado</option>
                      <option value="inactivo" <?= $t['estado']=='inactivo'?'selected':'' ?>>Inactivo</option>
                    </select>
                  </div>
                </div>
                <div style="display:flex;gap:6px;margin-top:8px;">
                  <button type="submit" class="btn btn-green" style="padding:5px 14px;font-size:.82rem;">Guardar</button>
                  <button type="button" onclick="toggleForm('edit-t-<?= $t['id_telefono'] ?>')" class="btn btn-gray" style="padding:5px 12px;font-size:.82rem;">Cancelar</button>
                </div>
              </form>
            </div>
            <!-- Ajustar saldo -->
            <div id="saldo-<?= $t['id_telefono'] ?>" style="display:none;margin-top:8px;background:#fffbeb;border-radius:8px;padding:10px;">
              <form method="POST" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="accion" value="ajustar_saldo">
                <input type="hidden" name="id_tel" value="<?= $t['id_telefono'] ?>">
                <input type="number" name="monto" step="0.01" min="0.01" placeholder="Monto" style="width:85px;padding:5px 8px;font-size:.82rem;">
                <select name="tipo" style="padding:5px 8px;font-size:.82rem;width:auto;"><option value="agregar">Agregar</option><option value="descontar">Descontar</option></select>
                <button type="submit" class="btn btn-green" style="padding:5px 12px;font-size:.82rem;">OK</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </table>
    </div>

  </main>
</div>

<script>
// Muestra u oculta los formularios de registro, edicion y saldo
function toggleForm(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

</body>
</html>
