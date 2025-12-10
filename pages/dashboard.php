<?php
require_once __DIR__.'/../api/config.php';

// kalau file diakses lewat index.php, $currentPage & $baseUrl sudah di-set
// kalau ada yang akses langsung /pages/dashboard.php, kita kasih default
$currentPage = $currentPage ?? 'dashboard';
$baseUrl = $baseUrl ?? '';

$NODE_ID = intval($_ENV['NODE_ID'] ?? 1);

// Ambil 2 data terbaru untuk metric & persen
$sql = 'SELECT created_at, data FROM sensor_data WHERE node_id=? ORDER BY created_at DESC LIMIT 2';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $NODE_ID);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

$latestRow = $rows[0] ?? null;
$prevRow = $rows[1] ?? null;

$latest = $latestRow ? json_decode($latestRow['data'], true) : [];
$prev = $prevRow ? json_decode($prevRow['data'], true) : [];

$createdAt = $latestRow['created_at'] ?? '-';

$aqi = floatval($latest['AirQualityIndex'] ?? 0);
$gli = floatval($latest['GasLeakIndex'] ?? 0);
$co = floatval($latest['COLevel'] ?? 0);

$aqiPrev = floatval($prev['AirQualityIndex'] ?? 0);
$gliPrev = floatval($prev['GasLeakIndex'] ?? 0);
$coPrev = floatval($prev['COLevel'] ?? 0);

function calcPercent($current, $prev)
{
    if ($prev == 0) {
        return 0.0;
    }

    return ($current - $prev) / $prev * 100.0;
}

$aqiDelta = calcPercent($aqi, $aqiPrev);
$gliDelta = calcPercent($gli, $gliPrev);
$coDelta = calcPercent($co, $coPrev);

$overall = strtoupper($latest['OverallStatus'] ?? 'UNKNOWN');

$statusReport = $latest['StatusReport'] ?? [];
$vocText = $statusReport[0] ?? 'Unknown';
$gasText = $statusReport[1] ?? 'Unknown';
$coTextS = $statusReport[2] ?? 'Unknown';

function mapStatusBadge($text)
{
    $t = strtolower($text);
    if (str_contains($t, 'danger') || str_contains($t, 'leak detected')) {
        return ['Bahaya', 'bg-red-500/20 text-red-400'];
    }
    if (str_contains($t, 'warning') || str_contains($t, 'possible leak')) {
        return ['Peringatan', 'bg-yellow-500/20 text-yellow-400'];
    }
    if (str_contains($t, 'normal') || str_contains($t, 'safe') || str_contains($t, 'no gas')) {
        return ['Aman', 'bg-green-500/20 text-green-400'];
    }

    return ['Tidak Diketahui', 'bg-slate-500/20 text-slate-300'];
}

[$vocLabel, $vocClass] = mapStatusBadge($vocText);
[$gasLabel, $gasClass] = mapStatusBadge($gasText);
[$coLabel,  $coClass] = mapStatusBadge($coTextS);

$conn->close();
?>

<div class="flex flex-col gap-6">
  <!-- Header -->
  <div class="flex flex-wrap justify-between items-center gap-4">
    <div class="flex items-center gap-3">
      <!-- tombol hamburger: hanya muncul di mobile -->
      <button class="md:hidden p-2 rounded-lg hover:bg-white/10"
              type="button"
              onclick="toggleSidebar()">
        <span class="material-symbols-outlined">menu</span>
      </button>

      <div class="flex flex-col gap-2">
        <p class="text-white text-4xl font-black leading-tight tracking-[-0.033em]">
          Dashboard Pemantauan Emisi
        </p>
        <p class="text-[#92c0c9] text-base">
          Real-time emission monitoring and carbon offset data.
        </p>
      </div>
    </div>

    <div class="flex items-center gap-4">
  
      <div class="flex items-center gap-3">
        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"
          style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuB6X0_wHDenPCn4sA39nLyQ1yEHQRU3jDF78q4Hy198ipRAacYWv_7jD7Mw31QQM1RZcgTQaq4ytcvNxfgSID4BHaZo6ZN-u7po_1NGin7G0Ye_5TmpPRW6uC4ANzZ4oFGOCeHaLQsnuMu80RuGiqkSpy3NdMGcTj5rt1YP9IcqKhF9mv5x8aO2hWVVky5fTCmDJXI2cEfGmg2zRl51he7pB0hRn63Qu4PHvAyJW-F9hYfqWyXnm9vP7kHEot4ApPjbdzncWhz_QeN7");'></div>
        <div class="flex flex-col text-sm">
          <p class="text-white font-medium">Jane Doe</p>
          <p class="text-[#92c0c9]">Admin</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Overall Status -->
  <div class="@container">
    <div class="flex flex-1 flex-col items-start justify-between gap-4 rounded-lg border border-[#325e67] bg-[#111f22] p-5 @[480px]:flex-row @[480px]:items-center">
      <div class="flex items-center gap-4">
        <div class="flex items-center justify-center size-12 rounded-full 
          <?php
            if ($overall === 'DANGER') {
                echo 'bg-red-500/20';
            } elseif ($overall === 'WARNING') {
                echo 'bg-yellow-500/20';
            } else {
                echo 'bg-green-500/20';
            }
?>">
          <span class="material-symbols-outlined text-3xl
            <?php
    if ($overall === 'DANGER') {
        echo ' text-red-400';
    } elseif ($overall === 'WARNING') {
        echo ' text-yellow-400';
    } else {
        echo ' text-green-400';
    }
?>">
            health_and_safety
          </span>
        </div>
        <div class="flex flex-col gap-1">
          <p class="text-white text-base font-bold leading-tight">
            OVERALL STATUS: <?php echo htmlspecialchars($overall); ?>
          </p>
          <p class="text-[#92c0c9] text-base">
            Last update: <?php echo htmlspecialchars($createdAt); ?>
          </p>
        </div>
      </div>
      <a class="text-sm font-bold tracking-[0.015em] flex gap-2 text-white items-center hover:text-primary"
        href="<?php echo $baseUrl; ?>/index.php?page=alerts">
        View All Alerts
        <span class="material-symbols-outlined text-lg">arrow_forward</span>
      </a>
    </div>
  </div>

  <!-- Metric Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <!-- AQI -->
    <div class="flex flex-col gap-2 rounded-lg p-6 border border-[#325e67] bg-[#111f22]">
      <p class="text-white text-base font-medium">Air Quality Index (AQI)</p>
      <p class="text-primary text-4xl font-bold leading-tight">
        <?php echo number_format($aqi, 2); ?>
      </p>
      <div class="flex items-center gap-1">
        <?php
          $aqiIcon = $aqiDelta >= 0 ? 'trending_up' : 'trending_down';
$aqiColor = $aqiDelta >= 0 ? '#0bda54' : '#fa5f38';
?>
        <span class="material-symbols-outlined" style="color: <?php echo $aqiColor; ?>;">
          <?php echo $aqiIcon; ?>
        </span>
        <p class="text-base font-medium" style="color: <?php echo $aqiColor; ?>;">
          <?php echo sprintf('%+.1f%%', $aqiDelta); ?>
        </p>
      </div>
    </div>

    <!-- GLI -->
    <div class="flex flex-col gap-2 rounded-lg p-6 border border-[#325e67] bg-[#111f22]">
      <p class="text-white text-base font-medium">Gas Leak Index</p>
      <p class="text-white text-4xl font-bold leading-tight">
        <?php echo number_format($gli, 2); ?>
      </p>
      <div class="flex items-center gap-1">
        <?php
  $gliIcon = $gliDelta >= 0 ? 'trending_up' : 'trending_down';
$gliColor = $gliDelta >= 0 ? '#fa5f38' : '#0bda54';
?>
        <span class="material-symbols-outlined" style="color: <?php echo $gliColor; ?>;">
          <?php echo $gliIcon; ?>
        </span>
        <p class="text-base font-medium" style="color: <?php echo $gliColor; ?>;">
          <?php echo sprintf('%+.1f%%', $gliDelta); ?>
        </p>
      </div>
    </div>

    <!-- CO -->
    <div class="flex flex-col gap-2 rounded-lg p-6 border border-[#325e67] bg-[#111f22]">
      <p class="text-white text-base font-medium">CO Level (ppm)</p>
      <p class="text-white text-4xl font-bold leading-tight">
        <?php echo number_format($co, 4); ?>
      </p>
      <div class="flex items-center gap-1">
        <?php
  $coIcon = $coDelta >= 0 ? 'trending_up' : 'trending_down';
$coColor = $coDelta >= 0 ? '#0bda54' : '#fa5f38';
?>
        <span class="material-symbols-outlined" style="color: <?php echo $coColor; ?>;">
          <?php echo $coIcon; ?>
        </span>
        <p class="text-base font-medium" style="color: <?php echo $coColor; ?>;">
          <?php echo sprintf('%+.1f%%', $coDelta); ?>
        </p>
      </div>
    </div>
  </div>

  <!-- Emission Trends + Status Panel -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Emission Trends -->
    <div class="lg:col-span-2 flex flex-col gap-2 rounded-lg border border-[#325e67] bg-[#111f22] p-6">
      <div class="flex justify-between items-start gap-4">
        <div class="flex flex-col">
          <p class="text-white text-base font-medium">Emission Trends</p>
          <div class="flex items-baseline gap-4">
            <p id="trend-main-value" class="text-white text-[32px] font-bold leading-tight truncate">
              <?php echo number_format($aqi, 2); ?> AQI
            </p>
            <div class="flex gap-1 items-center">
              <p class="text-[#92c0c9] text-base">Last 24 Hours</p>
              <p id="trend-main-delta" class="text-[#0bda54] text-base font-medium">
                <?php echo sprintf('%+.1f%%', $aqiDelta); ?>
              </p>
            </div>
          </div>
        </div>
        <!-- Dropdown -->
        <div class="relative">
          <select id="trend-select"
            class="appearance-none bg-[#234248] border border-[#325e67] text-white text-sm rounded-lg focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2">
            <option value="AQI" selected>Air Quality Index (AQI)</option>
            <option value="GLI">Gas Leak Index (GLI)</option>
            <option value="CO">CO Level (ppm)</option>
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
            <span class="material-symbols-outlined text-lg">expand_more</span>
          </div>
        </div>
      </div>

      <div class="flex min-h-[180px] flex-1 flex-col gap-4 py-4">
        <!-- SVG Chart -->
        <svg id="trend-svg" fill="none" height="100%" width="100%"
          viewBox="0 0 480 150" preserveAspectRatio="none"
          xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
              <stop id="grad-stop-1" offset="0%" stop-color="#13c8ec" stop-opacity="0.3"></stop>
              <stop id="grad-stop-2" offset="100%" stop-color="#13c8ec" stop-opacity="0"></stop>
            </linearGradient>
          </defs>
          <path id="trend-fill" d="" fill="url(#trendGradient)"></path>
          <path id="trend-stroke" d="" stroke="#13c8ec" stroke-width="3" stroke-linecap="round"></path>
        </svg>

        <div id="trend-labels" class="flex justify-between">
          <!-- labels jam akan diisi JS -->
        </div>
      </div>
    </div>

    <!-- Status Laporan -->
    <div class="flex flex-col gap-4 rounded-lg border border-[#325e67] bg-[#111f22] p-6">
      <h3 class="text-white text-base font-medium">Status Laporan &amp; Peringatan</h3>
      <div class="flex flex-col gap-4">
        <div class="flex justify-between items-center">
          <p class="text-white">Status AQI</p>
          <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium <?php echo $vocClass; ?>">
            <?php echo htmlspecialchars($vocLabel); ?>
          </span>
        </div>
        <div class="w-full h-px bg-[#325e67]"></div>
        <div class="flex justify-between items-center">
          <p class="text-white">Status Kebocoran Gas</p>
          <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium <?php echo $gasClass; ?>">
            <?php echo htmlspecialchars($gasLabel); ?>
          </span>
        </div>
        <div class="w-full h-px bg-[#325e67]"></div>
        <div class="flex justify-between items-center">
          <p class="text-white">Status CO</p>
          <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium <?php echo $coClass; ?>">
            <?php echo htmlspecialchars($coLabel); ?>
          </span>
        </div>
        <div class="w-full h-px bg-[#325e67]"></div>
        <div class="flex justify-between items-center">
          <p class="text-white">Overall Status</p>
          <?php
    $ovClass = 'bg-green-500/20 text-green-400';
if ($overall === 'DANGER') {
    $ovClass = 'bg-red-500/20 text-red-400';
} elseif ($overall === 'WARNING') {
    $ovClass = 'bg-yellow-500/20 text-yellow-400';
}
?>
          <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium <?php echo $ovClass; ?>">
            <?php echo htmlspecialchars($overall); ?>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// warna per parameter
const TREND_COLORS = {
  AQI: { stroke: "#13c8ec", fill: "#13c8ec" },
  GLI: { stroke: "#ffaa55", fill: "#ffaa55" },
  CO:  { stroke: "#32ff95", fill: "#32ff95" }
};

const trendSelect     = document.getElementById('trend-select');
const svgStroke       = document.getElementById('trend-stroke');
const svgFill         = document.getElementById('trend-fill');
const gradStop1       = document.getElementById('grad-stop-1');
const gradStop2       = document.getElementById('grad-stop-2');
const labelsDiv       = document.getElementById('trend-labels');
const trendMainValue  = document.getElementById('trend-main-value');
const trendMainDelta  = document.getElementById('trend-main-delta');

function buildSmoothPath(values, width, height, padding) {
  const n = values.length;
  if (n === 0) return { strokePath: "", fillPath: "" };

  let min = Math.min(...values);
  let max = Math.max(...values);
  if (min === max) {
    min -= 1;
    max += 1;
  }
  const innerH = height - padding * 2;
  const range  = max - min;

  const xs = [];
  const ys = [];

  const stepX = n > 1 ? width / (n - 1) : 0;

  for (let i = 0; i < n; i++) {
    const x = stepX * i;
    const norm = (values[i] - min) / range;
    const y = height - padding - norm * innerH;
    xs.push(x);
    ys.push(y);
  }

  if (n === 1) {
    const x = width / 2;
    const y = ys[0];
    const strokePath = `M ${x} ${y}`;
    const fillPath   = `M 0 ${height - padding} L ${x} ${y} L ${width} ${height - padding} Z`;
    return { strokePath, fillPath };
  }

  let d = `M ${xs[0]} ${ys[0]}`;
  for (let i = 0; i < n - 1; i++) {
    const x_mid = (xs[i] + xs[i+1]) / 2;
    d += ` Q ${x_mid} ${ys[i]}, ${xs[i+1]} ${ys[i+1]}`;
  }

  let fill = d;
  fill += ` L ${xs[n-1]} ${height - padding}`;
  fill += ` L ${xs[0]} ${height - padding} Z`;

  return { strokePath: d, fillPath: fill };
}

async function loadTrend(param) {
  try {
    const res = await fetch(`${BASE_URL}/api/trend.php?param=${encodeURIComponent(param)}`);
    const data = await res.json();
    if (!data || data.error) {
      console.error(data?.error || 'Unknown error');
      svgStroke.setAttribute('d', '');
      svgFill.setAttribute('d', '');
      labelsDiv.innerHTML = '';
      return;
    }

    const labels = data.labels || [];
    const values = (data.values || []).map(parseFloat);

    const width   = 480;
    const height  = 150;
    const padding = 10;

    const { strokePath, fillPath } = buildSmoothPath(values, width, height, padding);
    svgStroke.setAttribute('d', strokePath);
    svgFill.setAttribute('d', fillPath);

    const c = TREND_COLORS[param] || TREND_COLORS.AQI;
    svgStroke.setAttribute('stroke', c.stroke);
    gradStop1.setAttribute('stop-color', c.fill);
    gradStop2.setAttribute('stop-color', c.fill);

    // update label bawah (max 7 titik)
    labelsDiv.innerHTML = '';
    if (labels.length > 0) {
      const count = Math.min(7, labels.length);
      for (let i = 0; i < count; i++) {
        const idx = Math.round(i * (labels.length - 1) / (count - 1 || 1));
        const p = document.createElement('p');
        p.className = "text-[#92c0c9] text-[13px] font-bold leading-normal tracking-[0.015em]";
        p.textContent = labels[idx];
        labelsDiv.appendChild(p);
      }
    }

    // update teks utama (value + delta)
    if (values.length > 0) {
      const latestVal = values[values.length - 1];
      const prevVal   = values.length > 1 ? values[values.length - 2] : latestVal;
      const delta     = prevVal !== 0 ? ((latestVal - prevVal) / prevVal * 100.0) : 0;

      let unit = "";
      if (param === "AQI") unit = " AQI";
      if (param === "GLI") unit = " GLI";
      if (param === "CO")  unit = " ppm";

      trendMainValue.textContent = `${latestVal.toFixed(2)}${unit}`;
      trendMainDelta.textContent = `${delta >= 0 ? '+' : ''}${delta.toFixed(1)}%`;
      trendMainDelta.style.color = delta >= 0 ? "#0bda54" : "#fa5f38";
    }

  } catch (err) {
    console.error(err);
  }
}

// on load
document.addEventListener('DOMContentLoaded', () => {
  loadTrend('AQI');
  if (trendSelect) {
    trendSelect.addEventListener('change', () => {
      const param = trendSelect.value;
      loadTrend(param);
    });
  }
});
</script>
