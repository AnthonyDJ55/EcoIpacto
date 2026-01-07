document.addEventListener("DOMContentLoaded", () => {
  const puntosSpan = document.getElementById("puntos");
  const historialLista = document.getElementById("lista-historial");
  let areaSeleccionada = null;

  document.querySelectorAll(".area-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".area-btn").forEach(b => b.classList.remove("selected"));
      btn.classList.add("selected");
      areaSeleccionada = btn.dataset.area;
    });
  });

  document.querySelectorAll(".semilla-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      if (!areaSeleccionada) {
        alert("Selecciona primero un área degradada.");
        return;
      }

      const puntosGanados = parseInt(btn.dataset.puntos);
      const nombre = btn.dataset.nombre;
      puntos += puntosGanados;
      puntosSpan.textContent = puntos;

      const registro = `${nombre} en ${areaSeleccionada} (+${puntosGanados})`;
      historial.push(registro);

      const li = document.createElement("li");
      li.textContent = registro;
      historialLista.appendChild(li);
    });
  });

  document.getElementById("btnGuardarPuntos").addEventListener("click", (e) => {
    e.preventDefault();
    guardarProgreso(true);
  });

  document.getElementById("btnFinalizarMision").addEventListener("click", () => {
    guardarProgreso(false); // guarda antes de redirigir
  });
});

function guardarProgreso(mostrarMensaje = false) {
  fetch("guardar_puntos_reforestacion.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id: userId, puntos: puntos, historial: historial })
  })
  .then(res => res.json())
  .then(data => {
    if (!data.success) {
      console.error("Error al guardar:", data.error);
    } else if (mostrarMensaje) {
      alert("✅ Puntos guardados correctamente");
    }
  });
}
