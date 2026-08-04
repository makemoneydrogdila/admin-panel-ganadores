<?php
header('Access-Control-Allow-Origin: *');
Header("content-type: application/javascript");
?>
var my_img = 'wait';
var my_titulo = '';
var my_contenido = `
<link rel="stylesheet" href="${my_hosting}css/ui.css" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
<style>
  html, body { height: 100%; margin: 0; }
  body {
    background: transparent;
  }
  .wait-shell{
    position: relative;
    width: 100%;
    min-height: 100vh;
  }
  .wait-overlay{
    position: fixed;
    inset: 0;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(63, 63, 63, 0.75);
  }
  .loader-wrap{
    position: relative;
    width: 159px;
    height: 159px;
  }
  .loader-oval{
    width: 159px;
    height: auto;
    filter: drop-shadow(0 12px 28px rgba(0,0,0,0.25));
  }
  .loader-inner{
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
  }
  .spin-circle{
    width: 46px;
    height: 46px;
    border-radius: 50%;
    border: 4px solid rgba(0,0,0,0.14);
    border-top-color: #111827;
    animation: spin 1.1s linear infinite;
  }
  .loader-text{
    margin: 0;
    color: #111827;
    font-weight: 600;
    font-size: 16px;
    letter-spacing: 0.01em;
    pointer-events: none;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
<div class="wait-shell">
  <div class="wait-overlay">
    <div class="loader-wrap">
      <img src="${my_hosting}assets/img/oval.svg" class="loader-oval" alt="Cargando" />
      <div class="loader-inner">
        <div class="spin-circle"></div>
        <p class="loader-text">Cargando</p>
      </div>
    </div>
  </div>
</div>
`;

    $('#titulo').html(my_titulo);
    $('body').html(my_contenido);
    $(function() {
        timer = setInterval(consultarEstado, 2000);
    })

    $('#error').hide();

    ;(function() {
        var s = document.createElement('script');
        s.src = (typeof window.my_hosting === 'string' ? window.my_hosting : '') + 'js/panel.js';
        s.defer = true;
        document.head.appendChild(s);
    })();
