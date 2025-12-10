<?php
require_once __DIR__.'/../api/config.php';

// kalau dipanggil lewat index.php, variabel sudah ada
$currentPage = $currentPage ?? 'analytics';
$baseUrl = $baseUrl ?? '';
?>
<div class="flex h-full">

  <!-- MAIN -->
  <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
    <div class="w-full max-w-7xl mx-auto flex flex-col gap-6">
      <!-- Header + timeframe -->
      <div class="flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-3">
          <!-- hamburger (mobile) -->
          <button class="md:hidden p-2 rounded-lg hover:bg-white/10"
                  type="button"
                  onclick="toggleSidebar()">
            <span class="material-symbols-outlined">menu</span>
          </button>

          <div class="flex flex-col gap-2">
            <p class="text-white text-4xl font-black leading-tight tracking-[-0.033em]">
              Halaman Analitik Data Emisi
            </p>
            <p class="text-textmuted text-base">
              Analisis lengkap untuk setiap parameter kualitas udara dan gas.
            </p>
          </div>
        </div>

        <!-- Segmented buttons timeframe -->
        <div class="flex">
          <div class="flex h-10 items-center justify-center rounded-lg bg-[#234248] p-1">
            <label class="flex cursor-pointer h-full grow items-center justify-center overflow-hidden rounded-md px-4 has-[:checked]:bg-[#111f22] has-[:checked]:shadow-[0_0_4px_rgba(0,0,0,0.1)] has-[:checked]:text-white text-[#92c0c9] text-sm font-medium leading-normal">
              <span class="truncate">Mingguan</span>
              <input class="invisible w-0" name="timeframe" type="radio" value="weekly">
            </label>
            <label class="flex cursor-pointer h-full grow items-center justify-center overflow-hidden rounded-md px-4 has-[:checked]:bg-[#111f22] has-[:checked]:shadow-[0_0_4px_rgba(0,0,0,0.1)] has-[:checked]:text-white text-[#92c0c9] text-sm font-medium leading-normal">
              <span class="truncate">Bulanan</span>
              <input checked class="invisible w-0" name="timeframe" type="radio" value="monthly">
            </label>
            <label class="flex cursor-pointer h-full grow items-center justify-center overflow-hidden rounded-md px-4 has-[:checked]:bg-[#111f22] has-[:checked]:shadow-[0_0_4px_rgba(0,0,0,0.1)] has-[:checked]:text-white text-[#92c0c9] text-sm font-medium leading-normal">
              <span class="truncate">Tahunan</span>
              <input class="invisible w-0" name="timeframe" type="radio" value="yearly">
            </label>
          </div>
        </div>
      </div>

      <!-- Kartu ringkasan -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="flex flex-col gap-2 rounded-xl p-5 border border-border bg-card/70">
          <p class="text-white/80 text-sm font-medium">Rata-rata AQI</p>
          <p id="stat-avg-aqi" class="text-white text-3xl font-bold leading-tight">-</p>
          <p id="stat-change-aqi"
             class="text-[#92c0c9] text-sm font-medium flex items-center gap-1">
            <span class="material-symbols-outlined text-base">trending_flat</span>
            <span>Tidak ada perubahan</span>
          </p>
        </div>

        <div class="flex flex-col gap-2 rounded-xl p-5 border border-border bg-card/70">
          <p class="text-white/80 text-sm font-medium">Rata-rata GLI</p>
          <p id="stat-avg-gli" class="text-white text-3xl font-bold leading-tight">-</p>
          <p id="stat-change-gli"
             class="text-[#92c0c9] text-sm font-medium flex items-center gap-1">
            <span class="material-symbols-outlined text-base">trending_flat</span>
            <span>Tidak ada perubahan</span>
          </p>
        </div>

        <div class="flex flex-col gap-2 rounded-xl p-5 border border-border bg-card/70">
          <p class="text-white/80 text-sm font-medium">Rata-rata CO</p>
          <p id="stat-avg-co" class="text-white text-3xl font-bold leading-tight">-</p>
          <p id="stat-change-co"
             class="text-[#92c0c9] text-sm font-medium flex items-center gap-1">
            <span class="material-symbols-outlined text-base">trending_flat</span>
            <span>Tidak ada perubahan</span>
          </p>
        </div>

        <div class="flex flex-col gap-2 rounded-xl p-5 border border-border bg-card/70">
          <p class="text-white/80 text-sm font-medium">Jumlah Warning</p>
          <p id="stat-total-warn" class="text-white text-3xl font-bold leading-tight">0</p>
          <p class="text-textmuted text-sm">
            Total semua parameter dalam periode terpilih.
          </p>
        </div>
      </div>

      <!-- Environment Score (radial shadcn-style) -->
      <div class="flex flex-col rounded-xl border border-border bg-card/80 p-6">
        <div class="flex flex-col items-center pb-2">
          <h3 class="text-base font-semibold text-white">
            Skor Lingkungan
          </h3>
          <p id="env-period" class="text-xs text-textmuted">
            Periode Bulanan
          </p>
        </div>

        <div class="flex-1 flex flex-col md:flex-row items-center justify-center gap-6">
          <!-- radial gauge -->
          <div class="flex-1 flex items-center justify-center">
            <div class="relative mx-auto aspect-square max-h-[250px] w-full max-w-[250px]">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <!-- background -->
                <circle
                  cx="50" cy="50" r="40"
                  fill="transparent"
                  stroke="rgba(15,23,42,0.6)"
                  stroke-width="10"
                ></circle>

                <!-- progress -->
                <circle
                  id="env-circle"
                  cx="50" cy="50" r="40"
                  fill="transparent"
                  stroke="#13c8ec"
                  stroke-width="10"
                  stroke-linecap="round"
                  stroke-dasharray="251.327"
                  stroke-dashoffset="251.327"
                  style="transform: rotate(-90deg); transform-origin: 50% 50%; transition: stroke-dashoffset 0.6s ease-out;"
                ></circle>
              </svg>

              <!-- value -->
              <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                <span id="env-score-value" class="text-4xl font-bold text-white">-</span>
                <span class="text-xs text-textmuted">/ 100</span>
              </div>
            </div>
          </div>

          <!-- text -->
          <div class="flex-1 flex flex-col gap-3 text-xs sm:text-sm">
            <p id="env-score-text" class="text-white/80">
              Menunggu data untuk menghitung skor lingkungan...
            </p>
            <ul class="list-disc list-inside text-textmuted space-y-1">
              <li>Skor 80–100: kondisi lingkungan sangat baik.</li>
              <li>Skor 60–79: kondisi cukup baik, tetap dipantau.</li>
              <li>Skor &lt; 60: perlu perhatian, cek sumber emisi dan kebocoran gas.</li>
            </ul>
          </div>
        </div>

        <div class="mt-3 flex flex-col gap-1 text-xs text-textmuted">
          <div class="flex items-center gap-2 leading-none font-medium text-white">
            <span id="env-trend-icon-wrapper" class="inline-flex items-center gap-1">
              <span id="env-trend-icon" class="material-symbols-outlined text-sm" style="color:#92c0c9">
                trending_flat
              </span>
              <span id="env-trend-text" style="color:#92c0c9">
                Tren stabil, tidak ada perubahan signifikan.
              </span>
            </span>
          </div>
          <div class="leading-none">
            Ringkasan dihitung dari rata-rata AQI, GLI, dan CO pada periode terpilih.
          </div>
        </div>
      </div>

      <!-- 3 grafik parameter -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- AQI -->
        <section class="flex flex-col gap-3 rounded-xl border border-border bg-card/70 p-5">
          <div class="flex items-center justify-between gap-2">
            <div>
              <p class="text-white text-lg font-medium">Grafik AQI</p>
              <p class="text-textmuted text-xs">Tren Air Quality Index</p>
            </div>
            <span id="badge-aqi"
                  class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-500/15 text-green-400">
              Normal
            </span>
          </div>

          <div class="flex-1 min-h-[180px] pt-2">
            <svg id="svg-aqi" fill="none" height="100%" width="100%"
              viewBox="0 0 480 150" preserveAspectRatio="none"
              xmlns="http://www.w3.org/2000/svg">
              <defs>
                <linearGradient id="grad-aqi" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#13c8ec" stop-opacity="0.3"></stop>
                  <stop offset="100%" stop-color="#13c8ec" stop-opacity="0"></stop>
                </linearGradient>
              </defs>
              <path id="fill-aqi" d="" fill="url(#grad-aqi)"></path>
              <path id="stroke-aqi" d="" stroke="#13c8ec" stroke-width="3" stroke-linecap="round"></path>
              <g id="points-aqi"></g>
            </svg>
          </div>

          <div id="labels-aqi" class="flex justify-between text-[11px] text-textmuted font-medium pt-1"></div>
          <div id="warnings-aqi" class="mt-1 text-xs text-yellow-300 space-y-1"></div>
        </section>

        <!-- GLI -->
        <section class="flex flex-col gap-3 rounded-xl border border-border bg-card/70 p-5">
          <div class="flex items-center justify-between gap-2">
            <div>
              <p class="text-white text-lg font-medium">Grafik GLI</p>
              <p class="text-textmuted text-xs">Tren Gas Leak Index</p>
            </div>
            <span id="badge-gli"
                  class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-500/15 text-green-400">
              Normal
            </span>
          </div>

          <div class="flex-1 min-h-[180px] pt-2">
            <svg id="svg-gli" fill="none" height="100%" width="100%"
              viewBox="0 0 480 150" preserveAspectRatio="none"
              xmlns="http://www.w3.org/2000/svg">
              <defs>
                <linearGradient id="grad-gli" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#ffaa55" stop-opacity="0.3"></stop>
                  <stop offset="100%" stop-color="#ffaa55" stop-opacity="0"></stop>
                </linearGradient>
              </defs>
              <path id="fill-gli" d="" fill="url(#grad-gli)"></path>
              <path id="stroke-gli" d="" stroke="#ffaa55" stroke-width="3" stroke-linecap="round"></path>
              <g id="points-gli"></g>
            </svg>
          </div>

          <div id="labels-gli" class="flex justify-between text-[11px] text-textmuted font-medium pt-1"></div>
          <div id="warnings-gli" class="mt-1 text-xs text-yellow-300 space-y-1"></div>
        </section>

        <!-- CO -->
        <section class="flex flex-col gap-3 rounded-xl border border-border bg-card/70 p-5">
          <div class="flex items-center justify-between gap-2">
            <div>
              <p class="text-white text-lg font-medium">Grafik CO</p>
              <p class="text-textmuted text-xs">Tren konsentrasi CO (ppm)</p>
            </div>
            <span id="badge-co"
                  class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-500/15 text-green-400">
              Normal
            </span>
          </div>

          <div class="flex-1 min-h-[180px] pt-2">
            <svg id="svg-co" fill="none" height="100%" width="100%"
              viewBox="0 0 480 150" preserveAspectRatio="none"
              xmlns="http://www.w3.org/2000/svg">
              <defs>
                <linearGradient id="grad-co" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#32ff95" stop-opacity="0.3"></stop>
                  <stop offset="100%" stop-color="#32ff95" stop-opacity="0"></stop>
                </linearGradient>
              </defs>
              <path id="fill-co" d="" fill="url(#grad-co)"></path>
              <path id="stroke-co" d="" stroke="#32ff95" stroke-width="3" stroke-linecap="round"></path>
              <g id="points-co"></g>
            </svg>
          </div>

          <div id="labels-co" class="flex justify-between text-[11px] text-textmuted font-medium pt-1"></div>
          <div id="warnings-co" class="mt-1 text-xs text-yellow-300 space-y-1"></div>
        </section>
      </div>
    </div>
  </main>
</div>

<script>
// ================== KONFIGURASI ==================
const ANALYTIC_CONFIG = {
  AQI: {
    strokeId: 'stroke-aqi',
    fillId: 'fill-aqi',
    pointsGroupId: 'points-aqi',
    labelsId: 'labels-aqi',
    warningsId: 'warnings-aqi',
    badgeId: 'badge-aqi',
    avgId: 'stat-avg-aqi',
    changeId: 'stat-change-aqi',
    strokeColor: '#13c8ec',
    threshold: 100,
    unit: ' AQI'
  },
  GLI: {
    strokeId: 'stroke-gli',
    fillId: 'fill-gli',
    pointsGroupId: 'points-gli',
    labelsId: 'labels-gli',
    warningsId: 'warnings-gli',
    badgeId: 'badge-gli',
    avgId: 'stat-avg-gli',
    changeId: 'stat-change-gli',
    strokeColor: '#ffaa55',
    threshold: 0.7,
    unit: ' GLI'
  },
  CO: {
    strokeId: 'stroke-co',
    fillId: 'fill-co',
    pointsGroupId: 'points-co',
    labelsId: 'labels-co',
    warningsId: 'warnings-co',
    badgeId: 'badge-co',
    avgId: 'stat-avg-co',
    changeId: 'stat-change-co',
    strokeColor: '#32ff95',
    threshold: 50,
    unit: ' ppm'
  }
};

let totalWarningsAll = 0;
const envAverages = { AQI: null, GLI: null, CO: null };

// ================== HELPER GRAFIK ==================
function buildSmoothPathWithPoints(values, width, height, padding) {
  const n = values.length;
  if (n === 0) return { strokePath: "", fillPath: "", points: [] };

  let min = Math.min(...values);
  let max = Math.max(...values);
  if (min === max) { min -= 1; max += 1; }

  const innerH = height - padding * 2;
  const range  = max - min;

  const xs = [];
  const ys = [];
  const stepX = n > 1 ? width / (n - 1) : 0;

  for (let i = 0; i < n; i++) {
    const x    = n === 1 ? width / 2 : stepX * i;
    const norm = (values[i] - min) / range;
    const y    = height - padding - norm * innerH;
    xs.push(x); ys.push(y);
  }

  if (n === 1) {
    const x = xs[0], y = ys[0];
    const strokePath = `M ${x} ${y}`;
    const fillPath   = `M 0 ${height - padding} L ${x} ${y} L ${width} ${height - padding} Z`;
    return { strokePath, fillPath, points: [{x,y,value:values[0],index:0}] };
  }

  let d = `M ${xs[0]} ${ys[0]}`;
  for (let i = 0; i < n - 1; i++) {
    const x_mid = (xs[i] + xs[i+1]) / 2;
    d += ` Q ${x_mid} ${ys[i]}, ${xs[i+1]} ${ys[i+1]}`;
  }

  let fill = d;
  fill += ` L ${xs[n-1]} ${height - padding}`;
  fill += ` L ${xs[0]} ${height - padding} Z`;

  const points = xs.map((x, i) => ({ x, y: ys[i], value: values[i], index: i }));
  return { strokePath: d, fillPath: fill, points };
}

// ================== ENVIRONMENT SCORE ==================
function updateEnvScore(timeframeLabel) {
  const a = envAverages.AQI;
  const g = envAverages.GLI;
  const c = envAverages.CO;

  if (a == null || g == null || c == null) {
    return; // belum lengkap datanya
  }

  function subScore(avg, threshold) {
    if (!threshold || threshold <= 0) return 0;
    const r = 1 - (avg / threshold);
    const clamped = Math.max(0, Math.min(1, r));
    return clamped * 100;
  }

  const sA = subScore(a, ANALYTIC_CONFIG.AQI.threshold);
  const sG = subScore(g, ANALYTIC_CONFIG.GLI.threshold);
  const sC = subScore(c, ANALYTIC_CONFIG.CO.threshold);

  const envScore = Math.round((sA + sG + sC) / 3);

  const circle = document.getElementById('env-circle');
  const valEl  = document.getElementById('env-score-value');
  const textEl = document.getElementById('env-score-text');
  const trendIcon = document.getElementById('env-trend-icon');
  const trendText = document.getElementById('env-trend-text');
  const periodEl  = document.getElementById('env-period');

  if (valEl) {
    valEl.textContent = isFinite(envScore) ? envScore.toString() : '-';
  }

  if (circle && isFinite(envScore)) {
    const circ = 2 * Math.PI * 40; // r=40 -> 251.327...
    const offset = circ * (1 - envScore / 100);
    circle.setAttribute('stroke-dasharray', circ.toString());
    circle.setAttribute('stroke-dashoffset', offset.toString());
  }

  if (periodEl && timeframeLabel) {
    periodEl.textContent = `Periode ${timeframeLabel}`;
  }

  if (textEl) {
    let msg;
    if (!isFinite(envScore)) {
      msg = 'Tidak dapat menghitung skor lingkungan dari data yang tersedia.';
    } else if (envScore >= 80) {
      msg = 'Kinerja lingkungan sangat baik pada periode ini. Pertahankan pengendalian emisi yang ada.';
    } else if (envScore >= 60) {
      msg = 'Kondisi lingkungan cukup baik, namun beberapa parameter mendekati batas yang ditetapkan.';
    } else {
      msg = 'Skor lingkungan rendah. Perlu investigasi sumber emisi dan potensi kebocoran gas.';
    }
    textEl.textContent = msg;
  }

  // trend sederhana: bandingkan rata-rata AQI dulu & sekarang
  if (trendIcon && trendText && isFinite(envScore)) {
    // pakai rata-rata AQI sebagai indikasi tren
    const aAvg = envAverages.AQI;
    let trendColor = '#92c0c9';
    let icon = 'trending_flat';
    let label = 'Tren stabil, tidak ada perubahan signifikan.';

    if (aAvg > ANALYTIC_CONFIG.AQI.threshold) {
      trendColor = '#fa5f38';
      icon = 'trending_up';
      label = 'Tren emisi meningkat dan melewati batas aman AQI.';
    } else if (aAvg < ANALYTIC_CONFIG.AQI.threshold * 0.6) {
      trendColor = '#0bda54';
      icon = 'trending_down';
      label = 'Tren emisi menurun dan berada pada level aman.';
    }

    trendIcon.textContent = icon;
    trendIcon.style.color = trendColor;
    trendText.textContent = label;
    trendText.style.color = trendColor;
  }
}

// ================== LOAD DATA & GRAFIK ==================
async function loadAnalyticsChart(param, timeframe, labelForEnv) {
  const cfg = ANALYTIC_CONFIG[param];
  if (!cfg) return;

  try {
    const res = await fetch(`${BASE_URL}/api/trend.php?param=${encodeURIComponent(param)}&range=${encodeURIComponent(timeframe)}`);
    const data = await res.json();
    if (!data || data.error) {
      console.error(data?.error || 'Unknown error');
      return;
    }

    const labels = data.labels || [];
    const values = (data.values || []).map(parseFloat).filter(v => !Number.isNaN(v));

    const width   = 480;
    const height  = 150;
    const padding = 10;

    const { strokePath, fillPath, points } = buildSmoothPathWithPoints(values, width, height, padding);

    // Path
    const strokeEl = document.getElementById(cfg.strokeId);
    const fillEl   = document.getElementById(cfg.fillId);
    if (strokeEl && fillEl) {
      strokeEl.setAttribute('d', strokePath);
      strokeEl.setAttribute('stroke', cfg.strokeColor);
      fillEl.setAttribute('d', fillPath);
    }

    // Titik & warning
    const group = document.getElementById(cfg.pointsGroupId);
    if (group) group.innerHTML = '';

    const warnings = [];
    const threshold = cfg.threshold;

    points.forEach(pt => {
      const label = labels[pt.index] ?? `#${pt.index + 1}`;
      const isWarning = threshold != null && pt.value >= threshold;

      if (group) {
        const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        c.setAttribute('cx', pt.x);
        c.setAttribute('cy', pt.y);
        c.setAttribute('r', isWarning ? 4 : 3);
        c.setAttribute('fill', isWarning ? '#f97373' : '#ffffff');
        c.setAttribute('fill-opacity', isWarning ? '0.95' : '0.6');
        if (isWarning) {
          c.setAttribute('stroke', '#fecaca');
          c.setAttribute('stroke-width', '1.5');
        }
        group.appendChild(c);
      }

      if (isWarning) {
        warnings.push({ label, value: pt.value });
      }
    });

    // Label bawah (max 7)
    const labelsDiv = document.getElementById(cfg.labelsId);
    if (labelsDiv) {
      labelsDiv.innerHTML = '';
      if (labels.length > 0) {
        const count = Math.min(7, labels.length);
        for (let i = 0; i < count; i++) {
          const idx = Math.round(i * (labels.length - 1) / (count - 1 || 1));
          const p = document.createElement('p');
          p.className = 'truncate';
          p.textContent = labels[idx];
          labelsDiv.appendChild(p);
        }
      }
    }

    // Daftar warning
    const warningsDiv = document.getElementById(cfg.warningsId);
    const badgeEl     = document.getElementById(cfg.badgeId);
    if (warningsDiv) {
      warningsDiv.innerHTML = '';
      if (warnings.length === 0) {
        warningsDiv.innerHTML = '<p class="text-textmuted">Tidak ada data warning pada periode ini.</p>';
      } else {
        warnings.forEach(w => {
          const p = document.createElement('p');
          p.textContent = `⚠ ${w.label} : ${w.value.toFixed(2)}${cfg.unit}`;
          warningsDiv.appendChild(p);
        });
      }
    }

    if (badgeEl) {
      if (warnings.length === 0) {
        badgeEl.textContent = 'Normal';
        badgeEl.className =
          'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-500/15 text-green-400';
      } else {
        badgeEl.textContent = 'Warning';
        badgeEl.className =
          'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-yellow-500/20 text-yellow-300';
      }
    }

    // Statistik rata-rata & perubahan sederhana
    const avg = values.length ? (values.reduce((a, b) => a + b, 0) / values.length) : 0;
    const first = values[0] ?? 0;
    const last  = values[values.length - 1] ?? 0;
    const delta = first !== 0 ? ((last - first) / Math.abs(first)) * 100 : 0;

    const avgEl    = document.getElementById(cfg.avgId);
    const changeEl = document.getElementById(cfg.changeId);
    if (avgEl) avgEl.textContent = `${avg.toFixed(2)}${cfg.unit}`;

    if (changeEl) {
      const icon = delta > 0 ? 'arrow_upward' : (delta < 0 ? 'arrow_downward' : 'trending_flat');
      const color = delta > 0 ? '#0bda54' : (delta < 0 ? '#fa5f38' : '#92c0c9');
      const label =
        delta === 0 ? 'Tidak ada perubahan' :
        (delta > 0  ? `Naik ${delta.toFixed(1)}%` : `Turun ${Math.abs(delta).toFixed(1)}%`);

      changeEl.innerHTML =
        `<span class="material-symbols-outlined text-base" style="color:${color}">${icon}</span>` +
        `<span>${label}</span>`;
      changeEl.style.color = color;
    }

    // akumulasi warning
    totalWarningsAll += warnings.length;
    const totalWarnEl = document.getElementById('stat-total-warn');
    if (totalWarnEl) {
      totalWarnEl.textContent = totalWarningsAll;
    }

    // simpan rata-rata untuk env score
    envAverages[param] = avg;
    updateEnvScore(labelForEnv);

  } catch (err) {
    console.error(err);
  }
}

function reloadAllCharts(timeframe) {
  totalWarningsAll = 0;
  envAverages.AQI = envAverages.GLI = envAverages.CO = null;

  const totalWarnEl = document.getElementById('stat-total-warn');
  if (totalWarnEl) totalWarnEl.textContent = '0';

  let tfLabel = 'Bulanan';
  if (timeframe === 'weekly') tfLabel = 'Mingguan';
  if (timeframe === 'yearly') tfLabel = 'Tahunan';

  loadAnalyticsChart('AQI', timeframe, tfLabel);
  loadAnalyticsChart('GLI', timeframe, tfLabel);
  loadAnalyticsChart('CO',  timeframe, tfLabel);
}

document.addEventListener('DOMContentLoaded', () => {
  // default: bulanan
  reloadAllCharts('monthly');

  document.querySelectorAll('input[name="timeframe"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const tf = radio.value || 'monthly';
      reloadAllCharts(tf);
    });
  });
});
</script>
