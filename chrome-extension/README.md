# HawCert Auto-Fill - Extensión de Chrome

Extensión de Chrome que rellena automáticamente credenciales usando certificados HawCert.

## Instalación

1. Abre Chrome y ve a `chrome://extensions/`
2. Activa el "Modo de desarrollador" (Developer mode) en la esquina superior derecha
3. Haz clic en "Cargar extensión sin empaquetar" (Load unpacked)
4. Selecciona la carpeta `chrome-extension`

## Configuración

1. Haz clic en el icono de la extensión en la barra de herramientas
2. Haz clic en "Configuración"
3. Introduce:
   - **URL de la API**: La URL base de tu servidor HawCert (ej: `https://hawcert.hawkins.es/api` o solo `https://hawcert.hawkins.es`)
   - **Certificado**: Pega tu certificado X.509 en formato PEM (puedes descargarlo desde el panel de HawCert)

## Uso

Una vez configurado, la extensión:
- Detecta automáticamente cuando estás en una página web que tiene credenciales guardadas
- Rellena automáticamente los campos de usuario y contraseña
- Opcionalmente puede enviar el formulario automáticamente (si está configurado)

También puedes hacer clic en el icono de la extensión y seleccionar "Rellenar ahora" para rellenar manualmente.

## Notas

- Las credenciales se almacenan de forma segura encriptadas en el servidor HawCert
- El certificado se almacena localmente en Chrome (no se envía al servidor excepto para validación)
- La extensión solo funciona con sitios web que tienen credenciales configuradas en HawCert
