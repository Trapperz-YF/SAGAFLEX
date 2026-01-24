<?php
// setup.php ACTUALIZADO
require 'db.php';

$sql = "
-- Tabla Usuarios (Ahora con BIO y FOTO)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    bio TEXT DEFAULT '¡Hola! Soy nuevo en Sagaflex.',
    profile_picture TEXT DEFAULT 'default.png',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla Posts
CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    category TEXT CHECK(category IN ('General', 'Tech', 'Random')) NOT NULL,
    is_private INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
";

try {
    $pdo->exec($sql);
    echo "Base de datos creada correctamente (versión corregida).";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>