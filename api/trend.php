<?php

header('Content-Type: application/json');

require_once 'config.php';

$param = $_GET['param'] ?? 'AQI';
$param = strtoupper($param);

// mapping key JSON
$map = [
    'AQI' => 'AirQualityIndex',
    'GLI' => 'GasLeakIndex',
    'CO' => 'COLevel',
];

if (!isset($map[$param])) {
    echo json_encode(['error' => 'Invalid parameter']);
    exit;
}

$key = $map[$param];
$NODE_ID = intval($_ENV['NODE_ID'] ?? 1);

// ====== RANGE & TIPE BUCKET ======
// dipakai analytics.php lewat ?range=weekly|monthly|yearly
// kalau tidak ada (dashboard lama), pakai default '24h'
$range = $_GET['range'] ?? '24h';

$rangeLower = strtolower($range);
switch ($rangeLower) {
    case 'weekly':
        // 7 hari terakhir, agregasi per HARI
        $interval = '7 DAY';
        $bucketType = 'day';
        break;
    case 'monthly':
        // 30 hari terakhir, agregasi per HARI
        $interval = '30 DAY';
        $bucketType = 'day';
        break;
    case 'yearly':
        // 365 hari terakhir, agregasi per BULAN
        $interval = '365 DAY';
        $bucketType = 'month';
        break;
    default:
        // kompatibel dengan dashboard: 24 jam, agregasi per JAM
        $interval = '24 HOUR';
        $bucketType = 'hour';
        break;
}

// ====== AMBIL DATA RAW ======
$sql = "
    SELECT created_at, data
    FROM sensor_data
    WHERE node_id = ?
      AND created_at >= NOW() - INTERVAL $interval
    ORDER BY created_at ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Failed to prepare statement']);
    exit;
}

$stmt->bind_param('i', $NODE_ID);
$stmt->execute();
$res = $stmt->get_result();

// ====== AGREGASI DI PHP (AVG per jam/hari/bulan) ======
$sum = []; // total value per bucket
$count = []; // jumlah sample per bucket
$labelForBucket = []; // label yang ditampilkan di chart

while ($row = $res->fetch_assoc()) {
    $d = json_decode($row['data'], true);
    if (!is_array($d) || !array_key_exists($key, $d)) {
        continue;
    }

    $value = floatval($d[$key]);
    $ts = strtotime($row['created_at']);

    switch ($bucketType) {
        case 'hour':
            // contoh key: 2025-12-10 13:00
            $bucketKey = date('Y-m-d H:00', $ts);
            $label = date('H:i', $ts);     // 13:00
            break;
        case 'day':
            // contoh key: 2025-12-10
            $bucketKey = date('Y-m-d', $ts);
            $label = date('d M', $ts);     // 10 Dec
            break;
        case 'month':
        default:
            // contoh key: 2025-12
            $bucketKey = date('Y-m', $ts);
            $label = date('M Y', $ts);     // Dec 2025
            break;
    }

    if (!isset($sum[$bucketKey])) {
        $sum[$bucketKey] = 0.0;
        $count[$bucketKey] = 0;
        $labelForBucket[$bucketKey] = $label;
    }

    $sum[$bucketKey] += $value;
    ++$count[$bucketKey];
}

$stmt->close();
$conn->close();

// urutkan bucket berdasarkan waktu
ksort($sum);

$labels = [];
$values = [];

foreach ($sum as $bucketKey => $total) {
    $c = $count[$bucketKey] ?: 1;
    $labels[] = $labelForBucket[$bucketKey];
    $values[] = $total / $c; // rata-rata per bucket
}

echo json_encode([
    'labels' => $labels,
    'values' => $values,
]);
