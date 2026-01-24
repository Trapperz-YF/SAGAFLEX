<?php
// fix_db.php
require 'db.php';

try {
    // Comando SQL para agregar la columna 'bio' a la tabla existente
    $sql = "ALTER TABLE users ADD COLUMN bio TEXT DEFAULT '¡Hola! Soy nuevo en Sagaflex.'";
    
    $pdo->exec($sql);
    echo "✅ Éxito: Se ha agregado la columna 'bio' a la tabla de usuarios. <br>";
    echo "Ya puedes volver a <a href='profile.php'>tu perfil</a>.";

} catch (PDOException $e) {
    echo "⚠️ Error o la columna ya existía: " . $e->getMessage();
}
?>