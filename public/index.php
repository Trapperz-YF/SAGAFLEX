<?php
require 'db.php';
session_start();
if (isset($_SESSION['user_id'])) { header("Location: home.php"); exit; }
$stmt = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.is_private = 0 ORDER BY posts.created_at DESC LIMIT 5");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido / Sagaflex</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { board: { bg: '#FDFCF8', border: '#B8860B', text: '#2A2210', accent: '#FFD700' } },
                    fontFamily: { mono: ['Consolas', 'monospace'] },
                    boxShadow: { 'hard': '4px 4px 0px 0px rgba(184, 134, 11, 1)' }
                }
            }
        }
    </script>
    <style> body { background-color: theme('colors.board.bg'); font-family: theme('fontFamily.mono'); } .btn-retro { background-color: theme('colors.board.accent'); border: 2px solid black; box-shadow: theme('boxShadow.hard'); font-weight: bold; } .btn-retro:hover { transform: translate(2px,2px); box-shadow: 2px 2px 0px 0px black; } </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-4 border-[10px] border-board-border">

    <div class="max-w-2xl w-full text-center">
        <img src="logo-gold.png" class="h-40 w-auto mx-auto mb-8 drop-shadow-md">
        
        <h1 class="text-4xl font-bold mb-2 text-board-text">SAGAFLEX</h1>
        <p class="text-lg mb-8 text-[#857F72]">Sistema de Intercambio de Mensajes</p>

        <div class="flex justify-center gap-6 mb-12">
            <a href="login.php" class="btn-retro px-8 py-3 text-xl block min-w-[150px]">ENTRAR</a>
            <a href="register.php" class="border-2 border-black px-8 py-3 text-xl font-bold hover:bg-black hover:text-board-accent transition block min-w-[150px]">REGISTRO</a>
        </div>

        <div class="text-left border-2 border-black bg-white p-4 shadow-hard">
            <h3 class="font-bold border-b-2 border-black mb-2 bg-yellow-100 pl-1">ÚLTIMAS TRANSMISIONES:</h3>
            <ul class="text-sm space-y-2">
                <?php foreach ($posts as $post): ?>
                    <li class="truncate">
                        <span class="text-board-border font-bold">>></span> 
                        <span class="font-bold"><?= htmlspecialchars($post['username']) ?></span>: 
                        <?= htmlspecialchars(substr($post['content'], 0, 50)) ?>...
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

</body>
</html>