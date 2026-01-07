document.addEventListener("DOMContentLoaded", () => {
  const campo = document.getElementById("campo");
  const abeja = document.getElementById("abeja");
  const puntajeSpan = document.getElementById("puntaje");
  const tiempoSpan = document.getElementById("tiempo");
  const guardarBtn = document.getElementById("guardar");
  const finalizarBtn = document.getElementById("finalizar");

  let puntos = 0;
  let tiempo = 60;
  let enFlor = false;
  let gameOver = false;

  let abejaX = 100;
  let abejaY = 100;
  let destinoX = null;
  let destinoY = null;

  // Alerta visual
  const alerta = document.createElement("div");
  alerta.textContent = "⚠️ ¡CUIDADO!";
  alerta.style.position = "absolute";
  alerta.style.top = "10px";
  alerta.style.left = "50%";
  alerta.style.transform = "translateX(-50%)";
  alerta.style.fontSize = "2em";
  alerta.style.color = "red";
  alerta.style.fontWeight = "bold";
  alerta.style.display = "none";
  alerta.style.zIndex = "10";
  campo.appendChild(alerta);

  // Animación de puntos
  const style = document.createElement("style");
  style.textContent = `
    @keyframes subir {
      0% { opacity: 1; transform: translateY(0); }
      100% { opacity: 0; transform: translateY(-30px); }
    }
  `;
  document.head.appendChild(style);

  function mostrarPuntos(x, y, valor) {
    const puntosDiv = document.createElement("div");
    puntosDiv.textContent = `+${valor}`;
    puntosDiv.style.position = "absolute";
    puntosDiv.style.left = x + "px";
    puntosDiv.style.top = y + "px";
    puntosDiv.style.fontSize = "20px";
    puntosDiv.style.color = "#388e3c";
    puntosDiv.style.fontWeight = "bold";
    puntosDiv.style.animation = "subir 1s ease-out forwards";
    campo.appendChild(puntosDiv);
    setTimeout(() => puntosDiv.remove(), 1000);
  }

  function crearFlor() {
    const flor = document.createElement("div");
    flor.className = "flor";
    const x = Math.random() * (campo.clientWidth - 60);
    const y = Math.random() * (campo.clientHeight - 60);
    flor.style.left = x + "px";
    flor.style.top = y + "px";
    campo.appendChild(flor);

    flor.addEventListener("click", () => {
      destinoX = x;
      destinoY = y;
    });

    setTimeout(() => flor.remove(), 6000);
  }

  function crearAve() {
    if (gameOver) return;

    const ave = document.createElement("div");
    ave.className = "ave";
    ave.style.top = Math.random() * (campo.clientHeight - 80) + "px";
    ave.style.left = "-100px";
    campo.appendChild(ave);

    let posX = -100;
    let velocidad = 4;

    const moverAve = setInterval(() => {
      if (gameOver) {
        clearInterval(moverAve);
        ave.remove();
        return;
      }

      posX += velocidad;
      ave.style.left = posX + "px";

      const aveRect = ave.getBoundingClientRect();
      const abejaRect = abeja.getBoundingClientRect();
      const distancia = Math.abs(aveRect.left - abejaRect.left);

      if (distancia < 150 && !enFlor) {
        alerta.style.display = "block";
      } else {
        alerta.style.display = "none";
      }

      if (
        !enFlor &&
        aveRect.left < abejaRect.right &&
        aveRect.right > abejaRect.left &&
        aveRect.top < abejaRect.bottom &&
        aveRect.bottom > abejaRect.top
      ) {
        gameOver = true;
        clearInterval(moverAve);
        ave.remove();
        mostrarGameOver("💀 ¡La ave te atrapó!");
      }

      if (posX > campo.clientWidth + 100) {
        clearInterval(moverAve);
        ave.remove();
        alerta.style.display = "none";
      }
    }, 20);
  }

  function mostrarGameOver(mensaje) {
    const overlay = document.createElement("div");
    overlay.style.position = "absolute";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = "100%";
    overlay.style.height = "100%";
    overlay.style.background = "rgba(0,0,0,0.7)";
    overlay.style.color = "#fff";
    overlay.style.fontSize = "2em";
    overlay.style.display = "flex";
    overlay.style.flexDirection = "column";
    overlay.style.justifyContent = "center";
    overlay.style.alignItems = "center";
    overlay.innerHTML = `<p>${mensaje}</p><p>🌼 Puntos obtenidos: ${puntos}</p>`;
    campo.appendChild(overlay);
  }

  function moverAbeja() {
    if (gameOver || destinoX === null || destinoY === null) return;

    const dx = destinoX - abejaX;
    const dy = destinoY - abejaY;
    const distancia = Math.sqrt(dx * dx + dy * dy);

    if (distancia < 5) {
      enFlor = true;
      destinoX = null;
      destinoY = null;
      puntos += 10;
      puntajeSpan.textContent = `⭐ Puntos: ${puntos}`;
      mostrarPuntos(abejaX, abejaY, 10);
    } else {
      enFlor = false;
      abejaX += dx / 10;
      abejaY += dy / 10;
    }

    abeja.style.left = abejaX + "px";
    abeja.style.top = abejaY + "px";
  }

  setInterval(moverAbeja, 50);
  setInterval(crearFlor, 1500);
  setInterval(crearAve, 5000);

  const tiempoInterval = setInterval(() => {
    if (gameOver) {
      clearInterval(tiempoInterval);
      return;
    }

    tiempo--;
    tiempoSpan.textContent = `⏱️ Tiempo: ${tiempo}`;
    if (tiempo <= 0) {
      clearInterval(tiempoInterval);
      mostrarGameOver("⏳ El tiempo se ha terminado");
    }
  }, 1000);

  guardarBtn.addEventListener("click", () => {
    fetch("guardar_polinizacion.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ jugador: jugador, puntaje: puntos })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("✅ Puntaje guardado correctamente");
      } else {
        alert("❌ Error al guardar");
      }
    });
  });

  finalizarBtn.addEventListener("click", () => {
    window.location.href = "index.php";
  });
});
