# 🔄 Flujo de Funcionamiento - HawCert Auto-Fill

## 📋 Resumen del Flujo Completo

### 1️⃣ Usuario visita un sitio web (ej: IONOS)
- El usuario navega a `https://www.ionos.com/login`
- La extensión detecta automáticamente la URL

### 2️⃣ Extensión envía solicitud al servidor
**Archivo:** `background.js` (líneas 67-76)

```javascript
POST https://hawcert.hawkins.es/api/get-credentials
{
  "certificate": "-----BEGIN CERTIFICATE-----\n...certificado completo...\n-----END CERTIFICATE-----",
  "url": "https://www.ionos.com/login"
}
```

### 3️⃣ Servidor valida y busca credenciales
**Archivo:** `app/Http/Controllers/Api/CredentialApiController.php`

- ✅ Valida que el certificado sea válido
- ✅ Verifica que el certificado no haya expirado
- ✅ Busca credenciales para esa URL y certificado/usuario
- ✅ Compara el patrón de URL (ej: `*ionos.com*`)

### 4️⃣ Servidor devuelve credenciales
**Archivo:** `app/Http/Controllers/Api/CredentialApiController.php` (líneas 92-105)

```json
{
  "success": true,
  "credential": {
    "username": "usuario@ejemplo.com",  // Ya desencriptado
    "password": "contraseña123",         // Ya desencriptado
    "username_field_selector": "#email",
    "password_field_selector": "#password",
    "submit_button_selector": "button[type='submit']",
    "auto_fill": true,
    "auto_submit": true
  }
}
```

### 5️⃣ Extensión rellena campos automáticamente
**Archivo:** `content.js`

- ✅ Busca campos usando selectores CSS o detección automática
- ✅ Rellena usuario y contraseña (de forma invisible)
- ✅ Envía el formulario automáticamente
- ✅ Usuario queda autenticado sin ver las credenciales

---

## 🔐 Seguridad Actual

### ✅ Protecciones Implementadas:

1. **Encriptación en Base de Datos:**
   - Las credenciales se almacenan encriptadas usando Laravel Crypt (AES-256-CBC)
   - Archivo: `app/Models/Credential.php` (líneas 81-92)

2. **Validación de Certificado:**
   - Solo certificados válidos y no expirados pueden obtener credenciales
   - Se verifica que el certificado exista en la base de datos

3. **HTTPS:**
   - Todas las comunicaciones son por HTTPS
   - Las credenciales viajan encriptadas en tránsito

4. **Asociación Usuario/Certificado:**
   - Las credenciales están asociadas a usuarios o certificados específicos
   - Solo se devuelven si el certificado tiene acceso

---

## 📊 Panel de Configuración

### Crear Credenciales en el Panel:

1. Ve a `https://hawcert.hawkins.es`
2. Inicia sesión
3. Ve a **Credenciales** → **Nueva Credencial**
4. Configura:
   - **Sitio Web**: "IONOS"
   - **Patrón de URL**: `*ionos.com*` o `https://www.ionos.com/*`
   - **Usuario**: Tu usuario de IONOS
   - **Contraseña**: Tu contraseña de IONOS
   - **Selectores CSS** (opcional):
     - Campo usuario: `#email` o `input[name="email"]`
     - Campo contraseña: `#password` o `input[type="password"]`
     - Botón envío: `button[type="submit"]`
   - **Asociar a**: Usuario o Certificado específico

5. Guardar

---

## 🔄 Flujo Detallado Técnico

```
[Usuario] → [Chrome] → [Extensión Content Script]
                              ↓
                    [Background Service Worker]
                              ↓
                    [POST /api/get-credentials]
                              ↓
                    [Servidor HawCert]
                              ↓
              [Valida Certificado X.509]
                              ↓
          [Busca Credenciales en BD]
                              ↓
    [Desencripta Credenciales (Laravel Crypt)]
                              ↓
              [Devuelve JSON con credenciales]
                              ↓
                    [Background Service Worker]
                              ↓
                    [Content Script]
                              ↓
              [Rellena campos DOM]
                              ↓
              [Envía formulario]
                              ↓
            [Usuario autenticado]
```

---

## 📝 Notas Importantes

1. **Las credenciales se almacenan encriptadas** en la base de datos usando Laravel Crypt
2. **El servidor las desencripta** antes de enviarlas (porque Laravel Crypt usa una clave de servidor)
3. **Las credenciales viajan por HTTPS** (encriptadas en tránsito)
4. **Solo certificados válidos** pueden obtener credenciales
5. **La extensión no almacena credenciales** localmente, siempre las obtiene del servidor

---

## 🚀 Mejoras Futuras Posibles

Si quieres mayor seguridad, podríamos implementar:

1. **Encriptación adicional usando el certificado:**
   - El servidor encripta las credenciales con la clave pública del certificado
   - La extensión desencripta con la clave privada del certificado
   - Requiere que la extensión tenga acceso a la clave privada

2. **Tokens temporales:**
   - El servidor genera tokens de un solo uso
   - La extensión intercambia tokens por credenciales
   - Los tokens expiran rápidamente

¿Quieres que implemente alguna de estas mejoras de seguridad?
