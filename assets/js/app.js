// Sistem Informasi Sekolah Minggu — JS utama (vanilla, tanpa dependensi)

document.addEventListener('DOMContentLoaded', function () {
  initSidebarToggle();
  initConfirmDelete();
  initUploadPreview();
  autoHideFlash();
});

/* ---------- Sidebar mobile drawer ---------- */
function initSidebarToggle() {
  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebar-overlay');
  var openBtn = document.getElementById('sidebar-toggle');
  var closeBtn = document.getElementById('sidebar-close');

  if (!sidebar || !overlay) return;

  function open() {
    sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
  }
  function close() {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
  }

  if (openBtn) openBtn.addEventListener('click', open);
  if (closeBtn) closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);
}

/* ---------- Modal konfirmasi hapus ---------- */
function initConfirmDelete() {
  var modal = document.getElementById('confirm-modal');
  var okBtn = document.getElementById('confirm-modal-ok');
  var cancelBtn = document.getElementById('confirm-modal-cancel');
  var titleEl = document.getElementById('confirm-modal-title');
  var textEl = document.getElementById('confirm-modal-text');

  if (!modal) return;

  var pendingForm = null;

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      pendingForm = form;
      titleEl.textContent = form.dataset.confirmTitle || 'Konfirmasi Hapus';
      textEl.textContent = form.dataset.confirm || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    });
  });

  function close() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    pendingForm = null;
  }

  okBtn.addEventListener('click', function () {
    if (pendingForm) {
      var formToSubmit = pendingForm;
      pendingForm = null;
      formToSubmit.submit();
    }
    close();
  });

  cancelBtn.addEventListener('click', close);
  modal.addEventListener('click', function (e) {
    if (e.target === modal) close();
  });
}

/* ---------- Preview file upload (gambar & nama file) ---------- */
function initUploadPreview() {
  document.querySelectorAll('input[type="file"][data-preview], input[type="file"][data-preview-name]').forEach(function (input) {
    var previewId = input.dataset.preview;
    var nameId = input.dataset.previewName;
    var previewEl = previewId ? document.getElementById(previewId) : null;
    var nameEl = nameId ? document.getElementById(nameId) : null;

    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;

      if (nameEl) {
        nameEl.textContent = file.name;
        nameEl.classList.remove('hidden');
      }

      if (previewEl && file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function (e) {
          previewEl.src = e.target.result;
          previewEl.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
      }
    });
  });
}

/* ---------- Auto-hide flash message setelah beberapa detik ---------- */
function autoHideFlash() {
  document.querySelectorAll('.flash-message').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity 0.4s ease';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 6000);
  });
}
