<?php session_start(); if(isset($_SESSION['user_id'])) header("Location: home.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso / Sagaflex</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { board: { bg: '#FDFCF8', border: '#B8860B', accent: '#FFD700' } }, boxShadow: { 'hard': '6px 6px 0px 0px black' } } } }
    </script>
    <style> body { background-color: theme('colors.board.bg'); font-family: 'Consolas', monospace; } .input-retro { border: 2px solid black; padding: 10px; width: 100%; background: white; } .input-retro:focus { background: #FFD700; outline: none; } </style>
</head>
<body class="flex items-center justify-center h-screen">
    <div class="w-full max-w-md p-8 border-4 border-black bg-white shadow-hard relative">
        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-board-accent border-2 border-black px-4 py-1 font-bold">
            ACCESO RESTRINGIDO
        </div>

        <div class="text-center mb-8">
            <img src="logo-gold.png" class="h-20 mx-auto">
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="bg-red-100 border-2 border-red-500 text-red-700 p-2 mb-4 text-center font-bold">
                ERROR: <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form action="actions.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <div>
                <label class="block font-bold mb-1">IDENTIFICADOR (EMAIL)</label>
                <input name="email" type="email" required class="input-retro">
            </div>
            <div>
                <label class="block font-bold mb-1">CLAVE DE ACCESO</label>
                <input name="password" type="password" required class="input-retro">
            </div>
            <button type="submit" class="w-full bg-black text-white py-3 font-bold text-lg hover:bg-board-accent hover:text-black border-2 border-transparent hover:border-black transition">
                INICIAR SESIÓN >>
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm">
            <a href="register.php" class="underline hover:text-board-border">SOLICITAR NUEVA CUENTA</a>
        </div>
    </div>
</body>
</html>