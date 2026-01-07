let score = 0;
let coins = 0;

const items = document.querySelectorAll('.item');
const bins = document.querySelectorAll('.bin');
const guardarBtn = document.getElementById('guardarBtn');
const finalizarBtn = document.getElementById('finalizarBtn');

items.forEach(item => item.addEventListener('dragstart', dragStart));
bins.forEach(bin => {
  bin.addEventListener('dragover', e => e.preventDefault());
  bin.addEventListener('drop', dropItem);
});

function dragStart(e) {
  e.dataTransfer.setData("text/plain", e.target.dataset.type);
  e.dataTransfer.setData("elementId", e.target.alt);
}

function dropItem(e) {
  e.preventDefault();
  const binType = e.target.dataset.type;
  const itemType = e.dataTransfer.getData("text/plain");
  const elementId = e.dataTransfer.getData("elementId");
  const itemEl = document.querySelector(`.item[alt="${elementId}"]`);

  if (binType === itemType) {
    score += 10;
    coins += 1;
    itemEl.classList.add('reciclado');
    document.getElementById('score').textContent = "Puntaje: " + score;
    document.getElementById('ecoCoins').textContent = "Eco-Monedas: " + coins;
    setTimeout(() => {
      itemEl.style.display = "none";
      itemEl.classList.remove('reciclado');
      verificarFinDelJuego();
    }, 500);
  } else {
    score = Math.max(0, score - 5);
    document.getElementById('score').textContent = "Puntaje: " + score;
    alert("⚠️ Ese ítem no va en ese contenedor");
  }
}

function verificarFinDelJuego() {
  const usados = Array.from(document.querySelectorAll('.item'))
    .every(item => item.style.display === "none");

  if (usados) {
    guardarBtn.style.display = 'inline-block';
    finalizarBtn.style.display = 'inline-block';
  }
}

function mostrarPopup() {
  const popup = document.getElementById('popupMensaje');
  popup.style.display = 'block';
  popup.classList.add('mostrar');
  setTimeout(() => {
    popup.classList.remove('mostrar');
    popup.style.display = 'none';
  }, 2500);
}

guardarBtn.addEventListener('click', function () {
  fetch('guardar_puntaje.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `score=${score}&ecoCoins=${coins}`
  })
  .then(res => res.json())
  .then(data => {
    console.log(data);
    if (data.estado === "ok") {
      mostrarPopup();
      setTimeout(() => location.reload(), 2600);
    } else {
      alert("❌ " + data.mensaje);
    }
  });
});
