(() => {
  // ================== Configuración general ==================
  const cvs = document.getElementById('game');
  const ctx = cvs.getContext('2d');
  const DPR = Math.max(1, Math.floor(window.devicePixelRatio || 1));
  // ajustar resolución real
  const baseW = 900, baseH = 520;
  cvs.width = baseW * DPR; cvs.height = baseH * DPR; ctx.scale(DPR, DPR);

  // HUD/Controles
  const elTiempo = document.getElementById('hud-tiempo');
  const elPuntos = document.getElementById('hud-puntos');
  const elCombo  = document.getElementById('hud-combo');
  const elVidas  = document.getElementById('hud-vidas');
  const elNivel  = document.getElementById('hud-nivel');

  const btnIniciar = document.getElementById('btn-iniciar');
  const btnPausa   = document.getElementById('btn-pausa');
  const btnGuardar = document.getElementById('btn-guardar');
  const btnRanking = document.getElementById('btn-ranking');
  const btnSonido  = document.getElementById('btn-sonido');

  const overlay = document.getElementById('overlay');
  const overlayTitulo = document.getElementById('overlay-titulo');
  const overlayTexto = document.getElementById('overlay-texto');
  const overlayAccion = document.getElementById('overlay-accion');

  const modalRanking = document.getElementById('ranking-modal');
  const rankingList = document.getElementById('ranking-list');
  const rankingCerrar = document.getElementById('ranking-cerrar');

  const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // ================== Estado del juego ==================
  const state = {
    running: false,
    paused: false,
    tiempo: 60,
    puntos: 0,
    combo: 1,
    comboProg: 0, // progresa y sube multiplicador
    vidas: 3,
    nivel: 1,
    spawnRate: 1200, // ms
    lastSpawn: 0,
    speedFactor: 1.0,
    end: false,
    sonido: true,
    powerUps: [], // activos
    entities: [],
    particles: [],
    input: { left: false, right: false, up: false, down: false, touch: false, tx: 0, ty: 0 },
    player: null,
    t0: performance.now()
  };

  // ================== Utilidades ==================
  const rand = (min, max) => Math.random() * (max - min) + min;
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
  const lerp = (a, b, t) => a + (b - a) * t;

  // ================== Entidades ==================
  class Entity {
    constructor(x, y, vx, vy, r, kind, data) {
      this.x = x; this.y = y; this.vx = vx; this.vy = vy;
      this.r = r; this.kind = kind; this.data = data || {};
      this.dead = false;
    }
    update(dt) {
      this.x += this.vx * dt; this.y += this.vy * dt;
      if (this.y > baseH + 60 || this.x < -60 || this.x > baseW + 60) this.dead = true;
    }
    draw(ctx) {}
  }

  class Player extends Entity {
    constructor() {
      super(baseW/2, baseH - 80, 0, 0, 24, 'player');
      this.speed = 260;
      this.magnet = false;
    }
    update(dt) {
      const inp = state.input;
      let ax = 0, ay = 0;
      ax += (inp.left ? -1 : 0) + (inp.right ? 1 : 0);
      ay += (inp.up ? -1 : 0) + (inp.down ? 1 : 0);
      if (state.input.touch) {
        // acercarse al toque
        this.x = lerp(this.x, state.input.tx, 0.18);
        this.y = lerp(this.y, state.input.ty, 0.18);
      } else {
        this.x += ax * this.speed * dt;
        this.y += ay * this.speed * dt;
      }
      this.x = clamp(this.x, 40, baseW - 40);
      this.y = clamp(this.y, 60, baseH - 50);
    }
    draw(ctx) {
      // barco estilizado
      ctx.save();
      ctx.translate(this.x, this.y);
      ctx.fillStyle = '#143a66';
      ctx.beginPath();
      ctx.moveTo(-30, 12); ctx.lineTo(30, 12); ctx.lineTo(18, 22); ctx.lineTo(-18, 22);
      ctx.closePath(); ctx.fill();
      ctx.fillStyle = '#e8f2ff';
      ctx.fillRect(-6, -8, 12, 20);
      ctx.fillStyle = '#1fb8ff';
      ctx.beginPath(); ctx.arc(0, -12, 12, 0, Math.PI*2); ctx.fill();
      // red/área de captura
      ctx.strokeStyle = 'rgba(255,255,255,0.5)';
      ctx.beginPath(); ctx.arc(0, 8, 34, 0, Math.PI*2); ctx.stroke();
      ctx.restore();
    }
  }

  class Plastic extends Entity {
    constructor(x, y, speed) {
      const vx = rand(-20, 20), vy = speed * rand(50, 90)/60;
      super(x, y, vx, vy, rand(10,16), 'plastic', { score: 10 });
      this.shape = Math.floor(rand(0,3));
      this.color = ['#9be7ff','#8de3d1','#f0ff9f'][this.shape];
    }
    update(dt) {
      super.update(dt);
      // leve flotación
      this.x += Math.sin(performance.now()/400 + this.y*0.01) * 0.15;
    }
    draw(ctx) {
      ctx.save(); ctx.translate(this.x, this.y);
      ctx.fillStyle = this.color;
      switch (this.shape) {
        case 0: ctx.fillRect(-8, -6, 16, 12); break; // tapita
        case 1: ctx.beginPath(); ctx.arc(0,0,this.r,0,Math.PI*2); ctx.fill(); break; // bola
        case 2: ctx.beginPath(); ctx.moveTo(-10,-6); ctx.lineTo(10,-6); ctx.lineTo(6,6); ctx.lineTo(-6,6); ctx.closePath(); ctx.fill(); break; // bolsa
      }
      ctx.restore();
    }
  }

  class Wildlife extends Entity {
    constructor(x, y, speed) {
      const vx = rand(-40, 40), vy = speed * rand(45, 70)/60;
      super(x, y, vx, vy, rand(14,20), 'wildlife', { penalty: 20 });
    }
    draw(ctx) {
      ctx.save(); ctx.translate(this.x, this.y);
      ctx.fillStyle = '#ffd166';
      ctx.beginPath(); ctx.ellipse(0,0,this.r+6,this.r-6,0,0,Math.PI*2); ctx.fill(); // pez
      ctx.fillStyle = '#ff9e3d';
      ctx.beginPath(); ctx.moveTo(10,0); ctx.lineTo(22,-10); ctx.lineTo(22,10); ctx.closePath(); ctx.fill(); // cola
      ctx.restore();
    }
  }

  class PowerUp extends Entity {
    constructor(x, y, type) {
      const vx = rand(-15, 15), vy = rand(40, 70)/60;
      super(x, y, vx, vy, 12, 'power', { type });
    }
    draw(ctx) {
      ctx.save(); ctx.translate(this.x, this.y);
      ctx.fillStyle = this.data.type === 'magnet' ? '#23d18b' :
                      this.data.type === 'slow'   ? '#ffd166' : '#a78bfa';
      ctx.beginPath(); ctx.arc(0,0,this.r,0,Math.PI*2); ctx.fill();
      ctx.fillStyle = '#0b1a2e';
      ctx.font = '12px system-ui'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      ctx.fillText(this.data.type === 'magnet' ? 'M' : this.data.type === 'slow' ? 'S' : 'C', 0, 0);
      ctx.restore();
    }
  }

  class Particle {
    constructor(x,y,color) {
      this.x=x; this.y=y; this.vx=rand(-80,80); this.vy=rand(-80,0); this.life=rand(0.3,0.7); this.color=color;
    }
    update(dt){ this.x+=this.vx*dt; this.y+=this.vy*dt; this.vy+=90*dt; this.life-=dt; }
    draw(ctx){ ctx.globalAlpha=Math.max(0,this.life); ctx.fillStyle=this.color; ctx.fillRect(this.x,this.y,3,3); ctx.globalAlpha=1; }
  }

  // ================== Sistema de audio (simple toggle) ==================
  const SFX = {
    enabled: true,
    play(name) {
      if (!this.enabled) return;
      // sonidos simples con WebAudio API podrían agregarse; placeholder:
      // console.log('sfx', name);
    }
  };

  // ================== Inicialización ==================
  function resetGame() {
    state.running = false;
    state.paused = false;
    state.tiempo = 60;
    state.puntos = 0;
    state.combo = 1; state.comboProg = 0;
    state.vidas = 3;
    state.nivel = 1;
    state.spawnRate = 1100;
    state.speedFactor = 1.0;
    state.end = false;
    state.entities = [];
    state.particles = [];
    state.powerUps = [];
    state.player = new Player();
    updateHUD();
    drawScene(0);
  }

  // ================== Spawner y dificultad ==================
  function spawnEntity() {
    const x = rand(40, baseW-40), y = -20;
    const roll = Math.random();
    const speed = 60 + (state.nivel-1) * 8;
    if (roll < 0.68) {
      state.entities.push(new Plastic(x, y, speed));
    } else if (roll < 0.88) {
      state.entities.push(new Wildlife(x, y, speed));
    } else {
      const type = Math.random() < 0.5 ? 'magnet' : (Math.random() < 0.5 ? 'slow' : 'clean');
      state.entities.push(new PowerUp(x, y, type));
    }
  }

  function adaptDifficulty() {
    // sube de nivel por puntos y reduce spawnRate
    const threshold = 120 * state.nivel;
    if (state.puntos >= threshold) {
      state.nivel++;
      state.spawnRate = Math.max(450, state.spawnRate - 110);
      state.speedFactor += 0.05;
      toast(`Nivel ${state.nivel} alcanzado`, '#23d18b');
    }
  }

  // ================== Colisiones y lógica ==================
  function dist(a,b){ const dx=a.x-b.x, dy=a.y-b.y; return Math.hypot(dx,dy); }

  function handleCollisions() {
    const p = state.player;
    for (const e of state.entities) {
      if (e.dead) continue;
      // atracción por imán
      if (e.kind === 'plastic' && hasPower('magnet')) {
        const d = Math.max(1, dist(p,e));
        const pull = 260 / (d*d);
        e.vx += (p.x - e.x) * pull;
        e.vy += (p.y - e.y) * pull;
      }
      if (dist(p, e) <= (e.r + 28)) {
        if (e.kind === 'plastic') {
          scorePlastic(e);
        } else if (e.kind === 'wildlife') {
          hitWildlife(e);
        } else if (e.kind === 'power') {
          pickPower(e);
        }
        e.dead = true;
      }
    }
  }

  function scorePlastic(e) {
    const base = 10;
    const points = Math.floor(base * state.combo);
    state.puntos += points;
    state.comboProg += 1;
    if (state.comboProg >= 5) { state.combo++; state.comboProg = 0; toast(`Combo x${state.combo}`, '#ffd166'); }
    burst(e.x, e.y, '#9be7ff');
    SFX.play('pickup');
  }

  function hitWildlife(e) {
    state.vidas -= 1;
    state.combo = 1; state.comboProg = 0;
    burst(e.x, e.y, '#ff5d73');
    SFX.play('hit');
    toast('¡Cuidado con la fauna!', '#ff5d73');
    if (state.vidas <= 0) endGame();
  }

  function pickPower(e) {
    const t = e.data.type;
    if (t === 'magnet') activatePower('magnet', 8);
    else if (t === 'slow') activatePower('slow', 6);
    else if (t === 'clean') { // limpia plásticos visibles
      let cleared = 0;
      state.entities.forEach(ent => { if (ent.kind === 'plastic') { ent.dead = true; cleared++; burst(ent.x, ent.y, '#8de3d1'); }});
      const gain = cleared * Math.floor(6 * state.combo);
      state.puntos += gain;
      toast(`Oleada limpia +${gain}`, '#a78bfa');
    }
    SFX.play('power');
  }

  function hasPower(name) { return state.powerUps.some(p => p.name === name && p.t > 0); }
  function activatePower(name, secs) {
    const existing = state.powerUps.find(p => p.name === name);
    if (existing) existing.t = Math.max(existing.t, secs);
    else state.powerUps.push({ name, t: secs });
  }

  function updatePowers(dt) {
    for (const p of state.powerUps) p.t -= dt;
    state.powerUps = state.powerUps.filter(p => p.t > 0);
  }

  // ================== Partículas & UI ==================
  function burst(x,y,color) { for (let i=0;i<10;i++) state.particles.push(new Particle(x,y,color)); }
  function toast(msg, color = '#ffd166') {
    overlay.classList.remove('hidden');
    overlayTitulo.textContent = 'Mensaje';
    overlayTexto.textContent = msg;
    overlay.querySelector('.panel').style.borderColor = color;
    overlayAccion.textContent = 'OK';
    overlayAccion.onclick = () => overlay.classList.add('hidden');
  }

  // ================== Bucle de juego ==================
  let last = performance.now();
  function loop(now) {
    if (!state.running || state.paused) { requestAnimationFrame(loop); return; }
    const dt = Math.min(0.033, (now - last) / 1000) * state.speedFactor * (hasPower('slow') ? 0.6 : 1);
    last = now;

    // tiempo
    state.tiempo -= dt;
    if (state.tiempo <= 0) { endGame(); }

    // spawner
    state.lastSpawn += dt * 1000;
    if (state.lastSpawn >= state.spawnRate) {
      spawnEntity();
      state.lastSpawn = 0;
    }

    // actualizar
    state.player.update(dt);
    for (const e of state.entities) e.update(dt);
    handleCollisions();
    updatePowers(dt);
    for (const p of state.particles) p.update(dt);
    state.entities = state.entities.filter(e => !e.dead);
    state.particles = state.particles.filter(p => p.life > 0);

    adaptDifficulty();
    updateHUD();
    drawScene(dt);

    requestAnimationFrame(loop);
  }

  function drawScene() {
    // fondo oleaje
    ctx.clearRect(0,0,baseW,baseH);
    // líneas de profundidad
    ctx.globalAlpha = 0.25;
    ctx.fillStyle = '#023a6a';
    for (let i=0;i<6;i++){
      ctx.fillRect(0, 100 + i*60 + Math.sin(performance.now()/700 + i)*6, baseW, 3);
    }
    ctx.globalAlpha = 1;

    // entidades
    for (const e of state.entities) e.draw(ctx);
    for (const p of state.particles) p.draw(ctx);
    state.player.draw(ctx);

    // indicadores de power-ups
    drawPowers();
  }

  function drawPowers() {
    const list = state.powerUps;
    const x0 = baseW - 16, y0 = 20, h = 16;
    ctx.save();
    ctx.font = '12px system-ui';
    ctx.textAlign = 'right';
    for (let i=0; i<list.length; i++) {
      const p = list[i];
      const y = y0 + i*(h+6);
      const color = p.name==='magnet' ? '#23d18b' : p.name==='slow' ? '#ffd166' : '#a78bfa';
      ctx.fillStyle = color; ctx.fillRect(x0-110, y, 110, h);
      ctx.fillStyle = '#0b1a2e'; ctx.fillText(`${p.name} ${p.t.toFixed(1)}s`, x0-6, y+12);
    }
    ctx.restore();
  }

  function updateHUD() {
    elTiempo.textContent = Math.max(0, Math.ceil(state.tiempo)).toString();
    elPuntos.textContent = state.puntos.toString();
    elCombo.textContent = `x${state.combo}`;
    elVidas.textContent = state.vidas.toString();
    elNivel.textContent = state.nivel.toString();
  }

  function endGame() {
    state.running = false; state.end = true;
    btnGuardar.disabled = false;
    overlay.classList.remove('hidden');
    overlayTitulo.textContent = 'Fin de partida';
    overlayTexto.textContent = `Puntaje: ${state.puntos}. ¿Quieres guardar tu resultado?`;
    overlayAccion.textContent = 'Aceptar';
    overlayAccion.onclick = () => overlay.classList.add('hidden');
  }

  // ================== Controles ==================
  window.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft' || e.key === 'a') state.input.left = true;
    if (e.key === 'ArrowRight' || e.key === 'd') state.input.right = true;
    if (e.key === 'ArrowUp' || e.key === 'w') state.input.up = true;
    if (e.key === 'ArrowDown' || e.key === 's') state.input.down = true;
    if (e.key === ' ' && state.running) togglePause();
  });
  window.addEventListener('keyup', e => {
    if (e.key === 'ArrowLeft' || e.key === 'a') state.input.left = false;
    if (e.key === 'ArrowRight' || e.key === 'd') state.input.right = false;
    if (e.key === 'ArrowUp' || e.key === 'w') state.input.up = false;
    if (e.key === 'ArrowDown' || e.key === 's') state.input.down = false;
  });

  // tacto
  cvs.addEventListener('pointerdown', e => { state.input.touch = true; setTouch(e); });
  cvs.addEventListener('pointermove', e => { if (state.input.touch) setTouch(e); });
  cvs.addEventListener('pointerup', () => { state.input.touch = false; });
  function setTouch(e){
    const rect = cvs.getBoundingClientRect();
    const x = (e.clientX - rect.left) * (baseW / rect.width);
    const y = (e.clientY - rect.top)  * (baseH / rect.height);
    state.input.tx = x; state.input.ty = y;
  }

  // botones UI
  btnIniciar.addEventListener('click', () => {
    resetGame();
    state.running = true;
    btnPausa.disabled = false;
    btnGuardar.disabled = true;
    overlay.classList.add('hidden');
    last = performance.now();
    requestAnimationFrame(loop);
  });

  function togglePause() {
    if (!state.running) return;
    state.paused = !state.paused;
    btnPausa.textContent = state.paused ? '▶️ Reanudar' : '⏸️ Pausa';
    overlay.classList.toggle('hidden', !state.paused);
    overlayTitulo.textContent = state.paused ? 'Pausa' : '';
    overlayTexto.textContent = state.paused ? 'La partida está en pausa.' : '';
    overlayAccion.textContent = state.paused ? 'Continuar' : 'Listo';
    overlayAccion.onclick = () => togglePause();
  }

  btnPausa.addEventListener('click', togglePause);

  btnGuardar.addEventListener('click', async () => {
    if (!state.end) { toast('Termina la partida para guardar.'); return; }
    btnGuardar.disabled = true;
    try {
      const form = new URLSearchParams();
      form.append('puntos', String(state.puntos));
      const res = await fetch('guardar_guardacostas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': CSRF },
        body: form.toString()
      });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Error');
      toast('¡Puntaje guardado con éxito!', '#23d18b');
    } catch (err) {
      toast('No se pudo guardar. Intenta de nuevo.', '#ff5d73');
      btnGuardar.disabled = false;
    }
  });

  btnRanking.addEventListener('click', async () => {
    modalRanking.classList.remove('hidden');
    rankingList.innerHTML = '<li>Cargando...</li>';
    try {
      const res = await fetch('top_guardacostas.php');
      const data = await res.json();
      if (!data.ok) throw new Error();
      rankingList.innerHTML = '';
      data.top.forEach((r, i) => {
        const li = document.createElement('li');
        li.textContent = `#${i+1} ${r.usuario} — ${r.puntos} pts`;
        rankingList.appendChild(li);
      });
    } catch {
      rankingList.innerHTML = '<li>Error al cargar ranking</li>';
    }
  });
  rankingCerrar.addEventListener('click', () => modalRanking.classList.add('hidden'));

  btnSonido.addEventListener('click', () => {
    SFX.enabled = !SFX.enabled;
    btnSonido.setAttribute('aria-pressed', String(SFX.enabled));
    btnSonido.textContent = SFX.enabled ? '🔊' : '🔈';
  });

  // primer render
  resetGame();
})();

function finalizarMision() {
  alert("¡Misión finalizada! Gracias por proteger el océano 🌊");
  window.location.href = "index.php"; // o la ruta que uses para volver al menú
}
