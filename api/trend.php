<?php

header('Content-Type: application/json');

require_once 'config.php';

$param = $_GET['param'] ?? 'AQI';
$param = strtoupper($param);

// Mapping JSON key
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

// Ambil data 24 jam
$sql = '
    SELECT created_at, data
    FROM sensor_data
    WHERE node_id = ?
    AND created_at >= NOW() - INTERVAL 24 HOUR
    ORDER BY created_at ASC
';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $NODE_ID);
$stmt->execute();
$res = $stmt->get_result();

$labels = [];
$values = [];

while ($row = $res->fetch_assoc()) {
    $d = json_decode($row['data'], true);
    $labels[] = date('H:i', strtotime($row['created_at']));
    $values[] = floatval($d[$key] ?? 0);
}

echo json_encode([
    'labels' => $labels,
    'values' => $values,
]);
