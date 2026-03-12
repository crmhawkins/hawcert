# API de Autenticación con Certificados - Guía de Implementación

## Descripción General

Este documento explica cómo integrar el sistema de autenticación basado en certificados X.509 en tus aplicaciones cliente. El servidor central (HawCert) valida certificados y genera keys de acceso temporales de un solo uso.

## Arquitectura

```
┌─────────────┐         ┌──────────────────┐         ┌─────────────────┐
│   Cliente   │─────────▶│ Servidor Central │─────────▶│ Servidor Destino│
│  (Tu App)   │         │   (HawCert)      │         │  (Tu Servicio)  │
└─────────────┘         └──────────────────┘         └─────────────────┘
      │                           │                            │
      │ 1. Envía certificado      │                            │
      │    + URL destino          │                            │
      │──────────────────────────▶│                            │
      │                           │                            │
      │ 2. Recibe access_key      │                            │
      │◀──────────────────────────│                            │
      │                           │                            │
      │ 3. Envía access_key       │                            │
      │───────────────────────────────────────────────────────▶│
      │                           │                            │
      │                           │ 4. Valida access_key       │
      │                           │◀───────────────────────────│
      │                           │                            │
      │                           │ 5. Respuesta de validación │
      │                           │───────────────────────────▶│
      │                           │                            │
      │ 6. Acceso permitido       │                            │
      │◀───────────────────────────────────────────────────────│
```

## Endpoints Disponibles

### Base URL
```
https://tu-servidor-central.com/api
```

### 1. Validar Certificado y Obtener Access Key

**Endpoint:** `POST /api/validate-access`

**Descripción:** Valida un certificado X.509 y genera una key de acceso temporal si el usuario tiene permisos para la URL solicitada.

**Request:**
```json
{
    "certificate": "-----BEGIN CERTIFICATE-----\n...certificado PEM completo...\n-----END CERTIFICATE-----",
    "url": "https://servicio-destino.com/api/recurso",
    "service_slug": "api"  // Opcional, se infiere de la URL si no se proporciona
}
```

**Response Exitosa (200):**
```json
{
    "success": true,
    "access_key": "ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "expires_at": "2026-03-13T14:45:00Z",
    "service": {
        "name": "API Principal",
        "slug": "api"
    },
    "user": {
        "id": 1,
        "name": "Usuario",
        "email": "usuario@example.com"
    },
    "permissions": ["read", "write", "admin"]
}
```

**Response de Error (400/403/404):**
```json
{
    "success": false,
    "message": "Descripción del error"
}
```

**Códigos de Estado:**
- `200`: Certificado válido, key generada
- `400`: Error en la solicitud (certificado inválido, URL mal formada)
- `403`: Certificado inválido, expirado o sin permisos
- `404`: Certificado no encontrado o servicio no existe
- `500`: Error interno del servidor

### 2. Validar Access Key

**Endpoint:** `POST /api/validate-key`

**Descripción:** Valida una key de acceso generada. Este endpoint es usado por los servidores destino para verificar las keys antes de permitir acceso.

**IMPORTANTE:** Las keys son de **un solo uso**. Una vez validadas, se marcan como usadas y no pueden reutilizarse.

**Request:**
```json
{
    "key": "ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "url": "https://servicio-destino.com/api/recurso"
}
```

**Response Exitosa (200):**
```json
{
    "success": true,
    "valid": true,
    "certificate": {
        "id": 1,
        "name": "Certificado Usuario",
        "common_name": "certificado-usuario"
    },
    "user": {
        "id": 1,
        "name": "Usuario",
        "email": "usuario@example.com"
    },
    "service": {
        "slug": "api"
    },
    "permissions": ["read", "write"],
    "expires_at": "2026-03-13T14:45:00Z"
}
```

**Response de Error (403/404):**
```json
{
    "success": false,
    "message": "Key de acceso no encontrada" // o "ya fue utilizada" o "ha expirado"
}
```

## Implementación en Cliente (Tu Aplicación)

### Paso 1: Cargar el Certificado

El certificado debe estar almacenado de forma segura en tu aplicación. Puede ser:
- Un archivo `.pem` o `.p12` en el sistema de archivos
- Almacenado en una base de datos encriptada
- En el almacenamiento seguro del sistema operativo

```javascript
// Ejemplo: Cargar certificado desde archivo
const fs = require('fs');
const certificatePEM = fs.readFileSync('/ruta/al/certificado.pem', 'utf8');
```

### Paso 2: Solicitar Access Key

```javascript
async function obtenerAccessKey(certificadoPEM, urlDestino) {
    const response = await fetch('https://servidor-central.com/api/validate-access', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            certificate: certificadoPEM,
            url: urlDestino,
            // service_slug es opcional si la URL permite inferirlo
        })
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(`Error al obtener access key: ${error.message}`);
    }

    const data = await response.json();
    return data.access_key;
}
```

### Paso 3: Usar la Access Key

```javascript
async function accederRecurso(accessKey, urlDestino) {
    const response = await fetch(urlDestino, {
        method: 'GET', // o POST, PUT, etc.
        headers: {
            'Authorization': `Bearer ${accessKey}`,
            'X-Target-URL': urlDestino, // Para que el servidor destino sepa qué URL validar
            'Content-Type': 'application/json'
        }
    });

    return await response.json();
}
```

### Ejemplo Completo

```javascript
class CertAuthClient {
    constructor(certificatePath, authServerUrl) {
        this.certificate = fs.readFileSync(certificatePath, 'utf8');
        this.authServerUrl = authServerUrl;
        this.accessKey = null;
        this.accessKeyExpires = null;
    }

    async obtenerAccessKey(urlDestino) {
        try {
            const response = await fetch(`${this.authServerUrl}/api/validate-access`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    certificate: this.certificate,
                    url: urlDestino
                })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message);
            }

            const data = await response.json();
            this.accessKey = data.access_key;
            this.accessKeyExpires = new Date(data.expires_at);
            
            return this.accessKey;
        } catch (error) {
            console.error('Error al obtener access key:', error);
            throw error;
        }
    }

    async hacerRequest(urlDestino, options = {}) {
        // Verificar si necesitamos una nueva key
        if (!this.accessKey || new Date() >= this.accessKeyExpires) {
            await this.obtenerAccessKey(urlDestino);
        }

        const response = await fetch(urlDestino, {
            ...options,
            headers: {
                ...options.headers,
                'Authorization': `Bearer ${this.accessKey}`,
                'X-Target-URL': urlDestino,
                'Content-Type': 'application/json'
            }
        });

        // Si la key fue rechazada (ya usada), obtener una nueva
        if (response.status === 401 || response.status === 403) {
            await this.obtenerAccessKey(urlDestino);
            return this.hacerRequest(urlDestino, options);
        }

        return response;
    }
}

// Uso
const client = new CertAuthClient(
    '/ruta/certificado.pem',
    'https://servidor-central.com'
);

const datos = await client.hacerRequest('https://servicio-destino.com/api/datos');
```

## Implementación en Servidor Destino

### Validar Access Key

Tu servidor destino debe validar cada key con el servidor central antes de permitir acceso:

```javascript
async function validarAccessKey(accessKey, urlDestino) {
    const response = await fetch('https://servidor-central.com/api/validate-key', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            key: accessKey,
            url: urlDestino
        })
    });

    if (!response.ok) {
        return null; // Key inválida
    }

    const data = await response.json();
    return data; // Retorna información del usuario y permisos
}

// Middleware de autenticación (ejemplo Express.js)
async function authMiddleware(req, res, next) {
    const authHeader = req.headers.authorization;
    const targetUrl = req.headers['x-target-url'] || req.originalUrl;

    if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return res.status(401).json({ error: 'No se proporcionó access key' });
    }

    const accessKey = authHeader.substring(7);
    const validacion = await validarAccessKey(accessKey, targetUrl);

    if (!validacion || !validacion.valid) {
        return res.status(403).json({ error: 'Access key inválida o expirada' });
    }

    // Adjuntar información del usuario a la request
    req.user = validacion.user;
    req.permissions = validacion.permissions;
    req.certificate = validacion.certificate;

    next();
}
```

### Ejemplo con Express.js

```javascript
const express = require('express');
const app = express();

app.use(express.json());

// Middleware de autenticación
app.use(async (req, res, next) => {
    const authHeader = req.headers.authorization;
    const targetUrl = req.protocol + '://' + req.get('host') + req.originalUrl;

    if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return res.status(401).json({ error: 'No autorizado' });
    }

    const accessKey = authHeader.substring(7);
    
    try {
        const response = await fetch('https://servidor-central.com/api/validate-key', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                key: accessKey,
                url: targetUrl
            })
        });

        if (!response.ok) {
            return res.status(403).json({ error: 'Access key inválida' });
        }

        const validacion = await response.json();
        req.user = validacion.user;
        req.permissions = validacion.permissions;
        next();
    } catch (error) {
        return res.status(500).json({ error: 'Error al validar access key' });
    }
});

// Rutas protegidas
app.get('/api/datos', (req, res) => {
    // Verificar permisos específicos si es necesario
    if (!req.permissions.includes('read')) {
        return res.status(403).json({ error: 'Sin permisos de lectura' });
    }

    res.json({
        message: 'Datos protegidos',
        user: req.user.name,
        permisos: req.permissions
    });
});
```

## Seguridad

### Características de Seguridad

1. **Keys de Un Solo Uso**
   - Cada key solo puede validarse una vez
   - Se marca como usada inmediatamente después de la validación
   - Previene reutilización incluso con requests simultáneos

2. **Validación de URL**
   - Cada key está asociada a una URL específica
   - No puede usarse en URLs diferentes
   - Validación estricta del host

3. **Expiración Automática**
   - Keys válidas por 24 horas (configurable)
   - Verificación de expiración en cada validación

4. **Validación Continua del Certificado**
   - El certificado asociado debe seguir siendo válido
   - Si el certificado se revoca, todas las keys dejan de funcionar

5. **Auditoría Completa**
   - Todos los accesos quedan registrados
   - Logging de IPs y timestamps
   - Detección de intentos de reutilización

### Mejores Prácticas

1. **Almacenamiento Seguro del Certificado**
   - Nunca almacenes certificados en texto plano
   - Usa encriptación para almacenar certificados
   - Limita permisos de acceso al archivo del certificado

2. **Manejo de Errores**
   - Implementa reintentos con backoff exponencial
   - Maneja correctamente keys expiradas
   - No expongas información sensible en errores

3. **Cache de Access Keys**
   - Cachea las keys hasta su expiración
   - Renueva automáticamente antes de expirar
   - Invalida el cache si la key es rechazada

4. **Validación en Servidor Destino**
   - Siempre valida la key con el servidor central
   - No confíes solo en la key sin validación
   - Implementa rate limiting para prevenir abuso

## Manejo de Errores Comunes

### Certificado Inválido o Expirado
```json
{
    "success": false,
    "message": "Certificado inválido o expirado"
}
```
**Solución:** Renovar o reactivar el certificado en el panel de administración.

### Sin Permisos para el Servicio
```json
{
    "success": false,
    "message": "El certificado no tiene acceso a este servicio"
}
```
**Solución:** Asignar el servicio al certificado en el panel de administración.

### Key Ya Utilizada
```json
{
    "success": false,
    "message": "Esta key ya fue utilizada"
}
```
**Solución:** Obtener una nueva key llamando nuevamente a `/api/validate-access`.

### Key Expirada
```json
{
    "success": false,
    "message": "Key de acceso ha expirado"
}
```
**Solución:** Obtener una nueva key llamando nuevamente a `/api/validate-access`.

## Ejemplo de Integración Completa

### Cliente (Node.js)

```javascript
const https = require('https');
const fs = require('fs');

class AuthClient {
    constructor(config) {
        this.authServerUrl = config.authServerUrl;
        this.certificatePath = config.certificatePath;
        this.certificate = fs.readFileSync(this.certificatePath, 'utf8');
        this.accessKey = null;
        this.keyExpires = null;
    }

    async obtenerAccessKey(urlDestino) {
        const response = await fetch(`${this.authServerUrl}/api/validate-access`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                certificate: this.certificate,
                url: urlDestino
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(`Error: ${error.message}`);
        }

        const data = await response.json();
        this.accessKey = data.access_key;
        this.keyExpires = new Date(data.expires_at);
        
        console.log(`Access key obtenida, expira: ${this.keyExpires}`);
        return this.accessKey;
    }

    async request(urlDestino, options = {}) {
        // Verificar si necesitamos nueva key
        if (!this.accessKey || new Date() >= this.keyExpires) {
            await this.obtenerAccessKey(urlDestino);
        }

        const response = await fetch(urlDestino, {
            ...options,
            headers: {
                ...options.headers,
                'Authorization': `Bearer ${this.accessKey}`,
                'X-Target-URL': urlDestino
            }
        });

        // Si la key fue rechazada, obtener nueva
        if (response.status === 401 || response.status === 403) {
            await this.obtenerAccessKey(urlDestino);
            return this.request(urlDestino, options);
        }

        return response;
    }
}

// Uso
const client = new AuthClient({
    authServerUrl: 'https://servidor-central.com',
    certificatePath: './certificado.pem'
});

// Hacer request autenticado
const response = await client.request('https://servicio-destino.com/api/datos');
const datos = await response.json();
console.log(datos);
```

### Servidor Destino (Express.js)

```javascript
const express = require('express');
const app = express();

app.use(express.json());

const AUTH_SERVER_URL = 'https://servidor-central.com';

async function validarKey(accessKey, urlDestino) {
    const response = await fetch(`${AUTH_SERVER_URL}/api/validate-key`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key: accessKey, url: urlDestino })
    });

    if (!response.ok) return null;
    return await response.json();
}

app.use(async (req, res, next) => {
    const authHeader = req.headers.authorization;
    const targetUrl = `${req.protocol}://${req.get('host')}${req.originalUrl}`;

    if (!authHeader?.startsWith('Bearer ')) {
        return res.status(401).json({ error: 'No autorizado' });
    }

    const accessKey = authHeader.substring(7);
    const validacion = await validarKey(accessKey, targetUrl);

    if (!validacion?.valid) {
        return res.status(403).json({ error: 'Access key inválida' });
    }

    req.user = validacion.user;
    req.permissions = validacion.permissions;
    next();
});

app.get('/api/datos', (req, res) => {
    if (!req.permissions.includes('read')) {
        return res.status(403).json({ error: 'Sin permisos' });
    }

    res.json({ 
        datos: 'Información protegida',
        usuario: req.user.name 
    });
});

app.listen(3000);
```

## Preguntas Frecuentes

### ¿Puedo reutilizar una access key?
**No.** Las keys son de un solo uso. Cada vez que necesites acceso, debes obtener una nueva key llamando a `/api/validate-access`.

### ¿Cuánto tiempo son válidas las keys?
Por defecto, 24 horas desde su generación. Puedes verificar la fecha de expiración en el campo `expires_at` de la respuesta.

### ¿Qué pasa si mi certificado expira?
Todas las keys generadas con ese certificado dejarán de funcionar inmediatamente. Debes renovar el certificado en el panel de administración.

### ¿Puedo usar la misma key en múltiples URLs?
**No.** Cada key está asociada a una URL específica y solo funciona para esa URL.

### ¿Cómo manejo errores de red?
Implementa reintentos con backoff exponencial y maneja correctamente los timeouts. Si el servidor central no está disponible, no podrás obtener nuevas keys.

## Soporte

Para más información o soporte, consulta la documentación del servidor central o contacta al administrador del sistema.
