<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect(dashboard_path($_SESSION['role']));
}

$error = '';
$old_username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $old_username = $username;

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Username atau password salah.';
        } elseif ((int) $user['is_active'] !== 1) {
            $error = 'Akun Anda tidak aktif. Silakan hubungi admin/pengelola.';
        } else {
            $nama = 'Administrator';
            if ($user['role'] === 'guru') {
                $stmt = $pdo->prepare('SELECT nama FROM guru WHERE user_id = ?');
                $stmt->execute([$user['id']]);
                $nama = $stmt->fetchColumn() ?: $username;
            } elseif ($user['role'] === 'siswa') {
                $stmt = $pdo->prepare('SELECT nama FROM siswa WHERE user_id = ?');
                $stmt->execute([$user['id']]);
                $nama = $stmt->fetchColumn() ?: $username;
            }

            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['nama']     = $nama;

            redirect(dashboard_path($user['role']));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk · <?= e(APP_NAME) ?></title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary: { DEFAULT: '#5B67D8', dark: '#454FC2', light: '#EBEAFB' },
        accent:  { DEFAULT: '#FFB84D', light: '#FFF2DC' },
        peach:   { DEFAULT: '#FF9587', light: '#FFE7E2' },
        mint:    { DEFAULT: '#3FC28E', light: '#DFF7EC' },
        ink:     '#342F4E',
        muted:   '#8C86A6',
        surface: '#F8F6FC',
        success: '#3FAE7E',
        danger:  '#E56767',
      },
      fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
    }
  }
}
</script>
<style>
  body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
  *:focus-visible { outline: 2px solid #5B67D8; outline-offset: 2px; }
  @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
  .float-slow { animation: float 5s ease-in-out infinite; }
  .float-slower { animation: float 7s ease-in-out infinite; animation-delay: .5s; }
</style>
</head>
<body class="bg-white text-ink antialiased">

<div class="min-h-screen w-full flex flex-col lg:flex-row">

  <!-- Panel kiri 60% : ilustrasi & sapaan -->
  <div class="relative overflow-hidden bg-primary w-full lg:w-[60%] flex flex-col justify-between p-8 sm:p-12 lg:p-16 min-h-[380px] lg:min-h-screen">
    <div class="absolute -right-24 -top-24 w-[26rem] h-[26rem] rounded-full bg-white/5"></div>
    <div class="absolute -left-16 bottom-0 w-72 h-72 rounded-full bg-white/5"></div>
    <div class="absolute right-16 bottom-28 w-16 h-16 rounded-2xl bg-accent/20 rotate-12 hidden sm:block"></div>
    <div class="absolute left-1/4 top-1/3 w-10 h-10 rounded-xl bg-white/10 rotate-12 hidden lg:block"></div>

    <div class="relative flex items-center gap-3">
      <div class="w-10 h-10 rounded-2xl bg-white/15 flex items-center justify-center">
        <i class="fa-solid fa-church text-white"></i>
      </div>
      <span class="font-extrabold text-lg text-white">Sekolah Minggu</span>
    </div>

    <div class="relative flex justify-center py-10 lg:py-6">
      <div class="relative w-48 h-48 sm:w-64 sm:h-64">
        <div class="absolute inset-0 rounded-full bg-white/10"></div>
        <div class="absolute inset-6 rounded-full bg-white/10 flex items-center justify-center">
          <i class="fa-solid fa-dove text-7xl sm:text-8xl text-accent"></i>
        </div>
        <div class="float-slow absolute -top-3 left-2 w-14 h-14 rounded-2xl bg-peach flex items-center justify-center shadow-lg">
          <i class="fa-solid fa-star text-white text-xl"></i>
        </div>
        <div class="float-slower absolute -top-3 right-2 w-12 h-12 rounded-2xl bg-accent flex items-center justify-center shadow-lg">
          <i class="fa-solid fa-heart text-white text-lg"></i>
        </div>
        <div class="float-slow absolute -bottom-5 right-8 w-16 h-16 rounded-2xl bg-mint flex items-center justify-center shadow-lg">
          <i class="fa-solid fa-child text-white text-2xl"></i>
        </div>
      </div>
    </div>

    <div class="relative">
      <h1 class="text-3xl sm:text-4xl font-extrabold leading-tight mb-3 text-white"><?= e(APP_NAME) ?></h1>
      <p class="text-white/80 text-sm sm:text-base leading-relaxed max-w-md">
        Kelola data guru, siswa, jadwal, kegiatan, absensi, materi, dan nilai rapor siswa sekolah minggu dalam satu tempat.
      </p>
      <p class="text-xs text-white/50 mt-8">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>.</p>
    </div>
  </div>

  <!-- Panel kanan 40% : form login -->
  <div class="relative w-full lg:w-[40%] bg-white flex items-center justify-center p-8 sm:p-12 lg:p-16">
    <div class="w-full max-w-sm">

      <h2 class="text-3xl font-extrabold text-ink mb-1">Selamat Datang</h2>
      <p class="text-sm text-muted mb-8">Masuk untuk mengelola sekolah minggu.</p>

      <?php if ($error !== ''): ?>
        <div class="flex items-start gap-3 bg-danger/10 text-danger rounded-2xl px-4 py-3 mb-6">
          <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
          <p class="flex-1 text-sm font-medium"><?= e($error) ?></p>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-5" novalidate>
        <?= csrf_field() ?>

        <div>
          <label for="username" class="block text-sm font-semibold text-ink mb-1.5">Username</label>
          <div class="relative">
            <i class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-sm"></i>
            <input type="text" id="username" name="username" value="<?= e($old_username) ?>" required autofocus
                   class="w-full rounded-2xl border border-slate-200 bg-surface pl-10 pr-4 py-3 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                   placeholder="Masukkan username">
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm font-semibold text-ink mb-1.5">Password</label>
          <div class="relative">
            <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-sm"></i>
            <input type="password" id="password" name="password" required
                   class="w-full rounded-2xl border border-slate-200 bg-surface pl-10 pr-4 py-3 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                   placeholder="Masukkan password">
          </div>
        </div>

        <button type="submit"
                class="w-full bg-primary hover:bg-primary-dark text-white rounded-2xl px-4 py-3 font-semibold transition-colors shadow-lg shadow-primary/30">
          Masuk
        </button>
      </form>

    </div>
  </div>

</div>
</body>
</html>
