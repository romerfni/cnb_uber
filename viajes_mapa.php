<?php
// viajes_mapa.php
session_start();
include 'includes/funciones.php';

if (isset($_GET['finalizar'])) {
    finalizarViaje($_GET['finalizar']);
    header('Location: viajes_mapa.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Uber - Historial con Mapas</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 1400px; margin: auto; background: white; border-radius: 10px; padding: 20px; }
        h1 { color: #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #007bff; color: white; padding: 12px; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background-color: #f5f5f5; }
        .estado { 
            padding: 5px 10px; 
            border-radius: 3px; 
            color: white;
            display: inline-block;
        }
        .btn-mapa {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
            margin-right: 5px;
        }
        .btn-finalizar {
            background: #ffc107;
            color: black;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
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
        .distancia {
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚖 Historial de Viajes con Mapas</h1>
        
        <?php echo verHistorialViajesConMapa(); ?>
        
        <div style="text-align: center;">
            <a href="index_mapa.php" class="btn-volver">← Pedir nuevo viaje</a>
        </div>
    </div>
</body>
</html>