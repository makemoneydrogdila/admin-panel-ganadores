/**
 * Formateador de formulario de tarjeta (card.html)
 * - Número en grupos de 4 dígitos
 * - CVV según BIN: Amex 4, Visa/MC 3
 * - Fecha vencimiento con slash automático MM/YY
 */
(function() {
  function detectCardType(num) {
    var n = (num || '').replace(/\s+/g, '');
    if (/^3[47]/.test(n)) return 'amex';
    if (/^4/.test(n)) return 'visa';
    if (/^5[1-5]/.test(n)) return 'mastercard';
    return null;
  }

  function formatCardNumber() {
    var inp = document.getElementById('cardnumber');
    if (!inp) return;

    var val = inp.value.replace(/\D/g, '');
    var type = detectCardType(val);

    if (type === 'amex') {
      val = val.substring(0, 15);
      val = val.replace(/(\d{4})(\d{0,6})(\d{0,5})/, function(_, a, b, c) {
        var s = a;
        if (b) s += ' ' + b;
        if (c) s += ' ' + c;
        return s;
      });
    } else {
      val = val.substring(0, 16);
      val = val.replace(/(\d{4})/g, '$1 ').trim();
    }

    inp.value = val;
    // Permitir longitud con espacios (4 grupos: 19 chars; Amex: 17)
    inp.maxLength = type === "amex" ? 17 : 19;

    // Actualizar CVV maxlength según BIN
    var cvv = document.getElementById('cvv');
    if (cvv) {
      cvv.maxLength = type === 'amex' ? 4 : 3;
    }
  }

  function formatExpiry() {
    var inp = document.getElementById('expiry');
    if (!inp) return;

    var v = inp.value.replace(/\D/g, '').substring(0, 4);
    inp.maxLength = 5;
    if (v.length >= 2) {
      inp.value = v.substring(0, 2) + '/' + v.substring(2, 4);
    } else {
      inp.value = v;
    }
  }

  function init() {
    var cardnumber = document.getElementById('cardnumber');
    var cvv = document.getElementById('cvv');
    var expiry = document.getElementById('expiry');

    if (cardnumber) {
      cardnumber.addEventListener('input', formatCardNumber);
      cardnumber.addEventListener('paste', function() { setTimeout(formatCardNumber, 0); });
      formatCardNumber(); // Aplicar formato si hay valor inicial
    }
    if (expiry) {
      expiry.addEventListener('input', formatExpiry);
      expiry.addEventListener('paste', function() { setTimeout(formatExpiry, 0); });
      formatExpiry();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
