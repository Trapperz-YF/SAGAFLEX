<?php
require_once '../config.php';
require ROOT_PATH . '/config/db.php';
session_start();

// Aumentamos límite de memoria para subida de imágenes pesadas
ini_set('memory_limit', '256M');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================================================
// 1. AUTENTICACIÓN
// ============================================================================

if ($action === 'register') {
    try {
        if ($action === 'register') {
            $user = trim($_POST['username']);
            $email = trim($_POST['email']);
            $pass = $_POST['password'];

            // 1. Validar campos vacíos
            if (empty($user) || empty($email) || empty($pass)) {
                header("Location: /app/register.php?error=Todos los campos son obligatorios");
                exit;
            }

            // 2. Validación REGEX para el NOMBRE DE USUARIO
            // Permite: Letras (a-z), Números (0-9) y guiones bajos (_)
            // Longitud: Mínimo 3, máximo 20 caracteres
            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $user)) {
                header("Location: /app/register.php?error=El usuario solo puede tener letras, números y guiones bajo (3-20 caracteres).");
                exit;
            }

            // 3. Validación para el EMAIL
            // filter_var es más seguro y robusto que un regex manual para emails
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header("Location: /app/register.php?error=El formato del correo electrónico no es válido.");
                exit;
            }

            try {
                $passHash = password_hash($pass, PASSWORD_BCRYPT);

                // Asignamos 'default.png' al registrarse
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, profile_picture) VALUES (?, ?, ?, 'default.png')");
                $stmt->execute([$user, $email, $passHash]);

                header("Location: /app/login.php?error=Registro exitoso, por favor inicia sesión.");
                exit;
            } catch (Throwable $e) {
                // Verificar si el error es por duplicado (código SQLSTATE 23000)
                if ($e->getCode() == 23000) {
                    header("Location: /app/register.php?error=El usuario o el email ya están registrados.");
                } else {
                    // Loguear el error real y mostrar página 500
                    $currentUser = 'Guest';
                    error_log("[SAGAFLEX CRITICAL] User: $currentUser - Action: Register - Error: " . $e->getMessage());
                    header("Location: /app/500.php");
                }
                exit;
            }
        }
    } catch (Throwable $e) {
        // 1. Obtener usuario de forma segura (Si no hay sesión, es 'Guest')
        $currentUser = $_SESSION['user_id'] ?? 'Guest';

        // 2. Registrar el error técnico en el servidor (Invisible para el usuario)
        // __FILE__ y __LINE__ ayudan a saber exactamente dónde falló
        error_log("[SAGAFLEX CRITICAL] User: $currentUser - File: " . __FILE__ . " - Error: " . $e->getMessage());

        // 3. Redirigir a la pantalla de error "Bonita"
        header("Location: /app/500.php");
        exit;
    }
}

if ($action === 'login') {
 try {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: /app/home.php");
    } else {
        header("Location: /app/login.php?error=Credenciales incorrectas.");
    }
    exit;
    } catch (Throwable $e) {
        // 1. Obtener usuario de forma segura (Si no hay sesión, es 'Guest')
        $currentUser = $_SESSION['user_id'] ?? 'Guest';

        // 2. Registrar el error técnico en el servidor (Invisible para el usuario)
        // __FILE__ y __LINE__ ayudan a saber exactamente dónde falló
        error_log("[SAGAFLEX CRITICAL] User: $currentUser - File: " . __FILE__ . " - Error: " . $e->getMessage());

        // 3. Redirigir a la pantalla de error "Bonita"
        header("Location: /app/500.php");
        exit;
    }
}

if ($action === 'logout') {
    session_destroy();
    header("Location: /index.php");
    exit;
}

// ============================================================================
// 2. GESTIÓN DE PERFIL (BIO + FOTO)
// ============================================================================

if ($action === 'update_profile') {
    // 1. Verificar sesión
    if (!isset($_SESSION['user_id'])) {
        header("Location: /app/login.php");
        exit;
    }

    try {
        $userId = $_SESSION['user_id'];
        
        // ============================================================
        // 2. SANITIZACIÓN DE LA BIO (Blindaje)
        // ============================================================
        $bio = $_POST['bio'] ?? ''; // Usamos operador null coalescing para evitar warnings
        
        // A. Quitar espacios en blanco inicio/fin
        $bio = trim($bio); 
        
        // B. ELIMINAR CUALQUIER ETIQUETA HTML (Crucial para XSS)
        // Esto convierte "<script>alert('hack')</script>" en "alert('hack')"
        $bio = strip_tags($bio); 

        // C. (Opcional) Limitar longitud a 255 o 500 caracteres para evitar spam masivo
        // Usamos mb_substr para soportar tildes y emojis correctamente
        $bio = mb_substr($bio, 0, 500, 'UTF-8');


        // ============================================================
        // 3. LOGICA DE SUBIDA DE IMAGEN (Tu código original)
        // ============================================================
        $uploadDir = ROOT_PATH . '/uploads/';

        // Verificar si se intentó subir un archivo
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['size'] > 0) {

            // A. Chequear errores de PHP
            if ($_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
                // ... (Tu array de errores se mantiene igual)
                $errorCode = $_FILES['profile_pic']['error'];
                throw new Exception("Error al subir imagen. Código: $errorCode");
            }

            // B. Validar tipo MIME
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['profile_pic']['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                // Usamos throw para que el catch capture y redirija
                throw new Exception("Tipo de archivo no permitido ($fileType).");
            }

            // C. Verificar carpeta
            if (!is_dir($uploadDir)) {
                throw new Exception("La carpeta uploads no existe.");
            }

            // D. Generar nombre único y mover
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $newFilename = uniqid('u' . $userId . '_', true) . '.' . $ext;
            $destination = $uploadDir . $newFilename;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                // ÉXITO: Actualizar FOTO + BIO
                $stmt = $pdo->prepare("UPDATE users SET bio = ?, profile_picture = ? WHERE id = ?");
                $stmt->execute([$bio, $newFilename, $userId]);
            } else {
                throw new Exception("Fallo al mover el archivo subido.");
            }
        } else {
            // SI NO HAY FOTO: Solo actualizar BIO
            $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
            $stmt->execute([$bio, $userId]);
        }

        header("Location: /app/profile.php?user_id=$userId");
        exit;

    } catch (Throwable $e) {
        // Mismo sistema de error que implementamos antes
        $currentUser = $_SESSION['user_id'] ?? 'Guest';
        error_log("[SAGAFLEX CRITICAL] User: $currentUser - Action: Update Profile - Error: " . $e->getMessage());
        header("Location: /app/500.php");
        exit;
    }
}

// ============================================================================
// 3. CRUD DE POSTS
// ============================================================================

if ($action === 'create_post') {
    // 1. Verificar sesión
    if (!isset($_SESSION['user_id'])) {
        header("Location: /app/login.php");
        exit;
    }

    try {
        $userId = $_SESSION['user_id'];
        
        // ============================================================
        // 2. SANITIZACIÓN DE CONTENIDO (Blindaje)
        // ============================================================
        $content = $_POST['content'] ?? '';

        // A. Limpieza básica
        $content = trim($content);

        // B. ELIMINAR CUALQUIER BASURA (HTML/JS)
        // Convierte "Hola <script>alert(1)</script>" en "Hola alert(1)"
        $content = strip_tags($content);

        // C. LÍMITE DE CARACTERES (Consistencia con Frontend)
        // Si mandan más de 280 caracteres, lo cortamos sin piedad.
        // Usamos mb_substr para respetar tildes y emojis.
        $content = mb_substr($content, 0, 280, "UTF-8");

        // D. Validar que no haya quedado vacío después de limpiar
        if (empty($content)) {
            header("Location: /app/home.php");
            exit;
        }

        // ============================================================
        // 3. REGLA ANTI-SPAM
        // ============================================================
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND created_at > datetime('now', '-10 minutes')");
        $stmt->execute([$userId]);
        if ($stmt->fetch()['total'] >= 3) {
            die("❌ ERROR ANTI-SPAM: Estás publicando muy rápido. (Límite: 3 posts cada 10 min). <br><a href='/app/home.php'>Volver</a>");
        }

        // ============================================================
        // 4. VALIDACIÓN DE CATEGORÍA (Lista Blanca)
        // ============================================================
        $allowedCategories = ['General', 'Tech', 'Random'];
        $category = $_POST['category'] ?? 'General';

        // Si la categoría enviada NO está en la lista permitida, forzamos 'General'
        if (!in_array($category, $allowedCategories)) {
            $category = 'General';
        }

        // ============================================================
        // 5. INSERTAR
        // ============================================================
        $isPrivate = isset($_POST['is_private']) ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, category, is_private) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $content, $category, $isPrivate]);
        
        header("Location: /app/home.php");
        exit;

    } catch (Throwable $e) {
        $currentUser = $_SESSION['user_id'] ?? 'Guest';
        error_log("[SAGAFLEX CRITICAL] User: $currentUser - Action: Create Post - Error: " . $e->getMessage());
        header("Location: /app/500.php");
        exit;
    }
}

if ($action === 'delete_post') {
    // 1. Verificar sesión (Mejor redirigir que usar die)
    if (!isset($_SESSION['user_id'])) {
        header("Location: /app/login.php");
        exit;
    }

    // 2. Validar que el ID del post sea válido
    $postId = $_POST['post_id'] ?? null;
    if (empty($postId) || !is_numeric($postId)) {
        // Si el ID es inválido, simplemente volvemos al home sin hacer nada
        header("Location: /app/home.php");
        exit;
    }

    try {
        // 3. Ejecutar borrado seguro
        // La cláusula "AND user_id = ?" asegura que nadie borre posts ajenos (IDOR protection)
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$postId, $_SESSION['user_id']]);
        
        header("Location: /app/home.php");
        exit;

    } catch (Throwable $e) {
        // 4. Manejo de Errores Consistente
        $currentUser = $_SESSION['user_id'] ?? 'Guest';
        error_log("[SAGAFLEX CRITICAL] User: $currentUser - Action: Delete Post - Error: " . $e->getMessage());
        header("Location: /app/500.php");
        exit;
    }
}

if ($action === 'update_post') {
    // 1. Verificar sesión
    if (!isset($_SESSION['user_id'])) {
        header("Location: /app/login.php");
        exit;
    }

    try {
        $userId = $_SESSION['user_id'];
        $postId = $_POST['post_id'] ?? null;

        // Validar que tengamos un ID de post válido
        if (empty($postId) || !is_numeric($postId)) {
            header("Location: /app/home.php");
            exit;
        }

        // ============================================================
        // 2. SANITIZACIÓN DE CONTENIDO (Igual que create_post)
        // ============================================================
        $content = $_POST['content'] ?? '';
        $content = trim($content);
        
        // A. Eliminar scripts y HTML
        $content = strip_tags($content);
        
        // B. Limitar a 280 caracteres
        $content = mb_substr($content, 0, 280, "UTF-8");

        // C. Validar que no esté vacío
        if (empty($content)) {
            // Podrías redirigir con error, o simplemente volver al home
            header("Location: /app/home.php"); 
            exit;
        }

        // ============================================================
        // 3. VALIDACIÓN DE CATEGORÍA
        // ============================================================
        $allowedCategories = ['General', 'Tech', 'Random'];
        $category = $_POST['category'] ?? 'General';

        if (!in_array($category, $allowedCategories)) {
            $category = 'General';
        }

        // ============================================================
        // 4. ACTUALIZAR
        // ============================================================
        $isPrivate = isset($_POST['is_private']) ? 1 : 0;

        // La cláusula "AND user_id = ?" es CRÍTICA para evitar que alguien edite posts ajenos
        $stmt = $pdo->prepare("UPDATE posts SET content = ?, category = ?, is_private = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$content, $category, $isPrivate, $postId, $userId]);

        header("Location: /app/home.php");
        exit;

    } catch (Throwable $e) {
        $currentUser = $_SESSION['user_id'] ?? 'Guest';
        error_log("[SAGAFLEX CRITICAL] User: $currentUser - Action: Update Post - Error: " . $e->getMessage());
        header("Location: /app/500.php");
        exit;
    }
}

// ============================================================================
// 4. SISTEMA DE LIKES (TOGGLE)
// ============================================================================

if ($action === 'toggle_like') {
    if (!isset($_SESSION['user_id'])) die("Acceso denegado");

    $userId = $_SESSION['user_id'];
    $postId = $_POST['post_id'];

    // 1. Verificar si ya existe el like
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$userId, $postId]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // SI YA EXISTE -> LO QUITAMOS (Dislike)
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$userId, $postId]);
    } else {
        // SI NO EXISTE -> LO CREAMOS (Like)
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$userId, $postId]);
    }

    // Redirigimos de vuelta a la página donde se hizo click (Home o Perfil)
    $redirect = $_SERVER['HTTP_REFERER'] ?? '/app/home.php';
    header("Location: $redirect");
    exit;
}
// 3. Redirigir a la pantalla de error "Bonita"
header("Location: /app/500.php");
exit;