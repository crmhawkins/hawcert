// Options script para HawCert Auto-Fill

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('configForm');
  const apiUrlInput = document.getElementById('apiUrl');
  const certificateFileInput = document.getElementById('certificateFile');
  const certStatus = document.getElementById('certStatus');
  const clearCertBtn = document.getElementById('clearCert');
  const successDiv = document.getElementById('success');
  const errorDiv = document.getElementById('error');

  /** Certificado ya guardado en storage (no se muestra el contenido) */
  let savedCertificate = null;
  /** Contenido leído del último archivo seleccionado (sustituye al guardado al enviar) */
  let pendingCertificate = null;
  let pendingFileName = null;

  function setCertStatus(message, type) {
    certStatus.textContent = message;
    certStatus.className = 'cert-status' + (type ? ' ' + type : '');
  }

  function extractCertificatePem(text) {
    const t = String(text || '').trim();
    if (!t.includes('-----BEGIN CERTIFICATE-----')) {
      return null;
    }
    const start = t.indexOf('-----BEGIN CERTIFICATE-----');
    const end = t.indexOf('-----END CERTIFICATE-----');
    if (end === -1) {
      return null;
    }
    return t.slice(start, end + '-----END CERTIFICATE-----'.length).trim();
  }

  chrome.storage.local.get(['config'], (result) => {
    const config = result.config || {};
    if (config.apiUrl) {
      apiUrlInput.value = config.apiUrl;
    }
    savedCertificate = config.certificate && String(config.certificate).trim() ? config.certificate.trim() : null;
    if (savedCertificate) {
      setCertStatus('Hay un certificado guardado. Sube un archivo nuevo para reemplazarlo.', 'ok');
    } else {
      setCertStatus('No hay certificado guardado. Debes subir un archivo .pem para usar la extensión.', 'warn');
    }
  });

  certificateFileInput.addEventListener('change', () => {
    const file = certificateFileInput.files && certificateFileInput.files[0];
    if (!file) {
      pendingCertificate = null;
      pendingFileName = null;
      setCertStatus(
        savedCertificate ? 'Hay un certificado guardado. Sube un archivo nuevo para reemplazarlo.' : 'No hay certificado guardado.',
        savedCertificate ? 'ok' : 'warn'
      );
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      const raw = String(reader.result || '');
      const pem = extractCertificatePem(raw);
      if (!pem) {
        pendingCertificate = null;
        pendingFileName = null;
        showError('El archivo no contiene un bloque PEM de certificado (-----BEGIN CERTIFICATE----- … -----END CERTIFICATE-----).');
        certificateFileInput.value = '';
        return;
      }
      pendingCertificate = pem;
      pendingFileName = file.name;
      setCertStatus(`Archivo listo: ${file.name} (se guardará al pulsar «Guardar configuración»).`, 'ok');
      errorDiv.style.display = 'none';
    };
    reader.onerror = () => {
      showError('No se pudo leer el archivo.');
      pendingCertificate = null;
      pendingFileName = null;
    };
    reader.readAsText(file, 'UTF-8');
  });

  clearCertBtn.addEventListener('click', () => {
    savedCertificate = null;
    pendingCertificate = null;
    pendingFileName = null;
    certificateFileInput.value = '';
    chrome.storage.local.get(['config'], (result) => {
      const config = { ...(result.config || {}) };
      delete config.certificate;
      chrome.storage.local.set({ config }, () => {
        setCertStatus('Certificado eliminado. Sube un archivo .pem para volver a configurar.', 'warn');
        showSuccess();
      });
    });
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const apiUrl = apiUrlInput.value.trim();
    const certificate = (pendingCertificate || savedCertificate || '').trim();

    if (!certificate || !certificate.includes('-----BEGIN CERTIFICATE-----')) {
      showError('Selecciona un archivo .pem con tu certificado X.509 (o guarda uno que ya tuvieras subiendo de nuevo).');
      return;
    }

    let normalizedApiUrl = apiUrl;
    if (!normalizedApiUrl.endsWith('/api')) {
      if (normalizedApiUrl.endsWith('/')) {
        normalizedApiUrl += 'api';
      } else {
        normalizedApiUrl += '/api';
      }
    }

    chrome.storage.local.set(
      {
        config: {
          apiUrl: normalizedApiUrl,
          certificate: certificate,
        },
      },
      () => {
        if (chrome.runtime.lastError) {
          showError('Error al guardar: ' + chrome.runtime.lastError.message);
        } else {
          savedCertificate = certificate;
          pendingCertificate = null;
          pendingFileName = null;
          certificateFileInput.value = '';
          setCertStatus('Certificado guardado correctamente.', 'ok');
          showSuccess();
        }
      }
    );
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
