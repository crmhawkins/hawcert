// Popup script para HawCert Auto-Fill

document.addEventListener('DOMContentLoaded', () => {
  const statusDiv = document.getElementById('status');
  const fillNowButton = document.getElementById('fillNow');
  const picker = document.getElementById('credentialPicker');
  const searchInput = document.getElementById('credentialSearch');
  const rememberWrap = document.getElementById('rememberWrap');
  const rememberChoice = document.getElementById('rememberChoice');

  let lastTab = null;
  let lastDomain = null;
  let lastList = null;

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

  function setPickerVisible(visible) {
    if (!picker || !searchInput || !rememberWrap) return;
    picker.classList.toggle('hidden', !visible);
    searchInput.classList.toggle('hidden', !visible);
    rememberWrap.classList.toggle('hidden', !visible);
  }

  function domainFromUrl(url) {
    try {
      const u = new URL(url);
      return u.hostname;
    } catch (e) {
      return null;
    }
  }

  function renderPicker(list) {
    lastList = Array.isArray(list) ? list : [];
    if (!picker) return;
    picker.innerHTML = '';

    if (!lastList.length) {
      const div = document.createElement('div');
      div.className = 'row';
      div.textContent = 'No hay opciones.';
      picker.appendChild(div);
      return;
    }

    lastList.forEach((c) => {
      const row = document.createElement('div');
      row.className = 'row';
      row.setAttribute('data-id', String(c.id));
      row.setAttribute('data-name', String((c.website_name || '')).toLowerCase());
      row.setAttribute('data-pattern', String((c.website_url_pattern || '')).toLowerCase());
      row.setAttribute('data-hint', String((c.username_hint || '')).toLowerCase());

      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.name = 'credentialPick';
      radio.value = String(c.id);

      const meta = document.createElement('div');
      meta.className = 'meta';

      const name = document.createElement('div');
      name.className = 'name';
      const hint = c.username_hint ? ` (${c.username_hint})` : '';
      name.textContent = `${c.website_name || 'Credencial'}${hint}`;

      const sub = document.createElement('div');
      sub.className = 'sub';
      const notes = c.notes ? ` · ${c.notes}` : '';
      sub.textContent = `${c.website_url_pattern || ''}${notes}`;

      meta.appendChild(name);
      meta.appendChild(sub);

      row.appendChild(radio);
      row.appendChild(meta);

      row.addEventListener('click', () => {
        radio.checked = true;
        applySelected();
      });

      picker.appendChild(row);
    });
  }

  function filterPicker(q) {
    const query = String(q || '').trim().toLowerCase();
    const rows = picker ? picker.querySelectorAll('.row') : [];
    rows.forEach((row) => {
      const name = row.getAttribute('data-name') || '';
      const pattern = row.getAttribute('data-pattern') || '';
      const hint = row.getAttribute('data-hint') || '';
      const show = !query || name.includes(query) || pattern.includes(query) || hint.includes(query);
      row.style.display = show ? '' : 'none';
    });
  }

  async function applySelected() {
    if (!picker || !lastTab || !lastDomain) return;
    const checked = picker.querySelector('input[name="credentialPick"]:checked');
    if (!checked) return;

    const credentialId = Number(checked.value);
    if (!Number.isFinite(credentialId) || credentialId <= 0) return;

    if (rememberChoice && rememberChoice.checked) {
      chrome.storage.local.get(['rememberedCredentialByHost'], (result) => {
        const map = result.rememberedCredentialByHost || {};
        map[lastDomain] = credentialId;
        chrome.storage.local.set({ rememberedCredentialByHost: map });
      });
    }

    chrome.tabs.sendMessage(lastTab.id, { action: 'fillWithCredentialId', credential_id: credentialId }, (response) => {
      if (chrome.runtime.lastError) {
        statusDiv.className = 'status error';
        statusDiv.textContent = 'Error: ' + chrome.runtime.lastError.message;
        return;
      }
      if (response && response.success) {
        statusDiv.className = 'status success';
        statusDiv.textContent = 'Credenciales rellenadas exitosamente.';
      } else {
        statusDiv.className = 'status error';
        statusDiv.textContent = (response && response.error) ? response.error : 'No se pudo rellenar con la credencial seleccionada.';
      }
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', (e) => filterPicker(e.target.value));
  }

  // Botón para rellenar manualmente
  fillNowButton.addEventListener('click', () => {
    chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
      if (!tabs[0]) {
        statusDiv.className = 'status error';
        statusDiv.textContent = 'No se pudo acceder a la pestaña actual.';
        return;
      }

      lastTab = tabs[0];
      lastDomain = domainFromUrl(tabs[0].url);
      setPickerVisible(false);

      // 1) Si hay credencial recordada para este host, intentar usarla directamente
      chrome.storage.local.get(['rememberedCredentialByHost'], (result) => {
        const map = result.rememberedCredentialByHost || {};
        const rememberedId = lastDomain ? map[lastDomain] : null;

        if (rememberedId) {
          chrome.tabs.sendMessage(lastTab.id, { action: 'fillWithCredentialId', credential_id: rememberedId }, (response) => {
            if (chrome.runtime.lastError) {
              statusDiv.className = 'status info';
              statusDiv.textContent = 'Recarga la página e intenta de nuevo.';
              return;
            }
            if (response && response.success) {
              statusDiv.className = 'status success';
              statusDiv.textContent = 'Credenciales rellenadas exitosamente.';
              return;
            }
            // Si falla, caemos al selector normal
            requestAndPick();
          });
          return;
        }

        requestAndPick();
      });

      function requestAndPick() {
        chrome.runtime.sendMessage({ action: 'getCredentials', url: lastTab.url, manual: true }, (res) => {
          if (chrome.runtime.lastError) {
            statusDiv.className = 'status error';
            statusDiv.textContent = 'Error: ' + chrome.runtime.lastError.message;
            return;
          }
          if (!res || !res.success || !res.data) {
            statusDiv.className = 'status error';
            statusDiv.textContent = (res && res.error) ? res.error : 'Error al obtener credenciales.';
            return;
          }

          if (res.data.mode === 'multiple') {
            statusDiv.className = 'status info';
            statusDiv.textContent = 'Elige qué cuenta quieres usar en esta web.';
            renderPicker(res.data.credentials || []);
            if (rememberChoice) rememberChoice.checked = false;
            if (searchInput) searchInput.value = '';
            setPickerVisible(true);
            return;
          }

          if (res.data.mode === 'single' && res.data.credential) {
            // Usar la credencial única directamente
            chrome.tabs.sendMessage(lastTab.id, { action: 'fillWithCredentialId', credential_id: res.data.credential.id }, (response) => {
              if (chrome.runtime.lastError) {
                statusDiv.className = 'status error';
                statusDiv.textContent = 'Error: ' + chrome.runtime.lastError.message;
                return;
              }
              if (response && response.success) {
                statusDiv.className = 'status success';
                statusDiv.textContent = 'Credenciales rellenadas exitosamente.';
              } else {
                statusDiv.className = 'status info';
                statusDiv.textContent = 'No se encontraron credenciales para esta página.';
              }
            });
            return;
          }

          statusDiv.className = 'status info';
          statusDiv.textContent = 'No se encontraron credenciales para esta página.';
        });
      }
    });
  });
});
