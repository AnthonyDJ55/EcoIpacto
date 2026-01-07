<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movilidad Sostenible - EcoImpacto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        :root { --main: #FFEB3B; --danger: #ff4757; --success: #4CAF50; }
        body { margin: 0; overflow: hidden; font-family: 'Poppins', sans-serif; background: #1a1a1a; color: white; }
        
        #overlay-inicio { position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; }
        .card { background: white; color: #333; padding: 30px; border-radius: 25px; text-align: center; max-width: 400px; }
        
        .hud { width: 100%; height: 70px; display: flex; justify-content: space-around; align-items: center; background: #000; border-bottom: 3px solid var(--main); }
        .main-stage { height: calc(100vh - 150px); position: relative; width: 100%; }
        .vehicle { position: absolute; font-size: 80px; cursor: pointer; }
        
        .btn { background: var(--success); color: white; padding: 15px 40px; border-radius: 50px; border: none; font-weight: bold; cursor: pointer; text-decoration: none; margin: 5px; display: inline-block; }
        #modal-game-over { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 2000; flex-direction: column; align-items: center; justify-content: center; }
    </style>
</head>
<body>

<div id="overlay-inicio">
    <div class="card animate__animated animate__backInDown">
        <h1 style="color: #f39c12">🚲 MOVILIDAD VERDE</h1>
        <p>¡Promueve el transporte limpio! <br> Haz clic en <b>bicis, scooters y buses</b>. Evita los <b>autos</b>.</p>
        <button onclick="empezar()" class="btn">¡A CORRER!</button>
    </div>
</div>

<div class="hud">
    <div>PUNTOS: <span id="puntos">0</span></div>
    <div id="timer" style="color: var(--main)">TIEMPO: 30s</div>
</div>

<div class="main-stage" id="stage"></div>

<a href="../../index.php" style="position:fixed; bottom:20px; left:20px; color:#777; text-decoration:none;">[ SALIR AL MENÚ ]</a>

<div id="modal-game-over">
    <h1>TIEMPO TERMINADO</h1>
    <div style="font-size: 3rem; margin: 20px;">EcoScore: <span id="final-score">0</span></div>
    <div>
        <button onclick="location.reload()" class="btn">REINTENTAR</button>
        <a href="../../index.php" class="btn" style="background: #444;">SALIR</a>
    </div>
</div>

<script>
    let score = 0, activo = false, tiempo = 30;
    const stage = document.getElementById('stage');

    function empezar() {
        document.getElementById('overlay-inicio').style.display = 'none';
        activo = true;
        spawn();
        let reloj = setInterval(() => {
            if(!activo) { clearInterval(reloj); return; }
            tiempo--;
            document.getElementById('timer').innerText = "TIEMPO: " + tiempo + "s";
            if(tiempo <= 0) {
                activo = false;
                document.getElementById('final-score').innerText = score;
                document.getElementById('modal-game-over').style.display = 'flex';
            }
        }, 1000);
    }

    function spawn() {
        if(!activo) return;
        const v = document.createElement('div');
        v.className = 'vehicle animate__animated animate__bounceIn';
        const isGood = Math.random() > 0.4;
        v.innerText = isGood ? ['🚲', '🛴', '🚌'][Math.floor(Math.random()*3)] : '🚗';
        v.style.left = Math.random() * 80 + 5 + '%';
        v.style.top = Math.random() * 60 + 10 + '%';

        v.onclick = () => {
            if(!activo) return;
            if(isGood) { score += 20; v.innerText = '✨'; }
            else { score = Math.max(0, score - 30); v.innerText = '💨'; }
            document.getElementById('puntos').innerText = score;
            setTimeout(() => v.remove(), 400);
        };
        stage.appendChild(v);
        setTimeout(() => { if(v.parentNode) v.remove(); }, 1200);
        setTimeout(spawn, 700);
    }
</script>
</body>
</html>