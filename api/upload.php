<?php

header('Content-Type: application/json');

// ======== DATABASE CONFIG (SESUIKAN DENGAN KREDENSIALMU) ========
$host = 'telkomambis.com';
$dbname = 'telkomam_carbonsense';
$dbuser = 'telkomam_carbonsense';
$dbpass = 'carbonsense';

// ======== CONNECT DATABASE ========
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database Connection Failed']);
    exit;
}

// ======== AMBIL JSON DARI ESP ========
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Ambil API key & payload
$api_key = $data['api_key'] ?? null;
$payload = $data['payload'] ?? null;

if (!$api_key || !$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing api_key or payload']);
    exit;
}

// ======== VERIFIKASI API KEY (bcrypt) ========
$stmt = $conn->prepare('SELECT id, api_key FROM sensor_nodes');
$stmt->execute();
$result = $stmt->get_result();

$node_id = null;

while ($row = $result->fetch_assoc()) {
    if (password_verify($api_key, $row['api_key'])) {
        $node_id = $row['id'];
        break;
    }
}

if (!$node_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API Key']);
    exit;
}

// ======== INSERT DATA SENSOR KE TABEL sensor_data ========
$json_payload = json_encode($payload);

$stmt = $conn->prepare('INSERT INTO sensor_data (node_id, data) VALUES (?, ?)');
$stmt->bind_param('is', $node_id, $json_payload);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Sensor data stored successfully',
        'node_id' => $node_id,
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to insert sensor data']);
}

// Close connection
$stmt->close();
$conn->close();
