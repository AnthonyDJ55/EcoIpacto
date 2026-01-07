<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISTEMA: PROTOCOLO ECO-WARRIOR</title>
    <link rel="stylesheet" href="../../public/css/estilo_index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        :root { --neon-blue: #00f2ff; --neon-red: #ff0055; --neon-green: #39ff14; }
        
        body { margin: 0; overflow: hidden; background: #050505; font-family: 'Share Tech Mono', monospace; color: white; }

        /* Fondo estilo Rejilla Tron/Cyberpunk */
        #game-canvas {
            position: relative; width: 100vw; height: 100vh;
            background: 
                linear-gradient(rgba(0, 242, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 242, 255, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            box-shadow: inset 0 0 100px rgba(0,0,0,1);
        }

        /* Personajes con efecto Glow */
        .entity { 
            position: absolute; width: 45px; height: 45px; 
            display: flex; align-items: center; justify-content: center; 
            border-radius: 8px; font-weight: bold; z-index: 100;
            transition: transform 0.1s;
        }
        #player { 
            background: var(--neon-blue); border: 2px solid white; 
            box-shadow: 0 0 20px var(--neon-blue); color: black;
        }
        #enemy { 
            background: var(--neon-red); border: 2px solid white; 
            box-shadow: 0 0 20px var(--neon-red); color: white;
        }

        /* Válvulas Industriales */
        .valve {
            position: absolute; width: 70px; height: 70px;
            background: #1a1a1a; border: 2px solid #333;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            border-radius: 50%; transition: 0.3s;
        }
        .valve.leaking { 
            border-color: var(--neon-red); 
            animation: pulse-red 0.5s infinite alternate; 
        }
        .valve.leaking::before {
            content: 'FUGA CRÍTICA'; position: absolute; top: -25px;
            color: var(--neon-red); font-size: 10px; width: 80px; text-align: center;
        }

        /* Interfaz de Usuario (UI) */
        .top-hud {
            position: fixed; top: 0; width: 100%; height: 60px;
            background: rgba(0,0,0,0.8); border-bottom: 2px solid var(--neon-blue);
            display: flex; align-items: center; justify-content: space-around; z-index: 1000;
        }
        .status-bar { width: 250px; height: 10px; border: 1px solid var(--neon-blue); }
        #flood-fill { height: 100%; width: 0%; background: var(--neon-blue); box-shadow: 0 0 10px var(--neon-blue); transition: 0.3s; }

        /* Munición de Energía */
        .plasma {
            position: absolute; width: 20px; height: 6px; 
            background: var(--neon-green); border-radius: 10px;
            box-shadow: 0 0 15px var(--neon-green); z-index: 50;
        }

        /* Botón de Menú Estilo Comando */
        .btn-abort {
            position: fixed; bottom: 20px; left: 20px;
            background: transparent; color: var(--neon-red);
            border: 1px solid var(--neon-red); padding: 10px 20px;
            text-decoration: none; font-size: 12px; z-index: 1000;
        }
        .btn-abort:hover { background: var(--neon-red); color: white; }

        @keyframes pulse-red { from { box-shadow: 0 0 5px var(--neon-red); } to { box-shadow: 0 0 25px var(--neon-red); } }
    </style>
</head>
<body>

<div id="start-screen" style="position:fixed; inset:0; background:black; z-index:5000; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
    <h1 class="animate__animated animate__pulse infinite" style="color:var(--neon-blue); font-size:3rem; letter-spacing:10px;">PROYECTO: ECO-DEFENSOR</h1>
    <div style="color:#666; font-size:1rem; margin:20px;">[ ACCESO AUTORIZADO - NIVEL INGENIERÍA ]</div>
    <div style="background:rgba(255,255,255,0.05); padding:40px; border:1px solid #333; border-radius:10px; text-align:left;">
        > MOVIMIENTO: [W, A, S, D] O FLECHAS<br>
        > ATAQUE: [CLIC IZQUIERDO] (PULSO DE PLASMA)<br>
        > REPARACIÓN: TECLA [E] (CERCA DE LA VÁLVULA)
    </div>
    <button onclick="bootGame()" style="margin-top:30px; padding:15px 60px; background:var(--neon-blue); border:none; color:black; font-weight:bold; cursor:pointer;">EJECUTAR_MISIÓN.EXE</button>
</div>

<div class="top-hud">
    <div>ESTADO: <span id="sys-status" style="color:var(--neon-green);">SISTEMA OK</span></div>
    <div>INUNDACIÓN: <div class="status-bar"><div id="flood-fill"></div></div></div>
    <div>DATA_SAVED: <span id="score">0000</span> TB</div>
</div>

<a href="../../index.php" class="btn-abort">[ ABORTAR_PROTOCOLO ]</a>

<div id="game-canvas">
    <div id="player" class="entity">PC</div>
    <div id="enemy" class="entity">VIR</div>
    
    <div class="valve" id="v1" style="top:25%; left:20%;">⚙️</div>
    <div class="valve" id="v2" style="top:25%; left:75%;">⚙️</div>
    <div class="valve" id="v3" style="top:70%; left:20%;">⚙️</div>
    <div class="valve" id="v4" style="top:70%; left:75%;">⚙️</div>
    <div class="valve" id="v5" style="top:48%; left:48%;">⚙️</div>
</div>

<div id="end-modal" style="display:none; position:fixed; inset:0; background:black; z-index:6000; flex-direction:column; align-items:center; justify-content:center;">
    <h1 id="end-title" style="font-size:4rem;"></h1>
    <p style="font-size:1.5rem; color:var(--neon-blue);">RECURSOS PROTEGIDOS: <span id="end-score"></span> TB</p>
    <button onclick="location.reload()" style="padding:15px 40px; background:white; color:black; border:none; margin-top:20px; cursor:pointer;">REINICIAR_SISTEMA</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
<script>
    let pX = 100, pY = 100, eX = 600, eY = 400;
    let score = 0, flood = 0, gameActive = false, enemyStunned = false;
    let keys = {};

    function bootGame() {
        document.getElementById('start-screen').style.display = 'none';
        gameActive = true;
        requestAnimationFrame(update);
        loopEnemigo();
    }

    window.addEventListener('keydown', e => keys[e.key.toLowerCase()] = true);
    window.addEventListener('keyup', e => keys[e.key.toLowerCase()] = false);

    function update() {
        if (!gameActive) return;

        // Movimiento Jugador
        if (keys['w'] || keys['arrowup']) pY -= 6;
        if (keys['s'] || keys['arrowdown']) pY += 6;
        if (keys['a'] || keys['arrowleft']) pX -= 6;
        if (keys['d'] || keys['arrowright']) pX += 6;

        pX = Math.max(0, Math.min(window.innerWidth - 45, pX));
        pY = Math.max(60, Math.min(window.innerHeight - 45, pY));

        const player = document.getElementById('player');
        player.style.left = pX + 'px';
        player.style.top = pY + 'px';

        revisarCercania();
        gestionarInundacion();
        requestAnimationFrame(update);
    }

    function loopEnemigo() {
        if (!gameActive) return;
        if (!enemyStunned) {
            // IA: Moverse hacia la válvula más cercana que no esté goteando
            const valves = document.querySelectorAll('.valve:not(.leaking)');
            if (valves.length > 0) {
                let target = valves[0].getBoundingClientRect();
                eX += (target.left - eX) * 0.05;
                eY += (target.top - eY) * 0.05;
                
                if (Math.hypot(target.left - eX, target.top - eY) < 20) {
                    valves[0].classList.add('leaking');
                }
            }
            const enemy = document.getElementById('enemy');
            enemy.style.left = eX + 'px';
            enemy.style.top = eY + 'px';
        }
        setTimeout(loopEnemigo, 30);
    }

    // Sistema de Disparo de Plasma
    window.addEventListener('mousedown', e => {
        if (!gameActive) return;
        disparar(e.clientX, e.clientY);
    });

    function disparar(tx, ty) {
        const p = document.createElement('div');
        p.className = 'plasma';
        p.style.left = (pX + 20) + 'px';
        p.style.top = (pY + 20) + 'px';
        document.body.appendChild(p);

        let angle = Math.atan2(ty - pY, tx - pX);
        p.style.transform = `rotate(${angle}rad)`;

        let plasmaInt = setInterval(() => {
            let px = parseFloat(p.style.left) + Math.cos(angle) * 15;
            let py = parseFloat(p.style.top) + Math.sin(angle) * 15;
            p.style.left = px + 'px';
            p.style.top = py + 'px';

            // Colisión con enemigo
            if (Math.hypot(px - eX, py - eY) < 40) {
                aturdirEnemigo();
                p.remove();
                clearInterval(plasmaInt);
            }

            if (px < 0 || px > window.innerWidth || py < 0 || py > window.innerHeight) {
                p.remove();
                clearInterval(plasmaInt);
            }
        }, 20);
    }

    function aturdirEnemigo() {
        enemyStunned = true;
        const enemy = document.getElementById('enemy');
        enemy.style.boxShadow = "0 0 50px white";
        enemy.innerText = "ERR";
        setTimeout(() => {
            enemyStunned = false;
            enemy.style.boxShadow = "0 0 20px var(--neon-red)";
            enemy.innerText = "VIR";
        }, 2500);
    }

    function revisarCercania() {
        document.querySelectorAll('.valve.leaking').forEach(v => {
            let rect = v.getBoundingClientRect();
            if (Math.hypot(rect.left - pX, rect.top - pY) < 80) {
                if (keys['e']) {
                    v.classList.remove('leaking');
                    score += 100;
                    document.getElementById('score').innerText = score.toString().padStart(4, '0');
                }
            }
        });
    }

    function gestionarInundacion() {
        let leaks = document.querySelectorAll('.valve.leaking').length;
        flood += leaks * 0.08;
        document.getElementById('flood-fill').style.width = flood + "%";
        
        if (flood >= 100) finalizar(false);
        if (score >= 2000) finalizar(true);
    }

    function finalizar(ganaste) {
        gameActive = false;
        const modal = document.getElementById('end-modal');
        modal.style.display = 'flex';
        document.getElementById('end-title').innerText = ganaste ? "SISTEMA RESTAURADO" : "SISTEMA COLAPSADO";
        document.getElementById('end-title').style.color = ganaste ? "var(--neon-green)" : "var(--neon-red)";
        document.getElementById('end-score').innerText = score;
        if (ganaste) confetti({ particleCount: 200, colors: ['#00f2ff', '#39ff14'] });
    }
</script>
</body>
</html>