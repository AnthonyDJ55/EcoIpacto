<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reciclaje Ninja Pro - EcoImpacto</title>
    <link rel="stylesheet" href="../../public/css/estilo_index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        /* CSS EXTENSO PARA DISEÑO PROFESIONAL */
        :root {
            --organico: #4CAF50; --plastico: #2196F3; --papel: #FFEB3B;
            --danger: #ff4757; --success: #2ed573;
        }

        body { 
            margin: 0; overflow: hidden; font-family: 'Poppins', sans-serif;
            background: #f0f2f5; transition: background 0.5s;
        }

        /* Pantalla de Inicio / Tutorial */
        #overlay-inicio {
            position: fixed; inset: 0; background: rgba(0,0,0,0.9);
            z-index: 1000; display: flex; align-items: center; justify-content: center;
        }

        .card-instrucciones {
            background: white; width: 90%; max-width: 500px;
            padding: 40px; border-radius: 30px; text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        /* Interfaz de Juego */
        .game-container {
            display: flex; flex-direction: column; height: 100vh;
            justify-content: space-between; align-items: center; padding: 20px;
        }

        .hud {
            width: 100%; max-width: 800px; display: flex;
            justify-content: space-between; align-items: center;
            background: white; padding: 15px 30px; border-radius: 50px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .life-container { color: var(--danger); font-size: 1.5rem; }
        
        .timer-bar-container {
            width: 100%; max-width: 600px; height: 12px;
            background: #ddd; border-radius: 10px; margin: 20px 0; overflow: hidden;
        }
        #timer-fill {
            width: 100%; height: 100%; background: var(--success);
            transition: width 1s linear, background 0.3s;
        }

        /* Área Central */
        .main-stage {
            flex-grow: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; width: 100%;
        }

        #item-display {
            font-size: 150px; cursor: default;
            filter: drop-shadow(0 15px 20px rgba(0,0,0,0.2));
            user-select: none; margin-bottom: 20px;
        }

        .item-name {
            font-size: 1.5rem; font-weight: bold; text-transform: uppercase;
            color: #555; letter-spacing: 2px;
        }

        /* Botes de Basura */
        .bins-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 20px; width: 100%; max-width: 900px; padding-bottom: 40px;
        }

        .bin-item {
            height: 180px; border-radius: 25px; border: none;
            cursor: pointer; position: relative; overflow: hidden;
            transition: transform 0.2s, filter 0.2s;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: white; font-weight: 900; font-size: 1.2rem;
        }

        .bin-item:hover { transform: translateY(-10px); filter: brightness(1.1); }
        .bin-item:active { transform: scale(0.95); }

        .bin-org { background: var(--organico); box-shadow: 0 10px 0 #388E3C; }
        .bin-pla { background: var(--plastico); box-shadow: 0 10px 0 #1976D2; }
        .bin-pap { background: var(--papel); color: #333; box-shadow: 0 10px 0 #FBC02D; }

        /* Pantalla Final */
        #modal-game-over {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.95); z-index: 2000;
            flex-direction: column; align-items: center; justify-content: center;
            color: white; text-align: center;
        }

        /* Modo Oscuro */
        body.dark-mode { background: #121212; }
        body.dark-mode .hud, body.dark-mode .card-instrucciones { background: #222; color: white; }
        body.dark-mode .item-name { color: #aaa; }
    </style>
</head>
<body>

<div id="overlay-inicio">
    <div class="card-instrucciones animate__animated animate__fadeInUp">
        <h1 style="color: var(--plastico); margin-bottom: 10px;">♻️ RECICLAJE NINJA</h1>
        <p>Demuestra tu agilidad clasificando residuos. <br> ¡No dejes que el tiempo se agote!</p>
        <div style="text-align: left; margin: 20px 0; background: #f9f9f9; padding: 15px; border-radius: 15px; color: #444;">
            <small><b>CONTROLES:</b><br>
            • Haz clic en el contenedor correcto.<br>
            • Cada acierto te da 10 puntos.<br>
            • Los errores restan 5 puntos y tiempo.</small>
        </div>
        <button onclick="iniciarMision()" class="btn-perfil" style="background: var(--success); border: none; padding: 15px 50px; border-radius: 50px; color: white; font-weight: bold; cursor: pointer; width: 100%;">¡EMPEZAR JUEGO!</button>
    </div>
</div>

<div class="game-container">
    <div class="hud">
        <div class="score-box">⭐ PUNTOS: <span id="val-puntos">0</span></div>
        <div class="life-container">❤️❤️❤️</div>
    </div>

    <div class="timer-bar-container">
        <div id="timer-fill"></div>
    </div>

    <div class="main-stage">
        <div id="item-display" class="animate__animated">🍎</div>
        <div class="item-name" id="item-label">Manzana Mordida</div>
    </div>

    <div class="bins-grid">
        <button class="bin-item bin-org" onclick="validarResiduo('organico')">
            <span style="font-size: 3rem;">🟢</span><br>ORGÁNICO
        </button>
        <button class="bin-item bin-pla" onclick="validarResiduo('plastico')">
            <span style="font-size: 3rem;">🔵</span><br>PLÁSTICO
        </button>
        <button class="bin-item bin-pap" onclick="validarResiduo('papel')">
            <span style="font-size: 3rem;">🟡</span><br>PAPEL
        </button>
    </div>
</div>

<div id="modal-game-over">
    <h1 id="final-title" style="font-size: 4rem;">¡TIEMPO AGOTADO!</h1>
    <p id="final-msg" style="font-size: 1.5rem;"></p>
    <div style="font-size: 3rem; color: var(--success); margin: 30px 0;">+<span id="final-score">0</span> EcoPuntos</div>
    <div style="display: flex; gap: 20px;">
        <button onclick="location.reload()" class="btn-perfil" style="background: white; color: black; border: none; padding: 15px 30px; border-radius: 10px; cursor: pointer;">Reintentar</button>
        <a href="../../index.php" class="btn-perfil" style="background: var(--plastico); color: white; border: none; padding: 15px 30px; border-radius: 10px; text-decoration: none;">Menú Principal</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
<script>
    // --- DATOS DEL JUEGO ---
    const residuos = [
        { e: '🍎', n: 'Manzana', t: 'organico' }, { e: '🍌', n: 'Cáscara de Banana', t: 'organico' },
        { e: '🍕', n: 'Restos de Pizza', t: 'organico' }, { e: '🍼', n: 'Botella de Agua', t: 'plastico' },
        { e: '🥤', n: 'Vaso Desechable', t: 'plastico' }, { e: '🧼', n: 'Envase Jabón', t: 'plastico' },
        { e: '📰', n: 'Periódico Viejo', t: 'papel' }, { e: '📦', n: 'Caja Cartón', t: 'papel' },
        { e: '📄', n: 'Hojas de Papel', t: 'papel' }
    ];

    let puntos = 0;
    let tiempo = 100; // Porcentaje
    let activo = false;
    let actual = null;

    // --- LÓGICA ---
    function iniciarMision() {
        document.getElementById('overlay-inicio').style.display = 'none';
        activo = true;
        generarNuevoItem();
        bucleTiempo();
    }

    function generarNuevoItem() {
        if(!activo) return;
        actual = residuos[Math.floor(Math.random() * residuos.length)];
        const display = document.getElementById('item-display');
        display.innerText = actual.e;
        document.getElementById('item-label').innerText = actual.n;
        
        display.classList.remove('animate__jackInTheBox');
        void display.offsetWidth;
        display.classList.add('animate__jackInTheBox');
    }

    function validarResiduo(tipoUsuario) {
        if(!activo) return;

        if(tipoUsuario === actual.t) {
            puntos += 10;
            tiempo = Math.min(100, tiempo + 5); // Ganar un poco de tiempo
            document.getElementById('val-puntos').innerText = puntos;
            generarNuevoItem();
        } else {
            puntos = Math.max(0, puntos - 5);
            tiempo -= 10; // Penalización de tiempo
            document.getElementById('val-puntos').innerText = puntos;
            document.getElementById('item-display').classList.add('animate__shakeX');
            setTimeout(() => document.getElementById('item-display').classList.remove('animate__shakeX'), 500);
        }
    }

    function bucleTiempo() {
        const timerFill = document.getElementById('timer-fill');
        const intervalo = setInterval(() => {
            if(!activo) { clearInterval(intervalo); return; }
            
            tiempo -= 1.5; // Velocidad de caída del tiempo
            timerFill.style.width = tiempo + "%";

            if(tiempo <= 30) timerFill.style.background = "var(--danger)";
            else timerFill.style.background = "var(--success)";

            if(tiempo <= 0) {
                clearInterval(intervalo);
                finalizarJuego();
            }
        }, 200);
    }

    function finalizarJuego() {
        activo = false;
        document.getElementById('final-score').innerText = puntos;
        document.getElementById('modal-game-over').style.display = 'flex';
        
        const mensajes = ["¡Nivel Ninja alcanzado!", "¡Eres un experto del reciclaje!", "¡Increíble trabajo!"];
        document.getElementById('final-msg').innerText = mensajes[Math.floor(Math.random()*mensajes.length)];

        confetti({ particleCount: 200, spread: 80, origin: { y: 0.6 } });
    }

    if (localStorage.getItem('dark-mode') === 'enabled') document.body.classList.add('dark-mode');
</script>
</body>
</html>