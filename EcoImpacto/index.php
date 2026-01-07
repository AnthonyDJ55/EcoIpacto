<?php
// Ubicación: EcoImpacto/index.php (RAÍZ)
session_start();

// 1. CONEXIÓN: Al estar en la raíz, entramos directo a config
include_once "config/conexion.php";

// Verificar si hay un usuario logueado
$id = $_SESSION['id'] ?? null;

// Variables por defecto
$foto = 'uploads/default.png'; // Ruta desde la raíz
$nombreUsuario = 'Invitado';

if ($id) {
    // 2. CONSULTA: Ajustada a tu SQL (nombre_usuario y foto_perfil)
    $sql = "SELECT foto_perfil, nombre_usuario FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($fotoPerfilBD, $nombreBD);
    $stmt->fetch();
    $stmt->close();

    // 3. LÓGICA DE FOTO: 
    // Como en la BD solo guardamos "perfil_abc.jpg", aquí le sumamos la carpeta "uploads/"
    if (!empty($fotoPerfilBD)) {
        $foto = "uploads/" . $fotoPerfilBD;
    }
    
    $nombreUsuario = !empty($nombreBD) ? $nombreBD : 'Usuario';

    // Sincronizar la sesión por si acaso
    $_SESSION['foto_perfil'] = $foto;
    $_SESSION['nombre_usuario'] = $nombreUsuario;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>EcoJuegos - Jugando por el planeta</title>
  <link rel="stylesheet" href="public/css/estilo_index.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <header>
    <div class="dashboard-header">
      <div class="user-info">
        <img src="<?= htmlspecialchars($foto) ?>" alt="Usuario" class="avatar-usuario">
        <div>
          <div class="nombre-usuario"><?= htmlspecialchars($nombreUsuario) ?></div>
          <div class="badge-puntos"> Master </div>
        </div>
      </div>
      <nav class="user-actions">
        <button id="dark-mode-toggle" class="btn-perfil" style="background: #333; color: white; border: none;">
        🌙
        </button>
        <a href="src/views/perfil.php" class="btn-perfil">Editar perfil</a>
        <form method="post" action="src/views/login.php" class="form-cerrar">
          <button type="submit" class="btn-cerrar" title="Cerrar sesión">&#x274C;</button>
        </form>
      </nav>
    </div>
    <div class="welcome-banner">
      <h1>🌎 ¡Bienvenido a <span class="eco">EcoJuegos</span>!</h1>
      <p>Gana puntos mientras cuidas el planeta con nuestras misiones ecológicas.</p>
    </div>
  </header>

  <main>
    <section id="juegos">
      <h2>🎮 Elige una misión ecológica</h2>
      <div class="juegos-grid">
        <a href="src/views/reciclaje_ninja.php" class="juego-card">
          <span class="icono-juego">♻️</span>
          <strong>Reciclaje Ninja</strong>
          <span class="desc-juego">Clasifica materiales rápidamente</span>
        </a>
        <a href="src/views/reforestacion_virtual.php" class="juego-card">
          <span class="icono-juego">🌳</span>
          <strong>Reforestación virtual</strong>
          <span class="desc-juego">Planta árboles en zonas degradadas</span>
        </a>
        <a href="src/views/ahorro_agua.php" class="juego-card">
          <span class="icono-juego">💧</span>
          <strong>Ahorro de agua</strong>
          <span class="desc-juego">Evita desperdicios en casa</span>
        </a>
        <a href="src/views/polinizacion.php" class="juego-card">
          <span class="icono-juego">🐝</span>
          <strong>Polinización veloz</strong>
          <span class="desc-juego">Ayuda a las abejas a polinizar</span>
        </a>
        <a href="src/views/energia_fantasma.php" class="juego-card">
          <span class="icono-juego">🔌</span>
          <strong>Cazador de energía fantasma</strong>
          <span class="desc-juego">Apaga dispositivos sin uso</span>
        </a>
        <a href="src/views/combustible.php" class="juego-card">
          <span class="icono-juego">🚴‍♂️</span>
          <strong>Reto sin combustibles</strong>
          <span class="desc-juego">Evita emisiones con tu bicicleta</span>
        </a>
        <a href="src/views/guardacostas.php" class="juego-card">
          <span class="icono-juego">🌊</span>
          <strong>Guardacostas verde</strong>
          <span class="desc-juego">Rescata el océano del plástico</span>
        </a>
        <a href="src/views/ecopuzzle.php" class="juego-card">
          <span class="icono-juego">🍃</span>
          <strong>EcoPuzzle del bosque</strong>
          <span class="desc-juego">Reconstruye hábitats naturales</span>
        </a>
      </div>
    </section>
  </main>

  <section class="videos-eco">
    <h2>🌍 Videos recomendados</h2>
    <div class="video-container">
        <div class="video-card">
          <iframe width="100%" height="215" src="https://www.youtube.com/embed/Gpc1s9qSeVM" title="Medio ambiente" frameborder="0" allowfullscreen></iframe>
          <p>✅ Cómo cuidar el medio ambiente</p>
        </div>
        <div class="video-card">
          <iframe width="100%" height="215" src="https://www.youtube.com/embed/nvUqnpicSd0" title="Consejos" frameborder="0" allowfullscreen></iframe>
          <p>🌱 10 consejos ecológicos</p>
        </div>
    </div>
  </section>

  <footer>
    <p>EcoJuegos — Una iniciativa para aprender y proteger 🌿</p>
  </footer>
  <script>
    const btn = document.querySelector('#dark-mode-toggle');
    const body = document.body;

    // Al cargar la página, revisar si ya estaba en modo oscuro
    if (localStorage.getItem('dark-mode') === 'enabled') {
        body.classList.add('dark-mode');
        btn.innerHTML = '☀️';
    }

    btn.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('dark-mode', 'enabled');
            btn.innerHTML = '☀️'; // Cambia a sol
        } else {
            localStorage.setItem('dark-mode', 'disabled');
            btn.innerHTML = '🌙'; // Cambia a luna
        }
    });
</script>
</body>
</html>