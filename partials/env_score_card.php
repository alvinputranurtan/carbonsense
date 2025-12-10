<?php
// ====== CONFIG NILAI (bisa kamu override sebelum include) ======
$envScore = isset($envScore) ? floatval($envScore) : 85;   // 0–100
$envChange = isset($envChange) ? floatval($envChange) : 5.2;  // % perbandingan
$envLabel = $envLabel ?? 'Skor Lingkungan';
$envPeriod = $envPeriod ?? 'Periode ini';
?>

<div class="flex flex-col rounded-xl border border-border bg-card/80 p-6">
  <!-- Header -->
  <div class="flex flex-col items-center pb-2">
    <h3 class="text-base font-semibold text-white">
      <?php echo htmlspecialchars($envLabel); ?>
    </h3>
    <p class="text-xs text-textmuted">
      <?php echo htmlspecialchars($envPeriod); ?>
    </p>
  </div>

  <!-- Radial gauge -->
  <div class="flex-1 flex items-center justify-center pb-2">
    <div class="relative mx-auto aspect-square max-h-[250px] w-full max-w-[250px]">
      <svg viewBox="0 0 100 100" class="w-full h-full">
        <?php
        // hitung circumference (C = 2πr)
        $radius = 40;
$circ = 2 * M_PI * $radius;
$score = max(0, min(100, $envScore));
$offset = $circ * (1 - $score / 100.0);
?>
        <!-- background circle -->
        <circle
          cx="50" cy="50" r="<?php echo $radius; ?>"
          fill="transparent"
          stroke="rgba(15,23,42,0.6)"
          stroke-width="10"
        ></circle>

        <!-- progress circle -->
        <circle
          id="env-radial-circle"
          cx="50" cy="50" r="<?php echo $radius; ?>"
          fill="transparent"
          stroke="#13c8ec"
          stroke-width="10"
          stroke-linecap="round"
          stroke-dasharray="<?php echo $circ; ?>"
          stroke-dashoffset="<?php echo $offset; ?>"
          style="transform: rotate(-90deg); transform-origin: 50% 50%;"
        ></circle>
      </svg>

      <!-- value text -->
      <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
        <span class="text-4xl font-bold text-white">
          <?php echo number_format($score, 0); ?>
        </span>
        <span class="text-xs text-textmuted">
          / 100
        </span>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="mt-2 flex flex-col gap-1 text-xs text-textmuted">
    <?php
      $up = $envChange >= 0;
$icon = $up ? 'trending_up' : 'trending_down';
$color = $up ? '#0bda54' : '#fa5f38';
$change = ($up ? '+' : '').number_format($envChange, 1).'%';
?>
    <div class="flex items-center gap-2 leading-none font-medium text-white">
      <span class="inline-flex items-center gap-1">
        <span class="material-symbols-outlined text-sm" style="color: <?php echo $color; ?>">
          <?php echo $icon; ?>
        </span>
        <span style="color: <?php echo $color; ?>">
          Trending <?php echo $up ? 'up' : 'down'; ?> <?php echo $change; ?> periode ini
        </span>
      </span>
    </div>
    <div class="leading-none">
      Ringkasan kondisi berdasarkan rata-rata parameter kualitas udara dan gas.
    </div>
  </div>
</div>
