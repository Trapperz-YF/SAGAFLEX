<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$userId = $_SESSION['user_id'];

// Obtener mi avatar
$stmtMe = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtMe->execute([$userId]);
$myUser = $stmtMe->fetch();
$myAvatar = $myUser['profile_picture'] ?? 'default.png';

// Consulta SQL
$sql = "
    SELECT 
        posts.*, 
        users.username, 
        users.profile_picture,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as total_likes,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as liked_by_me
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    WHERE posts.is_private = 0 OR posts.user_id = ? 
    ORDER BY posts.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, $userId]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tablón / Sagaflex X</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        board: {
                            bg: '#FDFCF8',       // Crema papel
                            post: '#FFFDF5',     // Post fondo
                            border: '#B8860B',   // Dorado Oscuro (Dark Goldenrod) para bordes duros
                            text: '#2A2210',     // Marrón casi negro
                            meta: '#857F72',     // Gris cálido para info
                            link: '#800000',     // Rojo sangre para acciones
                            accent: '#FFD700'    // Dorado brillante logo
                        }
                    },
                    fontFamily: {
                        mono: ['Consolas', 'Monaco', 'Lucida Console', 'Courier New', 'monospace'],
                        sans: ['Verdana', 'Arial', 'sans-serif'],
                    },
                    boxShadow: {
                        'hard': '4px 4px 0px 0px rgba(184, 134, 11, 1)', // Sombra sólida dura
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/petite-vue" defer init></script>
    <style>
        body { background-color: theme('colors.board.bg'); color: theme('colors.board.text'); font-family: theme('fontFamily.sans'); }
        .hard-border { border: 2px solid theme('colors.board.border'); }
        .meta { font-family: theme('fontFamily.mono'); font-size: 0.8rem; color: theme('colors.board.meta'); }
        .btn-retro {
            background-color: theme('colors.board.accent');
            color: black;
            font-family: theme('fontFamily.mono');
            font-weight: bold;
            border: 2px solid black;
            box-shadow: theme('boxShadow.hard');
            transition: all 0.1s;
            text-transform: uppercase;
        }
        .btn-retro:hover { transform: translate(1px, 1px); box-shadow: 2px 2px 0px 0px rgba(0,0,0,1); }
        .btn-retro:active { transform: translate(4px, 4px); box-shadow: none; }
        .input-retro {
            background: white;
            border: 2px solid theme('colors.board.border');
            padding: 0.5rem;
            font-family: theme('fontFamily.mono');
        }
        .input-retro:focus { outline: none; background-color: #FFFFF0; border-color: black; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="overflow-x-hidden">

<div class="container mx-auto max-w-7xl flex min-h-screen border-x-2 border-board-border bg-board-bg">
    
    <header class="hidden lg:flex w-64 flex-col sticky top-0 h-screen p-4 border-r-2 border-board-border bg-[#F8F5E9]">
        <div class="mb-8 p-2 text-center">
            <a href="home.php">
                <img src="logo-gold.png" alt="Sagaflex" class="h-24 w-auto object-contain mx-auto drop-shadow-sm">
            </a>
            <div class="font-mono text-xs font-bold text-board-border mt-2 tracking-widest">[SYSTEM_ONLINE]</div>
        </div>
        
        <nav class="space-y-4 font-mono text-lg">
            <a href="home.php" class="block p-2 hover:bg-board-border hover:text-white transition border-l-4 border-transparent hover:border-black">
                > /Tablón/
            </a>
            <a href="profile.php?user_id=<?= $_SESSION['user_id'] ?>" class="block p-2 hover:bg-board-border hover:text-white transition border-l-4 border-transparent hover:border-black">
                > /Mi_Archivo/
            </a>
            <a href="actions.php?action=logout" class="block p-2 text-red-700 hover:bg-red-700 hover:text-white transition mt-8 font-bold border-l-4 border-transparent hover:border-black">
                [X] DESCONECTAR
            </a>
        </nav>
    </header>

    <main class="flex-1 min-h-screen">
        
        <div class="lg:hidden p-4 border-b-2 border-board-border bg-board-accent flex justify-between items-center sticky top-0 z-20">
            <img src="logo-gold.png" class="h-8 w-auto">
            <a href="actions.php?action=logout" class="font-mono font-bold border-2 border-black px-2 bg-white">[SALIR]</a>
        </div>

        <div class="p-6 bg-[#FDFCF5] border-b-4 border-board-border" v-scope="{ chars: 0, max: 280, content: '' }">
            <h2 class="font-mono text-sm font-bold text-board-link mb-2">// NUEVA_ENTRADA</h2>
            <form action="actions.php" method="POST" class="flex gap-4">
                <div class="hidden sm:block w-16 h-16 hard-border bg-white flex-shrink-0">
                    <img src="uploads/<?= htmlspecialchars($myAvatar) ?>" class="w-full h-full object-cover grayscale hover:grayscale-0 transition">
                </div>

                <div class="flex-1 space-y-3">
                    <textarea 
                        name="content" v-model="content" @input="chars = $el.value.length" :maxlength="max"
                        class="input-retro w-full h-24 resize-none text-lg" 
                        placeholder="Escriba aquí..."></textarea>
                    
                    <div class="flex flex-wrap justify-between items-center gap-4">
                        <div class="flex gap-2 font-mono text-sm">
                            <select name="category" class="input-retro py-1 cursor-pointer">
                                <option value="General">:: General</option>
                                <option value="Tech">:: Tech</option>
                                <option value="Random">:: Random</option>
                            </select>
                            <label class="flex items-center gap-2 cursor-pointer select-none bg-white border-2 border-board-border px-2">
                                <input type="checkbox" name="is_private" value="1" class="accent-board-link"> 
                                <span>LOCKED</span>
                            </label>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="font-mono font-bold" :class="chars > 260 ? 'text-red-600' : 'text-board-border'">{{ chars }}/{{ max }}</span>
                            <button type="submit" :disabled="chars === 0" class="btn-retro px-6 py-2 disabled:opacity-50 disabled:shadow-none">
                                ENVIAR >>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="p-4 space-y-6 pb-20 bg-board-bg">
            <?php foreach ($posts as $post): ?>
                <article class="relative bg-white border-2 border-board-border p-4 shadow-[4px_4px_0px_#E5E7EB]">
                    <div class="meta flex flex-wrap items-baseline gap-2 mb-3 pb-2 border-b-2 border-dashed border-gray-300">
                        <span class="text-board-link font-bold text-base"><?= htmlspecialchars($post['username']) ?></span>
                        <span>ID:<?= substr(md5($post['user_id']), 0, 8) ?></span>
                        <span><?= date("Y/m/d(D)H:i", strtotime($post['created_at'])) ?></span>
                        <span>No.<?= $post['id'] ?></span>
                        <span class="ml-auto font-bold bg-board-border text-white px-1 text-xs uppercase"><?= $post['category'] ?></span>
                        <?php if($post['is_private']): ?>
                            <span class="text-red-600 font-bold bg-yellow-100 px-1 border border-red-200">[PRIVATE]</span>
                        <?php endif; ?>
                    </div>

                    <div class="flex gap-4">
                         <a href="profile.php?user_id=<?= $post['user_id'] ?>" class="flex-shrink-0">
                            <div class="w-16 h-16 border-2 border-black p-0.5 bg-white">
                                <img src="uploads/<?= htmlspecialchars($post['profile_picture'] ?? 'default.png') ?>" class="w-full h-full object-cover">
                            </div>
                        </a>
                        
                        <div class="flex-1">
                            <p class="text-base leading-relaxed whitespace-pre-wrap text-black font-medium"><?= htmlspecialchars($post['content']) ?></p>
                            
                            <div class="mt-4 flex items-center justify-end gap-6 meta select-none">
                                <?php if($post['user_id'] == $_SESSION['user_id']): ?>
                                    <a href="edit.php?id=<?= $post['id'] ?>" class="hover:text-board-link hover:underline">[EDIT]</a>
                                    <form action="actions.php" method="POST" onsubmit="return confirm('¿Eliminar registro permanentemente?');" class="inline">
                                        <input type="hidden" name="action" value="delete_post"><input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="hover:text-red-600 hover:underline">[DEL]</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form action="actions.php" method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_like"><input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="group flex items-center gap-1 hover:text-board-border transition">
                                        <?php if($post['liked_by_me']): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-board-accent drop-shadow-sm">
                                              <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                                            </svg>
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                            </svg>
                                        <?php endif; ?>
                                        <span class="font-bold text-lg"><?= $post['total_likes'] > 0 ? $post['total_likes'] : '' ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>
    
    <aside class="hidden xl:block w-72 p-6 border-l-2 border-board-border bg-[#FDFCF8]">
        <h3 class="font-mono font-bold text-board-link mb-4 bg-yellow-100 p-1 border border-yellow-200 text-center">// TENDENCIAS</h3>
        <ul class="space-y-3 font-mono text-sm">
             <li><a href="#" class="text-board-text hover:text-board-link hover:underline">>> #SagaflexX</a></li>
             <li><a href="#" class="text-board-text hover:text-board-link hover:underline">>> #NuevoDiseño</a></li>
             <li><a href="#" class="text-board-text hover:text-board-link hover:underline">>> #4chanVibes</a></li>
        </ul>
        <div class="mt-8 p-4 border-2 border-black bg-board-accent text-center font-bold font-mono">
            AD: ÚNETE A LA SAGA
        </div>
    </aside>

</div>
</body>
</html>