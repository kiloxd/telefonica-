<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

$msg_ok  = "";
$msg_err = "";

// Procesar acciones de usuarios
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // Crear un usuario nuevo
    if ($accion == 'crear') {
        $nombre    = mysqli_real_escape_string($con, trim($_POST['nombre']));
        $apellidos = mysqli_real_escape_string($con, trim($_POST['apellidos']));
        $direccion = mysqli_real_escape_string($con, trim($_POST['direccion']));
        $correo    = mysqli_real_escape_string($con, trim($_POST['correo']));
        $pass      = $_POST['contrasena'];
        $estado    = in_array($_POST['estado'], array('activo','inactivo','bloqueado')) ? $_POST['estado'] : 'activo';

        if (empty($nombre) || empty($correo) || empty($pass)) {
            $msg_err = "Nombre, correo y contrasena son obligatorios.";
        } else {
            $chk = mysqli_query($con, "SELECT id_usuario FROM usuario WHERE correo='$correo'");
            if (mysqli_num_rows($chk) > 0) {
                $msg_err = "El correo ya esta registrado.";
            } else {
                $hash = md5($pass);
                mysqli_query($con, "INSERT INTO persona (nombre, apellidos, direccion) VALUES ('$nombre','$apellidos','$direccion')");
                $id_p = mysqli_insert_id($con);
                mysqli_query($con, "INSERT INTO usuario (id_persona, correo, contrasena, estado) VALUES ($id_p, '$correo', '$hash', '$estado')");
                $msg_ok = "Usuario creado correctamente.";
            }
        }
    }

    // Editar un usuario
    if ($accion == 'editar') {
        $id_u      = (int)$_POST['id_usuario'];
        $nombre    = mysqli_real_escape_string($con, trim($_POST['nombre']));
        $apellidos = mysqli_real_escape_string($con, trim($_POST['apellidos']));
        $direccion = mysqli_real_escape_string($con, trim($_POST['direccion']));
        $estado    = in_array($_POST['estado'], array('activo','inactivo','bloqueado')) ? $_POST['estado'] : 'activo';
        $nueva_pass = $_POST['nueva_pass'];

        $res_p = mysqli_query($con, "SELECT id_persona FROM usuario WHERE id_usuario=$id_u");
        $id_p  = mysqli_fetch_assoc($res_p)['id_persona'];
        mysqli_query($con, "UPDATE persona SET nombre='$nombre', apellidos='$apellidos', direccion='$direccion' WHERE id_persona=$id_p");
        mysqli_query($con, "UPDATE usuario SET estado='$estado' WHERE id_usuario=$id_u");
        if (!empty($nueva_pass)) {
            $hash = md5($nueva_pass);
            mysqli_query($con, "UPDATE usuario SET contrasena='$hash' WHERE id_usuario=$id_u");
        }
        $msg_ok = "Usuario actualizado correctamente.";
    }

    // Eliminar usuario y sus datos relacionados
    if ($accion == 'eliminar') {
        $id_u = (int)$_POST['id_usuario'];
        $res_tels = mysqli_query($con, "SELECT id_telefono FROM telefono WHERE id_usuario=$id_u");
        while ($t = mysqli_fetch_assoc($res_tels)) {
            $id_t = $t['id_telefono'];
            mysqli_query($con, "DELETE FROM mensaje   WHERE id_tel_emisor=$id_t OR id_tel_receptor=$id_t");
            mysqli_query($con, "DELETE FROM recarga   WHERE id_telefono=$id_t");
            mysqli_query($con, "DELETE FROM historial WHERE id_telefono=$id_t");
            mysqli_query($con, "DELETE FROM contacto  WHERE id_tel_dueno=$id_t OR id_tel_contacto=$id_t");
            mysqli_query($con, "DELETE FROM incidencia WHERE id_telefono=$id_t");
        }
        mysqli_query($con, "DELETE FROM telefono WHERE id_usuario=$id_u");
        mysqli_query($con, "DELETE FROM sesion   WHERE id_usuario=$id_u");
        $res_p = mysqli_query($con, "SELECT id_persona FROM usuario WHERE id_usuario=$id_u");
        $id_p  = mysqli_fetch_assoc($res_p)['id_persona'];
        mysqli_query($con, "DELETE FROM usuario WHERE id_usuario=$id_u");
        mysqli_query($con, "DELETE FROM persona  WHERE id_persona=$id_p");
        $msg_ok = "Usuario eliminado.";
    }
}

// Buscador de usuarios
$buscar = isset($_GET['buscar']) ? mysqli_real_escape_string($con, trim($_GET['buscar'])) : '';
$where  = $buscar ? "WHERE p.nombre LIKE '%$buscar%' OR p.apellidos LIKE '%$buscar%' OR u.correo LIKE '%$buscar%'" : '';
$res_u = mysqli_query($con, "SELECT u.id_usuario, p.nombre, p.apellidos, p.direccion, u.correo, u.estado, u.fecha_registro,
                                    COUNT(t.id_telefono) as total_tels
                             FROM usuario u
                             INNER JOIN persona p ON u.id_persona = p.id_persona
                             LEFT JOIN telefono t ON u.id_usuario = t.id_usuario
                             $where GROUP BY u.id_usuario ORDER BY u.fecha_registro DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Usuarios | Compania Telefonica</title>
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
    <a class="active" href="gestion_usuarios.php">Usuarios</a>
    <a href="gestion_telefonos.php">Telefonos</a>
    <a href="reportes.php">Reportes</a>
    <div class="sep"></div>
    <a href="logout.php">Cerrar Sesion</a>
  </nav>

  <!-- CONTENIDO -->
  <main class="content">
    <h1 class="page-title">Usuarios</h1>

    <?php if ($msg_ok): ?>
      <div class="alert alert-success"><?= $msg_ok ?></div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
      <div class="alert alert-error"><?= $msg_err ?></div>
    <?php endif; ?>

    <!-- Buscador y boton nuevo -->
    <div class="card">
      <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <form method="GET" style="display:flex;gap:12px;flex:1;align-items:flex-end;flex-wrap:wrap;">
          <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label>Buscar usuario</label>
            <input type="text" name="buscar" placeholder="Nombre, apellido o correo..." value="<?= htmlspecialchars($buscar) ?>">
          </div>
          <button type="submit" class="btn btn-blue">Buscar</button>
          <?php if ($buscar): ?><a href="gestion_usuarios.php" class="btn btn-gray">Limpiar</a><?php endif; ?>
        </form>
        <button onclick="toggleForm('form-nuevo')" class="btn btn-green">Nuevo Usuario</button>
      </div>
    </div>

    <!-- Formulario nuevo usuario -->
    <div id="form-nuevo" style="display:none;">
      <div class="card" style="border:2px solid #059669;">
        <h3 style="color:#059669;">Crear Nuevo Usuario</h3>
        <form method="POST">
          <input type="hidden" name="accion" value="crear">
          <div class="form-row">
            <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" placeholder="Juan" required></div>
            <div class="form-group"><label>Apellidos</label><input type="text" name="apellidos" placeholder="Perez Garcia"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Correo *</label><input type="email" name="correo" placeholder="correo@ejemplo.com" required></div>
            <div class="form-group"><label>Contrasena *</label><input type="password" name="contrasena" placeholder="Min. 4 caracteres" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Direccion</label><input type="text" name="direccion" placeholder="Calle y numero"></div>
            <div class="form-group"><label>Estado inicial</label>
              <select name="estado"><option value="activo">Activo</option><option value="inactivo">Inactivo</option><option value="bloqueado">Bloqueado</option></select>
            </div>
          </div>
          <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-green">Crear Usuario</button>
            <button type="button" onclick="toggleForm('form-nuevo')" class="btn btn-gray">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Lista de usuarios -->
    <div class="card">
      <h3>Lista de Usuarios (<?= mysqli_num_rows($res_u) ?>)</h3>
      <table>
        <tr>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Telefonos</th>
          <th>Estado</th>
          <th>Registro</th>
          <th>Acciones</th>
        </tr>
        <?php while ($u = mysqli_fetch_assoc($res_u)): ?>
        <tr>
          <td><strong><?= htmlspecialchars($u['nombre'].' '.$u['apellidos']) ?></strong></td>
          <td style="font-size:.85rem;"><?= htmlspecialchars($u['correo']) ?></td>
          <td style="text-align:center;"><?= $u['total_tels'] ?></td>
          <td><span class="badge <?= $u['estado']=='activo'?'badge-green':($u['estado']=='bloqueado'?'badge-red':'badge-gray') ?>"><?= $u['estado'] ?></span></td>
          <td style="font-size:.8rem;color:#64748b;"><?= substr($u['fecha_registro'],0,10) ?></td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
              <button onclick="toggleForm('edit-<?= $u['id_usuario'] ?>')" class="btn btn-blue" style="padding:5px 10px;font-size:.8rem;">Editar</button>
              <form method="POST" onsubmit="return confirm('Eliminar usuario y todos sus datos?');">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                <button class="btn btn-red" style="padding:5px 10px;font-size:.8rem;">Eliminar</button>
              </form>
            </div>
            <!-- Formulario de edicion -->
            <div id="edit-<?= $u['id_usuario'] ?>" style="display:none;margin-top:10px;background:#f8faff;border-radius:8px;padding:14px;">
              <form method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                  <div style="flex:1;min-width:130px;"><label style="font-size:.8rem;font-weight:600;">Nombre</label><input type="text" name="nombre" value="<?= htmlspecialchars($u['nombre']) ?>" style="padding:6px 10px;font-size:.85rem;"></div>
                  <div style="flex:1;min-width:130px;"><label style="font-size:.8rem;font-weight:600;">Apellidos</label><input type="text" name="apellidos" value="<?= htmlspecialchars($u['apellidos']) ?>" style="padding:6px 10px;font-size:.85rem;"></div>
                  <div style="flex:1;min-width:130px;"><label style="font-size:.8rem;font-weight:600;">Direccion</label><input type="text" name="direccion" value="<?= htmlspecialchars($u['direccion']) ?>" style="padding:6px 10px;font-size:.85rem;"></div>
                  <div style="flex:1;min-width:120px;"><label style="font-size:.8rem;font-weight:600;">Nueva contrasena (opcional)</label><input type="password" name="nueva_pass" placeholder="Dejar vacio para no cambiar" style="padding:6px 10px;font-size:.85rem;"></div>
                  <div style="min-width:120px;"><label style="font-size:.8rem;font-weight:600;">Estado</label>
                    <select name="estado" style="padding:6px 10px;font-size:.85rem;">
                      <option value="activo" <?= $u['estado']=='activo'?'selected':'' ?>>Activo</option>
                      <option value="inactivo" <?= $u['estado']=='inactivo'?'selected':'' ?>>Inactivo</option>
                      <option value="bloqueado" <?= $u['estado']=='bloqueado'?'selected':'' ?>>Bloqueado</option>
                    </select>
                  </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:10px;">
                  <button type="submit" class="btn btn-green" style="padding:6px 16px;font-size:.85rem;">Guardar</button>
                  <button type="button" onclick="toggleForm('edit-<?= $u['id_usuario'] ?>')" class="btn btn-gray" style="padding:6px 14px;font-size:.85rem;">Cancelar</button>
                </div>
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
// Muestra u oculta los formularios de nuevo usuario y edicion
function toggleForm(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

</body>
</html>
