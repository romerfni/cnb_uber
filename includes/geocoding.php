<?php
// includes/geocoding.php

class GeocodingService {
    
    // Convertir una dirección a coordenadas usando Nominatim (OpenStreetMap)
    public function obtenerCoordenadas($direccion) {
        // Limpiar la dirección para la URL
        $direccion = urlencode($direccion);
        
        // URL de Nominatim (servicio gratuito de OpenStreetMap)
        $url = "https://nominatim.openstreetmap.org/search?q={$direccion}&format=json&limit=1";
        
        // Configurar opciones de la petición HTTP
        $opciones = [
            'http' => [
                'header' => "User-Agent: MiUberEducativo/1.0\r\n"
            ]
        ];
        
        $contexto = stream_context_create($opciones);
        
        // Hacer la petición
        $respuesta = file_get_contents($url, false, $contexto);
        
        if ($respuesta === false) {
            return null;
        }
        
        $datos = json_decode($respuesta, true);
        
        if (empty($datos)) {
            return null;
        }
        
        // Devolver las coordenadas
        return [
            'lat' => floatval($datos[0]['lat']),
            'lng' => floatval($datos[0]['lon']),
            'nombre' => $datos[0]['display_name']
        ];
    }
    
    // Calcular distancia entre dos puntos (fórmula de Haversine)
    public function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
        $radioTierra = 6371; // Kilómetros
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distancia = $radioTierra * $c;
        
        return round($distancia, 2);
    }
    
    // Calcular tarifa basada en la distancia
    public function calcularTarifa($distancia) {
        $tarifaBase = 30; // Precio base
        $precioPorKm = 8; // Precio por kilómetro
        
        $tarifa = $tarifaBase + ($distancia * $precioPorKm);
        return round($tarifa, 2);
    }
}
?>