<?php
// router.php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);

// 1. BLOQUEAR ACCESO DIRECTO A .DB
if ($ext === 'db' || $ext === 'sqlite') {
    http_response_code(403);
    die("⛔ ACCESO DENEGADO: No puedes descargar la base de datos.");
}

// 2. Si el archivo existe (css, js, imagenes), sírvelo normalmente
if (file_exists(__DIR__ . $path) && $path !== '/') {
    return false; // Deja que PHP sirva el archivo estático
}

// 3. Si no es archivo estático, carga el index.php (o lo que corresponda)
// En tu caso, como usas rutas directas a archivos php, el router solo necesita proteger la DB.
// Si el archivo PHP existe, se ejecuta.
require_once 'index.php';
?>