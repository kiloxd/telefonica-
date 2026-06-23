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

// Crear carpeta de fotos si no existe
if (!file_exists('uploads/fotos')) {
    mkdir('uploads/fotos', 0777, true);
}

// Datos del usuario
$res_u = mysqli_query($con, "SELECT u.*, p.nombre, p.apellidos, p.direccion
                              FROM usuario u INNER JOIN persona p ON u.id_persona=p.id_persona
                              WHERE u.id_usuario=$uid");
$u = mysqli_fetch_assoc($res_u);

// Procesar acciones de configuracion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // Guardar datos personales
    if ($accion == 'datos') {
        $nombre    = mysqli_real_escape_string($con, trim($_POST['nombre']));
        $apellidos = mysqli_real_escape_string($con, trim($_POST['apellidos']));
        $direccion = mysqli_real_escape_string($con, trim($_POST['direccion']));
        $res_p = mysqli_query($con, "SELECT id_persona FROM usuario WHERE id_usuario=$uid");
        $id_p  = mysqli_fetch_assoc($res_p)['id_persona'];
        mysqli_query($con, "UPDATE persona SET nombre='$nombre', apellidos='$apellidos', direccion='$direccion' WHERE id_persona=$id_p");
        $_SESSION['nombre'] = $nombre;
        $msg_ok = "Datos personales actualizados.";
    }

    // Subir nueva foto de perfil
    if ($accion == 'foto') {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $ext_ok = array('jpg','jpeg','png','gif','webp');
            $ext    = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $ext_ok)) {
                $msg_err = "Solo se permiten imagenes JPG, PNG, GIF o WEBP.";
            } elseif ($_FILES['foto']['size'] > 2097152) {
                $msg_err = "La imagen no debe pesar mas de 2MB.";
            } else {
                if ($u['foto_perfil'] && file_exists('uploads/fotos/'.$u['foto_perfil'])) {
                    unlink('uploads/fotos/'.$u['foto_perfil']);
                }
                $nombre_archivo = 'user_'.$uid.'_'.time().'.'.$ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/fotos/'.$nombre_archivo);
                mysqli_query($con, "UPDATE usuario SET foto_perfil='$nombre_archivo' WHERE id_usuario=$uid");
                $msg_ok = "Foto de perfil actualizada.";
            }
        } else {
            $msg_err = "No se recibio ningun archivo.";
        }
    }

    // Quitar la foto de perfil
    if ($accion == 'quitar_foto') {
        if ($u['foto_perfil'] && file_exists('uploads/fotos/'.$u['foto_perfil'])) {
            unlink('uploads/fotos/'.$u['foto_perfil']);
        }
        mysqli_query($con, "UPDATE usuario SET foto_perfil=NULL WHERE id_usuario=$uid");
        $msg_ok = "Foto de perfil eliminada.";
    }

    // Cambiar la contrasena
    if ($accion == 'contrasena') {
        $actual   = md5($_POST['pass_actual']);
        $nueva    = $_POST['pass_nueva'];
        $confirma = $_POST['pass_confirma'];
        $chk = mysqli_query($con, "SELECT id_usuario FROM usuario WHERE id_usuario=$uid AND contrasena='$actual'");
        if (mysqli_num_rows($chk) == 0) {
            $msg_err = "La contrasena actual es incorrecta.";
        } elseif ($nueva != $confirma) {
            $msg_err = "Las nuevas contrasenas no coinciden.";
        } elseif (strlen($nueva) < 4) {
            $msg_err = "La nueva contrasena debe tener al menos 4 caracteres.";
        } else {
            $hash = md5($nueva);
            mysqli_query($con, "UPDATE usuario SET contrasena='$hash' WHERE id_usuario=$uid");
            $msg_ok = "Contrasena cambiada correctamente.";
        }
    }

    // Guardar preferencias de tema y notificaciones
    if ($accion == 'preferencias') {
        $notif = isset($_POST['notificaciones']) ? 1 : 0;
        $tema_n = in_array($_POST['tema'], array('claro','oscuro')) ? $_POST['tema'] : 'claro';
        mysqli_query($con, "UPDATE usuario SET notificaciones=$notif, tema='$tema_n' WHERE id_usuario=$uid");
        $msg_ok = "Preferencias guardadas.";
    }

    // Recargar datos despues de cambios
    $res_u = mysqli_query($con, "SELECT u.*, p.nombre, p.apellidos, p.direccion
                                  FROM usuario u INNER JOIN persona p ON u.id_persona=p.id_persona
                                  WHERE u.id_usuario=$uid");
    $u = mysqli_fetch_assoc($res_u);
}

// Telefonos del usuario para la pestana de bloqueos
$res_t = mysqli_query($con, "SELECT * FROM telefono WHERE id_usuario=$uid");
$tels  = array();
while ($r = mysqli_fetch_assoc($res_t)) { $tels[] = $r; }
$ids_t = !empty($tels) ? implode(',', array_map(function($t){ return $t['id_telefono']; }, $tels)) : '0';

$tema = $u['tema'];
$foto = $u['foto_perfil'];
$tab  = isset($_GET['tab']) ? $_GET['tab'] : 'perfil';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configuracion | Compania Telefonica</title>
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
.form-group { margin-bottom:16px; }
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
.tabs { display:flex; gap:0; margin-bottom:22px; border:1.5px solid #cbd5e1; border-radius:9px; overflow:hidden; max-width:600px; }
.tabs a { flex:1; text-align:center; padding:10px 6px; font-weight:600; font-size:.85rem; text-decoration:none; color:#64748b; background:#f8fafc; }
.tabs a.activo { background:#0057B7; color:#fff; }
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
      <div class="avatar-letra"><?= strtoupper(substr($u['nombre'],0,1)) ?></div>
    <?php endif; ?>
    Hola, <strong><?= htmlspecialchars($u['nombre']) ?></strong>
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
    <a class="active" href="configuracion.php">Configuracion</a>
    <a href="sesiones.php">Sesiones</a>
    <div class="sep"></div>
    <a href="logout.php">Cerrar Sesion</a>
  </nav>

  <!-- CONTENIDO -->
  <main class="content">
    <h1 class="page-title">Configuracion</h1>

    <?php if ($msg_ok): ?>
      <div class="alert alert-success"><?= $msg_ok ?></div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
      <div class="alert alert-error"><?= $msg_err ?></div>
    <?php endif; ?>

    <!-- Pestanas -->
    <div class="tabs">
      <a href="?tab=perfil" class="<?= $tab=='perfil'?'activo':'' ?>">Perfil</a>
      <a href="?tab=contrasena" class="<?= $tab=='contrasena'?'activo':'' ?>">Contrasena</a>
      <a href="?tab=preferencias" class="<?= $tab=='preferencias'?'activo':'' ?>">Preferencias</a>
      <a href="?tab=bloqueos" class="<?= $tab=='bloqueos'?'activo':'' ?>">Bloqueos</a>
    </div>

    <?php if ($tab == 'perfil'): ?>
    <div style="display:flex;gap:20px;flex-wrap:wrap;">
      <!-- Foto de perfil -->
      <div class="card" style="min-width:220px;max-width:260px;text-align:center;">
        <h3>Foto de Perfil</h3>
        <div style="margin:16px auto;">
          <?php if ($foto && file_exists('uploads/fotos/'.$foto)): ?>
            <img src="uploads/fotos/<?= htmlspecialchars($foto) ?>" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #0057B7;" alt="foto">
          <?php else: ?>
            <div style="width:120px;height:120px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:2.8rem;color:#0057B7;"><?= strtoupper(substr($u['nombre'],0,1)) ?></div>
          <?php endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="accion" value="foto">
          <div class="form-group" style="text-align:left;">
            <label>Seleccionar imagen</label>
            <input type="file" name="foto" accept="image/*" style="font-size:.82rem;">
          </div>
          <button type="submit" class="btn btn-blue" style="width:100%;padding:9px;">Subir Foto</button>
        </form>
        <?php if ($foto): ?>
        <form method="POST" style="margin-top:8px;" onsubmit="return confirm('Eliminar foto de perfil?');">
          <input type="hidden" name="accion" value="quitar_foto">
          <button type="submit" class="btn btn-gray" style="width:100%;padding:8px;font-size:.85rem;">Quitar Foto</button>
        </form>
        <?php endif; ?>
      </div>

      <!-- Datos personales -->
      <div class="card" style="flex:1;min-width:280px;">
        <h3>Datos Personales</h3>
        <form method="POST">
          <input type="hidden" name="accion" value="datos">
          <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($u['nombre']) ?>" required>
          </div>
          <div class="form-group">
            <label>Apellidos</label>
            <input type="text" name="apellidos" value="<?= htmlspecialchars($u['apellidos']) ?>" required>
          </div>
          <div class="form-group">
            <label>Direccion</label>
            <input type="text" name="direccion" value="<?= htmlspecialchars($u['direccion']) ?>">
          </div>
          <div class="form-group">
            <label>Correo (solo lectura)</label>
            <input type="text" value="<?= htmlspecialchars($u['correo']) ?>" disabled style="background:#f8fafc;color:#94a3b8;">
          </div>
          <button type="submit" class="btn btn-blue">Guardar Cambios</button>
        </form>
      </div>
    </div>

    <?php elseif ($tab == 'contrasena'): ?>
    <div class="card" style="max-width:420px;">
      <h3>Cambiar Contrasena</h3>
      <form method="POST">
        <input type="hidden" name="accion" value="contrasena">
        <div class="form-group">
          <label>Contrasena actual</label>
          <input type="password" name="pass_actual" placeholder="Tu contrasena actual" required>
        </div>
        <div class="form-group">
          <label>Nueva contrasena</label>
          <input type="password" name="pass_nueva" placeholder="Min. 4 caracteres" required>
        </div>
        <div class="form-group">
          <label>Confirmar nueva contrasena</label>
          <input type="password" name="pass_confirma" placeholder="Repite la contrasena" required>
        </div>
        <button type="submit" class="btn btn-red">Cambiar Contrasena</button>
      </form>
    </div>

    <?php elseif ($tab == 'preferencias'): ?>
    <div class="card" style="max-width:420px;">
      <h3>Preferencias del Sistema</h3>
      <form method="POST">
        <input type="hidden" name="accion" value="preferencias">
        <div class="form-group">
          <label style="font-size:.95rem;font-weight:700;">Tema de la interfaz</label>
          <div style="display:flex;gap:14px;margin-top:10px;">
            <label style="display:flex;align-items:center;gap:8px;font-weight:400;cursor:pointer;background:#fff;border:2px solid <?= $u['tema']=='claro'?'#0057B7':'#cbd5e1' ?>;border-radius:10px;padding:12px 18px;flex:1;justify-content:center;color:#374151;">
              <input type="radio" name="tema" value="claro" <?= $u['tema']=='claro'?'checked':'' ?> style="width:auto;"> Claro
            </label>
            <label style="display:flex;align-items:center;gap:8px;font-weight:400;cursor:pointer;background:#1e293b;color:#e2e8f0;border:2px solid <?= $u['tema']=='oscuro'?'#0057B7':'#334155' ?>;border-radius:10px;padding:12px 18px;flex:1;justify-content:center;">
              <input type="radio" name="tema" value="oscuro" <?= $u['tema']=='oscuro'?'checked':'' ?> style="width:auto;"> Oscuro
            </label>
          </div>
        </div>
        <div class="form-group" style="margin-top:20px;">
          <label style="font-size:.95rem;font-weight:700;display:flex;align-items:center;gap:12px;cursor:pointer;color:<?= $tema=='oscuro'?'#e2e8f0':'#374151' ?>;">
            <input type="checkbox" name="notificaciones" value="1" <?= $u['notificaciones']?'checked':'' ?> style="width:18px;height:18px;">
            Activar notificaciones del sistema
          </label>
          <p style="font-size:.82rem;color:#64748b;margin-top:6px;margin-left:30px;">Recibe avisos sobre mensajes, recargas y cambios en tu cuenta.</p>
        </div>
        <button type="submit" class="btn btn-blue">Guardar Preferencias</button>
      </form>
    </div>

    <?php elseif ($tab == 'bloqueos'): ?>
    <div class="card">
      <h3>Numeros Bloqueados</h3>
      <?php
      $res_blq = mysqli_query($con, "SELECT c.id_contacto, c.id_tel_dueno, c.nombre_contacto, c.bloqueado,
                                            tf.numero AS num_contacto, tm.numero AS mi_numero
                                     FROM contacto c
                                     INNER JOIN telefono tf ON c.id_tel_contacto = tf.id_telefono
                                     INNER JOIN telefono tm ON c.id_tel_dueno = tm.id_telefono
                                     WHERE c.id_tel_dueno IN ($ids_t)
                                     ORDER BY c.bloqueado DESC, c.nombre_contacto");
      ?>
      <?php if (mysqli_num_rows($res_blq) == 0): ?>
        <p style="color:#64748b;">No tienes contactos registrados.</p>
      <?php else: ?>
      <table>
        <tr>
          <th>Mi Telefono</th>
          <th>Numero</th>
          <th>Nombre</th>
          <th>Estado</th>
          <th>Accion</th>
        </tr>
        <?php while ($c = mysqli_fetch_assoc($res_blq)): ?>
        <tr>
          <td style="font-size:.85rem;"><?= htmlspecialchars($c['mi_numero']) ?></td>
          <td><strong><?= htmlspecialchars($c['num_contacto']) ?></strong></td>
          <td><?= htmlspecialchars($c['nombre_contacto'] ?: '---') ?></td>
          <td>
            <?php if ($c['bloqueado']): ?>
              <span class="badge badge-red">Bloqueado</span>
            <?php else: ?>
              <span class="badge badge-green">Activo</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" action="contactos.php">
              <input type="hidden" name="accion" value="<?= $c['bloqueado'] ? 'desbloquear' : 'bloquear' ?>">
              <input type="hidden" name="id_tel" value="<?= $c['id_tel_dueno'] ?>">
              <input type="hidden" name="id_contacto" value="<?= $c['id_contacto'] ?>">
              <?php if ($c['bloqueado']): ?>
                <button class="btn btn-green" style="padding:5px 14px;font-size:.82rem;">Desbloquear</button>
              <?php else: ?>
                <button class="btn btn-red" style="padding:5px 14px;font-size:.82rem;">Bloquear</button>
              <?php endif; ?>
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
