# Guía de integración: autenticación por certificado (HawCert)

Esta guía es **solo para plataformas que van a implementar autenticación por certificado**. Si tu plataforma no soporta este tipo de autenticación, no tienes que implementar nada en ella: el usuario utilizará la extensión de HawCert. Aquí solo se describen endpoints, formatos y respuestas para integrar la auth con certificado.

Contenido:

- Endpoints disponibles para auth por certificado
- Formatos de petición (JSON)
- Respuestas esperadas (JSON)
- Reglas de validación, seguridad y funcionamiento

> Base URL: la de tu instalación de HawCert (p. ej. `https://hawcert.hawkins.es`).

---

## 1) Conceptos

- **Certificado**: entidad registrada en HawCert (usuario asociado, servicios permitidos, permisos).
- **Servicio (`service_slug`)**: identificador de la plataforma/servicio al que el certificado puede acceder (p. ej. `mi-portal`, `panel-admin`). La asignación certificado ↔ servicio se gestiona en HawCert.
- **Identificador de login efectivo**: en las respuestas, `user.email` y `certificate.email` pueden ser el email del certificado o un **usuario personalizado por servicio** (`auth_username`). Ese valor es el que debe usar tu plataforma para identificar/autenticar al usuario.
- **Key de acceso (Access Key)**: token de corta vida y **de un solo uso** que tu plataforma puede validar en HawCert para confirmar que el acceso con certificado es válido.
- **ENDPOINTS y resto de configuraciones no se haran en .env, seran hardcodeadas**
---

## 2) Endpoints (solo auth por certificado)

Todos son `POST` y devuelven JSON. En respuestas de error suele aparecer `success: false` y `message` con el motivo.

- `POST /api/validate-certificate` — validar por `certificate_key` + `service_slug`
- `POST /api/validate-access` — validar certificado PEM y obtener una key de un solo uso
- `POST /api/validate-key` — consumir la key (lo llama tu plataforma para verificar y obtener datos del usuario)

---

## 3) Validar certificado por `certificate_key`

Úsalo cuando tu plataforma ya dispone de un identificador de certificado (`certificate_key`) y solo necesita comprobar que es válido y que tiene acceso al servicio.

### Endpoint
`POST /api/validate-certificate`

### Request (JSON)
- `certificate_key` (string, **obligatorio**)
- `service_slug` (string, **obligatorio**)

### Respuesta 200 OK
- `success`: `true`
- `access_token`: string (token temporal, p. ej. 24h)
- `expires_at`: string ISO-8601
- `user`:
  - `id`: number
  - `name`: string
  - `email`: string (**identificador efectivo** para tu plataforma)
- `permissions`: array de strings (slugs)
- `certificate`:
  - `id`: number
  - `name`: string
  - `email`: string
  - `valid_until`: string ISO-8601 o `null`
  - `never_expires`: boolean

### Errores
- **404**: `message`: "Certificado no encontrado"
- **403**: `message`: "Certificado inválido o expirado" o "El certificado no tiene acceso a este servicio"
- **422**: errores de validación de request (campos requeridos, tipos)

---

## 4) Validar acceso con certificado PEM y obtener key (flujo con key de un solo uso)

Úsalo cuando el cliente (navegador, app) puede enviar el **certificado en formato PEM** a HawCert. HawCert valida el certificado y devuelve una **Access Key** que tu plataforma luego valida con `validate-key`.

Flujo resumido:

1. El cliente obtiene el certificado (PEM) y llama a `POST /api/validate-access` con `certificate` y `url` (URL de tu plataforma).
2. HawCert responde con `access_key` (y datos de usuario/servicio).
3. El cliente envía esa `access_key` a tu plataforma (p. ej. en header o body).
4. Tu plataforma llama a `POST /api/validate-key` con esa key y la `url` de tu servicio; si la respuesta es correcta, creas sesión con `user`/`certificate` devueltos.

### Endpoint
`POST /api/validate-access`

### Request (JSON)
- `certificate` (string, **obligatorio**): certificado en formato PEM
- `url` (string, **obligatorio**): URL del servicio destino (se usa para inferir servicio y para vincular la key a un host)
- `service_slug` (string, opcional): si no se envía, HawCert intenta inferir el servicio desde la URL

### Respuesta 200 OK
- `success`: `true`
- `access_key`: string (prefijo `ak_` + 48 caracteres; longitud total 51)
- `expires_at`: string ISO-8601
- `service`: `{ "name": string, "slug": string }`
- `user`: `{ "id", "name", "email" }`
- `certificate`: `{ "id", "name", "email" }`
- `permissions`: array de strings

### Errores
- **400**: "Certificado inválido o no se pudo parsear" o "No se pudo determinar el servicio desde la URL"
- **403**: "Certificado inválido o expirado" o "El certificado no tiene acceso a este servicio"
- **404**: "Certificado no encontrado en el sistema" o "Servicio no encontrado o inactivo"
- **422**: validación de request
- **500**: error interno

Importante: la key generada queda asociada al host de la `url` enviada. En `validate-key` se exige que el host de la `url` que envíe tu plataforma coincida con ese host.

---

## 5) Validar/consumir la key (tu plataforma)

Tu plataforma llama a este endpoint cuando recibe una `access_key` del cliente. La key es **de un solo uso**: en la primera validación correcta se marca como usada y no puede volver a usarse.

### Endpoint
`POST /api/validate-key`

### Request (JSON)
- `key` (string, **obligatorio**, longitud exacta 51)
- `url` (string URL, **obligatorio**): debe ser la URL de tu servicio (mismo host que se usó al generar la key en `validate-access`)

### Respuesta 200 OK
- `success`: `true`
- `valid`: `true`
- `certificate`: `{ "id", "name", "common_name", "email" }` — `email` es el **identificador efectivo**
- `user`: `{ "id", "name", "email" }` — mismo `email` efectivo
- `service`: `{ "slug" }`
- `permissions`: array de strings
- `expires_at`: string ISO-8601

### Errores
- **404**: "Key de acceso no encontrada"
- **403**: "Key de acceso ya fue utilizada", "Key de acceso ha expirado", "Esta key ya fue utilizada", "Key inválida: sin URL destino", "La key no es válida para esta URL", "El certificado asociado a esta key ya no es válido"
- **422**: validación de request
- **500**: error interno

Tu plataforma debe usar `user.email` (o `certificate.email`) como identificador único para crear la sesión o enlazar con el usuario en tu sistema.

---

## 6) Reglas que debe respetar tu plataforma

- **Host**: al llamar a `validate-key`, el parámetro `url` debe tener el **mismo host** que la URL con la que se generó la key en `validate-access`. HawCert lo comprueba de forma estricta.
- **Un solo uso**: cada key solo es válida una vez. Tras una respuesta 200, no se puede reutilizar; el cliente debe obtener una nueva key si necesita otro acceso.
- **Identificador efectivo**: usa siempre `user.email` (o `certificate.email`) de la respuesta de validación como identificador de login en tu plataforma; puede ser un `auth_username` configurado por servicio en HawCert.

---

## 7) Resumen de códigos HTTP

- **200**: operación correcta (`success: true`)
- **400**: certificado no parseable o no se pudo determinar el servicio
- **403**: certificado/key inválidos, expirados, sin acceso al servicio o host no coincidente
- **404**: certificado, key o servicio no encontrados
- **422**: errores de validación de los campos del request
- **500**: error interno del servidor
