<?php
require_once __DIR__.'/../api/config.php';
$currentPage = 'analytics';
$baseUrl = $baseUrl ?? '';
?>

<div class="flex flex-col gap-4">
  <div class="flex items-center gap-3">
    <button class="md:hidden p-2 rounded-lg hover:bg-white/10"
            type="button"
            onclick="toggleSidebar()">
      <span class="material-symbols-outlined">menu</span>
    </button>
    <h1 class="text-3xl font-bold">Analytics</h1>
  </div>

  <p class="text-textmuted">
    Halaman analytics masih kosong, nanti diisi grafik / laporan.
  </p>
</div>
