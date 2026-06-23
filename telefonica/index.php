<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Compania Telefonica - Inicio</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; background:#0057B7;
       min-height:100vh; display:flex; flex-direction:column; }
header { background:rgba(0,0,0,.25); padding:18px 40px;
         display:flex; justify-content:space-between; align-items:center; }
header .logo { color:#fff; font-size:1.5rem; font-weight:700; letter-spacing:1px;
               display:flex; align-items:center; gap:10px; }
header .logo span { color:#7dd3fc; }
header .logo img { width:40px; height:40px; }
header nav a { color:#dbeafe; text-decoration:none; margin-left:20px;
               font-size:.9rem; transition:.2s; }
header nav a:hover { color:#fff; }
.hero { flex:1; display:flex; flex-direction:column; align-items:center;
        justify-content:center; text-align:center; padding:60px 20px; }
.hero h1 { color:#fff; font-size:2.8rem; font-weight:800; margin-bottom:16px; }
.hero p  { color:#bfdbfe; font-size:1.15rem; max-width:600px; line-height:1.7;
           margin-bottom:40px; }
.btn-group { display:flex; gap:16px; flex-wrap:wrap; justify-content:center; }
.btn { padding:14px 36px; border-radius:8px; font-size:1rem; font-weight:600;
       text-decoration:none; transition:.25s; cursor:pointer; border:none; }
.btn-primary  { background:#fff; color:#0057B7; }
.btn-primary:hover  { background:#dbeafe; }
.btn-outline  { background:transparent; color:#fff; border:2px solid #fff; }
.btn-outline:hover  { background:rgba(255,255,255,.12); }
.cards { display:flex; gap:24px; flex-wrap:wrap; justify-content:center;
         padding:40px 20px 60px; }
.card { background:rgba(255,255,255,.12); border-radius:14px; padding:28px;
        width:220px; text-align:center; color:#fff; }
.card h3 { font-size:1.05rem; margin-bottom:8px; }
.card p  { font-size:.85rem; color:#bfdbfe; line-height:1.5; }
footer { background:rgba(0,0,0,.3); color:#93c5fd; text-align:center;
         padding:16px; font-size:.85rem; }

         .card{
    background:rgba(255,255,255,.12);
    border-radius:14px;
    width:220px;
    text-align:center;
    color:#fff;
    overflow:hidden;
}

.card img{
    width:100%;
    height:140px;
    object-fit:cover;
    display:block;
}

.card h3{
    margin:15px 0 8px;
}

.card p{
    padding:0 15px 20px;
    font-size:.85rem;
    color:#bfdbfe;
    line-height:1.5;
}

</style>
</head>
<body>

<!-- Encabezado con logo y menu -->
<header>
  <div class="logo">
    <!-- Aqui va la imagen del logo (254x254) -->
    <img src="img/logo.png" alt="logo">
    Compañia <span>Telefonica</span>
  </div>
  <nav>
    <a href="login.php">Iniciar Sesion</a>
    <a href="registro.php">Registrarse</a>
  </nav>
</header>

<!-- Seccion de bienvenida -->
<div class="hero">
  <h1>Bienvenido al Sistema Telefonico</h1>
  <p>Gestiona tus comunicaciones, saldo, mensajes y configuraciones de desvio de llamadas desde un solo lugar.</p>
  <div class="btn-group">
    <a href="login.php"   class="btn btn-primary">Iniciar Sesion</a>
    <a href="registro.php" class="btn btn-outline">Crear Cuenta</a>
  </div>
</div>

<!-- Tarjetas informativas -->
<div class="cards">

  <div class="card">
    <img src="img/carta1.png" alt="Mensajeria">
    <h3>Mensajeria</h3>
    <p>Envia y recibe mensajes con saldo validado en tiempo real.</p>
  </div>

  <div class="card">
    <img src="img/carta2.png" alt="Saldo">
    <h3>Saldo</h3>
    <p>Consulta y recarga tu credito facilmente.</p>
  </div>

  <div class="card">
    <img src="img/carta3.png" alt="Desvio de Llamadas">
    <h3>Desvio de Llamadas</h3>
    <p>Configura tus desvios con validacion anticiclos.</p>
  </div>

  <div class="card">
    <img src="img/carta4.png" alt="Administracion">
    <h3>Administracion</h3>
    <p>Panel para monitoreo, reportes y gestion de usuarios.</p>
  </div>

</div>

<footer>
  Compania Telefonica - Fundamentos de Ingenieria de Software - TESCo
</footer>

</body>
</html>
