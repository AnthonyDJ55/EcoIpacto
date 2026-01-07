// ----- Configuración básica -----
const CONFIG = {
  ancho: 980,
  alto: 560,
  dispositivos: 8,       // cantidad
  fantasmas: 4,
  powerups: 2,
  tiempoSeg: 120,
  vidas: 3,
  velocidadJugador: 3.2,
  boostVelocidad: 1.8,
  duracionBoostMs: 8000,
  duracionEscudoMs: 8000,
  radioInteraccion: 52,
  deteccionFantasma: 180,
  danoInvulnerableMs: 1200,
};

const game = document.getElementById('game');
const puntajeEl = document.getElementById('puntaje');
const restantesEl = document.getElementById('restantes');
const tiempoEl = document.getElementById('tiempo');
const vidasEl = document.getElementById('vidas');
const pausaBtn = document.getElementById('pausaBtn');

const modal = document.getElementById('modal');
const modalTitulo = document.getElementById('modal-titulo');
const modalMsg = document.getElementById('modal-mensaje');
const modalResumen = document.getElementById('modal-resumen');
const bIniciar = document.getElementById('btn-iniciar');
const bReintentar = document.getElementById('btn-reintentar');
const bContinuar = document.getElementById('btn-continuar');
const resEnergia = document.getElementById('res-energia');
const resDispositivos = document.getElementById('res-dispositivos');
const resTiempo = document.getElementById('res-tiempo');
const resVidas = document.getElementById('res-vidas');
const buffsEl = document.getElementById('buffs');

// ----- Estado -----
let estado = 'menu'; // 'jugando' | 'pausa' | 'fin'
let teclas = {};
let tInicio = 0;
let tiempoRest = CONFIG.tiempoSeg * 1000;
let rafId = null;

const rnd = (min, max) => Math.random() * (max - min) + min;
const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

const world = {
  jugador: null,
  dispositivos: [],
  fantasmas: [],
  powerups: [],
  ahorro: 0,
  restantes: 0,
  vidas: CONFIG.vidas,
  shieldHasta: 0,
  speedHasta: 0,
  invulHasta: 0,
};

// ----- Utilidades DOM -----
function crear(tag, cls, parent = game){
  const el = document.createElement('div');
  if (cls) el.className = cls;
  el.classList.add('entity');
  parent.appendChild(el);
  return el;
}
function setPos(el, x, y){ el.style.transform = `translate(${x}px, ${y}px)`; }
function aabb(a, b){
  return !(a.x + a.w < b.x || a.x > b.x + b.w || a.y + a.h < b.y || a.y > b.y + b.h);
}
function distancia(a, b){
  const dx = (a.x + a.w/2) - (b.x + b.w/2);
  const dy = (a.y + a.h/2) - (b.y + b.h/2);
  return Math.hypot(dx, dy);
}
function flotante(texto, x, y, color = '#b5ffd6'){
  const f = document.createElement('div');
  f.className = 'floating';
  f.textContent = texto;
  f.style.left = `${x}px`; f.style.top = `${y}px`; f.style.color = color;
  game.appendChild(f);
  setTimeout(()=> f.remove(), 900);
}
function tiempoFmt(ms){
  const s = Math.max(0, Math.floor(ms/1000));
  const mm = String(Math.floor(s/60)).padStart(2,'0');
  const ss = String(s%60).padStart(2,'0');
  return `${mm}:${ss}`;
}

// ----- Inicialización -----
function prepararEscena(){
  game.innerHTML = '';
  game.style.width = CONFIG.ancho + 'px';
  game.style.height = CONFIG.alto + 'px';

  world.ahorro = 0; world.restantes = 0; world.vidas = CONFIG.vidas;
  world.dispositivos = []; world.fantasmas = []; world.powerups = [];
  world.shieldHasta = 0; world.speedHasta = 0; world.invulHasta = 0;

  // Jugador
  const pEl = crear('div', 'player');
  const jugador = { x: 40, y: CONFIG.alto/2-21, w: 42, h: 42, vx:0, vy:0, el:pEl };
  setPos(pEl, jugador.x, jugador.y);
  world.jugador = jugador;

  // Colocación segura sin solapes fuertes
  const ocupados = [];
  function libre(x, y, w=40, h=40){
    return !ocupados.some(o => !(x + w < o.x || x > o.x + o.w || y + h < o.y || y > o.y + o.h));
  }
  function registrar(x,y,w=40,h=40){ ocupados.push({x,y,w,h}); }

  // Dispositivos
  const wattsPosibles = [5, 8, 10, 12, 15, 18, 20, 25, 30];
  const dCant = CONFIG.dispositivos;
  let intentos = 0;
  for (let i=0;i<dCant;i++){
    let x, y;
    do{
      x = Math.floor(rnd(80, CONFIG.ancho-100));
      y = Math.floor(rnd(60, CONFIG.alto-100));
      intentos++;
      if (intentos>500) break;
    }while(!libre(x,y,40,40));
    registrar(x,y,40,40);

    const el = crear('div', 'device on');
    setPos(el, x, y);
    el.title = 'Acércate y presiona E para apagar';
    const w = wattsPosibles[Math.floor(Math.random()*wattsPosibles.length)];
    const dev = { x,y,w:40,h:40, el, estado:'on', watts:w };
    world.dispositivos.push(dev);
  }
  world.restantes = world.dispositivos.length;

  // Fantasmas
  for (let i=0;i<CONFIG.fantasmas;i++){
    let x, y;
    do{
      x = Math.floor(rnd(60, CONFIG.ancho-80));
      y = Math.floor(rnd(40, CONFIG.alto-80));
    }while(!libre(x,y,42,46));
    registrar(x,y,42,46);
    const el = crear('div', 'ghost');
    el.appendChild(Object.assign(document.createElement('div'), {className:'skirt'}));
    setPos(el, x, y);
    const g = { x,y,w:42,h:46, el, vx:rnd(-1,1), vy:rnd(-1,1), speed:rnd(1.2,1.8), perseguir:false, stunnedHasta:0 };
    world.fantasmas.push(g);
  }

  // Power-ups
  const tipos = ['speed','shield'];
  for (let i=0;i<CONFIG.powerups;i++){
    let x, y;
    do{
      x = Math.floor(rnd(60, CONFIG.ancho-80));
      y = Math.floor(rnd(40, CONFIG.alto-80));
    }while(!libre(x,y,34,34));
    registrar(x,y,34,34);
    const tipo = tipos[i%tipos.length];
    const el = crear('div', `power ${tipo}`);
    setPos(el, x, y);
    world.powerups.push({ x,y,w:34,h:34, el, tipo });
  }

  actualizarHUD();
}

function actualizarHUD(){
  puntajeEl.textContent = `${world.ahorro} W`;
  restantesEl.textContent = world.restantes;
  vidasEl.textContent = world.vidas;
  tiempoEl.textContent = tiempoFmt(tiempoRest);
  renderBuffs();
}

function renderBuffs(){
  buffsEl.innerHTML = '';
  const ahora = performance.now();
  if (world.shieldHasta > ahora){
    const b = document.createElement('span'); b.className = 'badge'; b.textContent = '🛡️ Escudo';
    buffsEl.appendChild(b);
  }
  if (world.speedHasta > ahora){
    const b = document.createElement('span'); b.className = 'badge'; b.textContent = '⚡ Velocidad';
    buffsEl.appendChild(b);
  }
}

// ----- Ciclo de juego -----
function loop(ts){
  if (estado !== 'jugando'){ return; }
  if (!tInicio) tInicio = ts;
  const dt = 16; // aproximado para movimiento estable
  tiempoRest -= (ts - (tInicio || ts));
  tInicio = ts;

  moverJugador(dt);
  moverFantasmas(dt);
  chequearInteracciones();
  actualizarHUD();

  // Fin por tiempo
  if (tiempoRest <= 0){
    terminar('Derrota', 'Se agotó el tiempo. ¡Inténtalo de nuevo!');
    return;
  }
  // Fin por objetivo
  if (world.restantes <= 0){
    terminar('¡Victoria!', 'Apagaste todos los dispositivos. ¡Buen ahorro!');
    return;
  }

  rafId = requestAnimationFrame(loop);
}

function moverJugador(){
  const p = world.jugador;
  const ahora = performance.now();
  const speedBase = CONFIG.velocidadJugador;
  const speed = (world.speedHasta > ahora ? speedBase * CONFIG.boostVelocidad : speedBase);
  p.vx = 0; p.vy = 0;
  if (teclas['ArrowLeft'] || teclas['a']) p.vx = -speed;
  if (teclas['ArrowRight'] || teclas['d']) p.vx = speed;
  if (teclas['ArrowUp'] || teclas['w']) p.vy = -speed;
  if (teclas['ArrowDown'] || teclas['s']) p.vy = speed;

  // Normalizar diagonal
  if (p.vx !== 0 && p.vy !== 0){ p.vx *= 0.707; p.vy *= 0.707; }

  p.x = clamp(p.x + p.vx, 4, CONFIG.ancho - p.w - 4);
  p.y = clamp(p.y + p.vy, 4, CONFIG.alto - p.h - 4);
  setPos(p.el, p.x, p.y);
}

function moverFantasmas(){
  const p = world.jugador;
  const ahora = performance.now();

  for (const g of world.fantasmas){
    if (g.stunnedHasta > ahora){
      g.el.classList.add('stunned');
      continue;
    } else {
      g.el.classList.remove('stunned');
    }

    const d = distancia(g, p);
    g.perseguir = d < CONFIG.deteccionFantasma;
    g.el.classList.toggle('pursuit', g.perseguir);

    if (g.perseguir){
      const ang = Math.atan2((p.y+20) - (g.y+20), (p.x+20) - (g.x+20));
      g.vx = Math.cos(ang) * g.speed * 1.2;
      g.vy = Math.sin(ang) * g.speed * 1.2;
    } else {
      // Patrulla aleatoria con leve ruido
      g.vx += rnd(-0.2, 0.2); g.vy += rnd(-0.2, 0.2);
      const mag = Math.hypot(g.vx, g.vy) || 1;
      const norm = (g.speed / mag);
      g.vx *= norm; g.vy *= norm;
    }

    g.x += g.vx; g.y += g.vy;

    // Rebotar en bordes
    if (g.x < 4 || g.x > CONFIG.ancho - g.w - 4) g.vx *= -1, g.x = clamp(g.x, 4, CONFIG.ancho - g.w - 4);
    if (g.y < 4 || g.y > CONFIG.alto - g.h - 4) g.vy *= -1, g.y = clamp(g.y, 4, CONFIG.alto - g.h - 4);

    setPos(g.el, g.x, g.y);

    // Colisión con jugador
    if (aabb(g, p)){
      if (world.shieldHasta > ahora){
        // Aturdir fantasma
        g.stunnedHasta = ahora + 1600;
        flotante('¡Aturdido!', g.x, g.y, '#ffd8e1');
      } else if (world.invulHasta < ahora){
        world.vidas--;
        world.invulHasta = ahora + CONFIG.danoInvulnerableMs;
        flotante('-1 ❤️', p.x, p.y, '#ff8fa0');
        if (world.vidas <= 0){
          terminar('Derrota', 'Los fantasmas te alcanzaron. ¡Inténtalo de nuevo!');
          return;
        }
      }
    }
  }
}

function chequearInteracciones(){
  const p = world.jugador;
  const ahora = performance.now();

  // Interacción E con dispositivos cercanos
  if (teclas['e']){
    const cercano = world.dispositivos.find(d => d.estado==='on' && distancia(p, d) < CONFIG.radioInteraccion);
    if (cercano){
      cercano.estado = 'off';
      cercano.el.classList.remove('on'); cercano.el.classList.add('off');
      world.ahorro += cercano.watts;
      world.restantes--;
      flotante(`+${cercano.watts} W`, cercano.x, cercano.y, '#d7ff9e');
      teclas['e'] = false; // evitar repetir
    }
  }

  // Power-ups
  for (let i=world.powerups.length-1; i>=0; i--){
    const pow = world.powerups[i];
    if (aabb(pow, p)){
      if (pow.tipo === 'speed'){
        world.speedHasta = Math.max(world.speedHasta, ahora) + CONFIG.duracionBoostMs;
        flotante('⚡ Velocidad', p.x, p.y, '#c8fff3');
      }
      if (pow.tipo === 'shield'){
        world.shieldHasta = Math.max(world.shieldHasta, ahora) + CONFIG.duracionEscudoMs;
        flotante('🛡️ Escudo', p.x, p.y, '#c8fff3');
      }
      pow.el.remove();
      world.powerups.splice(i,1);
    }
  }
}

// ----- Controles -----
document.addEventListener('keydown', (e)=>{
  teclas[e.key] = true;
  if (e.key === 'p' || e.key === 'P'){ togglePausa(); }
});
document.addEventListener('keyup', (e)=>{
  teclas[e.key] = false;
});
pausaBtn.addEventListener('click', togglePausa);

function togglePausa(){
  if (estado !== 'jugando' && estado !== 'pausa') return;
  if (estado === 'jugando'){
    estado = 'pausa';
    cancelarLoop();
    mostrarModal('Pausa', 'Toma aire. Cuando quieras, continúa.', false, {continuar:true});
  } else {
    ocultarModal();
    continuar();
  }
}

// ----- Modal y flujo -----
function mostrarModal(titulo, msg, resumen=false, botones={}){
  modalTitulo.textContent = titulo;
  modalMsg.textContent = msg;
  modalResumen.classList.toggle('hidden', !resumen);
  bIniciar.classList.toggle('hidden', !botones.iniciar);
  bReintentar.classList.toggle('hidden', !botones.reintentar);
  bContinuar.classList.toggle('hidden', !botones.continuar);
  modal.classList.remove('hidden');
}
function ocultarModal(){ modal.classList.add('hidden'); }
function cancelarLoop(){ if (rafId) cancelAnimationFrame(rafId); rafId = null; tInicio = 0; }

function iniciar(){
  estado = 'jugando';
  tiempoRest = CONFIG.tiempoSeg * 1000;
  prepararEscena();
  ocultarModal();
  tInicio = 0;
  rafId = requestAnimationFrame(loop);
}
function continuar(){
  if (estado === 'pausa'){ estado = 'jugando'; ocultarModal(); tInicio = 0; rafId = requestAnimationFrame(loop); }
}
function terminar(titulo, msg){
  estado = 'fin';
  cancelarLoop();
  // Resumen
  resEnergia.textContent = `${world.ahorro} W`;
  resDispositivos.textContent = `${CONFIG.dispositivos - world.restantes}/${CONFIG.dispositivos}`;
  resTiempo.textContent = `${Math.max(0, Math.floor(tiempoRest/1000))} s`;
  resVidas.textContent = `${world.vidas}`;
  mostrarModal(titulo, msg, true, { reintentar:true });
}

// Botones modal
bIniciar.addEventListener('click', iniciar);
bReintentar.addEventListener('click', iniciar);
bContinuar.addEventListener('click', continuar);

// Mostrar pantalla de inicio
mostrarModal(
  'Energía Fantasma',
  'Apaga todos los dispositivos antes de que se termine el tiempo. Evita a los fantasmas y usa los power-ups.',
  false,
  { iniciar:true }
);

const guardarBtn = document.getElementById('guardarBtn');

guardarBtn.addEventListener('click', () => {
  if (estado !== 'fin') {
    alert('Primero termina la partida para guardar tu puntaje.');
    return;
  }

  guardarBtn.disabled = true;
  guardarBtn.textContent = 'Guardando...';

  fetch('guardar_energia.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      // jugador: NO lo envíes si vas a usar la sesión; o mantenlo si aún no tienes login
      // jugador: 'Estudiante1',
      ahorro_watts: world.ahorro,
      dispositivos_apagados: CONFIG.dispositivos - world.restantes,
      tiempo_restante: Math.max(0, Math.floor(tiempoRest/1000)),
      vidas_restantes: world.vidas
    })
  })
  .then(res => res.json())
  .then(resp => {
    if (resp && resp.success) {
      guardarBtn.textContent = '✅ ¡Guardado!';
    } else {
      throw new Error(resp?.error || 'Respuesta no válida');
    }
  })
  .catch(err => {
    console.error('Error al guardar:', err);
    guardarBtn.textContent = 'Reintentar';
    guardarBtn.disabled = false;
    alert('No se pudo guardar: ' + err.message);
  });
});

document.getElementById('btn-continuar').addEventListener('click', () => {
  window.location.href = 'index.php';
});
