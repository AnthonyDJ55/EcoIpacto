<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cazador de Energía - EcoImpacto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        :root { --cian: #00f2ff; --danger: #ff4757; --success: #2ed573; }
        body { margin: 0; overflow: hidden; font-family: 'Poppins', sans-serif; background: #0a0a0a; color: white; }
        
        /* Pantalla Inicio */
        #overlay-inicio { position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; }
        .card-instrucciones { background: white; color: #333; width: 90%; max-width: 450px; padding: 30px; border-radius: 25px; text-align: center; }
        
        .hud { width: 100%; max-width: 800px; display: flex; justify-content: space-around; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 50px; border: 1px solid var(--cian); margin: 20px auto; }
        .energy-bar-container { width: 100%; max-width: 600px; height: 15px; background: #333; border-radius: 10px; margin: 0 auto 20px auto; overflow: hidden; }
        #energy-fill { width: 100%; height: 100%; background: var(--cian); transition: width 0.2s linear; }
        
        .main-stage { height: 60vh; width: 100%; position: relative; }
        .device { position: absolute; font-size: 70px; cursor: pointer; filter: drop-shadow(0 0 15px var(--cian)); }

        #modal-game-over { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 2000; flex-direction: column; align-items: center; justify-content: center; }
        .btn-action { background: var(--success); color: white; border: none; padding: 15px 40px; border-radius: 50px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; }
    </style>
</head>
<body>

<div id="overlay-inicio">
    <div class="card-instrucciones animate__animated animate__fadeIn">
        <h1 style="color: var(--cian)">🔌 ENERGÍA FANTASMA</h1>
        <p>Los dispositivos encendidos consumen energía sin necesidad. <br><b>¡Haz clic para apagarlos rápido!</b></p>
        <button onclick="iniciarJuego()" class="btn-action">INICIAR OPERACIÓN</button>
    </div>
</div>

<div class="game-container">
    <div class="hud">
        <div>⭐ PUNTOS: <span id="val-puntos">0</span></div>
        <div style="color: var(--cian)">MODO: AHORRO ACTIVO</div>
    </div>
    <div class="energy-bar-container"><div id="energy-fill"></div></div>
    <div class="main-stage" id="stage"></div>
</div>

<a href="../../index.php" style="position:fixed; bottom:20px; left:20px; color:white; text-decoration:none;">[ REGRESAR AL MENÚ ]</a>

<div id="modal-game-over">
    <h1 style="color:var(--danger)">APAGÓN TOTAL</h1>
    <div style="font-size: 3rem; margin: 20px;">Score: <span id="final-score">0</span></div>
    <div>
        <button onclick="location.reload()" class="btn-action">REINTENTAR</button>
        <a href="../../index.php" class="btn-action" style="background: #555;">SALIR</a>
    </div>
</div>

<script>
    let puntos = 0, energia = 100, activo = false;
    const stage = document.getElementById('stage');

    function iniciarJuego() {
        document.getElementById('overlay-inicio').style.display = 'none';
        activo = true;
        spawn();
        bucleEnergia();
    }

    function spawn() {
        if(!activo) return;
        const dev = document.createElement('div');
        dev.className = 'device animate__animated animate__zoomIn';
        dev.innerText = ['📺', '💻', '💡', '🔌'][Math.floor(Math.random()*4)];
        dev.style.left = Math.random() * 80 + 10 + '%';
        dev.style.top = Math.random() * 70 + 10 + '%';
        
        dev.onclick = () => {
            puntos += 10;
            energia = Math.min(100, energia + 8);
            document.getElementById('val-puntos').innerText = puntos;
            dev.remove();
        };
        stage.appendChild(dev);
        setTimeout(() => { if(dev.parentNode) { dev.remove(); energia -= 10; } }, 1500);
        setTimeout(spawn, Math.max(300, 1000 - (puntos * 2)));
    }

    function bucleEnergia() {
        const fill = document.getElementById('energy-fill');
        const interval = setInterval(() => {
            if(!activo) { clearInterval(interval); return; }
            energia -= 0.5;
            fill.style.width = energia + "%";
            if(energia <= 0) {
                activo = false;
                document.getElementById('final-score').innerText = puntos;
                document.getElementById('modal-game-over').style.display = 'flex';
            }
        }, 100);
    }
</script>
</body>
</html>