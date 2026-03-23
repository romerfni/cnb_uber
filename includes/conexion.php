<?php
// includes/conexion.php

class ConexionDB {
    private $servidor = "localhost";
    private $usuario = "root";
    private $password = "";
    private $base_datos = "uber_simple";
    private $conexion;
    
    // Constructor: se ejecuta automáticamente al crear el objeto
    public function __construct() {
        $this->conectar();
    }
    
    // Método para conectar a la base de datos
    private function conectar() {
        $this->conexion = new mysqli(
            $this->servidor, 
            $this->usuario, 
            $this->password, 
            $this->base_datos
        );
        
        // Verificar si hay error
        if ($this->conexion->connect_error) {
            die("Error de conexión: " . $this->conexion->connect_error);
        }
        
        // Configurar para que use UTF-8 (acentos y ñ)
        $this->conexion->set_charset("utf8");
    }
    
    // Método para obtener la conexión
    public function getConexion() {
        return $this->conexion;
    }
    
    // Método para cerrar la conexión
    public function cerrar() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }
}
?>