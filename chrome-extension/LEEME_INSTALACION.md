# 🚀 Instalación Rápida - HawCert Auto-Fill

## ⚡ Instalación en 3 Pasos

### ✅ NO necesitas comprimir nada

La extensión se instala directamente desde la carpeta. **NO uses ZIP para modo desarrollador**.

---

## 📋 Pasos de Instalación

### 1️⃣ Abre Chrome Extensions
```
chrome://extensions/
```

### 2️⃣ Activa Modo Desarrollador
- Interruptor en la esquina superior derecha → **ACTIVAR**

### 3️⃣ Carga la Extensión
- Haz clic en **"Cargar extensión sin empaquetar"** o **"Load unpacked"**
- Selecciona la carpeta **`chrome-extension`** (la carpeta completa, NO archivos individuales)
- Haz clic en **"Seleccionar carpeta"**

---

## ⚙️ Configuración

1. Haz clic en **"Opciones"** debajo de la extensión
2. Introduce:
   - **URL API**: `https://hawcert.hawkins.es/api` (o solo `https://hawcert.hawkins.es`)
   - **Certificado**: Pega tu certificado PEM completo
3. Haz clic en **"Guardar Configuración"**

---

## ✅ ¡Listo!

La extensión está instalada y configurada. Ahora:
1. Crea credenciales en el panel de HawCert
2. Visita los sitios web configurados
3. La extensión rellenará automáticamente las credenciales

---

## 📝 Notas

- **NO comprimas** la carpeta para instalación en modo desarrollador
- Si quieres distribuirla, usa el script `empaquetar.ps1` para crear un ZIP
- Para más detalles, consulta `INSTALACION_PASO_A_PASO.md`
