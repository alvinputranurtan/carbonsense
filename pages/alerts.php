<?php
// pages/alerts.php

require_once __DIR__.'/../api/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// routing/layout
$currentPage = $currentPage ?? 'alerts';
$baseUrl = $baseUrl ?? '';

$NODE_ID = intval($_ENV['NODE_ID'] ?? 1);

// range waktu dari query
$range = $_GET['range'] ?? '7d';

// mapping label
$rangeLabel = [
    '24h' => '24 Jam Terakhir',
    '7d' => '7 Hari Terakhir',
    '30d' => '30 Hari Terakhir',
    'all' => 'Seluruh Data',
];
$currentRangeLabel = $rangeLabel[$range] ?? $rangeLabel['7d'];

// bangun WHERE tambahan untuk range
$rangeWhere = '';
if ($range === '24h') {
    $rangeWhere = 'AND created_at >= NOW() - INTERVAL 24 HOUR';
} elseif ($range === '7d') {
    $rangeWhere = 'AND created_at >= NOW() - INTERVAL 7 DAY';
} elseif ($range === '30d') {
    $rangeWhere = 'AND created_at >= NOW() - INTERVAL 30 DAY';
}

// helper: cek apakah baris ini alert
function row_has_alert($overall, $statusReport)
{
    $tOverall = strtolower($overall);

    // cuma ambil WARNING atau DANGER
    if ($overall === 'DANGER' || $overall === 'WARNING') {
        return true;
    }

    // cek di status report apakah ada kata danger/warning
    if (is_array($statusReport)) {
        foreach ($statusReport as $msg) {
            $t = strtolower($msg);
            if (str_contains($t, 'danger') || str_contains($t, 'warning')) {
                return true;
            }
        }
    }

    return false;
}

// helper: badge severity
function map_severity_badge($overall, $statusReport)
{
    $tOverall = strtolower($overall);

    if ($overall === 'DANGER' || str_contains($tOverall, 'danger')) {
        return ['Bahaya', 'bg-red-500/20 text-red-400'];
    }

    if ($overall === 'WARNING' || str_contains($tOverall, 'warning')) {
        return ['Peringatan', 'bg-yellow-500/20 text-yellow-300'];
    }

    // kalau bukan dua ini, abaikan (tidak ditampilkan)
    return [null, null];
}

// ambil data dari DB
$sql = "
    SELECT created_at, data
    FROM sensor_data
    WHERE node_id = ?
    $rangeWhere
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $NODE_ID);
$stmt->execute();
$res = $stmt->get_result();

$alerts = [];
$totalDanger = 0;
$totalWarning = 0;

while ($row = $res->fetch_assoc()) {
    $d = json_decode($row['data'], true) ?: [];

    $overall = strtoupper($d['OverallStatus'] ?? 'UNKNOWN');
    $statusReport = $d['StatusReport'] ?? [];

    if (!row_has_alert($overall, $statusReport)) {
        continue; // hanya simpan yang alert
    }

    $aqi = isset($d['AirQualityIndex']) ? floatval($d['AirQualityIndex']) : null;
    $gli = isset($d['GasLeakIndex']) ? floatval($d['GasLeakIndex']) : null;
    $co = isset($d['COLevel']) ? floatval($d['COLevel']) : null;

    $vocText = $statusReport[0] ?? null;
    $gasText = $statusReport[1] ?? null;
    $coText = $statusReport[2] ?? null;

    [$sevLabel, $sevClass] = map_severity_badge($overall, $statusReport);

    if ($sevLabel === 'Bahaya') {
        ++$totalDanger;
    } elseif ($sevLabel === 'Peringatan') {
        ++$totalWarning;
    }

    $alerts[] = [
        'time' => $row['created_at'],
        'overall' => $overall,
        'aqi' => $aqi,
        'gli' => $gli,
        'co' => $co,
        'vocText' => $vocText,
        'gasText' => $gasText,
        'coText' => $coText,
        'sevLabel' => $sevLabel,
        'sevClass' => $sevClass,
    ];

    [$sevLabel, $sevClass] = map_severity_badge($overall, $statusReport);

    // skip kalau bukan warning atau danger
    if ($sevLabel === null) {
        continue;
    }
}

$totalAlerts = count($alerts);
$stmt->close();
?>
<div class="flex min-h-screen">


  <!-- MAIN -->
  <main class="flex-1 p-6 lg:p-8">
    <div class="w-full max-w-6xl mx-auto flex flex-col gap-6">
      <!-- header -->
      <div class="flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-3">
          <!-- hamburger mobile -->
          <button class="md:hidden p-2 rounded-lg hover:bg-white/10"
                  type="button"
                  onclick="toggleSidebar()">
            <span class="material-symbols-outlined">menu</span>
          </button>
          <div class="flex flex-col gap-1">
            <p class="text-white text-4xl font-black leading-tight tracking-[-0.033em]">
              Riwayat Peringatan &amp; Insiden
            </p>
            <p class="text-textmuted text-base">
              Daftar lengkap semua status <span class="font-semibold">WARNING</span> dan <span class="font-semibold">DANGER</span> dari sensor.
            </p>
          </div>
        </div>

        <!-- segmented filter range -->
        <div class="flex">
          <div class="flex h-10 items-center justify-center rounded-lg bg-[#234248] p-1">
            <?php
              $ranges = [
                  '24h' => '24 Jam',
                  '7d' => '7 Hari',
                  '30d' => '30 Hari',
                  'all' => 'Semua',
              ];
foreach ($ranges as $key => $label) {
    $checked = ($range === $key) ? 'checked' : '';
    echo '
                  <label class="flex cursor-pointer h-full items-center justify-center overflow-hidden rounded-md px-3
                                has-[:checked]:bg-[#111f22] has-[:checked]:text-white text-[#92c0c9] text-xs sm:text-sm font-medium">
                    <span class="truncate">'.$label.'</span>
                    <input
                      type="radio"
                      class="invisible w-0"
                      name="alert-range"
                      value="'.$key.'"
                      '.$checked.'
                    />
                  </label>
                  ';
}
?>
          </div>
        </div>
      </div>

      <!-- summary cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="flex flex-col gap-1 rounded-lg border border-border bg-card/80 p-5">
          <p class="text-white/80 text-sm font-medium">Total Alerts</p>
          <p class="text-white text-3xl font-bold leading-tight"><?php echo $totalAlerts; ?></p>
          <p class="text-textmuted text-xs">Periode: <?php echo htmlspecialchars($currentRangeLabel); ?></p>
        </div>

        <div class="flex flex-col gap-1 rounded-lg border border-border bg-card/80 p-5">
          <p class="text-white/80 text-sm font-medium">Danger Events</p>
          <p class="text-red-400 text-3xl font-bold leading-tight"><?php echo $totalDanger; ?></p>
          <p class="text-textmuted text-xs">Status yang diklasifikasikan sebagai "Bahaya".</p>
        </div>

        <div class="flex flex-col gap-1 rounded-lg border border-border bg-card/80 p-5">
          <p class="text-white/80 text-sm font-medium">Warning Events</p>
          <p class="text-yellow-300 text-3xl font-bold leading-tight"><?php echo $totalWarning; ?></p>
          <p class="text-textmuted text-xs">Status peringatan sebelum kondisi bahaya.</p>
        </div>
      </div>

      <!-- list alerts -->
      <div class="flex flex-col gap-3">
        <h2 class="text-white text-[20px] font-bold tracking-[-0.02em]">
          Detail Peringatan
        </h2>

        <div class="overflow-hidden rounded-xl border border-border bg-card/80">
          <?php if ($totalAlerts === 0) { ?>
            <div class="p-6 text-sm text-textmuted text-center">
              Tidak ada data WARNING atau DANGER pada periode ini.
            </div>
          <?php } else { ?>
            <div class="max-h-[520px] overflow-y-auto divide-y divide-[#1f3940]">
              <?php foreach ($alerts as $alert) { ?>
                <?php
      $timeObj = new DateTime($alert['time']);
                  $timeStr = $timeObj->format('Y-m-d H:i:s');
                  $dateStr = $timeObj->format('d M Y');
                  $clock = $timeObj->format('H:i');
                  $summaryParts = [];
                  if ($alert['vocText']) {
                      $summaryParts[] = $alert['vocText'];
                  }
                  if ($alert['gasText']) {
                      $summaryParts[] = $alert['gasText'];
                  }
                  if ($alert['coText']) {
                      $summaryParts[] = $alert['coText'];
                  }
                  $summary = implode(' · ', $summaryParts);
                  ?>
                <div class="flex gap-4 p-4 sm:p-5">
                  <!-- timeline bullet -->
                  <div class="flex flex-col items-center">
                    <div class="mt-1 h-4 w-4 rounded-full border-2 border-[#13c8ec] bg-[#13c8ec]/20"></div>
                    <div class="flex-1 w-px bg-[#234248] mt-1"></div>
                  </div>

                  <!-- content -->
                  <div class="flex-1 flex flex-col gap-2">
                    <div class="flex flex-wrap justify-between gap-2 items-center">
                      <div class="flex flex-col">
                        <p class="text-white text-sm sm:text-base font-semibold">
                          <?php echo htmlspecialchars($alert['overall']); ?>
                        </p>
                        <p class="text-textmuted text-xs">
                          <?php echo htmlspecialchars($dateStr); ?> · <?php echo htmlspecialchars($clock); ?>
                        </p>
                      </div>
                      <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold <?php echo $alert['sevClass']; ?>">
                        <?php echo htmlspecialchars($alert['sevLabel']); ?>
                      </span>
                    </div>

                    <?php if ($summary !== '') { ?>
                      <p class="text-xs sm:text-sm text-white/80">
                        <?php echo htmlspecialchars($summary); ?>
                      </p>
                    <?php } ?>

                    <div class="grid grid-cols-3 gap-2 text-[11px] sm:text-xs text-textmuted mt-1">
                      <div>
                        <p class="uppercase tracking-[0.08em] text-[10px] text-textmuted/80">AQI</p>
                        <p class="text-white text-sm">
                          <?php echo $alert['aqi'] !== null ? number_format($alert['aqi'], 2) : '-'; ?>
                        </p>
                      </div>
                      <div>
                        <p class="uppercase tracking-[0.08em] text-[10px] text-textmuted/80">GLI</p>
                        <p class="text-white text-sm">
                          <?php echo $alert['gli'] !== null ? number_format($alert['gli'], 2) : '-'; ?>
                        </p>
                      </div>
                      <div>
                        <p class="uppercase tracking-[0.08em] text-[10px] text-textmuted/80">CO (ppm)</p>
                        <p class="text-white text-sm">
                          <?php echo $alert['co'] !== null ? number_format($alert['co'], 4) : '-'; ?>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              <?php } ?>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
// ganti range via radio button (tanpa form)
document.querySelectorAll('input[name="alert-range"]').forEach(radio => {
  radio.addEventListener('change', () => {
    const value = radio.value || '7d';
    const url = new URL(window.location.href);
    url.searchParams.set('page', 'alerts');
    url.searchParams.set('range', value);
    window.location.href = url.toString();
  });
});
</script>
