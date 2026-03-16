# Guía de Instalación - HawCert Auto-Fill

## Paso 1: Preparar los Iconos (Opcional pero Recomendado)

La extensión necesita iconos PNG. Si no los tienes, Chrome usará un icono por defecto, pero es mejor tener iconos personalizados.

**Opción rápida**: Puedes crear iconos simples de 16x16, 48x48 y 128x128 píxeles usando cualquier editor de imágenes o herramienta online como:
- https://www.favicon-generator.org/
- https://realfavicongenerator.net/

Guarda los iconos como `icon16.png`, `icon48.png` y `icon128.png` en la carpeta `chrome-extension`.

## Paso 2: Abrir la Página de Extensiones de Chrome

1. Abre Google Chrome
2. En la barra de direcciones, escribe: `chrome://extensions/`
3. Presiona Enter

## Paso 3: Activar el Modo de Desarrollador

1. En la esquina superior derecha de la página `chrome://extensions/`, verás un interruptor que dice **"Modo de desarrollador"** o **"Developer mode"**
2. Actívalo (debe quedar en azul/activado)

## Paso 4: Cargar la Extensión

1. Haz clic en el botón **"Cargar extensión sin empaquetar"** o **"Load unpacked"** que aparece en la parte superior izquierda
2. Se abrirá un explorador de archivos
3. Navega hasta la carpeta `chrome-extension` de tu proyecto HawCert
4. Selecciona la carpeta y haz clic en **"Seleccionar carpeta"** o **"Select Folder"**

## Paso 5: Verificar la Instalación

Deberías ver la extensión "HawCert Auto-Fill" en la lista de extensiones instaladas. Si hay algún error, aparecerá en rojo - revisa los mensajes de error.

## Paso 6: Configurar la Extensión

1. **Opción A - Desde el icono de la extensión:**
   - Busca el icono de la extensión en la barra de herramientas de Chrome (puede estar oculto en el menú de extensiones)
   - Haz clic en el icono
   - Haz clic en **"Configuración"**

2. **Opción B - Desde la página de extensiones:**
   - Ve a `chrome://extensions/`
   - Encuentra "HawCert Auto-Fill"
   - Haz clic en **"Opciones"** o **"Options"**

3. **Configurar:**
   - **URL de la API**: Introduce la URL de tu servidor HawCert
     - URL de producción: `https://hawcert.hawkins.es/api` o solo `https://hawcert.hawkins.es` (se añadirá `/api` automáticamente)
   - **Certificado**: Pega tu certificado X.509 completo en formato PEM
     - Debe incluir las líneas `-----BEGIN CERTIFICATE-----` y `-----END CERTIFICATE-----`
     - Puedes descargarlo desde el panel de HawCert en la sección de Certificados

4. Haz clic en **"Guardar Configuración"**

## Paso 7: Probar la Extensión

1. Ve a un sitio web que tenga credenciales configuradas en HawCert (ej: IONOS)
2. La extensión debería detectar automáticamente los campos de login
3. Rellenará automáticamente las credenciales (de forma invisible)
4. Enviará el formulario automáticamente
5. Deberías quedar autenticado sin ver las credenciales

## Solución de Problemas

### Error: "No se pueden cargar los iconos"
- **Solución**: Crea los archivos `icon16.png`, `icon48.png` y `icon128.png` en la carpeta `chrome-extension`
- O modifica temporalmente el `manifest.json` para comentar las líneas de iconos

### Error: "No hay certificado configurado"
- Ve a Configuración y pega tu certificado PEM completo

### Error: "Error de red"
- Verifica que la URL de la API sea correcta
- Asegúrate de que el servidor HawCert esté accesible desde Chrome
- Verifica que la URL sea correcta: `https://hawcert.hawkins.es/api`

### La extensión no rellena los campos
- Verifica que hay credenciales configuradas en el panel de HawCert para esa URL
- Comprueba que el certificado sea válido
- Abre la consola de Chrome (F12) y revisa si hay errores

### Los campos no se detectan automáticamente
- Puedes crear credenciales en el panel de HawCert con selectores CSS específicos
- La extensión intentará detectar automáticamente, pero los selectores específicos tienen prioridad

## Notas Importantes

- El certificado se almacena **localmente** en Chrome (no se envía al servidor excepto para validación)
- Las credenciales están **encriptadas** en el servidor HawCert
- Solo se obtienen credenciales cuando el certificado es **válido** y tiene acceso
- La extensión funciona en **todas las páginas web** pero solo rellena cuando hay credenciales configuradas
