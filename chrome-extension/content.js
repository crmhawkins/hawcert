// Content script para inyectar credenciales automáticamente

(function() {
  'use strict';

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

  // Patrones de texto para botones de envío (usados en findSubmitButton)
  const SUBMIT_TEXT_PATTERNS = [
    'Iniciar', 'Login', 'Entrar', 'Sign in', 'Log in', 'Iniciar sesión', 'Acceder'
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
   * Verifica si hay credenciales para esta URL y las rellena
   */
  async function checkAndFill() {
    if (isFilling) return;

    try {
      // Verificar que la extensión esté disponible
      if (!chrome.runtime || !chrome.runtime.sendMessage) {
        console.debug('HawCert: Runtime no disponible');
        return;
      }

      const response = await new Promise((resolve, reject) => {
        chrome.runtime.sendMessage(
          {
            action: 'getCredentials',
            url: currentUrl,
          },
          (response) => {
            // Verificar errores de Chrome
            if (chrome.runtime.lastError) {
              // El service worker puede no estar activo, esto es normal
              if (chrome.runtime.lastError.message.includes('Receiving end does not exist') ||
                  chrome.runtime.lastError.message.includes('message port closed')) {
                reject(new Error('Service worker no disponible'));
                return;
              }
              reject(new Error(chrome.runtime.lastError.message));
              return;
            }
            resolve(response);
          }
        );
      });

      if (response && response.success && response.credentials) {
        await fillCredentials(response.credentials);
      }
    } catch (error) {
      // Silenciar errores comunes (service worker inactivo, no hay credenciales, etc.)
      if (error.message && (
        error.message.includes('Service worker no disponible') ||
        error.message.includes('Receiving end does not exist') ||
        error.message.includes('message port closed')
      )) {
        // El service worker se activará cuando sea necesario, reintentar después de un delay
        setTimeout(() => {
          checkAndFill();
        }, 1000);
        return;
      }
      console.debug('HawCert: No se encontraron credenciales para esta URL', error.message);
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
   * Busca el botón de envío del formulario
   */
  function findSubmitButton(form) {
    // Buscar por selectores CSS
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

    // Buscar por texto en botones
    const buttons = form.querySelectorAll('button, input[type="button"], [role="button"]');

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
   * Rellena los campos con las credenciales de forma invisible
   */
  async function fillCredentials(credential) {
    if (isFilling) return;
    isFilling = true;

    try {
      let usernameField, passwordField;

      // Si hay selectores específicos configurados, usarlos primero
      if (credential.username_field_selector) {
        usernameField = document.querySelector(credential.username_field_selector);
      }
      if (credential.password_field_selector) {
        passwordField = document.querySelector(credential.password_field_selector);
      }

      // Si no se encontraron con selectores específicos, buscar automáticamente
      if (!usernameField) {
        usernameField = findField(USERNAME_PATTERNS);
      }
      if (!passwordField) {
        passwordField = findField(PASSWORD_PATTERNS, usernameField ? [usernameField] : []);
      }

      if (!usernameField || !passwordField) {
        console.debug('HawCert: No se encontraron los campos de formulario');
        return;
      }

      // Obtener el formulario padre
      const form = usernameField.closest('form') || passwordField.closest('form');

      // Ocultar campos visualmente mientras se rellenan
      const originalUsernameDisplay = usernameField.style.display;
      const originalPasswordDisplay = passwordField.style.display;
      const originalUsernameOpacity = usernameField.style.opacity;
      const originalPasswordOpacity = passwordField.style.opacity;
      const originalUsernameVisibility = usernameField.style.visibility;
      const originalPasswordVisibility = passwordField.style.visibility;

      // Ocultar temporalmente
      usernameField.style.opacity = '0';
      usernameField.style.visibility = 'hidden';
      usernameField.style.position = 'absolute';
      usernameField.style.left = '-9999px';

      passwordField.style.opacity = '0';
      passwordField.style.visibility = 'hidden';
      passwordField.style.position = 'absolute';
      passwordField.style.left = '-9999px';

      // Rellenar campos
      usernameField.value = credential.username;
      passwordField.value = credential.password;

      // Marcar como rellenados
      filledFields.add(usernameField);
      filledFields.add(passwordField);

      // Disparar eventos para que los frameworks detecten el cambio
      const events = ['input', 'change', 'keyup', 'keydown', 'focus', 'blur'];
      events.forEach(eventType => {
        usernameField.dispatchEvent(new Event(eventType, { bubbles: true, cancelable: true }));
        passwordField.dispatchEvent(new Event(eventType, { bubbles: true, cancelable: true }));
      });

      // También disparar eventos nativos del navegador
      const inputEvent = new InputEvent('input', { bubbles: true, cancelable: true });
      usernameField.dispatchEvent(inputEvent);
      passwordField.dispatchEvent(inputEvent);

      // Restaurar visibilidad después de un breve delay
      setTimeout(() => {
        usernameField.style.opacity = originalUsernameOpacity || '';
        usernameField.style.visibility = originalUsernameVisibility || '';
        usernameField.style.position = '';
        usernameField.style.left = '';

        passwordField.style.opacity = originalPasswordOpacity || '';
        passwordField.style.visibility = originalPasswordVisibility || '';
        passwordField.style.position = '';
        passwordField.style.left = '';
      }, 100);

      // Enviar formulario automáticamente (siempre)
      if (form) {
        setTimeout(() => {
          // Buscar botón de envío
          let submitButton = null;

          // Si hay selector específico, usarlo primero
          if (credential.submit_button_selector) {
            submitButton = form.querySelector(credential.submit_button_selector);
          }

          // Si no, buscar automáticamente
          if (!submitButton) {
            submitButton = findSubmitButton(form);
          }

          if (submitButton) {
            // Simular clic real con eventos completos
            submitButton.focus();
            submitButton.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
            submitButton.dispatchEvent(new MouseEvent('mouseup', { bubbles: true, cancelable: true }));
            submitButton.click();
          } else {
            // Si no hay botón, intentar enviar el formulario directamente
            // Primero intentar disparar evento submit para que los listeners lo capturen
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            const submitted = form.dispatchEvent(submitEvent);

            // Si el evento no fue cancelado, enviar el formulario
            if (submitted) {
              try {
                form.submit();
              } catch (e) {
                // Si falla, intentar con Enter en el campo de contraseña
                passwordField.dispatchEvent(new KeyboardEvent('keydown', {
                  key: 'Enter',
                  code: 'Enter',
                  keyCode: 13,
                  bubbles: true,
                  cancelable: true
                }));
                passwordField.dispatchEvent(new KeyboardEvent('keyup', {
                  key: 'Enter',
                  code: 'Enter',
                  keyCode: 13,
                  bubbles: true,
                  cancelable: true
                }));
                passwordField.dispatchEvent(new KeyboardEvent('keypress', {
                  key: 'Enter',
                  code: 'Enter',
                  keyCode: 13,
                  bubbles: true,
                  cancelable: true
                }));
              }
            }
          }
        }, 300);
      } else {
        // Si no hay formulario, intentar encontrar botones de envío en la página
        setTimeout(() => {
          let submitButton = findSubmitButton(document);
          if (submitButton) {
            submitButton.focus();
            submitButton.click();
          }
        }, 300);
      }
    } catch (error) {
      console.error('HawCert: Error al rellenar credenciales', error);
    } finally {
      isFilling = false;
    }
  }


  // Escuchar mensajes del popup para rellenar manualmente
  chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'fillNow') {
      checkAndFill().then(() => {
        sendResponse({ success: true });
      }).catch(() => {
        sendResponse({ success: false });
      });
      return true; // Mantener el canal abierto para respuesta asíncrona
    }
  });
})();
