<?php
// actions.php - VERSIÓN FINAL CON LIKES
require 'db.php';
session_start();

// Aumentamos límite de memoria para subida de imágenes pesadas
ini_set('memory_limit', '256M');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================================================
// 1. AUTENTICACIÓN
// ============================================================================

if ($action === 'register') {
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    if(empty($user) || empty($email) || empty($pass)) {
         header("Location: register.php?error=Todos los campos son obligatorios");
         exit;
    }

    $passHash = password_hash($pass, PASSWORD_BCRYPT);
    
    try {
        // Asignamos 'default.png' al registrarse
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, profile_picture) VALUES (?, ?, ?, 'default.png')");
        $stmt->execute([$user, $email, $passHash]);
        header("Location: login.php?error=Registro exitoso, por favor inicia sesión.");
    } catch (Exception $e) {
        header("Location: register.php?error=El usuario o email ya existe.");
    }
}

if ($action === 'login') {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: home.php");
    } else {
        header("Location: login.php?error=Credenciales incorrectas.");
    }
}

if ($action === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// ============================================================================
// 2. GESTIÓN DE PERFIL (BIO + FOTO)
// ============================================================================

if ($action === 'update_profile') {
    if (!isset($_SESSION['user_id'])) die("Acceso denegado");

    $userId = $_SESSION['user_id'];
    $bio = trim($_POST['bio']);
    $uploadDir = 'uploads/';

    // Verificar si se intentó subir un archivo
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['size'] > 0) {
        
        // A. Chequear errores de PHP
        if ($_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
             $errores = [
                 1 => "El archivo es demasiado pesado (server limit).",
                 2 => "El archivo es demasiado pesado (form limit).",
                 3 => "Subida parcial.",
                 4 => "No se subió archivo.",
                 6 => "Error de carpeta temporal.",
                 7 => "Error de escritura en disco.",
                 8 => "Extensión de PHP detuvo la subida."
             ];
             $errorCode = $_FILES['profile_pic']['error'];
             die("❌ ERROR AL SUBIR: " . ($errores[$errorCode] ?? "Error desconocido"));
        }

        // B. Validar tipo MIME (Seguridad)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['profile_pic']['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            die("❌ ERROR: Tipo de archivo no permitido. Solo JPG, PNG, GIF o WEBP.");
        }
        
        // C. Verificar carpeta
        if (!is_dir($uploadDir)) {
            die("❌ ERROR CRÍTICO: La carpeta 'uploads/' no existe. Créala en tu proyecto.");
        }
        
        // D. Generar nombre único y mover
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $newFilename = uniqid('u'.$userId.'_', true) . '.' . $ext;
        $destination = $uploadDir . $newFilename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
            // ÉXITO: Guardamos bio y nombre de foto
            $stmt = $pdo->prepare("UPDATE users SET bio = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$bio, $newFilename, $userId]);
        } else {
             die("❌ ERROR: No se pudo mover el archivo. Verifica permisos de escritura.");
        }

    } else {
        // Si no hay foto nueva, solo actualizamos Bio
        $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt->execute([$bio, $userId]);
    }

    header("Location: profile.php?user_id=$userId");
    exit;
}

// ============================================================================
// 3. CRUD DE POSTS
// ============================================================================

if ($action === 'create_post') {
    if (!isset($_SESSION['user_id'])) die("Acceso denegado");

    $userId = $_SESSION['user_id'];
    $content = trim($_POST['content']);

    if(empty($content)) {
         header("Location: home.php");
         exit;
    }

    // REGLA ANTI-SPAM: Máximo 3 posts en 10 minutos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND created_at > datetime('now', '-10 minutes')");
    $stmt->execute([$userId]);
    if ($stmt->fetch()['total'] >= 3) {
        die("❌ ERROR ANTI-SPAM: Estás publicando muy rápido. (Límite: 3 posts cada 10 min). <br><a href='home.php'>Volver</a>");
    }

    $category = $_POST['category'];
    $isPrivate = isset($_POST['is_private']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, category, is_private) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $content, $category, $isPrivate]);
    header("Location: home.php");
    exit;
}

if ($action === 'delete_post') {
    if (!isset($_SESSION['user_id'])) die("Acceso denegado");
    // Solo borra si el ID del post coincide con el ID del usuario dueño de la sesión
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['post_id'], $_SESSION['user_id']]);
    header("Location: home.php");
    exit;
}

if ($action === 'update_post') {
    if (!isset($_SESSION['user_id'])) die("Acceso denegado");
    
    $content = trim($_POST['content']);
    if(empty($content)) die("El post no puede estar vacío");

    // Capturamos el checkbox de privacidad (1 = Privado, 0 = Público)
    $isPrivate = isset($_POST['is_private']) ? 1 : 0;
    
    $category = $_POST['category'];
    $postId = $_POST['post_id'];
    $userId = $_SESSION['user_id'];

    // SQL Actualizado para guardar cambios de privacidad
    $stmt = $pdo->prepare("UPDATE posts SET content = ?, category = ?, is_private = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$content, $category, $isPrivate, $postId, $userId]);
    
    header("Location: home.php");
    exit;
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
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'home.php';
    header("Location: $redirect");
    exit;
}
?>