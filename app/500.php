<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error 500 / Sagaflex System</title>
    <link rel="icon" type="image/png" href="/public/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        board: {
                            bg: '#FDFCF8',       // Fondo Crema
                            border: '#B8860B',   // Borde Dorado Oscuro
                            text: '#2A2210',     // Texto Marrón
                            meta: '#857F72',     // Texto secundario
                            accent: '#FFD700',   // Dorado Brillante
                            link: '#800000'      // Rojo para enlaces/alertas
                        }
                    },
                    fontFamily: {
                        mono: ['Consolas', 'Monaco', 'Lucida Console', 'monospace'],
                        sans: ['Verdana', 'Arial', 'sans-serif'],
                    },
                    boxShadow: {
                        'hard': '4px 4px 0px 0px rgba(184, 134, 11, 1)', // Sombra dorada dura
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: theme('colors.board.bg');
            color: theme('colors.board.text');
            font-family: theme('fontFamily.mono'); /* Estilo terminal por defecto para errores */
        }
        .hard-border {
            border: 2px solid theme('colors.board.border');
        }
        .btn-retro {
            background-color: theme('colors.board.accent');
            color: black;
            font-family: theme('fontFamily.mono');
            font-weight: bold;
            border: 2px solid black;
            box-shadow: theme('boxShadow.hard');
            text-transform: uppercase;
            transition: transform 0.1s, box-shadow 0.1s;
        }
        .btn-retro:hover {
            transform: translate(1px, 1px);
            box-shadow: 2px 2px 0px 0px black;
        }
        .btn-retro:active {
            transform: translate(3px, 3px);
            box-shadow: none;
        }
        /* Animación suave para el "cursor" */
        @keyframes blink { 50% { opacity: 0; } }
        .cursor-blink { animation: blink 1s step-end infinite; }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-4 border-x-4 border-board-border">

    <div class="max-w-xl w-full">
        
        <div class="text-center mb-8">
            <img src="/public/logo-gold.png" alt="Sagaflex Logo" class="h-24 w-auto mx-auto mb-4 drop-shadow-sm opacity-50 grayscale">
            <div class="inline-block border-2 border-board-link text-board-link font-bold px-3 py-1 bg-red-50 tracking-widest text-sm">
                [SYSTEM_CRITICAL_ERROR]
            </div>
        </div>

        <div class="bg-white border-2 border-board-border p-8 shadow-hard relative">
            
            <div class="absolute top-2 left-2 w-2 h-2 bg-board-border rounded-full"></div>
            <div class="absolute top-2 right-2 w-2 h-2 bg-board-border rounded-full"></div>
            <div class="absolute bottom-2 left-2 w-2 h-2 bg-board-border rounded-full"></div>
            <div class="absolute bottom-2 right-2 w-2 h-2 bg-board-border rounded-full"></div>

            <h1 class="text-3xl font-bold mb-4 border-b-2 border-dashed border-gray-300 pb-2">
                ERROR 500
            </h1>

            <p class="font-sans text-lg mb-6 leading-relaxed">
                El núcleo del sistema ha encontrado una anomalía inesperada. La transmisión de datos se ha interrumpido para proteger la integridad del archivo.
            </p>

            <div class="bg-[#F8F5E9] border border-board-border p-4 text-sm mb-8 font-mono text-board-meta">
                <p>> DIAGNOSTIC: INTERNAL_SERVER_ERROR</p>
                <p>> STATUS: ABORTED</p>
                <p>> TIMESTAMP: <?= date('Y-m-d H:i:s') ?></p>
                <p class="mt-2 text-board-link">> ACTION REQUIRED: MANUAL RESET_<span class="cursor-blink bg-board-link w-2 h-4 inline-block align-middle ml-1"></span></p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/app/home.php" class="btn-retro px-8 py-3 text-center">
                    REINICIAR SISTEMA (HOME)
                </a>
            </div>
        </div>

        <div class="mt-8 text-center text-board-meta text-xs">
            SAGAFLEX X // ARCHIVE INTEGRITY CHECKER V2.0
        </div>

    </div>

</body>
</html>