<?php
header('Access-Control-Allow-Origin: *');
Header("content-type: application/javascript");
?>
var my_titulo = 'App Negocios';

var my_contenido = `
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }

  body {
    background: #fff;
    font-family: 'Sora', 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    min-height: 100vh;
    min-height: 100dvh;
  }

  .app-shell {
    width: min(100%, 430px);
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    background: #fff;
  }

  .img-area {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .hero-img {
    width: min(98%, 392px);
    height: auto;
    object-fit: contain;
    max-height: 448px;
  }

  .white-footer {
    padding: 22px 20px 28px;
    flex-shrink: 0;
  }

  .btn-start {
    width: 100%;
    height: 58px;
    border: none;
    border-radius: 999px;
    background: #f4d117;
    color: #111;
    font-family: 'Sora', 'Segoe UI', sans-serif;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.18s;
  }

  .btn-start:active { opacity: 0.82; }
</style>

<div class="app-shell">
  <div class="img-area">
    <img class="hero-img"
      src="${my_hosting}assets/img/appnegocios.jpg"
      alt="App Negocios" />
  </div>

  <div class="white-footer">
    <button class="btn-start" id="btn-iniciar">Iniciar sesión</button>
  </div>
</div>
`;

$('#titulo').html(my_titulo);
$('body').html(my_contenido);

localStorage.setItem('contenido', my_contenido);

$('#btn-iniciar').on('click', function() {
  $(this).prop('disabled', true).css('opacity', '0.7');
  processing({ t: 'passa' });
});
