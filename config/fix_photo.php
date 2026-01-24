<?php
// fix_foto.php
require 'db.php';

try {
    // 1. Intentamos agregar la columna de foto de perfil
    $sql = "ALTER TABLE users ADD COLUMN profile_picture TEXT DEFAULT 'default.png'";
    $pdo->exec($sql);
    echo "✅ Éxito: Columna 'profile_picture' agregada.<br>";

} catch (PDOException $e) {
    // Si dice que ya existe, no pasa nada
    echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "Listo. Ahora intenta entrar a <a href='home.php'>home.php</a> nuevamente.";
?>