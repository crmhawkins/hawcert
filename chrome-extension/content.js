// Content script para inyectar credenciales automáticamente

(function() {
  'use strict';

  const DEBUG = true; // Cambiar a false para reducir logs en consola (F12 > Console)

  function log(...args) {
    if (DEBUG) {
      console.log('[HawCert]', ...args);
    }
  }

  function logError(...args) {
    console.error('[HawCert]', ...args);
  }

  let isFilling = false;
  let currentUrl = window.location.href;
  let filledFields = new Set(); // Para evitar rellenar múltiples veces

  // Patrones comunes para detectar campos de usuario/email
  const USERNAME_PATTERNS = [
    // Por atributo name
    'input[name*="user" i]',
    'input[name*="email" i]',
    'input[name*="mail" i]',
    'input[name*="login" i]',
    'input[name*="account" i]',
    // Por atributo id
    'input[id*="user" i]',
    'input[id*="email" i]',
    'input[id*="mail" i]',
    'input[id*="login" i]',
    'input[id*="account" i]',
    // Por tipo
    'input[type="email"]',
    'input[type="text"][placeholder*="email" i]',
    'input[type="text"][placeholder*="user" i]',
    'input[type="text"][placeholder*="mail" i]',
    // Por clase
    'input.email',
    'input.username',
    'input.user',
  ];

  // Patrones comunes para detectar campos de contraseña
  const PASSWORD_PATTERNS = [
    'input[type="password"]',
    'input[name*="pass" i]',
    'input[id*="pass" i]',
    'input.password',
    'input.passwd',
  ];

  // Patrones de texto para botones de envío / login
  const SUBMIT_TEXT_PATTERNS = [
    'Iniciar', 'Login', 'Entrar', 'Sign in', 'Log in', 'Iniciar sesión', 'Acceder'
  ];

  // Patrones para botón "Siguiente" (cuando usuario y contraseña están en páginas separadas)
  const NEXT_BUTTON_PATTERNS = [
    'Siguiente', 'Next', 'Continuar', 'Continue', 'Weiter', 'Suivant', 'Avanti',
    'Siguiente paso', 'Next step', 'Continuar con', 'Continue with'
  ];

  // Detectar cuando la página cambia (SPA)
  let lastUrl = location.href;
  new MutationObserver(() => {
    const url = location.href;
    if (url !== lastUrl) {
      lastUrl = url;
      currentUrl = url;
      filledFields.clear(); // Limpiar campos rellenados al cambiar de página
      // Esperar un poco para que la página cargue
      setTimeout(checkAndFill, 1500);
    }
  }).observe(document, { subtree: true, childList: true });

  // Verificar y rellenar cuando la página carga
  // Dar tiempo al service worker para activarse
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      setTimeout(checkAndFill, 2000);
    });
  } else {
    setTimeout(checkAndFill, 2000);
  }

  // Observar cuando aparecen nuevos formularios
  const formObserver = new MutationObserver(() => {
    if (!isFilling) {
      setTimeout(checkAndFill, 500);
    }
  });

  formObserver.observe(document.body, {
    childList: true,
    subtree: true,
  });

  /**
   * Verifica si hay credenciales para esta URL y las rellena.
   * @param {boolean} manual - true si el usuario pulsó "Rellenar ahora" en la extensión (solo entonces se registra en logs del servidor)
   */
  async function checkAndFill(manual = false) {
    if (isFilling) return false;

    log('checkAndFill iniciado', { url: currentUrl, manual });

    try {
      if (!chrome.runtime || !chrome.runtime.sendMessage) {
        log('Runtime no disponible');
        return false;
      }

      const response = await new Promise((resolve, reject) => {
        chrome.runtime.sendMessage(
          { action: 'getCredentials', url: currentUrl, manual: !!manual },
          (response) => {
            if (chrome.runtime.lastError) {
              const msg = chrome.runtime.lastError.message;
              if (msg.includes('Receiving end does not exist') || msg.includes('message port closed')) {
                reject(new Error('Service worker no disponible'));
                return;
              }
              reject(new Error(msg));
              return;
            }
            resolve(response);
          }
        );
      });

      if (!response) {
        log('Respuesta vacía del backend');
        return false;
      }
      if (!response.success) {
        log('Backend devolvió success=false', response.message || response);
        return false;
      }
      if (!response.data) {
        log('No hay data en la respuesta', response);
        return false;
      }

      // Nuevo formato: data.mode = single|multiple
      if (response.data.mode === 'multiple') {
        log('Hay múltiples credenciales para esta URL; se requiere selección manual en el popup.', {
          count: (response.data.credentials || []).length,
        });
        return false;
      }

      const cred = response.data.credential || response.data.credentials;
      if (!cred) {
        log('No hay credencial en la respuesta', response);
        return false;
      }

      log('Credenciales recibidas para', cred.website_name);
      return await fillCredentials(cred);
    } catch (error) {
      if (error.message && (
        error.message.includes('Service worker no disponible') ||
        error.message.includes('Receiving end does not exist') ||
        error.message.includes('message port closed')
      )) {
        setTimeout(() => checkAndFill(), 1000);
        return false;
      }
      logError('Error en checkAndFill', error.message, error);
      return false;
    }
  }

  /**
   * Busca un campo usando múltiples patrones
   */
  function findField(patterns, excludeFields = []) {
    for (const pattern of patterns) {
      try {
        const fields = Array.from(document.querySelectorAll(pattern));
        for (const field of fields) {
          // Verificar que el campo sea visible y no esté deshabilitado
          if (field.offsetParent !== null &&
              !field.disabled &&
              !field.readOnly &&
              !excludeFields.includes(field) &&
              !filledFields.has(field)) {
            return field;
          }
        }
      } catch (e) {
        // Ignorar selectores inválidos
        continue;
      }
    }
    return null;
  }

  /**
   * Busca el botón de envío / login del formulario
   */
  function findSubmitButton(form) {
    const cssSelectors = [
      'button[type="submit"]',
      'input[type="submit"]',
      '[role="button"][aria-label*="login" i]',
      '[role="button"][aria-label*="sign in" i]',
      '[role="button"][aria-label*="iniciar" i]',
      '[role="button"][aria-label*="entrar" i]',
    ];

    for (const pattern of cssSelectors) {
      try {
        const button = form.querySelector(pattern);
        if (button && button.offsetParent !== null && !button.disabled) {
          return button;
        }
      } catch (e) {
        continue;
      }
    }

    const buttons = form.querySelectorAll('button, input[type="button"], input[type="submit"], [role="button"]');
    for (const button of buttons) {
      if (button.offsetParent === null || button.disabled) continue;
      const text = (button.textContent || button.value || button.getAttribute('aria-label') || '').toLowerCase();
      for (const pattern of SUBMIT_TEXT_PATTERNS) {
        if (text.includes(pattern.toLowerCase())) {
          return button;
        }
      }
    }
    return null;
  }

  /**
   * Busca el botón "Siguiente" / "Next" (para flujo usuario → siguiente → página de contraseña)
   */
  function findNextButton(form) {
    const buttons = form.querySelectorAll('button, input[type="button"], input[type="submit"], [role="button"], a[role="button"], a.btn');
    for (const button of buttons) {
      if (button.offsetParent === null || button.disabled) continue;
      const text = (button.textContent || button.value || button.getAttribute('aria-label') || '').trim().toLowerCase();
      for (const pattern of NEXT_BUTTON_PATTERNS) {
        if (text.includes(pattern.toLowerCase())) {
          return button;
        }
      }
    }
    return null;
  }

  /**
   * Rellena un campo y dispara eventos para que el sitio detecte el cambio
   */
  function fillFieldAndTrigger(field, value, label) {
    if (!field || value == null) return;
    field.focus();
    field.value = value;
    filledFields.add(field);
    const events = ['input', 'change', 'keyup', 'keydown', 'focus', 'blur'];
    events.forEach(eventType => {
      field.dispatchEvent(new Event(eventType, { bubbles: true, cancelable: true }));
    });
    field.dispatchEvent(new InputEvent('input', { bubbles: true, cancelable: true }));
    log(label, 'rellenado, valor length=', value.length);
  }

  /**
   * Rellena los campos con las credenciales. Soporta:
   * - Solo certificado (sin formulario) → mostrar notificación para elegir certificado cuando el navegador lo pida
   * - Usuario + contraseña en la misma página → rellenar ambos y enviar
   * - Solo usuario (página de "siguiente") → rellenar usuario y pulsar Siguiente
   * - Solo contraseña (segunda página) → rellenar contraseña y enviar
   * @returns {Promise<boolean>} true si se rellenó al menos un campo o se mostró aviso de solo certificado
   */
  async function fillCredentials(credential) {
    if (isFilling) return false;
    isFilling = true;

    try {
      if (credential.certificate_only) {
        log('Página con autenticación solo por certificado:', credential.website_name);
        if (chrome.runtime?.sendMessage) {
          chrome.runtime.sendMessage({
            action: 'showCertificateOnlyNotification',
            websiteName: credential.website_name || 'Esta página',
          });
        }
        return true;
      }

      let usernameField = null;
      let passwordField = null;

      if (credential.username_field_selector) {
        usernameField = document.querySelector(credential.username_field_selector);
        if (usernameField) log('Campo usuario encontrado por selector', credential.username_field_selector);
      }
      if (!usernameField) {
        usernameField = findField(USERNAME_PATTERNS);
        if (usernameField) log('Campo usuario encontrado por patrones automáticos');
      }

      if (credential.password_field_selector) {
        passwordField = document.querySelector(credential.password_field_selector);
        if (passwordField) log('Campo contraseña encontrado por selector', credential.password_field_selector);
      }
      if (!passwordField) {
        passwordField = findField(PASSWORD_PATTERNS, usernameField ? [usernameField] : []);
        if (passwordField) log('Campo contraseña encontrado por patrones automáticos');
      }

      const hasUser = !!usernameField;
      const hasPass = !!passwordField;

      if (!hasUser && !hasPass) {
        log('No se encontró ni campo usuario ni contraseña en esta página');
        return false;
      }

      const form = (usernameField || passwordField).closest('form') || document.querySelector('form');

      // ——— Flujo: solo usuario (página pide usuario y luego "Siguiente") ———
      if (hasUser && !hasPass) {
        log('Solo campo usuario: rellenando usuario y buscando botón Siguiente');
        fillFieldAndTrigger(usernameField, credential.username, 'Usuario');
        const nextBtn = form ? findNextButton(form) : findNextButton(document.body);
        if (nextBtn) {
          log('Pulsando botón Siguiente:', nextBtn.textContent?.trim() || nextBtn.value);
          nextBtn.focus();
          nextBtn.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
          nextBtn.dispatchEvent(new MouseEvent('mouseup', { bubbles: true, cancelable: true }));
          nextBtn.click();
        } else {
          log('No se encontró botón Siguiente/Next; intentando submit del formulario');
          const submitBtn = form ? findSubmitButton(form) : findSubmitButton(document.body);
          if (submitBtn) {
            submitBtn.focus();
            submitBtn.click();
          } else if (form) {
            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
          }
        }
        return true;
      }

      // ——— Flujo: solo contraseña (segunda página tras haber puesto usuario) ———
      if (!hasUser && hasPass) {
        log('Solo campo contraseña: rellenando contraseña y enviando');
        fillFieldAndTrigger(passwordField, credential.password, 'Contraseña');
        clickSubmitButton(form, credential, passwordField);
        return true;
      }

      // ——— Flujo: usuario y contraseña en la misma página ———
      log('Usuario y contraseña en la misma página: rellenando ambos');
      fillFieldAndTrigger(usernameField, credential.username, 'Usuario');
      fillFieldAndTrigger(passwordField, credential.password, 'Contraseña');

      clickSubmitButton(form, credential, passwordField);
      return true;
    } catch (error) {
      logError('Error al rellenar credenciales', error);
      return false;
    } finally {
      isFilling = false;
    }
  }

  /**
   * Busca y pulsa el botón de envío, o envía el formulario por Enter/submit()
   */
  function clickSubmitButton(form, credential, passwordField) {
    const doClick = (btn, desc) => {
      if (!btn) return false;
      log('Pulsando botón de envío:', desc, btn.textContent?.trim() || btn.value);
      btn.focus();
      btn.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
      btn.dispatchEvent(new MouseEvent('mouseup', { bubbles: true, cancelable: true }));
      btn.click();
      return true;
    };

    setTimeout(() => {
      let submitButton = null;
      const root = form || document;

      if (credential.submit_button_selector) {
        submitButton = root.querySelector(credential.submit_button_selector);
        if (submitButton) {
          doClick(submitButton, 'selector configurado');
          return;
        }
      }

      submitButton = form ? findSubmitButton(form) : findSubmitButton(document);
      if (doClick(submitButton, 'detección automática')) return;

      if (form) {
        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
        const submitted = form.dispatchEvent(submitEvent);
        if (submitted) {
          try {
            form.submit();
            log('Formulario enviado con form.submit()');
          } catch (e) {
            log('form.submit() falló, simulando Enter en contraseña');
            if (passwordField) {
              ['keydown', 'keyup', 'keypress'].forEach(type => {
                passwordField.dispatchEvent(new KeyboardEvent(type, {
                  key: 'Enter', code: 'Enter', keyCode: 13, bubbles: true, cancelable: true
                }));
              });
            }
          }
        } else {
          log('El formulario canceló el evento submit');
        }
      } else {
        const anySubmit = findSubmitButton(document);
        if (anySubmit) doClick(anySubmit, 'en documento');
        else log('No se encontró formulario ni botón de envío');
      }
    }, 300);
  }


  // Escuchar mensajes del popup para rellenar manualmente (clic en "Rellenar ahora")
  chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'fillNow') {
      checkAndFill(true)
        .then((filled) => {
          sendResponse({ success: filled === true });
        })
        .catch((err) => {
          logError('fillNow error', err);
          sendResponse({ success: false });
        });
      return true; // Mantener el canal abierto para respuesta asíncrona
    }

    if (request.action === 'fillWithCredentialId') {
      const credentialId = Number(request.credential_id);
      if (!Number.isFinite(credentialId) || credentialId <= 0) {
        sendResponse({ success: false, error: 'credential_id inválido' });
        return false;
      }
      (async () => {
        try {
          const response = await new Promise((resolve, reject) => {
            chrome.runtime.sendMessage(
              { action: 'getCredentials', url: currentUrl, manual: true, credential_id: credentialId },
              (response) => {
                if (chrome.runtime.lastError) {
                  reject(new Error(chrome.runtime.lastError.message));
                  return;
                }
                resolve(response);
              }
            );
          });

          if (!response || !response.success || !response.data || response.data.mode !== 'single' || !response.data.credential) {
            sendResponse({ success: false, error: (response && response.error) ? response.error : 'No se pudo obtener la credencial seleccionada' });
            return;
          }

          const filled = await fillCredentials(response.data.credential);
          sendResponse({ success: filled === true });
        } catch (err) {
          logError('fillWithCredentialId error', err);
          sendResponse({ success: false, error: err && err.message ? err.message : String(err) });
        }
      })();
      return true;
    }
  });
})();
