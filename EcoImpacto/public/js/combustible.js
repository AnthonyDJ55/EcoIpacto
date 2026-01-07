(() => {
  const fuentes = shuffle([
    // Limpias / No fósiles
    {n:'Solar fotovoltaica', t:'limpia', i:'☀️', d:'Convierte luz solar en electricidad.'},
    {n:'Eólica', t:'limpia', i:'🌬️', d:'Turbinas que aprovechan el viento.'},
    {n:'Hidroeléctrica', t:'limpia', i:'💧', d:'Energía del agua en movimiento.'},
    {n:'Geotérmica', t:'limpia', i:'🌋', d:'Calor interno de la Tierra.'},
    {n:'Mareomotriz', t:'limpia', i:'🌊', d:'Aprovecha mareas y olas.'},
    {n:'Hidrógeno verde', t:'limpia', i:'🟢H₂', d:'Producido con electricidad renovable.'},
    // Fósiles
    {n:'Carbón', t:'fosil', i:'🪨', d:'Sólido fósil, alto CO₂ por kWh.'},
    {n:'Petróleo', t:'fosil', i:'🛢️', d:'Base de gasolina y diésel.'},
    {n:'Gas natural', t:'fosil', i:'🔥', d:'Metano; menor CO₂ que carbón, pero fósil.'},
    {n:'Diésel', t:'fosil', i:'🚛', d:'Derivado del petróleo, transporte pesado.'},
    {n:'Gasolina', t:'fosil', i:'⛽', d:'Combustible de vehículos livianos.'},
    {n:'Querosen', t:'fosil', i:'✈️', d:'Aviación comercial y doméstico.'},
    {n:'Fuel oil', t:'fosil', i:'🧪', d:'Pesado, generación térmica antigua.'},
    {n:'Lignito', t:'fosil', i:'🪵', d:'Carbón de baja calidad, muy contaminante.'},
    {n:'Coque', t:'fosil', i:'⚫', d:'Proceso de destilación del carbón.'},
  ]);

  // Estado del juego
  const estado = {
    idx: 0,
    puntos: window.JUEGO_CTX?.puntosSesion || 0,
    tiempo: 60,
    vidas: 3,
    racha: 0,
    limpiasCorrectas: 0,
    erroresFosiles: 0,
    terminado: false,
    timerId: null
  };

  // UI refs
  const $ = s => document.querySelector(s);
  const ui = {
    carta: $('#carta-actual'),
    icono: $('#carta-icono'),
    nombre: $('#carta-nombre'),
    dato: $('#carta-dato'),
    btnFosil: $('#btn-fosil'),
    btnLimpia: $('#btn-limpia'),
    tiempo: $('#ui-tiempo'),
    vidas: $('#ui-vidas'),
    racha: $('#ui-racha'),
    puntos: $('#ui-puntos'),
    barra: $('#barra-progreso'),
    eco: $('#eco-msg'),
    logros: $('#ui-logros'),
    overlayIntro: $('#overlay-intro'),
    btnComenzar: $('#btn-comenzar'),

    final: $('#pantalla-final'),
    statFuentes: $('#stat-fuentes'),
    statErrores: $('#stat-errores'),
    statTiempo: $('#stat-tiempo'),
    statVidas: $('#stat-vidas'),
    statPuntos: $('#stat-puntos'),
    btnGuardar: $('#btn-guardar'),
    btnReintentar: $('#btn-reintentar'),
    btnContinuar: $('#btn-continuar'),
    msgGuardado: $('#mensaje-guardado')
  };

  // Eventos
  ui.btnComenzar.addEventListener('click', iniciarJuego);
  ui.btnFosil.addEventListener('click', () => clasificar('fosil'));
  ui.btnLimpia.addEventListener('click', () => clasificar('limpia'));
  document.addEventListener('keydown', e => {
    if (estado.terminado) return;
    if (e.key.toLowerCase() === 'a') clasificar('fosil');
    if (e.key.toLowerCase() === 's') clasificar('limpia');
  });

  ui.btnGuardar.addEventListener('click', guardarPuntos);
  ui.btnReintentar.addEventListener('click', () => location.reload());
  ui.btnContinuar.addEventListener('click', () => window.location.href = 'index.php');

  // Lógica
  function iniciarJuego(){
    ui.overlayIntro.classList.add('oculto');
    pintar();
    estado.timerId = setInterval(tick, 1000);
    ecoMsg('¡Vamos! Clasifica lo más rápido que puedas.');
  }

  function tick(){
    if (estado.tiempo <= 0) { terminar('¡Tiempo agotado!'); return; }
    estado.tiempo--;
    ui.tiempo.textContent = estado.tiempo;
    ui.barra.style.width = `${(estado.tiempo/60)*100}%`;
    if (estado.tiempo === 15) ecoMsg('⏳ ¡Últimos 15s! Mantén la calma.');
  }

  function pintar(){
    if (estado.idx >= fuentes.length) { terminar('¡Clasificaste todo!'); return; }
    const f = fuentes[estado.idx];
    ui.icono.textContent = f.i;
    ui.nombre.textContent = f.n;
    ui.dato.textContent = f.d;
    ui.carta.classList.remove('correcto','error');
  }

  function clasificar(eleccion){
    if (estado.terminado) return;
    const f = fuentes[estado.idx];
    const correcto = (eleccion === f.t);

    if (correcto) {
      estado.racha++;
      const bonus = 25 * Math.min(estado.racha-1, 5); // bonus por racha (cap 5)
      const base = f.t === 'limpia' ? 150 : 50;       // premiamos más las limpias
      estado.puntos += base + bonus;
      if (f.t === 'limpia') estado.limpiasCorrectas++;
      ecoMsg(randomDe([
        '¡Exacto! 🌿', '¡Bien visto! ✅', '¡Racha en aumento! 🔥', '¡Eso salva emisiones! 💪'
      ]));
      ui.carta.classList.add('correcto');
      desbloquearLogros();
    } else {
      estado.racha = 0;
      estado.vidas--;
      estado.puntos = Math.max(0, estado.puntos - 100);
      if (f.t === 'fosil') estado.erroresFosiles++;
      ecoMsg(randomDe([
        'Ups, revisa esa elección. ⚠️', 'Esa favorece emisiones. ❌', 'Respira y concéntrate. 🌬️'
      ]));
      ui.carta.classList.add('error');
      if (estado.vidas <= 0) { terminar('¡Sin vidas!'); return; }
    }

    // UI
    ui.vidas.textContent = estado.vidas;
    ui.racha.textContent = estado.racha;
    ui.puntos.textContent = estado.puntos;

    // Siguiente
    estado.idx++;
    setTimeout(pintar, 280);
  }

  function terminar(motivo){
    if (estado.terminado) return;
    estado.terminado = true;
    clearInterval(estado.timerId);
    ecoMsg(motivo + ' Mira tu resumen abajo.');
    // Stats finales
    ui.statFuentes.textContent = estado.limpiasCorrectas;
    ui.statErrores.textContent = estado.erroresFosiles;
    ui.statTiempo.textContent = estado.tiempo;
    ui.statVidas.textContent = estado.vidas;
    ui.statPuntos.textContent = estado.puntos;
    ui.final.classList.remove('oculto');
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
  }

  function ecoMsg(msg){ ui.eco.textContent = msg; }

  function desbloquearLogros(){
    const l = [];
    if (estado.racha >= 3) l.push('🔥 Racha x3');
    if (estado.limpiasCorrectas >= 5) l.push('🌿 Guardián Verde');
    if (estado.puntos >= 1000) l.push('🏆 Detector Maestro');
    ui.logros.innerHTML = l.map(x => `<li>${x}</li>`).join('');
  }

  async function guardarPuntos(){
    const token = document.querySelector('meta[name="csrf-token"]').content;
    ui.msgGuardado.textContent = 'Guardando...';
    ui.btnGuardar.disabled = true;

    try {
      const resp = await fetch('guardar_combustible.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
        juego: 'Reto: ¿Son Combustible?',
        puntos: estado.puntos,
        limpias: estado.limpiasCorrectas,
        errores: estado.erroresFosiles,
        tiempo: estado.tiempo,
        vidas: estado.vidas,
        csrf: token
        })
      });
      const data = await resp.json();
      if (data.ok) {
        ui.msgGuardado.textContent = '✅ Puntos guardados.';
      } else {
        ui.msgGuardado.textContent = '⚠️ No se pudo guardar: ' + (data.msg || 'intenta más tarde.');
        ui.btnGuardar.disabled = false;
      }
    } catch (e){
      ui.msgGuardado.textContent = '⚠️ Error de red al guardar.';
      ui.btnGuardar.disabled = false;
    }
  }

  // Utils
  function shuffle(a){ for(let i=a.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[a[i],a[j]]=[a[j],a[i]]} return a; }
  function randomDe(arr){ return arr[Math.floor(Math.random()*arr.length)]; }
})();
