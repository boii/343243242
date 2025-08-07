<?php
/**
 * Login Page (Revamped with Compact, High-Focus Floating Card)
 *
 * This version features a slimmer floating card with a larger logo
 * over a darkened, dynamic background from Pexels for maximum focus and elegance.
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

// Include config.php untuk mendapatkan $app_settings
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

// --- Logika untuk Gambar Latar Dinamis dari Pexels API ---
define('PEXELS_API_KEY', '1f478h1hjNoLlpfJ5BpjR0oTaynmVOGAot3zomNGIgCJTmayDfLUVpQd');

function getPexelsImage() {
    if (PEXELS_API_KEY === 'MASUKKAN_KUNCI_API_PEXELS_ANDA_DI_SINI' || PEXELS_API_KEY === '') {
        return 'https://images.pexels.com/photos/3278215/pexels-photo-3278215.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=750&w=1260';
    }

    $themes = ['operating room', 'laboratory', 'microscope', 'technology', 'minimalist abstract'];
    $random_theme = $themes[array_rand($themes)];
    
    $url = "https://api.pexels.com/v1/search?" . http_build_query([
        'query' => $random_theme,
        'per_page' => 1,
        'page' => rand(1, 100),
        'orientation' => 'landscape'
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ["Authorization: " . PEXELS_API_KEY],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return 'https://images.pexels.com/photos/3278215/pexels-photo-3278215.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=750&w=1260';
    } else {
        $data = json_decode($response, true);
        return $data['photos'][0]['src']['large2x'] ?? 'https://images.pexels.com/photos/3278215/pexels-photo-3278215.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=750&w=1260';
    }
}

$background_image_url = getPexelsImage();

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
            background-color: #1a202c; /* Latar belakang dasar jika gambar gagal dimuat */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            z-index: 1;
        }
        body::before { /* Overlay gelap untuk fokus */
            content: '';
            position: fixed;
            top: 0; right: 0; bottom: 0; left: 0;
            background-color: rgba(0, 0, 0, 0.6); /* Overlay lebih gelap */
            z-index: -1;
        }
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        /* PERUBAHAN: Kartu lebih ramping */
        .login-card {
            width: 100%;
            max-width: 380px; 
            background-color: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        /* PERUBAHAN: Logo lebih besar */
        .branding-logo { 
            max-height: 60px; /* Ukuran lebih besar */
            margin-bottom: 1rem; 
            display: block; 
            margin-left: auto; 
            margin-right: auto; 
        }
        .form-input-group { position: relative; }
        .form-input-group .material-icons { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .form-input { 
            border-radius: 0.375rem; padding: 0.6rem 1rem 0.6rem 2.75rem; border: 1px solid #d1d5db; 
            width: 100%; transition: border-color 0.2s, box-shadow 0.2s;
            background-color: white;
        }
        .form-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); outline: none; }
        .btn-login { 
            padding: 0.6rem; border-radius: 0.375rem; font-weight: 700; transition: background-color 0.3s ease; 
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            background-color: #3b82f6; color: white; width: 100%;
        }
        .btn-login:hover { background-color: #2563eb; }
        .alert-login {
            background-color: #fee2e2; border-left: 4px solid #ef4444; color: #b91c1c;
            padding: 0.75rem; margin-bottom: 1.25rem; font-size: 0.875rem; font-weight: 500;
        }
        .text-center h2 { font-size: 1.5rem; margin-bottom: 0.25rem; }
        .text-center p { font-size: 0.875rem; color: #6b7280; }
    </style>
</head>
<body style="background-image: url('<?php echo $background_image_url; ?>');">
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-6">
                <?php if (!empty($appLogoFilename) && file_exists('uploads/' . $appLogoFilename)): ?>
                    <img src="uploads/<?php echo htmlspecialchars($appLogoFilename); ?>" alt="Logo" class="branding-logo">
                <?php else: ?>
                    <span class="material-icons text-6xl text-blue-600 mb-2">qr_code_scanner</span>
                <?php endif; ?>
                <h2 class="font-bold text-gray-800"><?php echo htmlspecialchars($appInstanceName); ?></h2>
                <p>Silakan masuk untuk melanjutkan</p>
            </div>
            
            <?php if(!empty($login_err)): ?>
                <div class="alert-login" role="alert">
                    <?php echo htmlspecialchars($login_err); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                <div class="space-y-4">
                    <div class="form-input-group">
                        <span class="material-icons">person</span>
                        <input type="text" name="username" placeholder="Username" class="form-input <?php echo (!empty($username_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="form-input-group">
                         <span class="material-icons">lock</span>
                        <input type="password" name="password" placeholder="Password" class="form-input <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>" required>
                    </div>
                </div>
                
                <?php if(!empty($username_err) || !empty($password_err)): ?>
                    <p class="text-red-500 text-sm mt-2">
                        <?php echo htmlspecialchars($username_err ?: $password_err); ?>
                    </p>
                <?php endif; ?>
                
                <div class="mt-6">
                    <button type="submit" class="btn-login">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>