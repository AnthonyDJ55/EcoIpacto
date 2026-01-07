// Mostrar modal animado después de guardar y luego redirigir
function mostrarModalExito() {
  // Crea el modal si no existe
  let modal = document.getElementById('modal-exito');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'modal-exito';
    modal.innerHTML = `
      <div class="modal-contenido">
        <div class="check-animacion">
          <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="25" fill="none"/><path d="M14 27l7 7 15-15"/></svg>
        </div>
        <div class="mensaje">¡Puntos guardados exitosamente!</div>
      </div>
    `;
    document.body.appendChild(modal);
  }
  modal.style.display = 'flex';
  // Ocultar y redirigir después de 2 segundos
  setTimeout(() => {
    modal.style.display = 'none';
    window.location.href = "index.php";
  }, 2000);
}

document.addEventListener("DOMContentLoaded", function () {
  const finalizarBtn = document.getElementById('finalizarBtn');
  if (finalizarBtn) {
    finalizarBtn.addEventListener("click", function () {
      fetch('guardar_puntaje.php', {
        method: 'POST'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          mostrarModalExito();
        } else {
          alert("Error al guardar: " + (data.error || "Intenta de nuevo"));
        }
      })
      .catch(() => {
        alert("Error de conexión al guardar puntaje.");
      });
    });
  }
});