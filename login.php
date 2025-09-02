<?php
/**
 * Login Page (Revamped with Consistent Animated Gradient)
 *
 * This version features a subtle, slow-moving animated gradient background
 * using a color palette consistent with the main application's theme.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Authentication
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    $app_settings = [
        'app_instance_name' => 'Sterilabel',
        'app_logo_filename' => '',
    ]; 
    error_log("PENTING: config.php tidak ditemukan untuk login.php");
}

$appInstanceName = $app_settings['app_instance_name'] ?? 'Sterilabel';
$appLogoFilename = $app_settings['app_logo_filename'] ?? '';
$pageTitle = "Login - " . htmlspecialchars($appInstanceName);

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"]))) {
        $username_err = "Silakan masukkan username.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Silakan masukkan password Anda.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($username_err) && empty($password_err)) {
        $conn = connectToDatabase();
        if ($conn) {
            $sql = "SELECT user_id, username, password_hash, role, full_name FROM users WHERE username = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $param_username);
                $param_username = $username;
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($id, $db_username, $hashed_password, $role, $full_name);
                        if ($stmt->fetch()) {
                            if (password_verify($password, $hashed_password)) {
                                if (session_status() === PHP_SESSION_NONE) { session_start(); }
                                $_SESSION["loggedin"] = true;
                                $_SESSION["user_id"] = $id;
                                $_SESSION["username"] = $db_username;
                                $_SESSION["role"] = $role;
                                $_SESSION["full_name"] = $full_name; 
                                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                                header("location: index.php");
                                exit;
                            } else { $login_err = "Username atau password tidak valid."; }
                        }
                    } else { $login_err = "Username atau password tidak valid."; }
                } else { $login_err = "Oops! Terjadi kesalahan. Silakan coba lagi nanti."; }
                $stmt->close();
            }
            $conn->close();
        } else { $login_err = "Koneksi database gagal."; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { 
            font-family: 'Nunito', sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .gradient-bg {
            width: 100vw;
            height: 100vh;
            /* PERUBAHAN: Warna gradasi disesuaikan dengan tema aplikasi */
            background: linear-gradient(-45deg, #f4f7f6, #dbeafe, #bfdbfe, #3b82f6);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 400px; 
            background-color: white;
            border-radius: 0.75rem; /* rounded-xl */
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 2.5rem;
        }
        .branding-logo { 
            max-height: 50px; 
            margin-bottom: 0.5rem; 
            display: block; 
            margin-left: auto; 
            margin-right: auto; 
        }
        
        .form-input-group { position: relative; }
        .form-input-group .material-icons { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .form-input { 
            border-radius: 6px; 
            padding: 10px 10px 10px 2.75rem; 
            border: 1px solid #d1d5db; 
            width: 100%; 
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 2px rgba(59,130,246,0.3); 
            outline: none; 
        }
        
        .btn-login { 
            padding: 0.75rem; 
            border-radius: 6px; 
            font-weight: 600; 
            transition: background-color 0.3s ease; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer;
            background-color: #3b82f6; 
            color: white; 
            width: 100%;
            border: 1px solid #3b82f6;
        }
        .btn-login:hover { background-color: #2563eb; border-color: #2563eb; }
        
        .alert-login {
            background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b;
            padding: 1rem; margin-bottom: 1.5rem; font-size: 0.875rem; font-weight: 500;
            border-radius: 6px; display: flex; align-items: center;
        }
        .alert-login .material-icons { margin-right: 0.5rem; }
    </style>
</head>
<body>
    <div class="gradient-bg"></div>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-8">
                 <?php if (!empty($appLogoFilename) && file_exists('uploads/' . $appLogoFilename)): ?>
                    <img src="uploads/<?php echo htmlspecialchars($appLogoFilename); ?>" alt="Logo" class="branding-logo">
                <?php else: ?>
                    <img src="https://i.ibb.co/L0SM53S/focus-16747631.png" alt="Default Logo" class="branding-logo">
                <?php endif; ?>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($appInstanceName); ?></h1>
                <p class="text-gray-500 text-sm mt-1">Silakan masuk untuk memulai sesi Anda</p>
            </div>

            <?php if(!empty($login_err)): ?>
                <div class="alert-login" role="alert">
                    <span class="material-icons">error_outline</span>
                    <span><?php echo htmlspecialchars($login_err); ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                <div class="space-y-5">
                    <div>
                        <label for="username" class="font-semibold text-gray-700 text-sm">Username</label>
                        <div class="form-input-group mt-1">
                            <span class="material-icons">person</span>
                            <input type="text" id="username" name="username" class="form-input <?php echo (!empty($username_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <?php if(!empty($username_err)): ?>
                            <p class="text-red-600 text-xs mt-1"><?php echo htmlspecialchars($username_err); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="password" class="font-semibold text-gray-700 text-sm">Password</label>
                        <div class="form-input-group mt-1">
                            <span class="material-icons">lock</span>
                            <input type="password" id="password" name="password" class="form-input <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>" required>
                        </div>
                         <?php if(!empty($password_err)): ?>
                            <p class="text-red-600 text-xs mt-1"><?php echo htmlspecialchars($password_err); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-8">
                    <button type="submit" class="btn-login">
                        <span>Login</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>