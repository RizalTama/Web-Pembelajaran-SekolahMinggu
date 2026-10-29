    </main>

    <footer class="px-4 sm:px-6 lg:px-8 py-4 text-center text-xs text-muted">
      &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>.
    </footer>

  </div>
</div>

<div id="confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-ink/40 backdrop-blur-sm px-4">
  <div class="bg-white rounded-[1.75rem] shadow-xl max-w-sm w-full p-6">
    <div class="w-12 h-12 rounded-2xl bg-danger/10 text-danger flex items-center justify-center mb-4">
      <i class="fa-solid fa-triangle-exclamation text-xl"></i>
    </div>
    <h3 id="confirm-modal-title" class="text-lg font-bold text-ink mb-2">Konfirmasi Hapus</h3>
    <p id="confirm-modal-text" class="text-sm text-muted mb-6">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
    <div class="flex gap-3 justify-end">
      <button type="button" id="confirm-modal-cancel" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</button>
      <button type="button" id="confirm-modal-ok" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-danger hover:bg-danger/90">Hapus</button>
    </div>
  </div>
</div>

<script src="<?= e(BASE_URL) ?>/assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>
