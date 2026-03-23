<?php
// includes/funciones.php - VERSIÓN COMPLETA (Fase 3 + Fase 4)
require_once 'conexion.php';
require_once 'geocoding.php';

// ============================================
// FUNCIONES DE LA FASE 3 (BÁSICAS)
// ============================================

// Función para obtener todos los conductores disponibles
function obtenerConductores() {
    $db = new ConexionDB();
    $conn = $db->getConexion();
    
    $sql = "SELECT * FROM usuarios WHERE tipo = 'conductor'";
    $resultado = $conn->query($sql);
    
    $conductores = [];
    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $conductores[] = $fila;
        }
    }
    
    $db->cerrar();
    return $conductores;
}

// Función para obtener un conductor aleatorio disponible
function obtenerConductorAleatorio() {
    $conductores = obtenerConductores();
    
    if (empty($conductores)) {
        return null;
    }
    
    // Seleccionar un conductor al azar
    $indice = array_rand($conductores);
    return $conductores[$indice];
}

// Función para buscar un pasajero por nombre (¡ESTA ES LA QUE FALTABA!)
function buscarPasajeroPorNombre($nombre) {
    $db = new ConexionDB();
    $conn = $db->getConexion();
    
    // Usar prepared statement para evitar inyección SQL
    $sql = "SELECT * FROM usuarios WHERE tipo = 'pasajero' AND nombre = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $pasajero = null;
    if ($resultado->num_rows > 0) {
        $pasajero = $resultado->fetch_assoc();
    }
    
    $stmt->close();
    $db->cerrar();
    return $pasajero;
}

// Función para crear un nuevo pasajero si no existe
function crearPasajero($nombre) {
    $db = new ConexionDB();
    $conn = $db->getConexion();
    
    $sql = "INSERT INTO usuarios (nombre, tipo, auto) VALUES (?, 'pasajero', NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        $db->cerrar();
        return $id;
    } else {
        $stmt->close();
        $db->cerrar();
        return false;
    }
}

// Función para solicitar un viaje (versión básica sin mapa)
function solicitarViaje($nombrePasajero, $origen, $destino) {
    // 1. Buscar si el pasajero ya existe
    $pasajero = buscarPasajeroPorNombre($nombrePasajero);
    
    // 2. Si no existe, crearlo
    if (!$pasajero) {
        $pasajero_id = crearPasajero($nombrePasajero);
        if (!$pasajero_id) {
            return "✗ Error al registrar al pasajero.";
        }
    } else {
        $pasajero_id = $pasajero['id'];
    }
    
    // 3. Buscar un conductor disponible
    $conductor = obtenerConductorAleatorio();
    
    if (!$conductor) {
        return "✗ Lo sentimos, no hay conductores disponibles.";
    }
    
    // 4. Guardar el viaje en la base de datos
    $db = new ConexionDB();
    $conn = $db->getConexion();
    
    $sql = "INSERT INTO viajes (pasajero_id, conductor_id, origen, destino, estado) 
            VALUES (?, ?, ?, ?, 'en progreso')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $pasajero_id, $conductor['id'], $origen, $destino);
    
    if ($stmt->execute()) {
        $viaje_id = $stmt->insert_id;
        $stmt->close();
        $db->cerrar();
        
        return "✓ Conductor {$conductor['nombre']} asignado! Auto: {$conductor['auto']}. Viaje de {$origen} a {$destino}";
    } else {
        $stmt->close();
        $db->cerrar();
        return "✗ Error al guardar el viaje: " . $conn->error;
    }
}

// Función para obtener el historial de viajes (versión básica)
function verHistorialViajes() {
    $db = new ConexionDB();
    $conn = $db->getConexion();
    
    $sql = "SELECT 
                v.id,
                p.nombre as pasajero,
                c.nombre as conductor,
                c.auto,
                v.origen,
                v.destino,
                v.estado,
                v.fecha_solicitud
            FROM viajes v
            JOIN usuarios p ON v.pasajero_id = p.id
            JOIN usuarios c ON v.conductor_id = c.id
            ORDER BY v.fecha_solicitud DESC";
    
    $resultado = $conn->query($sql);
    
    $html = "<h3>Historial de Viajes</h3>";
    
    if ($resultado->num_rows == 0) {
        $html .= "<p>Todavía no hay viajes.</p>";
    } else {
        $html .= "<table style='width:100%; border-collapse: collapse;'>";
        $html .= "<tr style='background: #007bff; color: white;'>
                    <th style='padding: 10px;'>ID</th>
                    <th style='padding: 10px;'>Pasajero</th>
                    <th style='padding: 10px;'>Conductor</th>
                    <th style='padding: 10px;'>Auto</th>
                    <th style='padding: 10px;'>Origen</th>
                    <th style='padding: 10px;'>Destino</th>
                    <th style='padding: 10px;'>Estado</th>
                    <th style='padding: 10px;'>Fecha</th>
                  </tr>";
        
        while ($viaje = $resultado->fetch_assoc()) {
            $color_estado = ($viaje['estado'] == 'finalizado') ? '#28a745' : '#ffc107';
            $html .= "<tr style='border-bottom: 1px solid #ddd;'>";
            $html .= "<td style='padding: 10px;'>{$viaje['id']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['pasajero']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['conductor']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['auto']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['origen']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['destino']}</td>";
            $html .= "<td style='padding: 10px;'><span style='background: {$color_estado}; color: white; padding: 3px 10px; border-radius: 3px;'>{$viaje['estado']}</span></td>";
            $html .= "<td style='padding: 10px;'>{$viaje['fecha_solicitud']}</td>";
            $html .= "</tr>";
        }
        $html .= "</table>";
    }
    
    $db->cerrar();
    return $html;
}

// Función para finalizar un viaje
function finalizarViaje($viaje_id) {
    $db = new ConexionDB();
    $conn = $db->getConexion();
    
    $sql = "UPDATE viajes SET estado = 'finalizado', fecha_finalizacion = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $viaje_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $db->cerrar();
        return true;
    } else {
        $stmt->close();
        $db->cerrar();
        return false;
    }
}

// ============================================
// FUNCIONES DE LA FASE 4 (MAPAS)
// ============================================

// Función para solicitar un viaje CON COORDENADAS (versión con mapa)
function solicitarViajeConMapa($nombrePasajero, $origen, $destino) {
    $geocoding = new GeocodingService();
    
    // 1. Obtener coordenadas del origen
    $coordenadasOrigen = $geocoding->obtenerCoordenadas($origen);
    if (!$coordenadasOrigen) {
        return "✗ No se pudo encontrar la dirección de origen.";
    }
    
    // 2. Obtener coordenadas del destino
    $coordenadasDestino = $geocoding->obtenerCoordenadas($destino);
    if (!$coordenadasDestino) {
        return "✗ No se pudo encontrar la dirección de destino.";
    }
    
    // 3. Calcular distancia
    $distancia = $geocoding->calcularDistancia(
        $coordenadasOrigen['lat'],
        $coordenadasOrigen['lng'],
        $coordenadasDestino['lat'],
        $coordenadasDestino['lng']
    );
    
    // 4. Calcular tarifa
    $tarifa = $geocoding->calcularTarifa($distancia);
    
    // 5. Buscar o crear pasajero
    $pasajero = buscarPasajeroPorNombre($nombrePasajero);
    if (!$pasajero) {
        $pasajero_id = crearPasajero($nombrePasajero);
        if (!$pasajero_id) {
            return "✗ Error al registrar al pasajero.";
        }
    } else {
        $pasajero_id = $pasajero['id'];
    }
    
    // 6. Buscar conductor
    $conductor = obtenerConductorAleatorio();
    if (!$conductor) {
        return "✗ Lo sentimos, no hay conductores disponibles.";
    }
    
    // 7. Guardar viaje con coordenadas
    $db = new ConexionDB();
    $conn = $db->getConexion();
    
    $sql = "INSERT INTO viajes 
            (pasajero_id, conductor_id, origen, destino, 
             origen_lat, origen_lng, destino_lat, destino_lng,
             distancia_km, tarifa, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en progreso')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iissdddddd", 
        $pasajero_id, 
        $conductor['id'], 
        $origen, 
        $destino,
        $coordenadasOrigen['lat'],
        $coordenadasOrigen['lng'],
        $coordenadasDestino['lat'],
        $coordenadasDestino['lng'],
        $distancia,
        $tarifa
    );
    
    if ($stmt->execute()) {
        $viaje_id = $stmt->insert_id;
        $stmt->close();
        $db->cerrar();
        
        // Guardar coordenadas en sesión para mostrarlas en el mapa
        $_SESSION['ultimo_viaje'] = [
            'id' => $viaje_id,
            'origen' => [
                'lat' => $coordenadasOrigen['lat'],
                'lng' => $coordenadasOrigen['lng'],
                'nombre' => $origen
            ],
            'destino' => [
                'lat' => $coordenadasDestino['lat'],
                'lng' => $coordenadasDestino['lng'],
                'nombre' => $destino
            ],
            'distancia' => $distancia,
            'tarifa' => $tarifa,
            'conductor' => $conductor['nombre'],
            'auto' => $conductor['auto']
        ];
        
        return "✓ Conductor {$conductor['nombre']} asignado! Auto: {$conductor['auto']}. Distancia: {$distancia} km. Tarifa: \${$tarifa}";
    } else {
        $stmt->close();
        $db->cerrar();
        return "✗ Error al guardar el viaje: " . $conn->error;
    }
}

// Función para obtener detalles de un viaje específico
function obtenerViajePorId($viaje_id) {
    $db = new ConexionDB();
    $conn = $db->getConexion();
    
    $sql = "SELECT 
                v.*,
                p.nombre as pasajero_nombre,
                c.nombre as conductor_nombre,
                c.auto as conductor_auto
            FROM viajes v
            JOIN usuarios p ON v.pasajero_id = p.id
            JOIN usuarios c ON v.conductor_id = c.id
            WHERE v.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $viaje_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $viaje = null;
    if ($resultado->num_rows > 0) {
        $viaje = $resultado->fetch_assoc();
    }
    
    $stmt->close();
    $db->cerrar();
    return $viaje;
}

// Función para ver historial con formato mejorado (con mapas)
function verHistorialViajesConMapa() {
    $db = new ConexionDB();
    $conn = $db->getConexion();
    
    $sql = "SELECT 
                v.*,
                p.nombre as pasajero,
                c.nombre as conductor,
                c.auto
            FROM viajes v
            JOIN usuarios p ON v.pasajero_id = p.id
            JOIN usuarios c ON v.conductor_id = c.id
            ORDER BY v.fecha_solicitud DESC";
    
    $resultado = $conn->query($sql);
    
    $html = "<h3>Historial de Viajes</h3>";
    
    if ($resultado->num_rows == 0) {
        $html .= "<p>Todavía no hay viajes.</p>";
    } else {
        $html .= "<table style='width:100%; border-collapse: collapse;'>";
        $html .= "<tr style='background: #007bff; color: white;'>
                    <th style='padding: 10px;'>ID</th>
                    <th style='padding: 10px;'>Pasajero</th>
                    <th style='padding: 10px;'>Conductor</th>
                    <th style='padding: 10px;'>Auto</th>
                    <th style='padding: 10px;'>Origen</th>
                    <th style='padding: 10px;'>Destino</th>
                    <th style='padding: 10px;'>Distancia</th>
                    <th style='padding: 10px;'>Tarifa</th>
                    <th style='padding: 10px;'>Estado</th>
                    <th style='padding: 10px;'>Mapa</th>
                  </tr>";
        
        while ($viaje = $resultado->fetch_assoc()) {
            $color_estado = ($viaje['estado'] == 'finalizado') ? '#28a745' : '#ffc107';
            
            $html .= "<tr style='border-bottom: 1px solid #ddd;'>";
            $html .= "<td style='padding: 10px;'>{$viaje['id']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['pasajero']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['conductor']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['auto']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['origen']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['destino']}</td>";
            $html .= "<td style='padding: 10px;'>{$viaje['distancia_km']} km</td>";
            $html .= "<td style='padding: 10px;'>\${$viaje['tarifa']}</td>";
            $html .= "<td style='padding: 10px;'><span style='background: {$color_estado}; color: white; padding: 3px 10px; border-radius: 3px;'>{$viaje['estado']}</span></td>";
            $html .= "<td style='padding: 10px;'><a href='mapa_viaje.php?id={$viaje['id']}' target='_blank' style='background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Ver mapa</a></td>";
            $html .= "</tr>";
        }
        $html .= "</table>";
    }
    
    $db->cerrar();
    return $html;
}
?>