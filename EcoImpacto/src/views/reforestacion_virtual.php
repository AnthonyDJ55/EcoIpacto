<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reforestación Virtual - EcoJuegos</title>
    <link rel="stylesheet" href="../../public/css/estilo_index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body { margin: 0; overflow: hidden; font-family: 'Segoe UI', sans-serif; }
        
        /* Pantalla de Inicio / Instrucciones */
        #start-screen {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            z-index: 200;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .instrucciones-card {
            background: white;
            color: #333;
            padding: 30px;
            border-radius: 20px;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        body.dark-mode .instrucciones-card { background: #333; color: white; }

        .game-stage {
            width: 100vw;
            height: 100vh;
            background: #e6ccb2;
            background-image: radial-gradient(#ddb892 2px, transparent 2px);
            background-size: 30px 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        body.dark-mode .game-stage { background: #2c1d12; }

        .header-game {
            background: white;
            padding: 15px 50px;
            border-radius: 0 0 30px 30px;
            display: flex; gap: 50px;
            font-size: 1.5rem; font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 10;
        }
        body.dark-mode .header-game { background: #444; color: white; }

        .spot {
            position: absolute;
            width: 80px; height: 80px;
            display: flex; align-items: center; justify-content: center;
            font-size: 50px; cursor: pointer;
        }

        .hole { background: rgba(0,0,0,0.2); border-radius: 50%; width: 40px; height: 20px; }

        #end-screen {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.9);
            z-index: 100; color: white;
            flex-direction: column; align-items: center; justify-content: center;
        }
    </style>
</head>
<body>

<div id="start-screen">
    <div class="instrucciones-card animate__animated animate__backInDown">
        <h1 style="color: #2d6a4f;">🌲 Misión: Reforestar</h1>
        <p style="font-size: 1.2rem;">El suelo está seco y necesita árboles. <br><br> 
        <strong>¿Qué hacer?</strong><br>
        Haz clic rápidamente en los <b>hoyos</b> que aparecen en la tierra para plantar un árbol.</p>
        <button onclick="startGame()" class="btn-perfil" style="background:#2d6a4f; color:white; border:none; padding:15px 30px; font-size:1.2rem; cursor:pointer; border-radius:10px; margin-top:20px;">¡EMPEZAR MISIÓN!</button>
    </div>
</div>

<div class="game-stage">
    <div class="header-game">
        <span>🌳 Árboles: <span id="tree-count">0</span></span>
        <span>⏱️ Tiempo: <span id="timer">20</span>s</span>
    </div>
    <div class="forest-area" id="garden" style="position: relative; width: 100%; height: 100%;"></div>
</div>

<div id="end-screen" class="animate__animated animate__zoomIn">
    <h1 style="font-size: 4rem;">🌳 ¡Bosque Recuperado!</h1>
    <div style="font-size: 3rem; color: #b7e4c7; margin: 20px 0;">+<span id="final-score">0</span> EcoPuntos</div>
    <button onclick="location.reload()" class="btn-perfil" style="background:#2d6a4f; color:white; padding:15px 40px; font-size:1.2rem; cursor:pointer; border-radius:10px;">Plantar de nuevo</button>
    <br>
    <a href="../../index.php" style="color: white; text-decoration: underline;">Regresar al Menú</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>

<script>
    let trees = 0;
    let timeLeft = 20;
    let active = false; // El juego empieza detenido
    const garden = document.getElementById('garden');

    // Función que arranca el juego al presionar el botón
    function startGame() {
        document.getElementById('start-screen').style.display = 'none';
        active = true;
        
        // Iniciar temporizador
        const timer = setInterval(() => {
            timeLeft--;
            document.getElementById('timer').innerText = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timer);
                endGame();
            }
        }, 1000);

        // Crear los primeros hoyos
        for(let i=0; i<5; i++) createSpot();
    }

    function createSpot() {
        if (!active) return;
        const spot = document.createElement('div');
        spot.className = 'spot animate__animated animate__fadeIn';
        const x = Math.random() * (window.innerWidth - 100);
        const y = Math.random() * (window.innerHeight - 250) + 100;
        spot.style.left = x + 'px';
        spot.style.top = y + 'px';
        spot.innerHTML = '<div class="hole"></div>';

        spot.onclick = function() {
            if (this.dataset.planted) return;
            this.dataset.planted = "true";
            this.innerHTML = "🌳";
            this.classList.add('animate__bounceIn');
            trees++;
            document.getElementById('tree-count').innerText = trees;
            setTimeout(createSpot, 100);
            setTimeout(createSpot, 300);
        };
        garden.appendChild(spot);
    }

    function endGame() {
        active = false;
        document.getElementById('final-score').innerText = trees * 25;
        document.getElementById('end-screen').style.display = 'flex';
        confetti({ particleCount: 150, colors: ['#2d6a4f', '#52b788'], origin: { y: 0.6 } });
    }

    if (localStorage.getItem('dark-mode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }
</script>
</body>
</html>