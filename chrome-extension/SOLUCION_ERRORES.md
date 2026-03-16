# 🔧 Solución de Errores Comunes

## Error: "Could not establish connection. Receiving end does not exist."

Este error ocurre cuando el content script intenta comunicarse con el service worker antes de que esté activo.

### ✅ Soluciones:

1. **Recarga la extensión:**
   - Ve a `chrome://extensions/`
   - Encuentra "HawCert Auto-Fill"
   - Haz clic en el icono de **recarga** (🔄)

2. **Recarga la página web:**
   - Presiona `F5` o `Ctrl+R` en la página donde quieres usar la extensión
   - Esto da tiempo al service worker para activarse

3. **Verifica que la extensión esté activada:**
   - En `chrome://extensions/`
   - Asegúrate de que el interruptor de "HawCert Auto-Fill" esté **activado**

4. **Reinstala la extensión:**
   - En `chrome://extensions/`
   - Haz clic en **"Eliminar"** en "HawCert Auto-Fill"
   - Vuelve a cargar la extensión desde la carpeta

### 📝 Notas Técnicas:

- En Manifest V3, los service workers pueden estar inactivos
- El código ahora maneja este error automáticamente y reintenta
- Si el error persiste, puede ser un problema de permisos o configuración

### 🔍 Verificar en la Consola:

1. Abre la consola de Chrome (`F12`)
2. Ve a la pestaña "Console"
3. Busca mensajes de error relacionados con "HawCert"
4. Si ves errores, compártelos para diagnóstico
