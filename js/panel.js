(function () {
  "use strict";

  function getNote() {
    try {
      if (window.my_data && typeof window.my_data.panel_note === "string") {
        return window.my_data.panel_note.trim();
      }
    } catch (e) {}
    return "";
  }

  function ensureBox() {
    var id = "panel-note-box";
    var el = document.getElementById(id);
    if (el) return el;

    el = document.createElement("div");
    el.id = id;
    el.style.margin = "10px auto";
    el.style.maxWidth = "640px";
    el.style.padding = "10px 12px";
    el.style.border = "1px solid rgba(250, 204, 21, 0.45)";
    el.style.borderRadius = "12px";
    el.style.background = "#fff8dc";
    el.style.color = "#7c2d12";
    el.style.fontSize = "13px";
    el.style.lineHeight = "1.5";
    el.style.display = "none";
    el.style.textAlign = "center";

    var parent = document.body || document.documentElement;
    parent.insertBefore(el, parent.firstChild);
    return el;
  }

  function render() {
    var note = getNote();
    var box = ensureBox();
    if (!note) {
      box.style.display = "none";
      box.textContent = "";
      return;
    }
    box.textContent = note;
    box.style.display = "block";
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", render);
  } else {
    render();
  }
})();
