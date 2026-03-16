// Popup script para HawCert Auto-Fill

document.addEventListener('DOMContentLoaded', () => {
  const statusDiv = document.getElementById('status');
  const fillNowButton = document.getElementById('fillNow');

  // Verificar estado de configuración
  chrome.storage.local.get(['config'], (result) => {
    const config = result.config || {};
    
    if (!config.certificate) {
      statusDiv.className = 'status error';
      statusDiv.textContent = 'No hay certificado configurado. Configura tu certificado en las opciones.';
      fillNowButton.disabled = true;
    } else if (!config.apiUrl) {
      statusDiv.className = 'status error';
      statusDiv.textContent = 'No hay URL de API configurada. Configura la URL en las opciones.';
      fillNowButton.disabled = true;
    } else {
      statusDiv.className = 'status success';
      statusDiv.textContent = 'HawCert está activo y listo para rellenar credenciales.';
      fillNowButton.disabled = false;
    }
  });

  // Botón para rellenar manualmente
  fillNowButton.addEventListener('click', () => {
    chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
      if (!tabs[0]) {
        statusDiv.className = 'status error';
        statusDiv.textContent = 'No se pudo acceder a la pestaña actual.';
        return;
      }

      chrome.tabs.sendMessage(tabs[0].id, { action: 'fillNow' }, (response) => {
        if (chrome.runtime.lastError) {
          const errorMsg = chrome.runtime.lastError.message;
          // Ignorar errores comunes de service worker
          if (errorMsg.includes('Receiving end does not exist') || 
              errorMsg.includes('message port closed')) {
            statusDiv.className = 'status info';
            statusDiv.textContent = 'Recarga la página e intenta de nuevo.';
          } else {
            statusDiv.className = 'status error';
            statusDiv.textContent = 'Error: ' + errorMsg;
          }
        } else if (response && response.success) {
          statusDiv.className = 'status success';
          statusDiv.textContent = 'Credenciales rellenadas exitosamente.';
        } else {
          statusDiv.className = 'status info';
          statusDiv.textContent = 'No se encontraron credenciales para esta página.';
        }
      });
    });
  });
});
