<?php
// fix_likes.php
require 'db.php';

try {
    // Tabla relacional: Un usuario da like a un post
    // Usamos PRIMARY KEY compuesta para evitar duplicados (un usuario no puede dar 2 likes al mismo post)
    $sql = "
    CREATE TABLE IF NOT EXISTS likes (
        user_id INTEGER NOT NULL,
        post_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, post_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    );
    ";
    
    $pdo->exec($sql);
    echo "✅ Tabla 'likes' creada correctamente. <a href='home.php'>Volver al Home</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>