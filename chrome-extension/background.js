// Background service worker para HawCert Auto-Fill

// Configuración por defecto
const DEFAULT_CONFIG = {
  apiUrl: 'https://hawcert.hawkins.es/api',
  certificate: null,
};

// Inicializar configuración
chrome.runtime.onInstalled.addListener(() => {
  chrome.storage.local.get(['config'], (result) => {
    if (!result.config) {
      chrome.storage.local.set({ config: DEFAULT_CONFIG });
    }
  });
});

// Mantener el service worker activo
chrome.runtime.onConnect.addListener((port) => {
  // Mantener conexión activa
});

// Escuchar mensajes del content script
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  // Manejar mensajes de forma asíncrona
  if (request.action === 'getCredentials') {
    getCredentials(request.url, request.manual === true, request.credential_id)
      .then(data => {
        sendResponse({ success: true, data });
      })
      .catch(error => {
        sendResponse({ success: false, error: error.message });
      });
    return true; // Mantener el canal abierto para respuesta asíncrona
  }
  
  if (request.action === 'getConfig') {
    chrome.storage.local.get(['config'], (result) => {
      sendResponse({ config: result.config || DEFAULT_CONFIG });
    });
    return true;
  }

  if (request.action === 'showCertificateOnlyNotification') {
    showCertificateOnlyNotification(request.websiteName || 'Esta página');
    sendResponse({ ok: true });
    return true;
  }

  return false;
});

function showCertificateOnlyNotification(websiteName) {
  chrome.notifications.create({
    type: 'basic',
    title: 'HawCert – Solo certificado',
    message: `${websiteName} usa solo certificado. Si el navegador pide un certificado, elige tu certificado HawCert y marca "Recordar" para que se use automáticamente.`,
    priority: 1,
    requireInteraction: false,
  });
}

/**
 * Obtiene credenciales desde la API de HawCert.
 * @param {string} url - URL actual
 * @param {boolean} manual - true si el usuario pulsó "Rellenar ahora" (solo entonces el servidor registra el uso en logs)
 * @param {number|null} credentialId - credencial concreta a devolver (selección manual)
 */
async function getCredentials(url, manual = false, credentialId = null) {
  return new Promise((resolve, reject) => {
    chrome.storage.local.get(['config'], async (result) => {
      const config = result.config || DEFAULT_CONFIG;
      
      if (!config.certificate) {
        reject(new Error('No hay certificado configurado. Por favor, configura tu certificado en las opciones.'));
        return;
      }

      if (!config.apiUrl) {
        reject(new Error('No hay URL de API configurada. Por favor, configura la URL en las opciones.'));
        return;
      }

      try {
        const response = await fetch(`${config.apiUrl}/get-credentials`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            certificate: config.certificate,
            url: url,
            manual: !!manual,
            credential_id: typeof credentialId === 'number' ? credentialId : undefined,
          }),
        });

        const data = await response.json();

        if (!data.success) {
          reject(new Error(data.message || 'Error al obtener credenciales'));
          return;
        }

        resolve(data);
      } catch (error) {
        reject(new Error(`Error de red: ${error.message}`));
      }
    });
  });
}
