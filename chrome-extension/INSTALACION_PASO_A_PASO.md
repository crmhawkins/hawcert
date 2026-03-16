# 📦 Instalación de la Extensión HawCert Auto-Fill

## 🚀 Instrucciones Paso a Paso

### Paso 1: Abrir la Página de Extensiones de Chrome

1. Abre **Google Chrome**
2. En la barra de direcciones, escribe exactamente: `chrome://extensions/`
3. Presiona **Enter**

### Paso 2: Activar el Modo de Desarrollador

1. En la esquina **superior derecha** de la página, verás un interruptor que dice **"Modo de desarrollador"** o **"Developer mode"**
2. **Actívalo** (debe quedar en azul/activado)
   - Si está desactivado, haz clic en él para activarlo

### Paso 3: Cargar la Extensión

1. Después de activar el modo de desarrollador, verás varios botones nuevos en la parte superior
2. Haz clic en el botón **"Cargar extensión sin empaquetar"** o **"Load unpacked"**
3. Se abrirá un explorador de archivos de Windows
4. Navega hasta la carpeta de tu proyecto HawCert
5. Entra en la carpeta `chrome-extension`
6. **Selecciona la carpeta** `chrome-extension` (no los archivos dentro, sino la carpeta misma)
7. Haz clic en **"Seleccionar carpeta"** o **"Select Folder"**

### Paso 4: Verificar la Instalación

Deberías ver:
- ✅ La extensión **"HawCert Auto-Fill"** en la lista
- ✅ Un icono de extensión (puede ser genérico si no hay iconos personalizados)
- ✅ El estado debe ser **"Activada"** o **"Enabled"**

Si hay algún error, aparecerá en **rojo** debajo del nombre de la extensión.

### Paso 5: Configurar la Extensión

#### Opción A: Desde el Icono de la Extensión

1. Busca el icono de la extensión en la **barra de herramientas** de Chrome
   - Si no lo ves, haz clic en el icono de **extensión** (puzzle) en la barra de herramientas
   - Busca "HawCert Auto-Fill" y haz clic en el icono de **pin** 📌 para fijarlo en la barra
2. Haz clic en el icono de la extensión
3. Haz clic en **"Configuración"** o ve directamente a `chrome://extensions/` y haz clic en **"Opciones"** debajo de la extensión

#### Opción B: Desde la Página de Extensiones

1. Ve a `chrome://extensions/`
2. Encuentra **"HawCert Auto-Fill"**
3. Haz clic en **"Opciones"** o **"Options"**

#### Configurar los Datos:

1. **URL de la API de HawCert:**
   - URL de producción: `https://hawcert.hawkins.es/api`
   - También puedes escribir solo: `https://hawcert.hawkins.es` (se añadirá `/api` automáticamente)
   - ⚠️ **Nota**: La extensión añadirá `/api` automáticamente si no lo incluyes

2. **Certificado X.509:**
   - Ve al panel de HawCert (`https://hawcert.hawkins.es`)
   - Inicia sesión
   - Ve a **Certificados**
   - Haz clic en el certificado que quieras usar
   - Haz clic en **"Descargar PEM"** o copia el certificado completo
   - Pega el certificado completo en el campo de configuración
   - Debe incluir las líneas:
     ```
     -----BEGIN CERTIFICATE-----
     ...contenido del certificado...
     -----END CERTIFICATE-----
     ```

3. Haz clic en **"Guardar Configuración"**

### Paso 6: Probar la Extensión

1. **Crea una credencial en el panel de HawCert:**
   - Ve al panel de HawCert
   - Ve a **Credenciales** → **Nueva Credencial**
   - Configura:
     - **Sitio Web**: Ej: "IONOS"
     - **Patrón de URL**: Ej: `*ionos.com*` o `https://www.ionos.com/*`
     - **Usuario**: Tu usuario de IONOS
     - **Contraseña**: Tu contraseña de IONOS
     - **Selectores CSS** (opcional, la extensión los detectará automáticamente):
       - Campo usuario: `#username` o `input[name="email"]`
       - Campo contraseña: `#password` o `input[type="password"]`
     - Asocia a un **Usuario** o **Certificado**

2. **Visita el sitio web** (ej: IONOS)
3. La extensión debería:
   - ✅ Detectar automáticamente los campos de login
   - ✅ Rellenar las credenciales (de forma invisible)
   - ✅ Enviar el formulario automáticamente
   - ✅ Iniciar sesión sin que veas las credenciales

## 🔧 Solución de Problemas

### ❌ Error: "No se pueden cargar los iconos"
**Solución**: Los iconos son opcionales. Si aparece este error, ignóralo o crea iconos PNG simples de 16x16, 48x48 y 128x128 píxeles.

### ❌ Error: "No hay certificado configurado"
**Solución**: 
- Ve a Configuración de la extensión
- Pega tu certificado PEM completo (debe incluir `-----BEGIN CERTIFICATE-----` y `-----END CERTIFICATE-----`)

### ❌ Error: "Error de red" o "Failed to fetch"
**Solución**:
- Verifica que la URL de la API sea correcta: `https://hawcert.hawkins.es/api`
- Asegúrate de que el servidor HawCert esté accesible
- Prueba abrir la URL en el navegador: `https://hawcert.hawkins.es/api/get-credentials` (debería dar un error de método, pero confirmará que la API está accesible)
- Verifica tu conexión a internet
- Si hay problemas de CORS, verifica la configuración del servidor

### ❌ La extensión no rellena los campos
**Solución**:
1. Verifica que hay credenciales configuradas en el panel de HawCert para esa URL
2. Comprueba que el certificado sea válido y no haya expirado
3. Abre la **consola de Chrome** (F12) y revisa si hay errores
4. Verifica que el patrón de URL en las credenciales coincida con la URL actual

### ❌ Los campos no se detectan automáticamente
**Solución**:
- La extensión intenta detectar automáticamente campos comunes
- Si no los detecta, puedes especificar selectores CSS en las credenciales del panel:
  - Campo usuario: `input[name="email"]` o `#username`
  - Campo contraseña: `input[type="password"]` o `#password`

### ❌ El formulario no se envía automáticamente
**Solución**:
- La extensión intenta enviar automáticamente
- Si no funciona, puedes especificar un selector CSS para el botón de envío en las credenciales
- Ejemplo: `button[type="submit"]` o `#login-button`

## 📝 Notas Importantes

- ✅ El certificado se almacena **localmente** en Chrome (no se envía al servidor excepto para validación)
- ✅ Las credenciales están **encriptadas** en el servidor HawCert
- ✅ Solo se obtienen credenciales cuando el certificado es **válido** y tiene acceso
- ✅ La extensión funciona en **todas las páginas web** pero solo rellena cuando hay credenciales configuradas
- ✅ Los campos se rellenan de forma **invisible** (no verás el email ni la contraseña)
- ✅ El formulario se envía **automáticamente** después de rellenar

## 🎯 Resumen Rápido

1. `chrome://extensions/` → Activar modo desarrollador
2. Cargar extensión sin empaquetar → Seleccionar carpeta `chrome-extension`
3. Configurar → URL API + Certificado PEM
4. Crear credenciales en el panel de HawCert
5. ¡Listo! La extensión rellenará automáticamente
