<?php
require_once __DIR__.'/../api/config.php';

// kalau dipanggil lewat index.php, variabel sudah ada
$currentPage = $currentPage ?? 'nodes';
$baseUrl = $baseUrl ?? '';

// ambil semua node + latest sensor_data
$sql = '
  SELECT 
    n.id,
    n.node_name,
    n.latitude,
    n.longitude,
    n.location_label,
    n.created_at,
    sd.data       AS latest_data,
    sd.created_at AS last_seen
  FROM sensor_nodes n
  LEFT JOIN (
      SELECT s1.*
      FROM sensor_data s1
      INNER JOIN (
        SELECT node_id, MAX(created_at) AS max_created
        FROM sensor_data
        GROUP BY node_id
      ) s2
      ON s1.node_id = s2.node_id
      AND s1.created_at = s2.max_created
  ) sd ON sd.node_id = n.id
  ORDER BY n.id ASC
';

$result = $conn->query($sql);

$nodes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latest = [];
        if (!empty($row['latest_data'])) {
            $decoded = json_decode($row['latest_data'], true);
            if (is_array($decoded)) {
                $latest = $decoded;
            }
        }

        $overall = strtoupper($latest['OverallStatus'] ?? 'UNKNOWN');
        $aqi = isset($latest['AirQualityIndex']) ? floatval($latest['AirQualityIndex']) : null;
        $gli = isset($latest['GasLeakIndex']) ? floatval($latest['GasLeakIndex']) : null;
        $co = isset($latest['COLevel']) ? floatval($latest['COLevel']) : null;

        $nodes[] = [
            'id' => (int) $row['id'],
            'name' => $row['node_name'],
            'lat' => $row['latitude'] !== null ? (float) $row['latitude'] : null,
            'lng' => $row['longitude'] !== null ? (float) $row['longitude'] : null,
            'location' => $row['location_label'] ?? '',
            'created_at' => $row['created_at'],
            'last_seen' => $row['last_seen'],
            'status' => $overall,
            'aqi' => $aqi,
            'gli' => $gli,
            'co' => $co,
        ];
    }
}
$conn->close();

// siapkan untuk JS
$nodesJson = json_encode($nodes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<!-- Leaflet CSS & JS (TANPA integrity supaya tidak diblok) -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
  /* tinggi map */
  #nodes-map {
    width: 100%;
    height: 420px;
    border-radius: 0.75rem;
  }

  /* TURUNKAN z-index Leaflet supaya nggak nutup sidebar */
  .leaflet-pane,
  .leaflet-top,
  .leaflet-bottom {
    z-index: 10 !important;
  }

  /* scrollbar cyan tua */
  ::-webkit-scrollbar-thumb {
    background-color: #234248;
  }
  ::-webkit-scrollbar-thumb:hover {
    background-color: #1b3339;
  }
</style>

<div class="w-full max-w-7xl mx-auto flex flex-col gap-6">

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
        <p class="text-white text-4xl font-black leading-tight tracking-[-0.033em]">
          Sensor Nodes
        </p>
        <p class="text-textmuted text-base">
          Lokasi dan kondisi perangkat pemantau emisi di lapangan.
        </p>
      </div>
    </div>
  </div>

  <!-- PETA -->
  <section class="flex flex-col gap-3 rounded-xl border border-border bg-card/80 p-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h3 class="text-white text-base font-semibold">Peta Lokasi Nodes</h3>
        <p class="text-textmuted text-sm">
          Klik marker pada peta untuk melihat detail node dan status terakhirnya.
        </p>
      </div>
      <div class="flex items-center gap-4 text-xs">
        <div class="flex items-center gap-1">
          <span class="inline-block size-2 rounded-full bg-green-400"></span>
          <span class="text-textmuted">Aman</span>
        </div>
        <div class="flex items-center gap-1">
          <span class="inline-block size-2 rounded-full bg-yellow-300"></span>
          <span class="text-textmuted">Warning</span>
        </div>
        <div class="flex items-center gap-1">
          <span class="inline-block size-2 rounded-full bg-red-400"></span>
          <span class="text-textmuted">Danger</span>
        </div>
      </div>
    </div>

    <div class="mt-3 border border-[#234248] bg-[#0c181a] rounded-xl overflow-hidden">
      <div id="nodes-map"></div>
    </div>
  </section>

  <!-- TABEL NODES -->
  <section class="flex flex-col gap-3 rounded-xl border border-border bg-card/80 p-6">
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-white text-base font-semibold">Daftar Sensor Nodes</h3>
      <p class="text-textmuted text-xs">
        Total nodes: <?php echo count($nodes); ?>
      </p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-[#234248]">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-[#12262a]">
          <tr>
            <th class="px-4 py-3 text-xs font-semibold text-white">Node</th>
            <th class="px-4 py-3 text-xs font-semibold text-white">Lokasi</th>
            <th class="px-4 py-3 text-xs font-semibold text-white">Koordinat</th>
            <th class="px-4 py-3 text-xs font-semibold text-white">Status</th>
            <th class="px-4 py-3 text-xs font-semibold text-white">AQI</th>
            <th class="px-4 py-3 text-xs font-semibold text-white">GLI</th>
            <th class="px-4 py-3 text-xs font-semibold text-white">CO (ppm)</th>
            <th class="px-4 py-3 text-xs font-semibold text-white">Last Update</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#234248]">
        <?php if (!empty($nodes)) { ?>
          <?php foreach ($nodes as $node) { ?>
            <?php
              $status = $node['status'];
              $badgeClass = 'bg-slate-500/20 text-slate-200';
              $label = 'UNKNOWN';
              if ($status === 'SAFE') {
                  $badgeClass = 'bg-green-500/20 text-green-400';
                  $label = 'Safe';
              } elseif ($status === 'WARNING') {
                  $badgeClass = 'bg-yellow-500/20 text-yellow-200';
                  $label = 'Warning';
              } elseif ($status === 'DANGER') {
                  $badgeClass = 'bg-red-500/20 text-red-400';
                  $label = 'Danger';
              }
              $coord = ($node['lat'] !== null && $node['lng'] !== null)
                  ? sprintf('%.5f, %.5f', $node['lat'], $node['lng'])
                  : '-';
              ?>
            <tr class="hover:bg-white/5">
              <td class="px-4 py-3 text-white">
                <div class="flex flex-col">
                  <span class="font-medium"><?php echo htmlspecialchars($node['name']); ?></span>
                  <span class="text-[11px] text-textmuted">ID: <?php echo $node['id']; ?></span>
                </div>
              </td>
              <td class="px-4 py-3 text-textmuted">
                <?php echo $node['location'] ? htmlspecialchars($node['location']) : '-'; ?>
              </td>
              <td class="px-4 py-3 text-textmuted">
                <?php echo $coord; ?>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?php echo $badgeClass; ?>">
                  <?php echo $label; ?>
                </span>
              </td>
              <td class="px-4 py-3 text-white">
                <?php echo $node['aqi'] !== null ? number_format($node['aqi'], 2) : '-'; ?>
              </td>
              <td class="px-4 py-3 text-white">
                <?php echo $node['gli'] !== null ? number_format($node['gli'], 2) : '-'; ?>
              </td>
              <td class="px-4 py-3 text-white">
                <?php echo $node['co'] !== null ? number_format($node['co'], 4) : '-'; ?>
              </td>
              <td class="px-4 py-3 text-textmuted text-xs">
                <?php echo $node['last_seen'] ?: '-'; ?>
              </td>
            </tr>
          <?php } ?>
        <?php } else { ?>
          <tr>
            <td colspan="8" class="px-4 py-6 text-center text-textmuted">
              Belum ada node terdaftar.
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<script>
  const NODES = <?php echo $nodesJson ?: '[]'; ?>;

  document.addEventListener('DOMContentLoaded', () => {
    if (typeof L === 'undefined') {
      console.error('Leaflet belum ter-load. Cek koneksi ke CDN.');
      return;
    }

    // default center: Indonesia
    const map = L.map('nodes-map').setView([-2.5, 118], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const markers = [];

    function statusColor(status) {
      const s = (status || '').toUpperCase();
      if (s === 'DANGER')  return '#f97373';
      if (s === 'WARNING') return '#facc15';
      if (s === 'SAFE')    return '#22c55e';
      return '#e5e7eb';
    }

    NODES.forEach(node => {
      if (node.lat === null || node.lng === null) return;

      const color = statusColor(node.status);

      const marker = L.circleMarker([node.lat, node.lng], {
        radius: 9,
        color,
        weight: 2,
        fillColor: color,
        fillOpacity: 0.85
      }).addTo(map);

      const statusLabel = (node.status || 'UNKNOWN').toUpperCase();
      const lastSeen    = node.last_seen || '-';
      const aqi = node.aqi != null ? node.aqi.toFixed(2) : '-';
      const gli = node.gli != null ? node.gli.toFixed(2) : '-';
      const co  = node.co  != null ? node.co.toFixed(4) : '-';

      const popupHtml = `
        <div class="text-xs" style="color:#111827;">
          <p class="font-semibold mb-1">${node.name}</p>
          <p class="mb-1" style="color:#6b7280;">${node.location || ''}</p>
          <p class="mb-1" style="color:#6b7280;">
            Status:
            <span style="color:${color}; font-weight:600;">
              ${statusLabel}
            </span>
          </p>
          <p style="color:#4b5563;">AQI: <span style="font-weight:600;">${aqi}</span></p>
          <p style="color:#4b5563;">GLI: <span style="font-weight:600;">${gli}</span></p>
          <p style="color:#4b5563;">CO: <span style="font-weight:600;">${co}</span> ppm</p>
          <p class="mt-1" style="color:#9ca3af;">Last update: ${lastSeen}</p>
        </div>
      `;

      marker.bindPopup(popupHtml);
      markers.push(marker);
    });

    if (markers.length > 0) {
      const group = L.featureGroup(markers);
      map.fitBounds(group.getBounds(), { padding: [40, 40] });
    }
  });
</script>

