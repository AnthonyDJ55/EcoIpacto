<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISTEMA: REACCIÓN BIÓTICA</title>
    <style>
        :root { --pollen: #f1c40f; --danger: #ff4757; --bg: #0a0a0a; }
        body { margin: 0; overflow: hidden; background: var(--bg); font-family: 'Courier New', monospace; color: white; }
        
        /* HUD Superior */
        .hud { position: fixed; top: 0; width: 100%; height: 60px; background: #000; display: flex; justify-content: space-around; align-items: center; border-bottom: 2px solid var(--pollen); z-index: 100; }
        
        /* Flores */
        .flower { position: absolute; font-size: 50px; cursor: pointer; user-select: none; transition: transform 0.1s; }
        .flower:active { transform: scale(0.8); }
        
        /* Manual / Inicio */
        #manual { position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 1000; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        .box { border: 1px solid var(--pollen); padding: 30px; max-width: 450px; background: #111; }
        
        /* Botones */
        .btn { background: var(--pollen); color: black; border: none; padding: 12px 30px; font-weight: bold; cursor: pointer; margin-top: 20px; text-decoration: none; display: inline-block; }
        .btn-exit { position: fixed; bottom: 20px; left: 20px; background: transparent; color: var(--danger); border: 1px solid var(--danger); padding: 8px 15px; text-decoration: none; font-size: 12px; z-index: 100; }
        
        #game-over { display: none; position: fixed; inset: 0; background: rgba(255, 71, 87, 0.2); backdrop-filter: blur(10px); z-index: 2000; flex-direction: column; align-items: center; justify-content: center; }
    </style>
</head>
<body>

<div id="manual">
    <div class="box">
        <h2 style="color:var(--pollen)">MANUAL DE OPERACIÓN</h2>
        <p style="text-align: left; font-size: 14px;">
            > OBJETIVO: Polinizar (click) las flores amarillas.<br>
            > REGLA 1: No dejes que las flores desaparezcan.<br>
            > REGLA 2: Si una flor se vuelve ROJA 🚨, no la toques.<br>
            > REGLA 3: Tienes 3 vidas.
        </p>
        <button class="btn" onclick="startGame()">[ INICIAR_SISTEMA ]</button>
        <br><br>
        <a href="../../index.php" style="color:#666; font-size: 12px;">CANCELAR</a>
    </div>
</div>

<div class="hud">
    <div>SCORE: <span id="score">0</span></div>
    <div style="color:var(--danger)">VIDAS: <span id="lives">3</span></div>
</div>

<a href="../../index.php" class="btn-exit">[ ABORTAR_MISIÓN ]</a>

<div id="game-over">
    <h1 style="font-size: 3rem;">CORE_FAILURE</h1>
    <p>La polinización se ha detenido.</p>
    <button class="btn" onclick="location.reload()">REINTENTAR</button>
    <a href="../../index.php" class="btn" style="background:#fff">MENÚ PRINCIPAL</a>
</div>

<script>
    let score = 0;
    let lives = 3;
    let active = false;
    let speed = 2000;

    function startGame() {
        document.getElementById('manual').style.display = 'none';
        active = true;
        spawnLoop();
    }

    function spawnLoop() {
        if (!active) return;
        createFlower();
        let nextSpawn = Math.max(400, speed - (score * 15));
        setTimeout(spawnLoop, nextSpawn);
    }

    function createFlower() {
        const f = document.createElement('div');
        f.className = 'flower';
        
        // 20% de probabilidad de ser una flor trampa (roja)
        const isTrap = Math.random() < 0.2;
        f.innerText = isTrap ? '🚨' : '🌸';
        
        f.style.left = Math.random() * (window.innerWidth - 100) + 50 + 'px';
        f.style.top = Math.random() * (window.innerHeight - 150) + 100 + 'px';

        f.onclick = () => {
            if (!active) return;
            if (isTrap) {
                updateLives();
            } else {
                score += 10;
                document.getElementById('score').innerText = score;
            }
            f.remove();
        };

        document.body.appendChild(f);

        // Desaparece sola
        setTimeout(() => {
            if (f.parentNode) {
                if (!isTrap) updateLives(); // Si era normal y no la clickeaste, pierdes vida
                f.remove();
            }
        }, Math.max(700, speed - (score * 10)));
    }

    function updateLives() {
        lives--;
        document.getElementById('lives').innerText = lives;
        if (lives <= 0) {
            active = false;
            document.getElementById('game-over').style.display = 'flex';
        }
    }
</script>
</body>
</html>