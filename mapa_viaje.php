<?php
// mapa_viaje.php
session_start();
include 'includes/funciones.php';

$viaje_id = $_GET['id'] ?? 0;
$viaje = obtenerViajePorId($viaje_id);

if (!$viaje) {
    die("Viaje no encontrado");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Uber - Ruta del Viaje #<?php echo $viaje_id; ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 1000px; margin: auto; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        #mapa { height: 500px; width: 100%; border-radius: 10px; margin-bottom: 20px; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
        .info-item {
            text-align: center;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 14px;
        }
        .info-value {
            font-size: 24px;
            color: #333;
            margin-top: 5px;
        }
        .badge {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
        }
        .btn-volver {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚖 Ruta del Viaje #<?php echo $viaje_id; ?></h1>
            <p><strong>Pasajero:</strong> <?php echo $viaje['pasajero_nombre']; ?> | 
               <strong>Conductor:</strong> <?php echo $viaje['conductor_nombre']; ?> (<?php echo $viaje['conductor_auto']; ?>)</p>
        </div>
        
        <div id="mapa"></div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Distancia</div>
                <div class="info-value"><?php echo $viaje['distancia_km']; ?> km</div>
            </div>
            <div class="info-item">
                <div class="info-label">Tarifa</div>
                <div class="info-value">$<?php echo $viaje['tarifa']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Estado</div>
                <div class="info-value">
                    <span class="badge"><?php echo $viaje['estado']; ?></span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($viaje['fecha_solicitud'])); ?></div>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="viajes_mapa.php" class="btn-volver">← Volver al historial</a>
        </div>
    </div>

    <script>
    var map = L.map('mapa').setView([<?php echo $viaje['origen_lat']; ?>, <?php echo $viaje['origen_lng']; ?>], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Marcador de origen
    var origen = L.marker([<?php echo $viaje['origen_lat']; ?>, <?php echo $viaje['origen_lng']; ?>])
        .addTo(map)
        .bindPopup('📍 Origen: <?php echo addslashes($viaje['origen']); ?>')
        .openPopup();
    
    // Marcador de destino
    var destino = L.marker([<?php echo $viaje['destino_lat']; ?>, <?php echo $viaje['destino_lng']; ?>])
        .addTo(map)
        .bindPopup('🏁 Destino: <?php echo addslashes($viaje['destino']); ?>');
    
    // Dibujar línea de la ruta
    var ruta = L.polyline([
        [<?php echo $viaje['origen_lat']; ?>, <?php echo $viaje['origen_lng']; ?>],
        [<?php echo $viaje['destino_lat']; ?>, <?php echo $viaje['destino_lng']; ?>]
    ], {color: '#007bff', weight: 5, opacity: 0.7}).addTo(map);
    
    // Ajustar el mapa para mostrar toda la ruta
    var bounds = L.latLngBounds([
        [<?php echo $viaje['origen_lat']; ?>, <?php echo $viaje['origen_lng']; ?>],
        [<?php echo $viaje['destino_lat']; ?>, <?php echo $viaje['destino_lng']; ?>]
    ]);
    map.fitBounds(bounds, {padding: [50, 50]});
    
    // Intentar obtener la ruta real (si queremos más precisión)
    fetch(`https://router.project-osrm.org/route/v1/driving/${<?php echo $viaje['origen_lng']; ?>},${<?php echo $viaje['origen_lat']; ?>};${<?php echo $viaje['destino_lng']; ?>},${<?php echo $viaje['destino_lat']; ?>}?overview=full&geometries=geojson`)
        .then(response => response.json())
        .then(data => {
            if (data.code === 'Ok' && data.routes.length > 0) {
                var rutaReal = data.routes[0].geometry;
                L.geoJSON(rutaReal, {
                    style: {color: '#28a745', weight: 5, opacity: 0.5}
                }).addTo(map);
            }
        })
        .catch(error => console.log('Usando ruta en línea recta'));
    </script>
</body>
</html>