<?php
require __DIR__ . '/../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /app/login.php"); exit; }
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header("Location: /app/home.php"); exit; }
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$post = $stmt->fetch();
if (!$post) die("Error de acceso.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar / Sagaflex</title>
    <link rel="icon" type="image/png" href="/public/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { board: { bg: '#FDFCF8', border: '#B8860B', accent: '#FFD700' } } } } }
    </script>
    <style> body { background-color: theme('colors.board.bg'); font-family: 'Consolas', monospace; } .input-retro { border: 2px solid theme('colors.board.border'); padding: 10px; width: 100%; background: white; } </style>
</head>
<body class="p-8 flex justify-center items-center min-h-screen">
    <div class="w-full max-w-lg border-4 border-board-border bg-white p-6 relative">
        <h2 class="text-xl font-bold mb-4 bg-board-accent inline-block px-2">MODIFICAR ENTRADA :: No.<?= $post['id'] ?></h2>
        
        <form action="actions.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_post">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">

            <textarea name="content" rows="6" class="input-retro resize-none text-lg"><?= htmlspecialchars($post['content']) ?></textarea>
            
            <div class="flex gap-4">
                <select name="category" class="input-retro w-1/2">
                    <option value="General" <?= $post['category'] == 'General' ? 'selected' : '' ?>>General</option>
                    <option value="Tech" <?= $post['category'] == 'Tech' ? 'selected' : '' ?>>Tech</option>
                    <option value="Random" <?= $post['category'] == 'Random' ? 'selected' : '' ?>>Random</option>
                </select>
                <label class="flex items-center gap-2 border-2 border-board-border px-4 w-1/2 cursor-pointer bg-gray-50 hover:bg-white">
                    <input type="checkbox" name="is_private" value="1" <?= $post['is_private'] ? 'checked' : '' ?>>
                    <span>LOCKED</span>
                </label>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex-1 bg-black text-white font-bold py-2 border-2 border-black hover:bg-white hover:text-black">GUARDAR</button>
                <a href="/app/home.php" class="flex-1 text-center py-2 border-2 border-red-600 text-red-600 font-bold hover:bg-red-600 hover:text-white">CANCELAR</a>
            </div>
        </form>
    </div>
</body>
</html>