// Options script para HawCert Auto-Fill

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('configForm');
  const apiUrlInput = document.getElementById('apiUrl');
  const certificateInput = document.getElementById('certificate');
  const successDiv = document.getElementById('success');
  const errorDiv = document.getElementById('error');

  // Cargar configuración existente
  chrome.storage.local.get(['config'], (result) => {
    const config = result.config || {};
    if (config.apiUrl) {
      apiUrlInput.value = config.apiUrl;
    }
    if (config.certificate) {
      certificateInput.value = config.certificate;
    }
  });

  // Guardar configuración
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const apiUrl = apiUrlInput.value.trim();
    const certificate = certificateInput.value.trim();

    // Validar certificado básico
    if (!certificate.includes('-----BEGIN CERTIFICATE-----')) {
      showError('El certificado no parece ser un certificado PEM válido.');
      return;
    }

    // Normalizar URL de API
    let normalizedApiUrl = apiUrl;
    if (!normalizedApiUrl.endsWith('/api')) {
      if (normalizedApiUrl.endsWith('/')) {
        normalizedApiUrl += 'api';
      } else {
        normalizedApiUrl += '/api';
      }
    }

    // Guardar configuración
    chrome.storage.local.set({
      config: {
        apiUrl: normalizedApiUrl,
        certificate: certificate,
      }
    }, () => {
      if (chrome.runtime.lastError) {
        showError('Error al guardar: ' + chrome.runtime.lastError.message);
      } else {
        showSuccess();
      }
    });
  });

  function showSuccess() {
    successDiv.style.display = 'block';
    errorDiv.style.display = 'none';
    setTimeout(() => {
      successDiv.style.display = 'none';
    }, 3000);
  }

  function showError(message) {
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    successDiv.style.display = 'none';
  }
});
