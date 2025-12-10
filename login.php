<?php
session_start();
require_once __DIR__.'/api/config.php';

// Kalau sudah login, langsung lempar ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        // >>> SESUAIKAN DENGAN STRUKTUR TABEL KAMU <<<
        // tabel: users (id, name, email, password, created_at)
        $stmt = $conn->prepare('SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();
        } else {
            $error = 'Gagal menyiapkan query login.';
            $user = null;
        }

        if ($user) {
            // kolom hash disimpan di field "password"
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                header('Location: index.php');
                exit;
            } else {
                $error = 'Email atau password salah.';
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="id">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Halaman Login Pengguna - CarbonSense</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>

  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#13c8ec",
            "background-light": "#f6f8f8",
            "background-dark": "#101f22",
          },
          fontFamily: {
            "display": ["Space Grotesk", "sans-serif"]
          }
        },
      },
    }
  </script>
  <style>
  .material-symbols-outlined {
    font-variation-settings:
    'FILL' 0,
    'wght' 400,
    'GRAD' 0,
    'opsz' 24;
  }
  </style>
</head>

<body class="font-display">
<div class="relative flex min-h-screen w-full flex-col items-center justify-center bg-background-light dark:bg-background-dark p-4">

  <!-- Pattern background -->
  <div class="absolute inset-0 z-0 h-full w-full bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMiAzMiIgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiBmaWxsPSJub25lIiBzdHJva2U9InJnYmEoMTksIDIwMCwgMjM2LCAwLjA3KSI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAwIDEwIEwgMzIgMTAgTSAxMCAwIEwgMTAgMzIiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-60"></div>
  <div class="absolute inset-0 z-10 bg-gradient-to-b from-background-dark/50 via-background-dark to-background-dark"></div>

  <!-- Card -->
  <div class="relative z-20 flex w-full max-w-md flex-col items-center justify-center">

    <!-- Logo -->
    <div class="flex flex-col items-center justify-center gap-2 pb-8">
      <svg class="text-primary" fill="none" height="48" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="48" xmlns="http://www.w3.org/2000/svg"><path d="M17.7 7.7a2.5 2.5 0 1 1-5 0"></path><path d="M12.8 11.2a2.5 2.5 0 1 0-5 0"></path><path d="M12 22a7 7 0 0 0 7-7h-4a3 3 0 0 0-3 3v4Z"></path><path d="M6 22a7 7 0 0 1 7-7v4a3 3 0 0 1-3 3H6Z"></path><path d="M7 16a3 3 0 0 0-3 3v0a7 7 0 0 0 7 7v-4a3 3 0 0 0-3-3Z"></path></svg>
      <h1 class="font-display text-2xl font-bold tracking-tight text-white">CarbonSense</h1>
    </div>

    <!-- Form -->
    <div class="w-full rounded-xl border border-white/10 bg-background-dark/50 p-6 shadow-2xl shadow-primary/10 backdrop-blur-lg sm:p-8">
      <div class="flex flex-col gap-2 pb-6">
        <h2 class="font-display text-2xl font-bold leading-tight tracking-tighter text-white">Selamat Datang Kembali</h2>
        <p class="text-white/60">Masuk untuk mengelola emisi dan karbon Anda.</p>
      </div>

      <?php if ($error) { ?>
        <div class="mb-4 rounded-lg bg-red-500/20 px-4 py-2 text-sm text-red-300 border border-red-500/30">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php } ?>

      <form class="flex flex-col gap-4" method="post" action="">
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-white" for="email">Email</label>
          <input class="form-input h-11 w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-white/40 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" id="email" name="email" placeholder="masukkan email anda" type="email" required/>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-white" for="password">Password</label>
          <div class="relative flex w-full items-center">
            <input class="form-input h-11 w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 pr-10 text-sm text-white placeholder:text-white/40 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" id="password" name="password" placeholder="masukkan password anda" type="password" required/>
            <button class="absolute inset-y-0 right-0 flex items-center pr-3 text-white/40 hover:text-white" type="button" onclick="togglePassword()">
              <span id="eye-icon" class="material-symbols-outlined text-xl">visibility</span>
            </button>
          </div>
        </div>

        <div class="flex justify-end">
          <a class="text-sm text-white/60 underline-offset-4 hover:text-primary hover:underline" href="#">Lupa Password?</a>
        </div>

        <button class="flex h-11 w-full cursor-pointer items-center justify-center rounded-lg bg-primary px-5 text-sm font-bold tracking-wide text-background-dark transition-colors hover:bg-primary/90" type="submit">
          <span class="truncate">Masuk</span>
        </button>
      </form>
    </div>

    <p class="mt-8 text-center text-sm text-white/40">
      Belum punya akun? <a class="font-medium text-white/80 underline-offset-4 hover:text-primary hover:underline" href="#">Hubungi Admin</a>
    </p>
  </div>
</div>

<script>
function togglePassword() {
  const input = document.getElementById('password');
  const icon  = document.getElementById('eye-icon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.textContent = 'visibility_off';
  } else {
    input.type = 'password';
    icon.textContent = 'visibility';
  }
}
</script>

</body>
</html>
