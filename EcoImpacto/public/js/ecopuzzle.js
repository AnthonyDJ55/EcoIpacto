// EcoPuzzle — Match-3 ambiental
(() => {
  // Configuración
  const ROWS = 8;
  const COLS = 8;
  const MOVES_START = 25;
  const TARGET_SCORE = 2500;
  const POINTS_PER_TILE = 60; // base por ficha
  const TYPES = ['🌲','💧','♻️','⚡','🐾']; // árboles, agua, reciclaje, energía, fauna

  // Estado
  let grid = []; // matriz ROWS x COLS con emojis
  let score = 0;
  let moves = MOVES_START;
  let selected = null; // {r,c}
  let busy = false;
  let chain = 0;

  // DOM
  const gridEl = document.getElementById('grid');
  const puntajeEl = document.getElementById('puntaje');
  const movsEl = document.getElementById('movs');
  const metaEl = document.getElementById('meta');
  const saludFillEl = document.getElementById('saludFill');
  const estadoEl = document.getElementById('estado');
  const btnReiniciar = document.getElementById('btnReiniciar');
  const modal = document.getElementById('modal');
  const modalTitulo = document.getElementById('modalTitulo');
  const modalMensaje = document.getElementById('modalMensaje');
  const btnJugarDeNuevo = document.getElementById('btnJugarDeNuevo');
  const btnCerrar = document.getElementById('btnCerrar');

  // Inicialización
  metaEl.textContent = TARGET_SCORE.toString();
  btnReiniciar.addEventListener('click', resetGame);
  btnJugarDeNuevo.addEventListener('click', () => { hideModal(); resetGame(); });
  btnCerrar.addEventListener('click', hideModal);

  resetGame();

  // Funciones principales
  function resetGame(){
    score = 0;
    moves = MOVES_START;
    chain = 0;
    selected = null;
    busy = false;
    updateHUD();

    buildInitialGrid();
    renderGrid();
    estadoEl.textContent = 'Restaura el ecosistema haciendo combinaciones de 3 o más.';
  }

  function buildInitialGrid(){
    grid = Array.from({length: ROWS}, () => Array(COLS).fill(null));
    for (let r = 0; r < ROWS; r++){
      for (let c = 0; c < COLS; c++){
        let type;
        do {
          type = randomType();
        } while (createsStreak(r, c, type));
        grid[r][c] = type;
      }
    }
  }

  function renderGrid(){
    gridEl.innerHTML = '';
    gridEl.style.setProperty('grid-template-columns', `repeat(${COLS}, 56px)`);
    gridEl.style.setProperty('grid-template-rows', `repeat(${ROWS}, 56px)`);
    for (let r = 0; r < ROWS; r++){
      for (let c = 0; c < COLS; c++){
        const tile = document.createElement('button');
        tile.className = 'tile';
        tile.type = 'button';
        tile.setAttribute('role', 'gridcell');
        tile.setAttribute('aria-label', `Fila ${r+1}, Columna ${c+1}`);
        tile.dataset.r = r;
        tile.dataset.c = c;
        tile.textContent = grid[r][c];
        tile.addEventListener('click', onTileClick);
        gridEl.appendChild(tile);
      }
    }
  }

  function onTileClick(e){
    if (busy) return;
    const btn = e.currentTarget;
    const r = parseInt(btn.dataset.r, 10);
    const c = parseInt(btn.dataset.c, 10);
    const pos = { r, c };

    if (!selected){
      selectTile(btn);
      selected = pos;
      return;
    }

    // si vuelve a seleccionar la misma, deseleccionar
    if (selected.r === r && selected.c === c){
      clearSelection();
      return;
    }

    // si no es adyacente, cambiar selección
    if (!isAdjacent(selected, pos)){
      clearSelection();
      selectTile(btn);
      selected = pos;
      return;
    }

    // intento de swap
    busy = true;
    swap(selected, pos);
    renderGridAfterSwap(selected, pos);

    // detectar matches
    const matched = findMatches();
    if (matched.size > 0){
      moves -= 1;
      updateHUD();
      processMatches(matched).then(() => {
        clearSelection();
        busy = false;
        checkEnd();
      });
    } else {
      // revertir swap
      setTimeout(() => {
        swap(selected, pos);
        // animación de "shake" en ambos
        markShake([selected, pos]);
        renderGrid();
        clearSelection();
        busy = false;
      }, 180);
    }
  }

  // Utilidades de UI
  function selectTile(btn){
    btn.classList.add('selected');
  }
  function clearSelection(){
    const prev = gridEl.querySelector('.tile.selected');
    if (prev) prev.classList.remove('selected');
    selected = null;
  }
  function markShake(positions){
    requestAnimationFrame(() => {
      positions.forEach(({r,c}) => {
        const tile = getTileEl(r,c);
        if (tile){
          tile.classList.add('shake');
          setTimeout(() => tile.classList.remove('shake'), 260);
        }
      });
    });
  }
  function renderGridAfterSwap(a, b){
    // actualizar solo dos celdas para feedback rápido
    const ta = getTileEl(a.r, a.c);
    const tb = getTileEl(b.r, b.c);
    if (ta) ta.textContent = grid[a.r][a.c];
    if (tb) tb.textContent = grid[b.r][b.c];
  }
  function getTileEl(r,c){
    return gridEl.querySelector(`.tile[data-r="${r}"][data-c="${c}"]`);
  }

  // Lógica Match-3
  function findMatches(){
    const matched = new Set();

    // filas
    for (let r = 0; r < ROWS; r++){
      let runType = null, runStart = 0, runLen = 0;
      for (let c = 0; c < COLS; c++){
        const t = grid[r][c];
        if (t && t === runType){
          runLen++;
        } else {
          if (runLen >= 3){
            for (let k = runStart; k < runStart + runLen; k++){
              matched.add(key(r,k));
            }
          }
          runType = t;
          runStart = c;
          runLen = 1;
        }
      }
      if (runLen >= 3){
        for (let k = runStart; k < runStart + runLen; k++){
          matched.add(key(r,k));
        }
      }
    }

    // columnas
    for (let c = 0; c < COLS; c++){
      let runType = null, runStart = 0, runLen = 0;
      for (let r = 0; r < ROWS; r++){
        const t = grid[r][c];
        if (t && t === runType){
          runLen++;
        } else {
          if (runLen >= 3){
            for (let k = runStart; k < runStart + runLen; k++){
              matched.add(key(k,c));
            }
          }
          runType = t;
          runStart = r;
          runLen = 1;
        }
      }
      if (runLen >= 3){
        for (let k = runStart; k < runStart + runLen; k++){
          matched.add(key(k,c));
        }
      }
    }

    return matched;
  }

  async function processMatches(matched){
    chain = 1; // swap inicial
    // animar matched
    markMatched(matched);
    await wait(220);

    // eliminar y puntuar
    const removedCount = removeMatched(matched);
    addScore(removedCount, chain);
    collapse();
    renderGrid();

    // cascadas
    while (true){
      const newMatches = findMatches();
      if (newMatches.size === 0) break;
      chain++;
      markMatched(newMatches);
      await wait(200);
      const removed = removeMatched(newMatches);
      addScore(removed, chain);
      collapse();
      renderGrid();
    }

    updateHUD();
  }

  function markMatched(matched){
    matched.forEach(k => {
      const [r,c] = unkey(k);
      const tile = getTileEl(r,c);
      if (tile) tile.classList.add('matched', 'block');
    });
  }

  function removeMatched(matched){
    let count = 0;
    matched.forEach(k => {
      const [r,c] = unkey(k);
      if (grid[r][c] !== null){
        grid[r][c] = null;
        count++;
      }
    });
    return count;
  }

  function collapse(){
    for (let c = 0; c < COLS; c++){
      let write = ROWS - 1;
      for (let r = ROWS - 1; r >= 0; r--){
        if (grid[r][c] !== null){
          grid[write][c] = grid[r][c];
          write--;
        }
      }
      for (let r = write; r >= 0; r--){
        // rellenar evitando crear match instantáneo arriba
        let t;
        do {
          t = randomType();
        } while (createsStreak(r, c, t));
        grid[r][c] = t;
      }
    }
  }

  // Puntuación y HUD
  function addScore(tilesRemoved, currentChain){
    // bonificación por cadena/cascada
    const chainBonus = Math.max(1, currentChain);
    const gained = tilesRemoved * POINTS_PER_TILE * chainBonus;
    score += gained;
  }

  function updateHUD(){
    puntajeEl.textContent = score.toString();
    movsEl.textContent = moves.toString();
    const health = Math.min(100, Math.round((score / TARGET_SCORE) * 100));
    saludFillEl.style.width = `${health}%`;
    if (health < 40){
      estadoEl.textContent = 'La naturaleza necesita tu ayuda. ¡Sigue combinando! 🌱';
    } else if (health < 80){
      estadoEl.textContent = '¡El ecosistema se está recuperando! 💚';
    } else if (health < 100){
      estadoEl.textContent = '¡Casi lo logras! Un último esfuerzo. ✨';
    } else {
      estadoEl.textContent = 'Ecosistema restaurado. ¡Excelente! 🏆';
    }
  }

  function checkEnd(){
    const healthFull = (score >= TARGET_SCORE);
    if (healthFull){
      showWin();
      return;
    }
    if (moves <= 0){
      if (score >= TARGET_SCORE){
        showWin();
      } else {
        showLose();
      }
    }
  }

  function showWin(){
    modalTitulo.textContent = '¡Ecosistema restaurado! 🌎';
    modalMensaje.textContent = `Puntaje: ${score}. Excelente trabajo. Se guardará tu puntaje.`;
    showModal();
    saveScore(score);
  }

  function showLose(){
    modalTitulo.textContent = 'Ecosistema en riesgo 😔';
    modalMensaje.textContent = `Puntaje: ${score}. Puedes intentarlo de nuevo.`;
    showModal();
  }

  // Guardado
  function saveScore(puntos){
    fetch('guardar_ecopuzzle.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ puntos })
    })
    .then(r => r.json().catch(() => ({ ok:false })))
    .then(data => {
      if (!data || !data.ok){
        console.warn('No se pudo confirmar el guardado.');
      }
    })
    .catch(() => console.warn('Error de red al guardar.'));
  }

  // Helpers
  function isAdjacent(a, b){
    const dr = Math.abs(a.r - b.r);
    const dc = Math.abs(a.c - b.c);
    return (dr + dc === 1);
  }

  function swap(a, b){
    const t = grid[a.r][a.c];
    grid[a.r][a.c] = grid[b.r][b.c];
    grid[b.r][b.c] = t;
  }

  function randomType(){
    return TYPES[Math.floor(Math.random() * TYPES.length)];
  }

  function createsStreak(r, c, type){
    // evita crear 3 seguidos en la inicialización/caída (checa izquierda y arriba)
    const left1 = c > 0 ? grid[r][c-1] : null;
    const left2 = c > 1 ? grid[r][c-2] : null;
    if (left1 === type && left2 === type) return true;

    const up1 = r > 0 ? grid[r-1][c] : null;
    const up2 = r > 1 ? grid[r-2][c] : null;
    if (up1 === type && up2 === type) return true;

    return false;
  }

  function key(r,c){ return `${r},${c}`; }
  function unkey(k){ return k.split(',').map(Number); }
  function wait(ms){ return new Promise(res => setTimeout(res, ms)); }

  // Bloqueo de interacción durante animaciones críticas
  // (marcamos/quitamos clase .block en tiles si hiciera falta en expansiones)
})();
