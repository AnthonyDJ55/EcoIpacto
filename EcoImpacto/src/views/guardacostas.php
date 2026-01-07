<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Guardacostas - EcoImpacto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        :root { --water: #2196F3; --danger: #ff4757; }
        body { margin: 0; overflow: hidden; font-family: 'Poppins', sans-serif; background: #001f3f; color: white; }
        
        #overlay-inicio { position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; }
        .card { background: white; color: #333; padding: 30px; border-radius: 25px; text-align: center; }
        
        .hud { position: fixed; top: 20px; width: 90%; left: 5%; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 50px; display: flex; justify-content: space-around; z-index: 100; border: 2px solid var(--water); }
        .waste { position: absolute; font-size: 60px; cursor: pointer; filter: drop-shadow(0 0 10px #00f2ff); }
        
        .btn { background: var(--water); color: white; padding: 15px 40px; border-radius: 50px; border: none; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        #modal-game-over { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 2000; flex-direction: column; align-items: center; justify-content: center; }
    </style>
</head>
<body>

<div id="overlay-inicio">
    <div class="card animate__animated animate__zoomIn">
        <h1 style="color: var(--water)">🌊 GUARDACOSTAS</h1>
        <p>¡El océano está en peligro! <br> Recoge los <b>plásticos</b> antes de que se acumulen demasiado.</p>
        <button onclick="start()" class="btn">LIMPIAR MAR</button>
    </div>
</div>

<div class="hud">
    <div>Limpio: <span id="score">0</span></div>
    <div id="status" style="color: #00f2ff;">ESTADO: SEGURO</div>
</div>

<div id="stage" style="width: 100vw; height: 100vh;"></div>

<a href="../../index.php" style="position:fixed; bottom:20px; left:20px; color:white; text-decoration:none;">[ SALIR AL MENÚ ]</a>

<div id="modal-game-over">
    <h1 style="color: var(--danger)">COLAPSO MARINO</h1>
    <p>Demasiada basura acumulada.</p>
    <div style="font-size: 3rem; margin: 20px;">Limpio: <span id="final-score">0</span></div>
    <button onclick="location.reload()" class="btn">REINTENTAR</button>
    <a href="../../index.php" class="btn" style="background: #444;">SALIR</a>
</div>

<script>
    let score = 0, activo = false;
    const stage = document.getElementById('stage');

    function start() {
        document.getElementById('overlay-inicio').style.display = 'none';
        activo = true;
        spawn();
    }

    function spawn() {
        if(!activo) return;
        const w = document.createElement('div');
        w.className = 'waste animate__animated animate__fadeInUp';
        w.innerText = ['🧴', '🛍️', '🥫', '🚬'][Math.floor(Math.random()*4)];
        w.style.left = Math.random() * 85 + 5 + '%';
        w.style.top = Math.random() * 80 + 10 + '%';

        w.onclick = () => {
            score += 15;
            document.getElementById('score').innerText = score;
            w.remove();
        };
        stage.appendChild(w);

        const total = document.querySelectorAll('.waste').length;
        if(total > 10) document.getElementById('status').style.color = 'orange';
        if(total > 15) {
            activo = false;
            document.getElementById('final-score').innerText = score;
            document.getElementById('modal-game-over').style.display = 'flex';
        }

        setTimeout(spawn, Math.max(300, 1000 - (score/3)));
    }
</script>
</body>
</html>