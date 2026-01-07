document.addEventListener("DOMContentLoaded", () => {
  const barra = document.getElementById("barraAgua");
  const aguaValor = document.getElementById("aguaValor");

  document.querySelectorAll(".opcion-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const impacto = parseInt(btn.dataset.impacto);
      const decision = btn.dataset.decision;

      agua += impacto;
      if (agua < 0) agua = 0;
      if (agua > 100) agua = 100;

      barra.value = agua;
      aguaValor.textContent = agua;

      decisiones.push(decision);
      btn.disabled = true;
    });
  });

  document.getElementById("btnGuardarAgua").addEventListener("click", (e) => {
    e.preventDefault();
    guardarProgreso(true);
  });

  document.getElementById("btnFinalizar").addEventListener("click", () => {
    guardarProgreso(false);
  });
});

function guardarProgreso(mostrarMensaje = false) {
  fetch("guardar_agua.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id: userId, agua_ahorrada: agua, decisiones: decisiones })
  })
  .then(res => res.json())
  .then(data => {
    if (!data.success) {
      console.error("Error al guardar:", data.error);
    } else if (mostrarMensaje) {
      alert("✅ Progreso guardado correctamente");
    }
  });
}
