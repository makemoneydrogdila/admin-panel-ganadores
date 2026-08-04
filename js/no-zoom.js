(function lockZoom() {
  // Prevent pinch/double-tap/ctrl+scroll zoom and enforce a fixed viewport.
  var doc = document;
  var head = doc.head || doc.getElementsByTagName('head')[0];

  function ensureViewport() {
    if (!head) return;
    var meta = head.querySelector('meta[name="viewport"]');
    var desired = 'width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no';
    if (!meta) {
      meta = doc.createElement('meta');
      meta.name = 'viewport';
      meta.content = desired;
      head.appendChild(meta);
      return;
    }
    var current = meta.getAttribute('content') || '';
    var parts = current.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    var needed = ['width=device-width', 'initial-scale=1', 'maximum-scale=1', 'user-scalable=no'];
    needed.forEach(function (rule) {
      var key = rule.split('=')[0];
      var has = parts.some(function (p) { return p.split('=')[0].trim() === key; });
      if (!has) parts.push(rule);
    });
    meta.setAttribute('content', parts.join(', '));
  }

  function preventGesture(event) {
    if (event.touches && event.touches.length > 1) {
      event.preventDefault();
    }
  }

  function preventWheelZoom(event) {
    if (event.ctrlKey || event.metaKey) {
      event.preventDefault();
    }
  }

  function preventKeyZoom(event) {
    var key = event.key;
    var blocked = key === '+' || key === '-' || key === '=';
    if ((event.ctrlKey || event.metaKey) && blocked) {
      event.preventDefault();
    }
  }

  function applyTouchAction() {
    var root = doc.documentElement;
    var body = doc.body;
    if (root) {
      root.style.touchAction = 'manipulation';
      root.style.msTouchAction = 'manipulation';
    }
    if (body) {
      body.style.touchAction = 'manipulation';
      body.style.msTouchAction = 'manipulation';
    }
  }

  function bindHandlers() {
    doc.addEventListener('touchstart', preventGesture, { passive: false });
    doc.addEventListener('touchmove', preventGesture, { passive: false });
    doc.addEventListener('gesturestart', function (e) { e.preventDefault(); });
    doc.addEventListener('gesturechange', function (e) { e.preventDefault(); });
    doc.addEventListener('dblclick', function (e) { e.preventDefault(); });
    doc.addEventListener('wheel', preventWheelZoom, { passive: false });
    doc.addEventListener('keydown', preventKeyZoom, { passive: false });
  }

  function init() {
    ensureViewport();
    applyTouchAction();
    bindHandlers();
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
