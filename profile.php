<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$profileId = $_GET['user_id'] ?? header("Location: home.php");

$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$profileId]);
$userProfile = $stmtUser->fetch();
if (!$userProfile) die("Usuario no encontrado");

if ($profileId == $_SESSION['user_id']) {
    $sql = "SELECT posts.*, (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as total_likes, (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as liked_by_me FROM posts WHERE user_id = ? ORDER BY created_at DESC";
    $params = [$_SESSION['user_id'], $profileId];
} else {
    $sql = "SELECT posts.*, (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as total_likes, (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as liked_by_me FROM posts WHERE user_id = ? AND is_private = 0 ORDER BY created_at DESC";
     $params = [$_SESSION['user_id'], $profileId];
}
$stmtPosts = $pdo->prepare($sql);
$stmtPosts->execute($params);
$posts = $stmtPosts->fetchAll();

$isEditing = isset($_GET['edit']) && $_GET['edit'] == 'true' && $profileId == $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Archivo: <?= htmlspecialchars($userProfile['username']) ?></title>
    <link rel="icon" type="image/png" href="favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        board: { bg: '#FDFCF8', border: '#B8860B', text: '#2A2210', meta: '#857F72', link: '#800000', accent: '#FFD700' }
                    },
                    fontFamily: { mono: ['Consolas', 'monospace'], sans: ['Verdana', 'sans-serif'] },
                    boxShadow: { 'hard': '4px 4px 0px 0px rgba(184, 134, 11, 1)' }
                }
            }
        }
    </script>
    <style>
        body { background-color: theme('colors.board.bg'); color: theme('colors.board.text'); font-family: theme('fontFamily.sans'); }
        .hard-border { border: 2px solid theme('colors.board.border'); }
        .input-retro { background: white; border: 2px solid theme('colors.board.border'); padding: 0.5rem; font-family: theme('fontFamily.mono'); }
        .btn-retro { background-color: theme('colors.board.accent'); color: black; font-family: theme('fontFamily.mono'); font-weight: bold; border: 2px solid black; box-shadow: theme('boxShadow.hard'); }
    </style>
</head>
<body class="p-4 md:p-8">

<div class="container mx-auto max-w-4xl">
    
    <div class="mb-6 font-mono font-bold text-lg">
        <a href="home.php" class="hover:underline text-board-link"><< VOLVER AL TABLÓN</a>
    </div>

    <div class="hard-border bg-white p-6 shadow-hard mb-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 bg-board-border text-white font-mono text-xs px-2 py-1">CONFIDENTIAL</div>
        
        <div class="flex flex-col md:flex-row gap-8">
            <div class="flex-shrink-0 text-center">
                <div class="w-40 h-40 border-4 border-black p-1 bg-white mx-auto">
                    <img src="uploads/<?= htmlspecialchars($userProfile['profile_picture'] ?? 'default.png') ?>" class="w-full h-full object-cover grayscale hover:grayscale-0 transition">
                </div>
                <div class="mt-2 font-mono text-xs text-board-meta">IMG_REF: <?= substr(md5($userProfile['id']), 0, 6) ?></div>
            </div>

            <div class="flex-1">
                <?php if ($isEditing): ?>
                    <form action="actions.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">
                        <div>
                            <label class="block font-mono font-bold text-sm">NUEVA IMAGEN:</label>
                            <input type="file" name="profile_pic" class="input-retro w-full text-sm">
                        </div>
                        <div>
                            <label class="block font-mono font-bold text-sm">BIOGRAFÍA:</label>
                            <textarea name="bio" rows="3" class="input-retro w-full resize-none"><?= htmlspecialchars($userProfile['bio']) ?></textarea>
                        </div>
                        <div class="flex gap-4">
                            <button type="submit" class="btn-retro px-6 py-2">GUARDAR DATOS</button>
                            <a href="profile.php?user_id=<?= $profileId ?>" class="font-mono text-red-600 underline self-center">[CANCELAR]</a>
                        </div>
                    </form>
                <?php else: ?>
                    <h1 class="font-mono text-3xl font-bold border-b-2 border-black pb-2 mb-4 uppercase">
                        <?= htmlspecialchars($userProfile['username']) ?>
                    </h1>
                    
                    <div class="space-y-2 font-mono text-sm">
                        <p><span class="font-bold text-board-meta">ESTADO:</span> ACTIVO</p>
                        <p><span class="font-bold text-board-meta">REGISTRO:</span> <?= date("Y-m-d", strtotime($userProfile['created_at'])) ?></p>
                        <p><span class="font-bold text-board-meta">ENTRADAS:</span> <?= count($posts) ?></p>
                    </div>

                    <div class="mt-6 p-4 bg-[#F8F5E9] border border-board-border font-serif italic text-lg">
                        "<?= htmlspecialchars($userProfile['bio'] ?? 'Sin datos biográficos registrados.') ?>"
                    </div>

                    <?php if($profileId == $_SESSION['user_id']): ?>
                        <div class="mt-6 text-right">
                            <a href="profile.php?user_id=<?= $profileId ?>&edit=true" class="btn-retro px-4 py-1 text-sm inline-block">EDITAR EXPEDIENTE</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h3 class="font-mono font-bold text-xl mb-4 border-b-2 border-board-border inline-block">// REGISTRO DE ACTIVIDAD</h3>
    
    <div class="space-y-4">
        <?php if(empty($posts)): ?>
            <div class="p-8 text-center font-mono border-2 border-dashed border-board-border text-board-meta">
                NO SE ENCONTRARON ENTRADAS.
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="bg-white border border-board-border p-4 hover:shadow-md transition">
                    <div class="font-mono text-xs text-board-meta mb-2 border-b border-gray-100 pb-1 flex justify-between">
                        <span>FECHA: <?= date("Y/m/d H:i", strtotime($post['created_at'])) ?></span>
                        <span class="font-bold text-board-link"><?= $post['category'] ?></span>
                    </div>
                    <p class="whitespace-pre-wrap"><?= htmlspecialchars($post['content']) ?></p>
                    
                    <div class="mt-2 text-right">
                         <?php if($post['liked_by_me']): ?>
                             <span class="text-board-accent text-xl">♥</span>
                         <?php else: ?>
                             <span class="text-gray-300 text-xl">♡</span>
                         <?php endif; ?>
                         <span class="font-mono font-bold text-sm"><?= $post['total_likes'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</body>
</html>