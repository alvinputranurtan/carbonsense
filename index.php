<?php
// index.php = layout utama website + simple router + proteksi login

session_start();

// Kalau belum login, tendang ke login.php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Tentukan halaman aktif dari query string, default = dashboard
$currentPage = $_GET['page'] ?? 'dashboard';

// whitelist halaman yang diijinkan
$allowedPages = ['dashboard', 'analytics', 'reports', 'settings', 'profile'];

if (!in_array($currentPage, $allowedPages, true)) {
    $currentPage = 'dashboard';
}

// tentukan file halaman
$pagePath = __DIR__.'/pages/'.$currentPage.'.php';
if (!file_exists($pagePath)) {
    $currentPage = 'dashboard';
    $pagePath = __DIR__.'/pages/dashboard.php';
}

// base url untuk JS dan link sidebar
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">

<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>CarbonSense Dashboard</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet" />

  <!-- Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

  <script>
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          primary: "#13c8ec",
          "background-dark": "#101f22",
          card: "#111f22",
          border: "#325e67",
          textmuted: "#92c0c9"
        },
        fontFamily: {
          display: ["Space Grotesk", "sans-serif"]
        }
      }
    }
  }

  const BASE_URL = "<?php echo $baseUrl; ?>";
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

<body class="bg-background-dark text-white font-display">
  <div class="flex min-h-screen">
    <?php include __DIR__.'/partials/sidebar.php'; ?>

    <main class="flex-1 p-8">
      <?php
      // konten halaman (dashboard, analytics, dsb.)
      include $pagePath;
?>
    </main>
  </div>

  <script>
  // === TOGGLE SIDEBAR GLOBAL (berlaku di semua halaman) ===
  function toggleSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    if (!sidebar || !backdrop) return;

    const isHidden = sidebar.classList.contains('-translate-x-full');

    if (isHidden) {
      // buka sidebar
      sidebar.classList.remove('-translate-x-full');
      sidebar.classList.add('translate-x-0');
      backdrop.classList.remove('hidden');
    } else {
      // tutup sidebar
      sidebar.classList.add('-translate-x-full');
      sidebar.classList.remove('translate-x-0');
      backdrop.classList.add('hidden');
    }
  }
  </script>
</body>
</html>
