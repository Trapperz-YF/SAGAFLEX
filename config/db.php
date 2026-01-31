<?php
// config/db.php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../errores.log');
try {
    // __DIR__ es la carpeta 'config'. 
    // '/../' nos sube un nivel hacia la raíz (SAGAFLEX)
    $rutaBaseDatos = __DIR__ . '/../sagaflex.db';

    // Creamos la conexión usando esa ruta absoluta
    $pdo = new PDO("sqlite:" . $rutaBaseDatos);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>