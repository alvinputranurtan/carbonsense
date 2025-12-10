<?php
require_once __DIR__.'/../api/config.php';

// kalau dipanggil lewat index.php, variabel sudah ada
$currentPage = $currentPage ?? 'profile';
$baseUrl = $baseUrl ?? '';

// ambil user yang login
$userId = $_SESSION['user_id'] ?? null;

// kalau belum login, bisa redirect ke login
if (!$userId && !headers_sent()) {
    header('Location: '.$baseUrl.'/login.php');
    exit;
}

$successMsg = null;
$errorMsg = null;

// handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
    // ambil input
    $displayName = trim($_POST['name'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $profileBio = trim($_POST['profile_bio'] ?? '');
    $jobTitle = trim($_POST['job_title'] ?? '');
    $officeAddress = trim($_POST['office_address'] ?? '');

    // rakit JSON meta
    $meta = [
        'job_title' => $jobTitle,
        'office_address' => $officeAddress,
    ];
    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);

    // update DB
    $stmt = $conn->prepare('
        UPDATE users
        SET name = ?, company_name = ?, profile_bio = ?, profile_meta = ?
        WHERE id = ?
    ');
    if ($stmt) {
        $stmt->bind_param('ssssi', $displayName, $companyName, $profileBio, $metaJson, $userId);
        if ($stmt->execute()) {
            $successMsg = 'Profil berhasil diperbarui.';
            // sync nama di session kalau dipakai
            $_SESSION['user_name'] = $displayName;
        } else {
            $errorMsg = 'Gagal menyimpan perubahan profil.';
        }
        $stmt->close();
    } else {
        $errorMsg = 'Tidak dapat menyiapkan query.';
    }
}

// ambil data user terbaru
$user = [
    'email' => '',
    'name' => '',
    'company_name' => '',
    'profile_bio' => '',
    'profile_meta' => null,
];

if ($userId) {
    $stmt = $conn->prepare('
        SELECT id, email, name, company_name, profile_bio, profile_meta
        FROM users
        WHERE id = ?
        LIMIT 1
    ');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $user = $row;
        }
        $stmt->close();
    }
}

// decode meta
$metaArr = [];
if (!empty($user['profile_meta'])) {
    $decoded = json_decode($user['profile_meta'], true);
    if (is_array($decoded)) {
        $metaArr = $decoded;
    }
}

$jobTitle = $metaArr['job_title'] ?? '';
$officeAddress = $metaArr['office_address'] ?? '';
?>

<div class="w-full max-w-3xl mx-auto flex flex-col gap-6">

  <!-- Header -->
  <div class="flex flex-wrap justify-between items-center gap-4">
    <div class="flex items-center gap-3">
      <!-- hamburger (mobile) -->
      <button
        class="md:hidden p-2 rounded-lg hover:bg-white/10"
        type="button"
        onclick="toggleSidebar()">
        <span class="material-symbols-outlined">menu</span>
      </button>

      <div class="flex flex-col gap-1">
        <p class="text-white text-3xl md:text-4xl font-black leading-tight tracking-[-0.033em]">
          Profil Pengguna
        </p>
        <p class="text-textmuted text-sm md:text-base">
          Kelola informasi dasar akun dan metadata perusahaan Anda.
        </p>
      </div>
    </div>
  </div>

  <!-- Notifikasi -->
  <?php if ($successMsg) { ?>
    <div class="rounded-lg border border-emerald-600/40 bg-emerald-900/40 px-4 py-3 text-sm text-emerald-200">
      <?php echo htmlspecialchars($successMsg); ?>
    </div>
  <?php } elseif ($errorMsg) { ?>
    <div class="rounded-lg border border-red-600/40 bg-red-900/40 px-4 py-3 text-sm text-red-200">
      <?php echo htmlspecialchars($errorMsg); ?>
    </div>
  <?php } ?>

  <!-- Kartu Profil -->
  <section class="flex flex-col gap-6 rounded-xl border border-border bg-card/80 p-6">

    <!-- Header kecil & avatar (tanpa ubah foto) -->
    <div class="flex items-center gap-4">
      <div
        class="bg-center bg-no-repeat bg-cover rounded-full size-14"
        style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuB6X0_wHDenPCn4sA39nLyQ1yEHQRU3jDF78q4Hy198ipRAacYWv_7jD7Mw31QQM1RZcgTQaq4ytcvNxfgSID4BHaZo6ZN-u7po_1NGin7G0Ye_5TmpPRW6uC4ANzZ4oFGOCeHaLQsnuMu80RuGiqkSpy3NdMGcTj5rt1YP9IcqKhF9mv5x8aO2hWVVky5fTCmDJXI2cEfGmg2zRl51he7pB0hRn63Qu4PHvAyJW-F9hYfqWyXnm9vP7kHEot4ApPjbdzncWhz_QeN7");'>
      </div>
      <div class="flex flex-col">
        <p class="text-white text-lg font-semibold">
          <?php echo htmlspecialchars($user['name'] ?: 'Nama belum diisi'); ?>
        </p>
        <p class="text-textmuted text-sm">
          <?php echo htmlspecialchars($user['email'] ?: 'Email belum diisi'); ?>
        </p>
      </div>
    </div>

    <form method="post" class="flex flex-col gap-5">
      <!-- Info Akun -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-white" for="name">Nama Lengkap</label>
          <input
            id="name"
            name="name"
            type="text"
            class="form-input h-11 w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-white/40 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="Nama lengkap"
            value="<?php echo htmlspecialchars($user['name']); ?>"
            required
          />
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-white" for="email">Email</label>
          <input
            id="email"
            type="email"
            class="form-input h-11 w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/70 placeholder:text-white/40 focus:outline-none"
            value="<?php echo htmlspecialchars($user['email']); ?>"
            disabled
          />
          <p class="text-[11px] text-textmuted">
            Email tidak dapat diubah dari halaman ini.
          </p>
        </div>
      </div>

      <!-- Info Perusahaan -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-white" for="company_name">Nama Kantor / Perusahaan</label>
          <input
            id="company_name"
            name="company_name"
            type="text"
            class="form-input h-11 w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-white/40 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="Contoh: PT Green Environment"
            value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-white" for="job_title">Jabatan</label>
          <input
            id="job_title"
            name="job_title"
            type="text"
            class="form-input h-11 w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-white/40 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="Contoh: Environmental Engineer"
            value="<?php echo htmlspecialchars($jobTitle); ?>"
          />
        </div>
      </div>

      <!-- Alamat & bio -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-white" for="office_address">Alamat Kantor</label>
          <textarea
            id="office_address"
            name="office_address"
            rows="3"
            class="form-textarea w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-white/40 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="Alamat lengkap kantor atau lokasi operasional utama"><?php
              echo htmlspecialchars($officeAddress);
?></textarea>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-white" for="profile_bio">Deskripsi Singkat</label>
          <textarea
            id="profile_bio"
            name="profile_bio"
            rows="3"
            class="form-textarea w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-white/40 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="Deskripsikan fokus pekerjaan atau aktivitas pemantauan emisi Anda di sini."><?php
  echo htmlspecialchars($user['profile_bio'] ?? '');
?></textarea>
        </div>
      </div>

      <div class="flex flex-wrap justify-end gap-3 pt-2">
        <a
          href="<?php echo $baseUrl; ?>/index.php?page=dashboard"
          class="inline-flex items-center justify-center rounded-lg border border-white/20 bg-transparent px-4 h-10 text-sm font-medium text-textmuted hover:bg-white/5">
          Batal
        </a>
        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-lg bg-primary px-5 h-10 text-sm font-bold text-background-dark hover:bg-primary/90">
          Simpan Perubahan
        </button>
      </div>
    </form>
  </section>

  <!-- Ringkasan JSON (opsional, biar kelihatan kalau perlu debug) -->
  <!-- <section class="rounded-xl border border-border bg-card/60 p-4 text-xs text-textmuted">
    <p class="font-semibold text-white mb-2">Metadata JSON</p>
    <pre class="whitespace-pre-wrap break-words">
<?php echo htmlspecialchars(json_encode($metaArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
    </pre>
  </section> -->
</div>
