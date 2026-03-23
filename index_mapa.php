<?php
// index_mapa.php
session_start();
include 'includes/funciones.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pasajero = $_POST['pasajero'] ?? '';
    $origen = $_POST['origen'] ?? '';
    $destino = $_POST['destino'] ?? '';
    
    $mensaje = solicitarViajeConMapa($pasajero, $origen, $destino);
    
    if (strpos($mensaje, '✓') === 0) {
        $tipo_mensaje = 'exito';
        // Redirigir a la página del mapa del viaje
        if (isset($_SESSION['ultimo_viaje']['id'])) {
            header("Location: mapa_viaje.php?id=" . $_SESSION['ultimo_viaje']['id']);
            exit;
        }
    } else {
        $tipo_mensaje = 'error';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Uber - Pedir Viaje con Mapa</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: auto; background-color: white; border-radius: 10px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { flex: 1; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        #mapa { height: 400px; width: 100%; margin: 20px 0; border-radius: 10px; }
        button { 
            width: 100%; 
            padding: 15px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            font-size: 16px; 
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover { background: #218838; }
        .mensaje { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .exito { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚖 Pedir un Viaje con Mapa</h1>
        
        <div class="info-box">
            <strong>📍 Consejo:</strong> Escribe direcciones reales para ver el mapa (ej: "Plaza Mayor, Madrid" o "Torre Eiffel, París")
        </div>
        
        <?php if ($mensaje != ''): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="formViaje">
            <div class="form-row">
                <div class="form-group">
                    <label>Tu Nombre:</label>
                    <input type="text" name="pasajero" required placeholder="Ej. Sofía" id="nombre">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Origen:</label>
                    <input type="text" name="origen" required placeholder="Ej. Plaza Mayor, Madrid" id="origen">
                </div>
                <div class="form-group">
                    <label>Destino:</label>
                    <input type="text" name="destino" required placeholder="Ej. Puerta del Sol, Madrid" id="destino">
                </div>
            </div>
            
            <div id="mapa"></div>
            
            <button type="submit">🚗 Pedir Uber y Ver Ruta</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="viajes_mapa.php">Ver historial de viajes con mapas</a> | 
            <a href="index.php">Versión sin mapa</a>
        </p>
    </div>

    <script>
    var map = L.map('mapa').setView([40.4168, -3.7038], 13); // Madrid como centro por defecto
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    var marcadorOrigen = null;
    var marcadorDestino = null;
    var ruta = null;
    
    // Función para buscar dirección
    function buscarDireccion(direccion, tipo) {
        fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(direccion)}&format=json&limit=1`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    var lat = parseFloat(data[0].lat);
                    var lon = parseFloat(data[0].lon);
                    
                    if (tipo === 'origen') {
                        if (marcadorOrigen) map.removeLayer(marcadorOrigen);
                        marcadorOrigen = L.marker([lat, lon]).addTo(map)
                            .bindPopup('Origen: ' + direccion).openPopup();
                    } else {
                        if (marcadorDestino) map.removeLayer(marcadorDestino);
                        marcadorDestino = L.marker([lat, lon]).addTo(map)
                            .bindPopup('Destino: ' + direccion).openPopup();
                    }
                    
                    // Si tenemos ambos marcadores, dibujar ruta
                    if (marcadorOrigen && marcadorDestino) {
                        if (ruta) map.removeLayer(ruta);
                        ruta = L.polyline([
                            [marcadorOrigen.getLatLng().lat, marcadorOrigen.getLatLng().lng],
                            [marcadorDestino.getLatLng().lat, marcadorDestino.getLatLng().lng]
                        ], {color: 'blue', weight: 5}).addTo(map);
                        
                        // Ajustar el mapa para mostrar ambos puntos
                        var bounds = L.latLngBounds([
                            marcadorOrigen.getLatLng(),
                            marcadorDestino.getLatLng()
                        ]);
                        map.fitBounds(bounds, {padding: [50, 50]});
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Eventos cuando el usuario escribe
    document.getElementById('origen').addEventListener('change', function() {
        if (this.value) buscarDireccion(this.value, 'origen');
    });
    
    document.getElementById('destino').addEventListener('change', function() {
        if (this.value) buscarDireccion(this.value, 'destino');
    });
    </script>
</body>
</html>