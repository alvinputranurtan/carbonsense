<?php
// pages/billing.php

require_once __DIR__.'/../api/config.php';

// pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// user id dari session (fallback 1 kalau belum ada, supaya tidak error saat demo)
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 1;

// info routing/layout
$currentPage = $currentPage ?? 'billing';
$baseUrl = $baseUrl ?? '';

// konstanta untuk perhitungan
$RATE_PER_KG = 150; // Rp / kg CO2e

$successMessage = null;
$errorMessage = null;

// ================== HANDLE POST (PAY) ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'pay') {
        $billingId = isset($_POST['billing_id']) ? (int) $_POST['billing_id'] : 0;
        $cardHolder = trim($_POST['card_holder'] ?? '');
        $cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
        $cardExpiry = trim($_POST['card_expiry'] ?? '');
        $cardCvv = trim($_POST['card_cvv'] ?? '');

        // validasi sederhana
        if ($billingId <= 0 || $cardNumber === '' || strlen($cardNumber) < 8) {
            $errorMessage = 'Data pembayaran tidak lengkap atau nomor kartu tidak valid.';
        } else {
            // cek apakah billing milik user & masih unpaid
            $stmt = $conn->prepare('
                SELECT id 
                FROM billing 
                WHERE id = ? AND user_id = ? AND payment_status = "unpaid"
            ');
            $stmt->bind_param('ii', $billingId, $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $errorMessage = 'Tagihan tidak ditemukan atau sudah dibayar.';
            } else {
                $last4 = substr($cardNumber, -4);

                // update tagihan jadi paid
                $stmt = $conn->prepare('
                    UPDATE billing
                    SET payment_status   = "paid",
                        paid_at          = NOW(),
                        card_last_digits = ?
                    WHERE id = ? AND user_id = ?
                ');
                $stmt->bind_param('sii', $last4, $billingId, $userId);

                if ($stmt->execute()) {
                    $successMessage = 'Pembayaran berhasil diproses.';
                } else {
                    $errorMessage = 'Gagal memproses pembayaran.';
                }
                $stmt->close();
            }
        }
    }
}

// ================== LOAD DATA UNTUK TAMPILAN ==================

// info user (opsional)
$user = null;
$stmt = $conn->prepare('SELECT name, email FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

// billing aktif: prioritaskan unpaid terbaru, kalau tidak ada pakai yang created_at terbaru
$stmt = $conn->prepare('
    SELECT *
    FROM billing
    WHERE user_id = ?
    ORDER BY payment_status = "unpaid" DESC, created_at DESC
    LIMIT 1
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$currentBill = $res->fetch_assoc();
$stmt->close();

// rata-rata skor lingkungan dari semua billing
$avgScore = null;
$stmt = $conn->prepare('SELECT AVG(avg_env_score) AS avg_score FROM billing WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $avgScore = $row['avg_score'] !== null ? (float) $row['avg_score'] : null;
}
$stmt->close();

// histori billing
$stmt = $conn->prepare('
    SELECT *
    FROM billing
    WHERE user_id = ?
    ORDER BY created_at DESC
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$historyRes = $stmt->get_result();

$billingRows = [];
while ($row = $historyRes->fetch_assoc()) {
    $billingRows[] = $row;
}
$stmt->close();

// data untuk chart spending: total paid per month (maks 6 bulan terakhir)
$spendLabels = [];
$spendValues = [];

$stmt = $conn->prepare('
    SELECT 
        DATE_FORMAT(created_at, "%Y-%m") AS ym,
        DATE_FORMAT(created_at, "%b %Y") AS label,
        SUM(payment_amount)              AS total_amount
    FROM billing
    WHERE user_id = ? AND payment_status = "paid"
    GROUP BY ym, label
    ORDER BY ym ASC
    LIMIT 6
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$spRes = $stmt->get_result();
while ($r = $spRes->fetch_assoc()) {
    $spendLabels[] = $r['label'];
    $spendValues[] = (float) $r['total_amount'];
}
$stmt->close();

?>
<div class="flex h-full">


  <!-- MAIN -->
  <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
    <div class="w-full max-w-5xl mx-auto flex flex-col gap-6">
      <!-- HEADER -->
      <div class="flex flex-wrap justify-between gap-3 items-center">
        <div class="flex items-center gap-3">
          <!-- hamburger mobile -->
          <button class="md:hidden p-2 rounded-lg hover:bg-white/10"
                  type="button"
                  onclick="toggleSidebar()">
            <span class="material-symbols-outlined">menu</span>
          </button>
          <div class="flex flex-col gap-1">
            <p class="text-white text-4xl font-black leading-tight tracking-[-0.033em]">
              Carbon Pay Marketplace
            </p>
            <p class="text-textmuted text-base">
              Offset jejak karbon bulanan berdasarkan skor lingkungan Anda.
            </p>
          </div>
        </div>

        <!-- <?php if ($user) { ?>
          <div class="hidden sm:flex flex-col items-end text-right text-xs text-textmuted">
            <span class="text-white text-sm font-medium">
              <?php echo htmlspecialchars($user['name'] ?? ''); ?>
            </span>
            <span><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
          </div>
        <?php } ?> -->
      </div>

      <!-- ALERTS -->
      <?php if ($successMessage) { ?>
        <div class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 text-emerald-300 px-4 py-3 text-sm">
          <?php echo htmlspecialchars($successMessage); ?>
        </div>
      <?php } elseif ($errorMessage) { ?>
        <div class="rounded-lg border border-red-500/40 bg-red-500/10 text-red-300 px-4 py-3 text-sm">
          <?php echo htmlspecialchars($errorMessage); ?>
        </div>
      <?php } ?>

      <!-- STAT CARDS -->
      <div class="flex flex-col sm:flex-row flex-wrap gap-4">
        <!-- Avg Monthly Score -->
        <div class="flex min-w-[158px] flex-1 flex-col gap-2 rounded-lg p-6 border border-border bg-card/70">
          <p class="text-white text-base font-medium leading-normal">Avg. Monthly Score</p>
          <div class="flex items-baseline gap-2">
            <p class="text-white tracking-light text-2xl font-bold leading-tight">
              <?php echo $avgScore !== null ? number_format($avgScore, 1) : '-'; ?>
            </p>
            <?php if ($avgScore !== null) { ?>
              <?php
                $scoreLabel = 'Unknown';
                $scoreColor = 'text-textmuted';
                if ($avgScore >= 80) {
                    $scoreLabel = 'Good';
                    $scoreColor = 'text-green-400';
                } elseif ($avgScore >= 60) {
                    $scoreLabel = 'Fair';
                    $scoreColor = 'text-amber-300';
                } else {
                    $scoreLabel = 'Poor';
                    $scoreColor = 'text-red-400';
                }
                ?>
              <p class="<?php echo $scoreColor; ?> text-sm font-medium">
                (<?php echo $scoreLabel; ?>)
              </p>
            <?php } ?>
          </div>
        </div>

        <!-- Rate -->
        <div class="flex min-w-[158px] flex-1 flex-col gap-2 rounded-lg p-6 border border-border bg-card/70">
          <p class="text-white text-base font-medium leading-normal">Current Offset Rate</p>
          <p class="text-white tracking-light text-2xl font-bold leading-tight">
            Rp <?php echo number_format($RATE_PER_KG, 0, ',', '.'); ?> / kg CO₂e
          </p>
        </div>
      </div>

      <!-- MAIN CONTENT: BILL + CHART -->
      <div class="flex flex-col lg:flex-row gap-6">
        <!-- LEFT: CURRENT BILL & PAYMENT -->
        <div class="flex flex-col gap-4 rounded-lg border border-border bg-card/80 p-6 lg:w-2/3">
          <h2 class="text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">
            Monthly Carbon Offset
          </h2>
          <p class="text-textmuted -mt-1 text-sm">
            Biaya offset dihitung berdasarkan skor lingkungan dan estimasi emisi bulanan Anda.
          </p>

          <?php if ($currentBill) { ?>
            <?php
                $emissionKg = $currentBill['payment_amount'] > 0
                  ? $currentBill['payment_amount'] / $RATE_PER_KG
                  : 0;

              $created = new DateTime($currentBill['created_at']);
              $nextPay = clone $created;
              $nextPay->modify('+1 month');
              ?>

            <div class="mt-2 flex flex-col gap-4 rounded-lg bg-[#234248]/40 p-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex flex-col">
                <p class="text-textmuted text-sm">Billing Period</p>
                <p class="text-white text-lg font-bold">
                  <?php echo $created->format('F Y'); ?>
                </p>
              </div>
              <div class="hidden sm:block h-12 border-l border-border"></div>
              <div class="flex flex-col">
                <p class="text-textmuted text-sm">Estimated Emissions</p>
                <p class="text-white text-lg font-bold">
                  <?php echo number_format($emissionKg, 0, ',', '.'); ?> kg CO₂e
                </p>
              </div>
              <div class="hidden sm:block h-12 border-l border-border"></div>
              <div class="flex flex-col">
                <p class="text-textmuted text-sm">Monthly Cost</p>
                <p class="text-white text-lg font-bold">
                  Rp <?php echo number_format($currentBill['payment_amount'], 0, ',', '.'); ?>
                </p>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-3">
              <div class="flex flex-col text-center sm:text-left">
                <p class="text-white text-base">
                  Status:
                  <?php if ($currentBill['payment_status'] === 'paid') { ?>
                    <span class="inline-flex items-center rounded-full bg-green-500/20 px-3 py-1 text-xs font-semibold text-green-400">
                      Paid
                    </span>
                  <?php } else { ?>
                    <span class="inline-flex items-center rounded-full bg-amber-500/20 px-3 py-1 text-xs font-semibold text-amber-300">
                      Unpaid
                    </span>
                  <?php } ?>
                </p>
                <p class="text-textmuted text-sm mt-1">
                  Next payment target: <?php echo $nextPay->format('j F Y'); ?>
                </p>
              </div>
            </div>

            <!-- PAYMENT FORM -->
            <?php if ($currentBill['payment_status'] === 'unpaid') { ?>
              <div class="mt-4 border-t border-border pt-4">
                <p class="text-white text-base font-semibold mb-3">
                  Pay with Credit Card
                </p>
                <form method="post" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <input type="hidden" name="action" value="pay">
                  <input type="hidden" name="billing_id" value="<?php echo (int) $currentBill['id']; ?>">

                  <div class="sm:col-span-2 flex flex-col gap-1">
                    <label class="text-sm text-textmuted">Card Holder Name</label>
                    <input
                      type="text"
                      name="card_holder"
                      class="form-input h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-white placeholder:text-textmuted focus:border-primary focus:ring-primary"
                      placeholder="Nama pada kartu"
                      required
                    >
                  </div>

                  <div class="flex flex-col gap-1">
                    <label class="text-sm text-textmuted">Card Number</label>
                    <input
                      type="text"
                      name="card_number"
                      inputmode="numeric"
                      class="form-input h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-white placeholder:text-textmuted focus:border-primary focus:ring-primary"
                      placeholder="4111 1111 1111 1111"
                      required
                    >
                  </div>

                  <div class="flex gap-3">
                    <div class="flex flex-col gap-1 flex-1">
                      <label class="text-sm text-textmuted">Expiry (MM/YY)</label>
                      <input
                        type="text"
                        name="card_expiry"
                        class="form-input h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-white placeholder:text-textmuted focus:border-primary focus:ring-primary"
                        placeholder="12/27"
                        required
                      >
                    </div>
                    <div class="flex flex-col gap-1 w-24">
                      <label class="text-sm text-textmuted">CVV</label>
                      <input
                        type="password"
                        name="card_cvv"
                        class="form-input h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-white placeholder:text-textmuted focus:border-primary focus:ring-primary"
                        placeholder="123"
                        required
                      >
                    </div>
                  </div>

                  <div class="sm:col-span-2">
                    <button
                      type="submit"
                      class="mt-2 flex w-full sm:w-auto items-center justify-center rounded-lg bg-primary text-black h-11 px-6 text-sm font-bold tracking-[0.015em] hover:bg-primary/90"
                    >
                      <span class="material-symbols-outlined text-black mr-2" style="font-size: 20px;">credit_card</span>
                      Pay Rp <?php echo number_format($currentBill['payment_amount'], 0, ',', '.'); ?>
                    </button>
                    <p class="mt-2 text-[11px] text-textmuted">
                      Catatan: ini hanya simulasi pembayaran. Jangan gunakan kartu asli pada demo ini.
                    </p>
                  </div>
                </form>
              </div>
            <?php } else { ?>
              <?php if (!empty($currentBill['card_last_digits'])) { ?>
                <p class="mt-3 text-xs text-textmuted">
                  Dibayar menggunakan kartu **** **** **** <?php echo htmlspecialchars($currentBill['card_last_digits']); ?>.
                </p>
              <?php } ?>
            <?php } ?>

          <?php } else { ?>
            <p class="text-textmuted text-sm mt-2">
              Belum ada data billing untuk akun ini.
            </p>
          <?php } ?>
        </div>

        <!-- RIGHT: REAL SPENDING CHART -->
        <div class="flex flex-col gap-4 rounded-lg border border-border bg-card/80 p-6 lg:w-1/3">
          <h3 class="text-white text-lg font-bold">Carbon Spending Overview</h3>
          <div class="w-full flex flex-col items-center justify-center min-h-[220px] gap-2">
            <!-- SVG CHART -->
            <svg id="spend-svg" class="w-full h-40" preserveAspectRatio="none"></svg>

            <!-- LABELS BULAN -->
            <div id="spend-labels"
                 class="mt-2 flex w-full justify-between text-[11px] text-textmuted gap-1">
            </div>

            <!-- PESAN KETIKA TIDAK ADA DATA -->
            <p id="spend-empty" class="text-[11px] text-textmuted text-center"></p>
          </div>
        </div>
      </div>

      <!-- PAYMENT HISTORY -->
      <div class="flex flex-col gap-3">
        <h2 class="text-white text-[20px] font-bold leading-tight tracking-[-0.015em]">
          Payment History
        </h2>
        <div class="overflow-x-auto rounded-lg border border-border">
          <table class="w-full text-left text-sm">
            <thead class="bg-[#234248]">
              <tr>
                <th class="p-3 font-semibold text-white">Period</th>
                <th class="p-3 font-semibold text-white">Created</th>
                <th class="p-3 font-semibold text-white">Amount Offset</th>
                <th class="p-3 font-semibold text-white">Cost</th>
                <th class="p-3 font-semibold text-white">Status</th>
                <th class="p-3 font-semibold text-white">Paid At</th>
                <th class="p-3 font-semibold text-white">Card</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#234248]">
              <?php if (!empty($billingRows)) { ?>
                <?php foreach ($billingRows as $bill) { ?>
                  <?php
                      $created = new DateTime($bill['created_at']);
                    $emission = $bill['payment_amount'] > 0
                      ? $bill['payment_amount'] / $RATE_PER_KG
                      : 0;
                    ?>
                  <tr>
                    <td class="p-3 text-white">
                      <?php echo $created->format('F Y'); ?>
                    </td>
                    <td class="p-3 text-textmuted">
                      <?php echo $created->format('Y-m-d'); ?>
                    </td>
                    <td class="p-3 text-white">
                      <?php echo number_format($emission, 0, ',', '.'); ?> kg CO₂e
                    </td>
                    <td class="p-3 text-white">
                      Rp <?php echo number_format($bill['payment_amount'], 0, ',', '.'); ?>
                    </td>
                    <td class="p-3">
                      <?php if ($bill['payment_status'] === 'paid') { ?>
                        <span class="inline-flex items-center rounded-full bg-green-500/20 px-3 py-1 text-xs font-medium text-green-400">
                          Paid
                        </span>
                      <?php } else { ?>
                        <span class="inline-flex items-center rounded-full bg-amber-500/20 px-3 py-1 text-xs font-medium text-amber-300">
                          Unpaid
                        </span>
                      <?php } ?>
                    </td>
                    <td class="p-3 text-textmuted">
                      <?php echo $bill['paid_at'] ? htmlspecialchars($bill['paid_at']) : '-'; ?>
                    </td>
                    <td class="p-3 text-textmuted">
                      <?php
                          echo !empty($bill['card_last_digits'])
                            ? '**** **** **** '.htmlspecialchars($bill['card_last_digits'])
                            : '-';
                    ?>
                    </td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr>
                  <td colspan="7" class="p-4 text-center text-textmuted">
                    Belum ada riwayat billing.
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- DATA JS UNTUK CHART SPENDING -->
<script>
window.SPEND_LABELS = <?php echo json_encode($spendLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.SPEND_VALUES = <?php echo json_encode($spendValues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<!-- CHART SPENDING: BAR SVG -->
<script>
(function () {
  const labels = window.SPEND_LABELS || [];
  const values = window.SPEND_VALUES || [];

  const svg        = document.getElementById('spend-svg');
  const labelsWrap = document.getElementById('spend-labels');
  const emptyEl    = document.getElementById('spend-empty');

  if (!svg) return;

  if (!values.length || Math.max(...values) <= 0) {
    if (emptyEl) {
      emptyEl.textContent = 'Belum ada pembayaran yang bisa ditampilkan.';
    }
    return;
  }

  const width   = 260;
  const height  = 140;
  const padding = 12;
  const maxBarH = height - padding * 2 - 10;
  const maxVal  = Math.max(...values);

  const ns = 'http://www.w3.org/2000/svg';
  svg.setAttribute('viewBox', `0 0 ${width} ${height}`);

  // bersihkan isi SVG
  while (svg.firstChild) svg.removeChild(svg.firstChild);

  // gradient bar
  const defs = document.createElementNS(ns, 'defs');
  const grad = document.createElementNS(ns, 'linearGradient');
  grad.setAttribute('id', 'spendGradient');
  grad.setAttribute('x1', '0');
  grad.setAttribute('y1', '0');
  grad.setAttribute('x2', '0');
  grad.setAttribute('y2', '1');

  const stop1 = document.createElementNS(ns, 'stop');
  stop1.setAttribute('offset', '0%');
  stop1.setAttribute('stop-color', '#13c8ec');
  stop1.setAttribute('stop-opacity', '0.9');

  const stop2 = document.createElementNS(ns, 'stop');
  stop2.setAttribute('offset', '100%');
  stop2.setAttribute('stop-color', '#13c8ec');
  stop2.setAttribute('stop-opacity', '0.15');

  grad.appendChild(stop1);
  grad.appendChild(stop2);
  defs.appendChild(grad);
  svg.appendChild(defs);

  // garis sumbu bawah
  const axis = document.createElementNS(ns, 'line');
  axis.setAttribute('x1', padding);
  axis.setAttribute('y1', height - padding);
  axis.setAttribute('x2', width - padding);
  axis.setAttribute('y2', height - padding);
  axis.setAttribute('stroke', '#1f2933');
  axis.setAttribute('stroke-width', '1');
  svg.appendChild(axis);

  // hitung ukuran bar
  const n          = values.length;
  const totalWidth = width - padding * 2;
  const gap        = 6;
  const barWidth   = Math.max(8, (totalWidth - gap * (n - 1)) / n);

  for (let i = 0; i < n; i++) {
    const val  = values[i];
    const h    = (val / maxVal) * maxBarH;
    const x    = padding + i * (barWidth + gap);
    const y    = (height - padding) - h;

    const rect = document.createElementNS(ns, 'rect');
    rect.setAttribute('x', x);
    rect.setAttribute('y', y);
    rect.setAttribute('width', barWidth);
    rect.setAttribute('height', h);
    rect.setAttribute('rx', '3');
    rect.setAttribute('fill', 'url(#spendGradient)');
    svg.appendChild(rect);
  }

  // label bulan
  if (labelsWrap) {
    labelsWrap.innerHTML = '';
    for (let i = 0; i < labels.length; i++) {
      const p = document.createElement('p');
      p.className = 'flex-1 text-center truncate';
      p.textContent = labels[i];
      labelsWrap.appendChild(p);
    }
  }

  if (emptyEl) emptyEl.textContent = '';
})();
</script>
