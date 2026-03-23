<?php
// viajes.php
session_start();
include 'includes/funciones.php';

// Procesar finalización de viaje si se recibe el parámetro
if (isset($_GET['finalizar'])) {
    finalizarViaje($_GET['finalizar']);
    header('Location: viajes.php'); // Redirigir para evitar reenviar el formulario
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Uber - Historial de Viajes</title>
    <style>
        body { font-family: Arial; margin: 40px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: auto; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
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
        .btn-finalizar {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
        }
        .btn-finalizar:hover {
            background: #218838;
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
        .btn-volver:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚖 Historial de Viajes</h1>
        
        <?php echo verHistorialViajes(); ?>
        
        <a href="index.php" class="btn-volver">← Pedir otro viaje</a>
    </div>
    
    <script>
    // Pequeño script para confirmar antes de finalizar un viaje
    function confirmarFinalizar() {
        return confirm('¿Estás seguro de finalizar este viaje?');
    }
    </script>
</body>
</html>