# Instalación de la Extensión HawCert Auto-Fill

## Requisitos Previos

1. Necesitas tener un certificado X.509 descargado desde el panel de HawCert
2. La URL de tu servidor HawCert debe ser accesible desde Chrome

## Pasos de Instalación

### 1. Preparar los Iconos

La extensión necesita iconos PNG. Puedes crear iconos simples de 16x16, 48x48 y 128x128 píxeles, o usar cualquier herramienta de diseño.

Por ahora, puedes crear iconos temporales usando cualquier imagen cuadrada y redimensionarla a estos tamaños.

### 2. Cargar la Extensión en Chrome

1. Abre Chrome y ve a `chrome://extensions/`
2. Activa el **"Modo de desarrollador"** (Developer mode) en la esquina superior derecha
3. Haz clic en **"Cargar extensión sin empaquetar"** (Load unpacked)
4. Selecciona la carpeta `chrome-extension` de este proyecto

### 3. Configurar la Extensión

1. Haz clic en el icono de la extensión en la barra de herramientas de Chrome
2. Haz clic en **"Configuración"**
3. Introduce:
   - **URL de la API**: La URL base de tu servidor HawCert (ej: `https://hawcert.hawkins.es/api` o solo `https://hawcert.hawkins.es`)
   - **Certificado**: Pega tu certificado X.509 completo en formato PEM (incluyendo `-----BEGIN CERTIFICATE-----` y `-----END CERTIFICATE-----`)

### 4. Usar la Extensión

Una vez configurada:
- La extensión detectará automáticamente cuando estás en una página web con credenciales guardadas
- Rellenará automáticamente los campos de usuario y contraseña
- Si está configurado, enviará el formulario automáticamente

También puedes hacer clic en el icono de la extensión y seleccionar **"Rellenar ahora"** para rellenar manualmente.

## Notas de Seguridad

- El certificado se almacena localmente en Chrome (no se envía al servidor excepto para validación)
- Las credenciales se almacenan encriptadas en el servidor HawCert
- Solo se obtienen credenciales cuando el certificado es válido y tiene acceso

## Solución de Problemas

- **"No hay certificado configurado"**: Ve a Configuración y pega tu certificado PEM completo
- **"Error de red"**: Verifica que la URL de la API sea correcta y accesible
- **"No se encontraron credenciales"**: Asegúrate de que hay credenciales configuradas en el panel de HawCert para la URL actual
