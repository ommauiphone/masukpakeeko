<?php
session_start();

// Fungsi helper untuk memanggil WHM API
function call_whm_api($host, $username, $token, $function, $params = []) {
    $base_url = "https://$host:2087/json-api/$function";
    $query = http_build_query(array_merge(['api.version' => '1'], $params));
    $url = "$base_url?$query";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // False untuk testing, gunakan true di production
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: whm $username:$token"
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['error' => 'Gagal terhubung ke server WHM', 'status_code' => $http_code];
    }

    $result = json_decode($response, true);
    if ($result === null) {
        return ['error' => 'Respon JSON tidak valid', 'status_code' => $http_code];
    }

    return $result;
}

// Fungsi untuk mengubah data JSON ke tabel HTML
function json_to_table($data, $columns = []) {
    if (empty($data) || !is_array($data)) {
        return '<p class="text-muted">Tidak ada data untuk ditampilkan.</p>';
    }

    $html = '<div class="table-responsive"><table class="table table-striped table-bordered">';
    $html .= '<thead><tr>';
    foreach ($columns as $col) {
        $html .= '<th>' . htmlspecialchars($col) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($columns as $key) {
            $value = isset($row[$key]) ? $row[$key] : '-';
            $html .= '<td>' . htmlspecialchars($value) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';
    return $html;
}

// Fungsi untuk render template HTML dengan Bootstrap
function render_template($content, $title, $message = '') {
    $sidebar = '
        <div class="sidebar bg-light d-flex flex-column p-3">
            <h5 class="mb-3 fw-bold">WHM Remote</h5>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item"><a href="?page=dashboard" class="nav-link link-dark"><i class="bi bi-house me-2"></i>Dashboard</a></li>
                <li><a href="?page=server_config" class="nav-link link-dark"><i class="bi bi-gear me-2"></i>Server Configuration</a></li>
                <li><a href="?page=security_center" class="nav-link link-dark"><i class="bi bi-shield-lock me-2"></i>Security Center</a></li>
                <li><a href="?page=account_info" class="nav-link link-dark"><i class="bi bi-person-lines-fill me-2"></i>Account Information</a></li>
                <li><a href="?page=account_functions" class="nav-link link-dark"><i class="bi bi-person-gear me-2"></i>Account Functions</a></li>
                <li><a href="?page=multi_account" class="nav-link link-dark"><i class="bi bi-people me-2"></i>Multi Account</a></li>
                <li><a href="?page=packages" class="nav-link link-dark"><i class="bi bi-box-seam me-2"></i>Packages</a></li>
                <li><a href="?page=dns_functions" class="nav-link link-dark"><i class="bi bi-globe me-2"></i>DNS Functions</a></li>
                <li><a href="?page=email" class="nav-link link-dark"><i class="bi bi-envelope me-2"></i>Email</a></li>
                <li><a href="?page=cpanel" class="nav-link link-dark"><i class="bi bi-grid me-2"></i>cPanel</a></li>
                <li><a href="?page=ssl_tls" class="nav-link link-dark"><i class="bi bi-lock me-2"></i>SSL/TLS</a></li>
                <li><a href="?page=development" class="nav-link link-dark"><i class="bi bi-code-slash me-2"></i>Development</a></li>
                <li><a href="?page=plugins" class="nav-link link-dark"><i class="bi bi-plug me-2"></i>Plugins</a></li>
                <li><a href="?page=create_account" class="nav-link link-dark"><i class="bi bi-person-plus me-2"></i>Create New Account</a></li>
                <li><a href="?page=reset_password" class="nav-link link-dark"><i class="bi bi-key me-2"></i>Reset Password</a></li>
                <li><a href="?page=logout" class="nav-link link-dark"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    ';

    return '
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body { padding-top: 60px; }
            .sidebar { width: 250px; height: 100vh; position: fixed; top: 0; left: 0; z-index: 100; overflow-y: auto; }
            .main-content { margin-left: 250px; padding: 20px; }
            @media (max-width: 767.98px) {
                .sidebar { width: 100%; height: auto; position: static; }
                .main-content { margin-left: 0; }
                .fatBorder { padding: 15px; }
                .propertyEditor { flex-direction: column; align-items: flex-start; }
                .propertyLabel { width: 100%; margin-bottom: 5px; }
                .propertyValue { width: 100%; }
            }
            .navbar-brand { font-weight: bold; }
            .table-responsive { max-height: 500px; overflow-y: auto; }
            .card { max-width: 800px; }
            .alert { max-width: 800px; }
            .fatBorder { border: 1px solid #dee2e6; padding: 20px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .groupEditor { margin-bottom: 0; }
            .propertyGroup { margin-bottom: 20px; }
            .propertyEditor { display: flex; align-items: center; margin-bottom: 15px; gap: 10px; }
            .propertyLabel { width: 200px; font-weight: 500; }
            .propertyValue { flex: 1; }
            .propertyCombined { margin-bottom: 15px; }
            .collapsible-wrapper h3 { margin: 0; font-size: 1.5rem; padding-bottom: 10px; }
            .width-inherit { width: 100%; }
            #pwStrengthContainer { display: flex; align-items: center; gap: 15px; }
            #pwStrengthWidget { width: 250px; }
            .form-control { max-width: 400px; }
            .text-muted { font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="?page=dashboard">WHM Remote</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="?page=login_whm">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login to WHM
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        ' . $sidebar . '
        <main class="main-content">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($title) . '</li>
                </ol>
            </nav>
            ' . ($message ? '<div class="alert alert-' . (strpos($message, 'Error') === false ? 'success' : 'danger') . ' alert-dismissible fade show" role="alert"><i class="bi bi-' . (strpos($message, 'Error') === false ? 'check-circle' : 'exclamation-circle') . ' me-2"></i>' . htmlspecialchars($message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>' : '') . '
            ' . $content . '
        </main>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Sederhana password strength indicator
            document.addEventListener("DOMContentLoaded", function() {
                const passwordInput = document.getElementById("password");
                const strengthWidget = document.getElementById("password_strength");
                if (passwordInput && strengthWidget) {
                    passwordInput.addEventListener("input", function() {
                        const value = passwordInput.value;
                        let strength = 0;
                        if (value.length > 0) strength += 20;
                        if (value.length > 8) strength += 20;
                        if (/[A-Z]/.test(value)) strength += 20;
                        if (/[0-9]/.test(value)) strength += 20;
                        if (/[^A-Za-z0-9]/.test(value)) strength += 20;
                        strengthWidget.style.width = strength + "%";
                        strengthWidget.style.backgroundColor = strength < 40 ? "#ff0000" : strength < 80 ? "#ffc107" : "#28a745";
                        strengthWidget.parentElement.querySelector("span").textContent = strength < 40 ? "Very Weak" : strength < 60 ? "Weak" : strength < 80 ? "Medium" : "Strong";
                    });
                }
            });
        </script>
    </body>
    </html>
    ';
}

// Halaman Login
if (!isset($_GET['page']) || $_GET['page'] === 'login' || !isset($_SESSION['host'])) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $host = $_POST['host'] ?? '';
        $username = $_POST['username'] ?? '';
        $token = $_POST['token'] ?? '';

        // Test koneksi dengan API sederhana (listaccts)
        $result = call_whm_api($host, $username, $token, 'listaccts');
        if (!isset($result['error'])) {
            $_SESSION['host'] = $host;
            $_SESSION['username'] = $username;
            $_SESSION['token'] = $token;
            header('Location: ?page=dashboard');
            exit;
        } else {
            $error = "Login gagal: " . ($result['error'] ?? 'Periksa host, username, atau token.');
        }
    }

    echo '
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Panel Remote WHM</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; }
            .login-card { max-width: 400px; margin: 50px auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="login-card">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Login ke WHM Remote</h3>
                        ' . ($error ? '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-circle me-2"></i>' . htmlspecialchars($error) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>' : '') . '
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Hostname/Domain Server</label>
                                <input type="text" name="host" class="form-control" required placeholder="server.example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username (root/reseller)</label>
                                <input type="text" name="username" class="form-control" required placeholder="root">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API Token</label>
                                <input type="password" name="token" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    ';
    exit;
}

// Login to WHM
if (isset($_GET['page']) && $_GET['page'] === 'login_whm') {
    $message = '';
    $whm_user = $_SESSION['username']; // Menggunakan username dari sesi
    $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'create_user_session', [
        'user' => $whm_user,
        'service' => 'whostmgrd',
        'app' => 'home'
    ]);

    if (!isset($result['error']) && isset($result['data']['url'])) {
        $url = $result['data']['url'];
        header('Location: ' . $url);
        exit;
    } else {
        $message = isset($result['error']) ? "Error: {$result['error']}" : "Gagal membuat sesi WHM.";
        $content = '
            <h2>Login to WHM</h2>
            <div class="card">
                <div class="card-body">
                    <p class="text-danger">Tidak dapat mengarahkan ke WHM. Silakan coba lagi.</p>
                </div>
            </div>
        ';
        echo render_template($content, "Login to WHM", $message);
        exit;
    }
}

// Halaman Dashboard
if (isset($_GET['page']) && $_GET['page'] === 'dashboard') {
    $content = '
        <h2>Selamat Datang di Dashboard!</h2>
        <p>Silakan pilih menu di sidebar kiri untuk mengelola server WHM Anda.</p>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Ringkasan</h5>
                        <p>Kelola akun, konfigurasi server, DNS, dan lainnya dengan mudah.</p>
                    </div>
                </div>
            </div>
        </div>
    ';
    echo render_template($content, "Dashboard");
    exit;
}

// 1. Server Configuration
if (isset($_GET['page']) && $_GET['page'] === 'server_config') {
    $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'servicestatus');
    $message = isset($result['error']) ? "Error: {$result['error']}" : "Berhasil mengambil status layanan.";
    $content = '';
    if (!isset($result['error']) && isset($result['data']['service'])) {
        $content = json_to_table($result['data']['service'], ['name', 'running', 'monitored']);
    }
    $content .= '<p class="mt-3"><strong>API digunakan:</strong> servicestatus (status layanan server).</p>';
    echo render_template("<h2>Server Configuration</h2>$content", "Server Configuration", $message);
    exit;
}

// 2. Security Center
if (isset($_GET['page']) && $_GET['page'] === 'security_center') {
    $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'api_token_list');
    $message = isset($result['error']) ? "Error: {$result['error']}" : "Berhasil mengambil daftar token API.";
    $content = '';
    if (!isset($result['error']) && isset($result['data']['tokens'])) {
        $content = json_to_table($result['data']['tokens'], ['name', 'create_time']);
    }
    $content .= '<p class="mt-3"><strong>API digunakan:</strong> api_token_list (daftar token API).</p>';
    echo render_template("<h2>Security Center</h2>$content", "Security Center", $message);
    exit;
}

// 3. Account Information
if (isset($_GET['page']) && $_GET['page'] === 'account_info') {
    $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'listaccts');
    $message = isset($result['error']) ? "Error: {$result['error']}" : "Berhasil mengambil daftar akun.";
    $content = '';
    if (!isset($result['error']) && isset($result['data']['acct'])) {
        $content = json_to_table($result['data']['acct'], ['user', 'domain', 'email', 'plan']);
    }
    $content .= '<p class="mt-3"><strong>API digunakan:</strong> listaccts (daftar semua akun).</p>';
    echo render_template("<h2>Account Information</h2>$content", "Account Information", $message);
    exit;
}

// 4. Account Functions
if (isset($_GET['page']) && $_GET['page'] === 'account_functions') {
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = $_POST['user'] ?? '';
        if (strlen($user) > 16) {
            $message = "Error: Username tidak boleh lebih dari 16 karakter.";
        } else {
            $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'suspendacct', ['user' => $user]);
            $message = isset($result['error']) ? "Error: {$result['error']}" : "Akun $user berhasil disuspend.";
        }
    }

    $content = '
        <h2>Account Functions</h2>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Suspend Akun</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username untuk Suspend</label>
                        <input type="text" name="user" class="form-control" required maxlength="16">
                    </div>
                    <button type="submit" class="btn btn-warning">Suspend Akun</button>
                </form>
            </div>
        </div>
        <p class="mt-3"><strong>API digunakan:</strong> suspendacct. Tambahkan fitur seperti unsuspend atau terminate.</p>
    ';
    echo render_template($content, "Account Functions", $message);
    exit;
}

// 5. Multi Account
if (isset($_GET['page']) && $_GET['page'] === 'multi_account') {
    $content = '
        <h2>Multi Account</h2>
        <p>Implementasikan API: setresellerlimits untuk mengatur batas reseller.</p>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Manajemen Reseller</h5>
                <p>Belum diimplementasikan. Silakan tambahkan form untuk mengatur limit reseller.</p>
            </div>
        </div>
    ';
    echo render_template($content, "Multi Account");
    exit;
}

// 6. Packages
if (isset($_GET['page']) && $_GET['page'] === 'packages') {
    $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'listpkgs');
    $message = isset($result['error']) ? "Error: {$result['error']}" : "Berhasil mengambil daftar paket.";
    $content = '';
    if (!isset($result['error']) && isset($result['data']['pkg'])) {
        $content = json_to_table($result['data']['pkg'], ['name', 'quota', 'bwlimit']);
    }
    $content .= '<p class="mt-3"><strong>API digunakan:</strong> listpkgs (daftar paket hosting).</p>';
    echo render_template("<h2>Packages</h2>$content", "Packages", $message);
    exit;
}

// 7. DNS Functions
if (isset($_GET['page']) && $_GET['page'] === 'dns_functions') {
    $message = '';
    
    // Handle form submissions for adding/editing/deleting records
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'add_record') {
            $domain = $_POST['domain'] ?? '';
            $type = $_POST['type'] ?? '';
            $name = $_POST['name'] ?? '';
            $address = $_POST['address'] ?? '';
            $ttl = $_POST['ttl'] ?? '14400';
            
            if (empty($name) || empty($address)) {
                $message = "Error: Nama record dan alamat tidak boleh kosong.";
            } else {
                $params = [
                    'domain' => $domain,
                    'name' => $name,
                    'type' => $type,
                    'ttl' => $ttl
                ];
                if ($type === 'A') {
                    $params['address'] = $address;
                } elseif ($type === 'CNAME') {
                    $params['cname'] = $address;
                } elseif ($type === 'MX') {
                    $params['exchange'] = $address;
                    $params['preference'] = $_POST['preference'] ?? '10';
                } elseif ($type === 'TXT') {
                    $params['txtdata'] = $address;
                } elseif ($type === 'SRV') {
                    $params['priority'] = $_POST['priority'] ?? '0';
                    $params['weight'] = $_POST['weight'] ?? '0';
                    $params['port'] = $_POST['port'] ?? '0';
                    $params['target'] = $address;
                }
                
                $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'addzonerecord', $params);
                $message = isset($result['error']) ? "Error: {$result['error']}" : "Record $type untuk $name berhasil ditambahkan.";
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_record') {
            $domain = $_POST['domain'] ?? '';
            $line = $_POST['line'] ?? '';
            $name = $_POST['name'] ?? '';
            $address = $_POST['address'] ?? '';
            $ttl = $_POST['ttl'] ?? '14400';
            
            if (empty($name) || empty($address) || empty($line)) {
                $message = "Error: Data record tidak lengkap.";
            } else {
                $params = [
                    'domain' => $domain,
                    'line' => $line,
                    'name' => $name,
                    'ttl' => $ttl
                ];
                if ($_POST['type'] === 'A') {
                    $params['address'] = $address;
                } elseif ($_POST['type'] === 'CNAME') {
                    $params['cname'] = $address;
                } elseif ($_POST['type'] === 'MX') {
                    $params['exchange'] = $address;
                    $params['preference'] = $_POST['preference'] ?? '10';
                } elseif ($_POST['type'] === 'TXT') {
                    $params['txtdata'] = $address;
                } elseif ($_POST['type'] === 'SRV') {
                    $params['priority'] = $_POST['priority'] ?? '0';
                    $params['weight'] = $_POST['weight'] ?? '0';
                    $params['port'] = $_POST['port'] ?? '0';
                    $params['target'] = $address;
                }
                
                $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'editzonerecord', $params);
                $message = isset($result['error']) ? "Error: {$result['error']}" : "Record untuk $name berhasil diedit.";
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_record') {
            $domain = $_POST['domain'] ?? '';
            $line = $_POST['line'] ?? '';
            
            if (empty($line)) {
                $message = "Error: Nomor baris record tidak valid.";
            } else {
                $params = [
                    'domain' => $domain,
                    'line' => $line
                ];
                
                $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'removezonerecord', $params);
                $message = isset($result['error']) ? "Error: {$result['error']}" : "Record berhasil dihapus.";
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_zone') {
            $domain = $_POST['domain'] ?? '';
            
            if (empty($domain)) {
                $message = "Error: Domain tidak valid.";
            } else {
                $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'resetzone', ['domain' => $domain]);
                $message = isset($result['error']) ? "Error: {$result['error']}" : "Zona DNS untuk $domain berhasil direset.";
            }
        }
    }
     
    // Handle AJAX request untuk mengambil data record berdasarkan line (opsional, jika ingin gunakan AJAX)
    if (isset($_GET['action']) && $_GET['action'] === 'get_record' && isset($_GET['domain']) && isset($_GET['line'])) {
        $domain = $_GET['domain'];
        $line = (int)$_GET['line'];
        
        // Panggil API WHM untuk mengambil data zona
        $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'dumpzone', ['domain' => $domain]);
        
        $record = null;
        if (!isset($result['error']) && isset($result['data']['record'])) {
            foreach ($result['data']['record'] as $rec) {
                if (isset($rec['line']) && (int)$rec['line'] === $line) {
                    $record = $rec;
                    break;
                }
            }
        }
        
        // Kirim respon JSON
        header('Content-Type: application/json');
        if ($record) {
            echo json_encode([
                'status' => 'success',
                'data' => $record
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Record not found or invalid line number.'
            ]);
        }
        exit;
    }

    // Ambil daftar zona DNS
    $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'listzones');
    $message = $message ?: (isset($result['error']) ? "Error: {$result['error']}" : "Berhasil mengambil daftar zona DNS.");
    
    $content = '<h2>DNS Zone Manager</h2>';
    if (!isset($result['error']) && isset($result['data']['zone'])) {
        $content .= '
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($result['data']['zone'] as $zone) {
            $domain = htmlspecialchars($zone['domain']);
            $content .= '
                        <tr>
                            <td>' . $domain . '</td>
                            <td class="action-buttons">
                                <button type="button" class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#addARecordModal" onclick="setModalData(\'' . $domain . '\', \'A\')">
                                    <i class="bi bi-plus"></i> A Record
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#addCNAMEModal" onclick="setModalData(\'' . $domain . '\', \'CNAME\')">
                                    <i class="bi bi-plus"></i> CNAME Record
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#addMXModal" onclick="setModalData(\'' . $domain . '\', \'MX\')">
                                    <i class="bi bi-plus"></i> MX Record
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#addTXTModal" onclick="setModalData(\'' . $domain . '\', \'TXT\')">
                                    <i class="bi bi-plus"></i> TXT Record
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#addSRVModal" onclick="setModalData(\'' . $domain . '\', \'SRV\')">
                                    <i class="bi bi-plus"></i> SRV Record
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.location.href=\'?page=dns_functions&manage=' . urlencode($domain) . '\'">
                                    <i class="bi bi-wrench"></i> Manage
                                </button>
                            </td>
                        </tr>';
        }
        $content .= '
                    </tbody>
                </table>
            </div>';
    } else {
        $content .= '<p class="text-muted">Tidak ada zona DNS untuk ditampilkan.</p>';
    }

    if (isset($_GET['manage'])) {
    $domain = $_GET['manage'];
    $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'dumpzone', ['domain' => $domain]);
    
    $content .= '<h3 class="mt-4">Manage Zone: ' . htmlspecialchars($domain) . '</h3>';

    // Filter and Pagination Parameters
    $filterText = isset($_GET['filter']) ? trim($_GET['filter']) : '';
    $filterType = isset($_GET['type']) ? trim($_GET['type']) : '';
    $page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
    $pageSize = isset($_GET['page_size']) ? max(10, (int)$_GET['page_size']) : 30;
    $validPageSizes = [10, 20, 50, 100, 500, 1000];
    if (!in_array($pageSize, $validPageSizes)) {
        $pageSize = 30;
    }

    // Filter Records
    $records = [];
    $errorMessage = '';
    if (!isset($result['error']) && isset($result['data']['record']) && is_array($result['data']['record'])) {
        foreach ($result['data']['record'] as $record) {
            // Validasi bahwa record memiliki tipe dan nama
            $recordType = isset($record['type']) ? $record['type'] : '';
            $recordName = isset($record['name']) ? $record['name'] : '';
            
            // Terapkan filter
            if ($filterType && $recordType !== $filterType) {
                continue;
            }
            if ($filterText && stripos($recordName, $filterText) === false) {
                continue;
            }
            $records[] = $record;
        }
    } else {
        $errorMessage = isset($result['error']) ? "Error dari API WHM: {$result['error']}" : "Tidak ada record DNS yang tersedia untuk domain ini.";
    }

    // Pagination
    $totalItems = count($records);
    $totalPages = max(1, ceil($totalItems / $pageSize));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $pageSize;
    $pagedRecords = array_slice($records, $offset, $pageSize);

    // Search and Filter UI
    $content .= '
        <div id="tableShowHideContainerManage" class="mb-4">
            <div id="paginationControls" class="row search-page-container">
                <div class="col-12 col-md-9">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="input-group mb-3">
                                <input type="text" id="filterList_input" class="form-control" placeholder="Filter by name" value="' . htmlspecialchars($filterText) . '" oninput="filterRecords()">
                                <button class="btn btn-outline-secondary" type="button" id="filterList_submit_btn" onclick="clearFilter()">
                                    <i class="bi bi-' . ($filterText ? 'x' : 'search') . '"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="quick-filters-nav">
                                <span class="quick-filter-label d-none d-md-inline">Filter:</span>
                                <ul class="nav nav-pills">
                                    <li class="nav-item">
                                        <a class="nav-link' . (!$filterType ? ' active' : '') . '" href="?page=dns_functions&manage=' . urlencode($domain) . '&page_size=' . $pageSize . '&page_num=1">All</a>
                                    </li>';
    $recordTypes = ['A', 'CNAME', 'MX', 'NS', 'SOA', 'SRV', 'TXT'];
    foreach ($recordTypes as $type) {
        $content .= '
                                    <li class="nav-item">
                                        <a class="nav-link' . ($filterType === $type ? ' active' : '') . '" href="?page=dns_functions&manage=' . urlencode($domain) . '&type=' . $type . '&page_size=' . $pageSize . '&page_num=1">' . $type . '</a>
                                    </li>';
    }
    $content .= '
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div id="paginator" class="pagination-container text-end d-none d-md-block">
                        <ul class="pagination">
                            <li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">
                                <a class="page-link" href="?page=dns_functions&manage=' . urlencode($domain) . '&filter=' . urlencode($filterText) . '&type=' . urlencode($filterType) . '&page_size=' . $pageSize . '&page_num=1">&laquo;</a>
                            </li>
                            <li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">
                                <a class="page-link" href="?page=dns_functions&manage=' . urlencode($domain) . '&filter=' . urlencode($filterText) . '&type=' . urlencode($filterType) . '&page_size=' . $pageSize . '&page_num=' . ($page - 1) . '">&lt;</a>
                            </li>
                            <li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '">
                                <a class="page-link" href="?page=dns_functions&manage=' . urlencode($domain) . '&filter=' . urlencode($filterText) . '&type=' . urlencode($filterType) . '&page_size=' . $pageSize . '&page_num=' . ($page + 1) . '">&gt;</a>
                            </li>
                            <li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '">
                                <a class="page-link" href="?page=dns_functions&manage=' . urlencode($domain) . '&filter=' . urlencode($filterText) . '&type=' . urlencode($filterType) . '&page_size=' . $pageSize . '&page_num=' . $totalPages . '">&raquo;</a>
                            </li>
                        </ul>
                        <p class="text-small text-end">Displaying ' . ($offset + 1) . ' to ' . min($offset + $pageSize, $totalItems) . ' out of ' . $totalItems . ' items</p>
                    </div>
                </div>
            </div>
            <div class="row action-bar mb-3">
                <div class="col-12">
                    <div class="d-none d-md-block float-start">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?page=dns_functions&manage=' . urlencode($domain) . '&action=view_raw_zone">View Raw DNS Zone File</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button class="dropdown-item" onclick="confirmResetZone(\'' . htmlspecialchars($domain) . '\')">Reset DNS Zone</button></li>
                            </ul>
                        </div>
                        <a href="https://corporate.vip1.noc401.com:2087/cpsess8480373797/scripts/doeditmx?domainselect=' . urlencode($domain) . '" target="_blank" class="btn btn-outline-primary btn-sm ms-2">
                            Email Routing Configuration
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                    <div class="float-end">
                        <button type="button" class="btn btn-primary btn-sm me-2" disabled>Save All Records</button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addARecordModal" onclick="setModalData(\'' . $domain . '\', \'A\')">Add Record</button>
                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addARecordModal" onclick="setModalData(\'' . $domain . '\', \'A\')">Add A Record</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addCNAMEModal" onclick="setModalData(\'' . $domain . '\', \'CNAME\')">Add CNAME Record</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addMXModal" onclick="setModalData(\'' . $domain . '\', \'MX\')">Add MX Record</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addTXTModal" onclick="setModalData(\'' . $domain . '\', \'TXT\')">Add TXT Record</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addSRVModal" onclick="setModalData(\'' . $domain . '\', \'SRV\')">Add SRV Record</a></li>
                            </ul>
                        </div>
                        <div class="btn-group ms-2" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="dropdown-item">Entries Per Page</li>';
    foreach ($validPageSizes as $size) {
        $content .= '
                                <li><a class="dropdown-item' . ($pageSize == $size ? ' active' : '') . '" href="?page=dns_functions&manage=' . urlencode($domain) . '&filter=' . urlencode($filterText) . '&type=' . urlencode($filterType) . '&page_size=' . $size . '&page_num=1">' . $size . '</a></li>';
    }
    $content .= '
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?page=dns_functions&manage=' . urlencode($domain) . '"><i class="bi bi-arrow-repeat"></i> Refresh List</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>';

    // Display Records Table
    $content .= '
        <div id="tableContainer" class="manage-view">
            <form name="manage_add_zr_form">
                <table class="table table-striped table-bordered responsive-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>TTL</th>
                            <th>Type</th>
                            <th>Record</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

    // Tampilkan pesan error jika ada
    if ($errorMessage) {
        $content .= '
                        <tr class="info empty-row">
                            <td colspan="5" class="text-danger">' . htmlspecialchars($errorMessage) . '</td>
                        </tr>';
    } elseif (empty($pagedRecords)) {
        $content .= '
                        <tr class="info empty-row">
                            <td colspan="5">No records found matching the current filters.</td>
                        </tr>';
    } else {
        foreach ($pagedRecords as $index => $record) {
            $recordName = htmlspecialchars($record['name'] ?? '');
            $recordType = htmlspecialchars($record['type'] ?? '');
            $recordTTL = htmlspecialchars($record['ttl'] ?? '14400');
            $recordLine = htmlspecialchars($record['line'] ?? '');
            $recordData = '';

            // Format data record berdasarkan tipe
            if ($recordType === 'A') {
                $recordData = htmlspecialchars($record['address'] ?? '');
                $recordData = "<div id=\"a_detail_$index\">$recordData</div>";
            } elseif ($recordType === 'CNAME') {
                $recordData = htmlspecialchars($record['cname'] ?? '');
                $recordData = "<div id=\"cname_detail_$index\">$recordData</div>";
            } elseif ($recordType === 'MX') {
                $priority = htmlspecialchars($record['preference'] ?? '0');
                $destination = htmlspecialchars($record['exchange'] ?? '');
                $recordData = "<div id=\"mx_detail_$index\"><span class=\"record-property-label\">Priority: </span>$priority<br><span class=\"record-property-label\">Destination: </span>$destination</div>";
            } elseif ($recordType === 'NS') {
                $recordData = htmlspecialchars($record['nsdname'] ?? '');
                $recordData = "<div id=\"ns_detail_$index\">$recordData</div>";
            } elseif ($recordType === 'SOA') {
                $serial = htmlspecialchars($record['serial'] ?? '');
                $mname = htmlspecialchars($record['mname'] ?? '');
                $retry = htmlspecialchars($record['retry'] ?? '');
                $refresh = htmlspecialchars($record['refresh'] ?? '');
                $expire = htmlspecialchars($record['expire'] ?? '');
                $rname = htmlspecialchars($record['email'] ?? '');
                $recordData = "<div id=\"soa_detail_$index\">
                    <span class=\"record-property-label\">Serial: </span>$serial<br>
                    <span class=\"record-property-label\">Mname: </span>$mname<br>
                    <span class=\"record-property-label\">Retry: </span>$retry<br>
                    <span class=\"record-property-label\">Refresh: </span>$refresh<br>
                    <span class=\"record-property-label\">Expire: </span>$expire<br>
                    <span class=\"record-property-label\">Rname: </span>$rname
                </div>";
            } elseif ($recordType === 'SRV') {
                $priority = htmlspecialchars($record['priority'] ?? '0');
                $weight = htmlspecialchars($record['weight'] ?? '0');
                $port = htmlspecialchars($record['port'] ?? '0');
                $target = htmlspecialchars($record['target'] ?? '');
                $recordData = "<div id=\"srv_detail_$index\">
                    <span class=\"record-property-label\">Priority: </span>$priority<br>
                    <span class=\"record-property-label\">Weight: </span>$weight<br>
                    <span class=\"record-property-label\">Port: </span>$port<br>
                    <span class=\"record-property-label\">Target: </span>$target
                </div>";
            } elseif ($recordType === 'TXT') {
                $txtData = htmlspecialchars($record['txtdata'] ?? '');
                $recordData = "<div id=\"txt_detail_$index\">$txtData</div>";
            }

            $content .= '
                        <tr id="zone_rec_row_' . $index . '" class="recordTableRow">
                            <td data-title="Name" class="potentially-really-long-text">' . $recordName . '</td>
                            <td data-title="TTL">' . $recordTTL . '</td>
                            <td data-title="Type">' . $recordType . '</td>
                            <td data-title="Record" class="zone-record-col potentially-really-long-text">' . $recordData . '</td>
                            <td data-title="Actions" class="action-buttons">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editRecordModal" 
                                    onclick="setEditModalData(\'' . htmlspecialchars($domain) . '\', \'' . $recordLine . '\', \'' . $recordType . '\', \'' . htmlspecialchars($recordName) . '\', \'' . htmlspecialchars($record['address'] ?? $record['cname'] ?? $record['exchange'] ?? $record['txtdata'] ?? $record['target'] ?? '') . '\', \'' . $recordTTL . '\', \'' . ($record['preference'] ?? '') . '\', \'' . ($record['priority'] ?? '') . '\', \'' . ($record['weight'] ?? '') . '\', \'' . ($record['port'] ?? '') . '\')">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>';
            if ($recordType !== 'SOA') {
                $content .= '
                                <button type="button" class="btn btn-outline-danger btn-sm ms-1" onclick="confirmDeleteRecord(\'' . htmlspecialchars($domain) . '\', \'' . $recordLine . '\', \'' . htmlspecialchars($recordName) . '\')">
                                    <i class="bi bi-trash"></i> Delete
                                </button>';
            }
            $content .= '
                            </td>
                        </tr>';
        }
    }
    $content .= '
                    </tbody>
                </table>
            </form>
        </div>';

        // Mobile Actions
        $content .= '
            <div class="row search-page-container d-block d-md-none">
                <div class="col-6">
                    <div class="float-start">
                        <a href="https://corporate.vip1.noc401.com:2087/cpsess8480373797/scripts/doeditmx?domainselect=' . urlencode($domain) . '" target="_blank" class="btn btn-outline-primary btn-sm">
                            Email Routing Configuration
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?page=dns_functions&manage=' . urlencode($domain) . '&action=view_raw_zone">View Raw DNS Zone File</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button class="dropdown-item" onclick="confirmResetZone(\'' . htmlspecialchars($domain) . '\')">Reset DNS Zone</button></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="pagination-container text-end">
                        <p class="text-small text-end">Displaying ' . ($offset + 1) . ' to ' . min($offset + $pageSize, $totalItems) . ' out of ' . $totalItems . ' items</p>
                        <ul class="pagination">
                            <li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">
                                <a class="page-link" href="?page=dns_functions&manage=' . urlencode($domain) . '&filter=' . urlencode($filterText) . '&type=' . urlencode($filterType) . '&page_size=' . $pageSize . '&page_num=1">&laquo;</a>
                            </li>
                            <li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">
                                <a class="page-link" href="?page=dns_functions&manage=' . urlencode($domain) . '&filter=' . urlencode($filterText) . '&type=' . urlencode($filterType) . '&page_size=' . $pageSize . '&page_num=' . ($page - 1) . '">&lt;</a>
                            </li>
                            <li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '">
                                <a class="page-link" href="?page=dns_functions&manage=' . urlencode($domain) . '&filter=' . urlencode($filterText) . '&type=' . urlencode($filterType) . '&page_size=' . $pageSize . '&page_num=' . ($page + 1) . '">&gt;</a>
                            </li>
                            <li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '">
                                <a class="page-link" href="?page=dns_functions&manage=' . urlencode($domain) . '&filter=' . urlencode($filterText) . '&type=' . urlencode($filterType) . '&page_size=' . $pageSize . '&page_num=' . $totalPages . '">&raquo;</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>';

        // Handle View Raw DNS Zone File
        if (isset($_GET['action']) && $_GET['action'] === 'view_raw_zone') {
            $rawZoneResult = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'getzonerecord', ['domain' => $domain]);
            $content .= '<h4 class="mt-4">Raw DNS Zone File</h4>';
            if (!isset($rawZoneResult['error'])) {
                $content .= '<pre>' . htmlspecialchars($rawZoneResult['data']['zone'][0]['raw'] ?? 'No data available') . '</pre>';
            } else {
                $content .= '<p class="text-danger">Error: ' . htmlspecialchars($rawZoneResult['error']) . '</p>';
            }
        }
    }

    // Modal untuk Add A Record
    $content .= '
        <div class="modal fade" id="addARecordModal" tabindex="-1" aria-labelledby="addARecordModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addARecordModalLabel">Add A Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add_record">
                            <input type="hidden" name="domain" id="a_domain">
                            <input type="hidden" name="type" value="A">
                            <div class="mb-3">
                                <label for="a_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="a_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="a_address" class="form-label">Address (IPv4)</label>
                                <input type="text" class="form-control" id="a_address" name="address" required placeholder="e.g., 192.168.1.1">
                            </div>
                            <div class="mb-3">
                                <label for="a_ttl" class="form-label">TTL</label>
                                <input type="number" class="form-control" id="a_ttl" name="ttl" value="14400">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save A Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

    // Modal untuk Add CNAME Record
    $content .= '
        <div class="modal fade" id="addCNAMEModal" tabindex="-1" aria-labelledby="addCNAMEModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCNAMEModalLabel">Add CNAME Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add_record">
                            <input type="hidden" name="domain" id="cname_domain">
                            <input type="hidden" name="type" value="CNAME">
                            <div class="mb-3">
                                <label for="cname_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="cname_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="cname_address" class="form-label">CNAME</label>
                                <input type="text" class="form-control" id="cname_address" name="address" required placeholder="e.g., www.example.com">
                            </div>
                            <div class="mb-3">
                                <label for="cname_ttl" class="form-label">TTL</label>
                                <input type="number" class="form-control" id="cname_ttl" name="ttl" value="14400">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save CNAME Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

    // Modal untuk Add MX Record
    $content .= '
        <div class="modal fade" id="addMXModal" tabindex="-1" aria-labelledby="addMXModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMXModalLabel">Add MX Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add_record">
                            <input type="hidden" name="domain" id="mx_domain">
                            <input type="hidden" name="type" value="MX">
                            <div class="mb-3">
                                <label for="mx_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="mx_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="mx_address" class="form-label">Mail Exchanger</label>
                                <input type="text" class="form-control" id="mx_address" name="address" required placeholder="e.g., mail.example.com">
                            </div>
                            <div class="mb-3">
                                <label for="mx_preference" class="form-label">Preference</label>
                                <input type="number" class="form-control" id="mx_preference" name="preference" value="10">
                            </div>
                            <div class="mb-3">
                                <label for="mx_ttl" class="form-label">TTL</label>
                                <input type="number" class="form-control" id="mx_ttl" name="ttl" value="14400">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save MX Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

    // Modal untuk Add TXT Record
    $content .= '
        <div class="modal fade" id="addTXTModal" tabindex="-1" aria-labelledby="addTXTModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTXTModalLabel">Add TXT Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add_record">
                            <input type="hidden" name="domain" id="txt_domain">
                            <input type="hidden" name="type" value="TXT">
                            <div class="mb-3">
                                <label for="txt_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="txt_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="txt_address" class="form-label">TXT Data</label>
                                <textarea class="form-control" id="txt_address" name="address" required placeholder="e.g., v=spf1 +a +mx +ip4:192.168.1.1 ~all"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="txt_ttl" class="form-label">TTL</label>
                                <input type="number" class="form-control" id="txt_ttl" name="ttl" value="14400">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save TXT Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

    // Modal untuk Add SRV Record
    $content .= '
        <div class="modal fade" id="addSRVModal" tabindex="-1" aria-labelledby="addSRVModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSRVModalLabel">Add SRV Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add_record">
                            <input type="hidden" name="domain" id="srv_domain">
                            <input type="hidden" name="type" value="SRV">
                            <div class="mb-3">
                                <label for="srv_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="srv_name" name="name" required placeholder="e.g., _service._proto.name">
                            </div>
                            <div class="mb-3">
                                <label for="srv_priority" class="form-label">Priority</label>
                                <input type="number" class="form-control" id="srv_priority" name="priority" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="srv_weight" class="form-label">Weight</label>
                                <input type="number" class="form-control" id="srv_weight" name="weight" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="srv_port" class="form-label">Port</label>
                                <input type="number" class="form-control" id="srv_port" name="port" required>
                            </div>
                            <div class="mb-3">
                                <label for="srv_address" class="form-label">Target</label>
                                <input type="text" class="form-control" id="srv_address" name="address" required placeholder="e.g., example.com">
                            </div>
                            <div class="mb-3">
                                <label for="srv_ttl" class="form-label">TTL</label>
                                <input type="number" class="form-control" id="srv_ttl" name="ttl" value="14400">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save SRV Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

     $content .= '
        <div class="modal fade" id="editRecordModal" tabindex="-1" aria-labelledby="editRecordModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRecordModalLabel">Edit DNS Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit_record">
                            <input type="hidden" name="domain" id="edit_domain">
                            <input type="hidden" name="line" id="edit_line">
                            <input type="hidden" name="type" id="edit_type">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_address" class="form-label" id="edit_address_label">Address/Target</label>
                                <input type="text" class="form-control" id="edit_address" name="address" required>
                            </div>
                            <div class="mb-3" id="edit_preference_container" style="display: none;">
                                <label for="edit_preference" class="form-label">Preference (MX only)</label>
                                <input type="number" class="form-control" id="edit_preference" name="preference">
                            </div>
                            <div class="mb-3" id="edit_priority_container" style="display: none;">
                                <label for="edit_priority" class="form-label">Priority (SRV only)</label>
                                <input type="number" class="form-control" id="edit_priority" name="priority">
                            </div>
                            <div class="mb-3" id="edit_weight_container" style="display: none;">
                                <label for="edit_weight" class="form-label">Weight (SRV only)</label>
                                <input type="number" class="form-control" id="edit_weight" name="weight">
                            </div>
                            <div class="mb-3" id="edit_port_container" style="display: none;">
                                <label for="edit_port" class="form-label">Port (SRV only)</label>
                                <input type="number" class="form-control" id="edit_port" name="port">
                            </div>
                            <div class="mb-3">
                                <label for="edit_ttl" class="form-label">TTL</label>
                                <input type="number" class="form-control" id="edit_ttl" name="ttl" value="14400">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

    // Modal untuk Delete Confirmation
    $content .= '
        <div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteRecordModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="delete_record">
                            <input type="hidden" name="domain" id="delete_domain">
                            <input type="hidden" name="line" id="delete_line">
                            <p>Are you sure you want to delete the record for <strong id="delete_record_name"></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

    // Modal untuk Reset Zone Confirmation
    $content .= '
        <div class="modal fade" id="resetZoneModal" tabindex="-1" aria-labelledby="resetZoneModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="resetZoneModalLabel">Confirm Reset Zone</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="reset_zone">
                            <input type="hidden" name="domain" id="reset_domain">
                            <p>Are you sure you want to reset the DNS zone for <strong>' . htmlspecialchars($domain) . '</strong>? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reset Zone</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
    $content .= '
               <script>
            function setEditModalData(domain, line, type, name, address, ttl, preference, priority, weight, port) {
                // Set data dasar
                document.getElementById("edit_domain").value = domain;
                document.getElementById("edit_line").value = line;
                document.getElementById("edit_type").value = type;
                document.getElementById("edit_name").value = name;
                document.getElementById("edit_address").value = address;
                document.getElementById("edit_ttl").value = ttl || "14400";
                document.getElementById("edit_address_label").textContent = type === "TXT" ? "TXT Data" : type === "MX" ? "Mail Exchanger" : type === "SRV" ? "Target" : "Address/Target";

                // Atur visibilitas field tambahan berdasarkan tipe record
                if (type === "MX") {
                    document.getElementById("edit_preference_container").style.display = "block";
                    document.getElementById("edit_preference").value = preference || "10";
                } else {
                    document.getElementById("edit_preference_container").style.display = "none";
                }
                if (type === "SRV") {
                    document.getElementById("edit_priority_container").style.display = "block";
                    document.getElementById("edit_weight_container").style.display = "block";
                    document.getElementById("edit_port_container").style.display = "block";
                    document.getElementById("edit_priority").value = priority || "0";
                    document.getElementById("edit_weight").value = weight || "0";
                    document.getElementById("edit_port").value = port || "0";
                } else {
                    document.getElementById("edit_priority_container").style.display = "none";
                    document.getElementById("edit_weight_container").style.display = "none";
                    document.getElementById("edit_port_container").style.display = "none";
                }

                // Panggil API untuk memastikan data record tidak kosong
                fetch("?page=dns_functions&action=get_record&domain=" + encodeURIComponent(domain) + "&line=" + encodeURIComponent(line))
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === "success" && data.data) {
                            const record = data.data;
                            document.getElementById("edit_name").value = record.name || "";
                            document.getElementById("edit_ttl").value = record.ttl || "14400";
                            document.getElementById("edit_type").value = record.type || type;
                            
                            if (record.type === "A") {
                                document.getElementById("edit_address").value = record.address || "";
                            } else if (record.type === "CNAME") {
                                document.getElementById("edit_address").value = record.cname || "";
                            } else if (record.type === "MX") {
                                document.getElementById("edit_address").value = record.exchange || "";
                                document.getElementById("edit_preference").value = record.preference || "10";
                                document.getElementById("edit_preference_container").style.display = "block";
                            } else if (record.type === "TXT") {
                                document.getElementById("edit_address").value = record.txtdata || "";
                                document.getElementById("edit_address_label").textContent = "TXT Data";
                            } else if (record.type === "SRV") {
                                document.getElementById("edit_address").value = record.target || "";
                                document.getElementById("edit_priority").value = record.priority || "0";
                                document.getElementById("edit_weight").value = record.weight || "0";
                                document.getElementById("edit_port").value = record.port || "0";
                                document.getElementById("edit_priority_container").style.display = "block";
                                document.getElementById("edit_weight_container").style.display = "block";
                                document.getElementById("edit_port_container").style.display = "block";
                            }
                        } else {
                            console.error("Failed to fetch record data: ", data.message);
                            alert("Error: Unable to fetch record data. Please try again.");
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching record data: ", error);
                        alert("Error: Unable to fetch record data. Please try again.");
                    });
            }

            function confirmDeleteRecord(domain, line, name) {
                document.getElementById("delete_domain").value = domain;
                document.getElementById("delete_line").value = line;
                document.getElementById("delete_record_name").textContent = name;
                new bootstrap.Modal(document.getElementById("deleteRecordModal")).show();
            }

            function confirmResetZone(domain) {
                document.getElementById("reset_domain").value = domain;
                new bootstrap.Modal(document.getElementById("resetZoneModal")).show();
            }

            function filterRecords() {
                const filterText = document.getElementById("filterList_input").value;
                const url = new URL(window.location.href);
                url.searchParams.set("filter", filterText);
                url.searchParams.set("page_num", 1);
                window.location.href = url.toString();
            }

            function clearFilter() {
                const url = new URL(window.location.href);
                url.searchParams.delete("filter");
                url.searchParams.set("page_num", 1);
                window.location.href = url.toString();
            }
        </script>';

    echo render_template($content, "DNS Zone Manager", $message);
    exit;
}


// 8. Email
if (isset($_GET['page']) && $_GET['page'] === 'email') {
    $content = '
        <h2>Email</h2>
        <p>Implementasikan API: list_pops untuk daftar akun email.</p>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Manajemen Email</h5>
                <p>Belum diimplementasikan. Silakan tambahkan form untuk mengelola akun email.</p>
            </div>
        </div>
    ';
    echo render_template($content, "Email");
    exit;
}

// 9. cPanel
if (isset($_GET['page']) && $_GET['page'] === 'cpanel') {
    $content = '
        <h2>cPanel</h2>
        <p>Implementasikan API: listaccts untuk info akun cPanel.</p>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Manajemen cPanel</h5>
                <p>Belum diimplementasikan. Silakan tambahkan fitur untuk mengelola akun cPanel.</p>
            </div>
        </div>
    ';
    echo render_template($content, "cPanel");
    exit;
}

// 10. SSL/TLS
if (isset($_GET['page']) && $_GET['page'] === 'ssl_tls') {
    $content = '
        <h2>SSL/TLS</h2>
        <p>Implementasikan API: installssl untuk instalasi sertifikat.</p>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Manajemen SSL/TLS</h5>
                <p>Belum diimplementasikan. Silakan tambahkan form untuk mengelola sertifikat SSL.</p>
            </div>
        </div>
    ';
    echo render_template($content, "SSL/TLS");
    exit;
}

// 11. Development
if (isset($_GET['page']) && $_GET['page'] === 'development') {
    $content = '
        <h2>Development</h2>
        <p>Implementasikan API: listphpversions untuk daftar versi PHP.</p>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Manajemen Pengembangan</h5>
                <p>Belum diimplementasikan. Silakan tambahkan fitur untuk mengelola versi PHP atau tools lain.</p>
            </div>
        </div>
    ';
    echo render_template($content, "Development");
    exit;
}

// 12. Plugins
if (isset($_GET['page']) && $_GET['page'] === 'plugins') {
    $content = '
        <h2>Plugins</h2>
        <p>WHM plugins dikelola via UI; gunakan API custom jika ada.</p>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Manajemen Plugins</h5>
                <p>Belum diimplementasikan. Silakan tambahkan fitur untuk mengelola plugins WHM.</p>
            </div>
        </div>
    ';
    echo render_template($content, "Plugins");
    exit;
}

// Create New Account
if (isset($_GET['page']) && $_GET['page'] === 'create_account') {
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if (strlen($username) > 16) {
            $message = "Error: Username tidak boleh lebih dari 16 karakter.";
        } elseif ($password !== $password2) {
            $message = "Error: Password dan Re-type Password tidak cocok.";
        } elseif (empty($password) && !isset($_POST['domain_option']) || $_POST['domain_option'] === 'use_own_domain' && empty($_POST['domain'])) {
            $message = "Error: Domain diperlukan jika memilih 'Use a domain that you own'.";
        } else {
            $params = [
                'username' => $username,
                'domain' => $_POST['domain'] ?? '',
                'password' => $password,
                'plan' => $_POST['msel'] ?? 'default',
                'contactemail' => $_POST['contactemail'] ?? '',
                'cgi' => isset($_POST['cgi']) ? 1 : 0,
                'digestauth' => isset($_POST['digestauth']) ? 1 : 0,
                'spamassassin' => isset($_POST['spamassassin']) ? 1 : 0,
                'spambox' => isset($_POST['spambox']) ? 1 : 0,
                'language' => $_POST['language'] ?? 'en',
                'mxcheck' => $_POST['mxcheck'] ?? 'auto',
                'dkim' => isset($_POST['dkim']) ? 1 : 0,
                'spf' => isset($_POST['spf']) ? 1 : 0,
                'dmarc' => isset($_POST['dmarc']) ? 1 : 0,
                'useregns' => isset($_POST['useregns']) ? 1 : 0
            ];
            $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'createacct', $params);
            $message = isset($result['error']) ? "Error: {$result['error']}" : "Akun $username berhasil dibuat.";
        }
    }

    // Ambil daftar package dari API
    $packages = [];
    $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'listpkgs');
    if (!isset($result['error']) && isset($result['data']['pkg'])) {
        $packages = $result['data']['pkg'];
    }

    $content = '
        <h2>Buat Akun Baru</h2>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Form Pembuatan Akun</h5>
                <form action="?page=create_account" method="POST" id="mainform">
                    <!-- Prevent password autofill -->
                    <input type="text" style="display:none">
                    <input type="password" autocomplete="off" style="display:none">

                    <!-- Domain Information -->
                    <div class="fatBorder">
                        <fieldset class="groupEditor">
                            <h3 class="collapsible-wrapper">Domain Information</h3>
                            <div class="content width-inherit" id="domain_information">
                                <div class="propertyGroup">
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="domain">Domain</label></div>
                                        <div class="propertyValue">
                                            <div class="mb-3">
                                                <input type="radio" name="domain_option" id="use_own_domain" value="use_own_domain" checked>
                                                <label for="use_own_domain" class="ms-2">Use a domain that you own</label>
                                            </div>
                                            <div class="mb-3">
                                                <input type="text" name="domain" id="domain" class="form-control" spellcheck="false">
                                            </div>
                                            <div class="mb-3">
                                                <input type="radio" name="domain_option" id="choose_domain_later" value="choose_domain_later">
                                                <label for="choose_domain_later" class="ms-2">Choose a domain later</label>
                                                <div class="text-muted small mt-1">We will assign a temporary domain to your website so you can start building right away.</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="username">Username</label></div>
                                        <div class="propertyValue">
                                            <input type="text" name="username" id="username" class="form-control" spellcheck="false" maxlength="16">
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="password">Password</label></div>
                                        <div class="propertyValue">
                                            <input type="password" autocomplete="off" name="password" id="password" class="form-control">
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="password2">Re-type Password</label></div>
                                        <div class="propertyValue">
                                            <input type="password" autocomplete="off" id="password2" class="form-control">
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyLabel">Strength</div>
                                        <div class="propertyValue">
                                            <div id="pwStrengthContainer">
                                                <div id="pwStrengthWidget" class="progress" style="height: 20px;">
                                                    <div id="password_strength" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                        <span>Very Weak (0/100)</span>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()">Password Generator</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="contactemail">Email</label></div>
                                        <div class="propertyValue">
                                            <input type="email" name="contactemail" id="contactemail" class="form-control" spellcheck="false">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Package -->
                    <div class="fatBorder">
                        <fieldset class="groupEditor">
                            <h3 class="collapsible-wrapper">Package</h3>
                            <div class="content width-inherit" id="package_wrapper">
                                <div class="propertyGroup">
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="pkgselect">Choose a Package</label></div>
                                        <div class="propertyValue">
                                            <select name="msel" id="pkgselect" class="form-control" style="max-width: 300px;">';
    foreach ($packages as $pkg) {
        $content .= '<option value="' . htmlspecialchars($pkg['name']) . '">' . htmlspecialchars($pkg['name']) . '</option>';
    }
    $content .= '
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Settings -->
                    <div class="fatBorder">
                        <fieldset class="groupEditor">
                            <h3 class="collapsible-wrapper">Settings</h3>
                            <div class="content width-inherit" id="settings_div">
                                <div class="alert alert-info mb-3">
                                    The package that you choose determines these settings. For example, you can only select an IP address if the selected package includes a dedicated IP address.
                                </div>
                                <div class="propertyGroup">
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="cgi">CGI Access</label></div>
                                        <div class="propertyValue">
                                            <input type="checkbox" name="cgi" id="cgi" value="1" checked>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="digestauth">Digest Authentication for Web Disk</label></div>
                                        <div class="propertyValue">
                                            <input type="checkbox" name="digestauth" id="digestauth" value="1">
                                            <i class="bi bi-info-circle ms-2" title="Windows Vista®, Windows® 7, and Windows® 8 require Digest Authentication support to be enabled in order to access your Web Disk over a clear text/unencrypted connection."></i>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="language">Locale</label></div>
                                        <div class="propertyValue">
                                            <select name="language" id="language" class="form-control" style="max-width: 300px;">
                                                <option value="ar">Arabic (العربية)</option>
                                                <option value="cs">Czech (čeština)</option>
                                                <option value="da">Danish (dansk)</option>
                                                <option value="de">German (Deutsch)</option>
                                                <option value="el">Greek (Ελληνικά)</option>
                                                <option value="en" selected>English</option>
                                                <option value="es">Spanish (español)</option>
                                                <option value="es_419">Latin American Spanish (español latinoamericano)</option>
                                                <option value="es_es">Iberian Spanish (español de España)</option>
                                                <option value="fi">Finnish (suomi)</option>
                                                <option value="fil">Filipino (fil)</option>
                                                <option value="fr">French (français)</option>
                                                <option value="he">Hebrew (עברית)</option>
                                                <option value="hu">Hungarian (magyar)</option>
                                                <option value="id">Indonesian (Bahasa Indonesia)</option>
                                                <option value="it">Italian (italiano)</option>
                                                <option value="ja">Japanese (日本語)</option>
                                                <option value="ko">Korean (한국어)</option>
                                                <option value="ms">Malay (Bahasa Melayu)</option>
                                                <option value="nb">Norwegian Bokmål (norsk bokmål)</option>
                                                <option value="nl">Dutch (Nederlands)</option>
                                                <option value="pl">Polish (polski)</option>
                                                <option value="pt">Portuguese (português)</option>
                                                <option value="pt_br">Brazilian Portuguese (português do Brasil)</option>
                                                <option value="ro">Romanian (română)</option>
                                                <option value="ru">Russian (русский)</option>
                                                <option value="sv">Swedish (svenska)</option>
                                                <option value="th">Thai (ไทย)</option>
                                                <option value="tr">Turkish (Türkçe)</option>
                                                <option value="uk">Ukrainian (українська)</option>
                                                <option value="vi">Vietnamese (Tiếng Việt)</option>
                                                <option value="zh">Chinese (中文)</option>
                                                <option value="zh_tw">Chinese (Taiwan) (中文（台湾）)</option>
                                            </select>
                                            <div class="text-muted small mt-1">Don’t see your language of choice? Take our Language Support Feedback Survey.</div>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="spamassassin">Enable Apache SpamAssassin™</label></div>
                                        <div class="propertyValue">
                                            <input type="checkbox" name="spamassassin" id="spamassassin" value="1" checked>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyLabel"><label for="spambox">Enable Spam Box</label></div>
                                        <div class="propertyValue">
                                            <input type="checkbox" name="spambox" id="spambox" value="1" checked>
                                            <i class="bi bi-info-circle ms-2" title="Save spam messages to a folder. You must enable Apache SpamAssassin™ to use this feature."></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Mail Routing Settings -->
                    <div class="fatBorder">
                        <fieldset class="groupEditor">
                            <h3 class="collapsible-wrapper">Mail Routing Settings <small>(optional)</small></h3>
                            <div class="content width-inherit" id="mail_routing_settings">
                                <div class="propertyGroup">
                                    <div class="propertyEditor">
                                        <div class="propertyCombined">
                                            <input type="radio" name="mxcheck" id="mxcheck_auto" value="auto">
                                            <label for="mxcheck_auto" class="ms-2">Automatically Detect Configuration</label> <span class="text-muted">(recommended)</span>
                                            <div class="mt-2">
                                                <ul class="list-unstyled ms-4">
                                                    <li>Local Mail Exchanger: <em class="small">If the lowest numbered mail exchanger points to an IP address on this server, the server will be configured to accept mail locally and from outside the server.</em></li>
                                                    <li class="mt-2">Backup Mail Exchanger: <em class="small">If a mail exchanger that is not the lowest numbered mail exchanger points to an IP address on this server, the server will be configured to act as a backup mail exchanger.</em></li>
                                                    <li class="mt-2">Remote Mail Exchanger: <em class="small">If there are no mail exchangers that point to an IP address on this server, the server will not accept mail locally and will send mail to the lowest MX record.</em></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyCombined">
                                            <input type="radio" name="mxcheck" id="mxcheck_local" value="local" checked>
                                            <label for="mxcheck_local" class="ms-2">Local Mail Exchanger</label>
                                            <div class="mt-2"><em class="small">Configure the server to always accept mail. Mail will be delivered locally on the server whenever it is sent from the server or outside the server.</em></div>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyCombined">
                                            <input type="radio" name="mxcheck" id="mxcheck_secondary" value="secondary">
                                            <label for="mxcheck_secondary" class="ms-2">Backup Mail Exchanger</label>
                                            <div class="mt-2"><em class="small">Configure the server as a backup mail exchanger. Mail will be held until a lower number mail exchanger is available.</em></div>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyCombined">
                                            <input type="radio" name="mxcheck" id="mxcheck_remote" value="remote">
                                            <label for="mxcheck_remote" class="ms-2">Remote Mail Exchanger</label>
                                            <div class="mt-2"><em class="small">Configure the server to not accept mail locally and send mail to the lowest MX record.</em></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <!-- DNS Settings -->
                    <div class="fatBorder">
                        <fieldset class="groupEditor">
                            <h3 class="collapsible-wrapper">DNS Settings <small>(optional)</small></h3>
                            <div class="content width-inherit" id="dns_settings">
                                <div class="propertyGroup">
                                    <div class="propertyEditor">
                                        <div class="propertyCombined">
                                            <label for="dkim">
                                                <input type="checkbox" name="dkim" id="dkim" value="1" checked disabled>
                                                Enable DKIM on this account.
                                            </label>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyCombined">
                                            <label for="spf">
                                                <input type="checkbox" name="spf" id="spf" value="1" checked disabled>
                                                Enable SPF on this account.
                                            </label>
                                            <span class="text-muted small ms-2"> (v=spf1 +a +mx +ip4:104.161.43.2 ~all)</span>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyCombined">
                                            <label for="dmarc">
                                                <input type="checkbox" name="dmarc" id="dmarc" value="1" checked>
                                                Enable DMARC on this account. (DMARC requires DKIM and SPF to be enabled.)
                                            </label>
                                            <span class="text-muted small ms-2"> (v=DMARC1; p=none;)</span>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyCombined">
                                            <label>
                                                <input type="checkbox" name="useregns" id="useregns" value="1">
                                                Use the nameservers specified at the Domain’s Registrar. (Ignore locally specified nameservers.)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="propertyEditor">
                                        <div class="propertyCombined">
                                            <div class="rowWrapper">
                                                <div class="fw-bold">Nameservers:</div>
                                                <div>ns1.nube.monster<br>ns2.nube.monster</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <button type="submit" class="btn btn-success mt-4">Create Account</button>
                </form>
            </div>
        </div>
        <p class="mt-3"><strong>API digunakan:</strong> createacct. Semua parameter akan dikirim ke API WHM.</p>
        <script>
            function generatePassword() {
                const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()";
                let password = "";
                for (let i = 0; i < 12; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                document.getElementById("password").value = password;
                document.getElementById("password2").value = password;
                const event = new Event("input");
                document.getElementById("password").dispatchEvent(event);
            }
        </script>
    ';
    echo render_template($content, "Create New Account", $message);
    exit;
}

// Reset Password
if (isset($_GET['page']) && $_GET['page'] === 'reset_password') {
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = trim($_POST['user'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        // Validasi input
        if (strlen($user) > 16) {
            $message = "Error: Username tidak boleh lebih dari 16 karakter.";
        } elseif (empty($user)) {
            $message = "Error: Username tidak boleh kosong.";
        } elseif (empty($password)) {
            $message = "Error: Password tidak boleh kosong.";
        } elseif (strlen($password) < 8) {
            $message = "Error: Password harus minimal 8 karakter.";
        } elseif (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d).+$/', $password)) {
            $message = "Error: Password harus mengandung kombinasi huruf dan angka.";
        } else {
            // Verifikasi bahwa pengguna ada menggunakan API listaccts
            $account_check = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'listaccts', ['search' => $user]);
            $user_exists = false;
            if (!isset($account_check['error']) && isset($account_check['data']['acct'])) {
                foreach ($account_check['data']['acct'] as $account) {
                    if ($account['user'] === $user) {
                        $user_exists = true;
                        break;
                    }
                }
            }

            if (!$user_exists) {
                $message = "Error: Pengguna '$user' tidak ditemukan di sistem.";
            } else {
                // Parameter untuk API passwd
                $params = [
                    'user' => $user,
                    'password' => $password,
                    'db_pass_update' => isset($_POST['db_pass_update']) ? 1 : 0
                ];

                // Panggil API passwd
                $result = call_whm_api($_SESSION['host'], $_SESSION['username'], $_SESSION['token'], 'passwd', $params);

                // Cek respon API
                if (isset($result['error'])) {
                    $message = "Error: {$result['error']}";
                    // Log error untuk debugging
                    error_log("Gagal reset password untuk pengguna '$user': {$result['error']}");
                } else {
                    $message = "Password untuk pengguna '$user' berhasil direset.";
                    // Log sukses untuk debugging
                    error_log("Password untuk pengguna '$user' berhasil direset.");

                    // Opsional: Verifikasi password baru (jika API mendukung)
                    // Catatan: WHM tidak menyediakan API langsung untuk verifikasi login, jadi langkah ini mungkin memerlukan metode alternatif
                }
            }
        }
    }

    $content = '
        <h2>Reset Password Akun</h2>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Form Reset Password</h5>
                <form method="POST" id="resetPasswordForm">
                    <div class="mb-3">
                        <label class="form-label">Username Akun</label>
                        <input type="text" name="user" class="form-control" required maxlength="16" placeholder="Masukkan username cPanel/WHM">
                        <small class="form-text text-muted">Username harus sesuai dengan akun cPanel/WHM yang ada.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" required minlength="8" placeholder="Masukkan password baru">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">Password harus minimal 8 karakter, mengandung huruf dan angka.</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="db_pass_update" class="form-check-input" checked id="db_pass_update">
                        <label class="form-check-label" for="db_pass_update">Update Password Database MySQL juga</label>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitButton">
                        <span id="submitText">Reset Password</span>
                        <span id="loadingSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </button>
                </form>
            </div>
        </div>
        <p class="mt-3"><strong>API digunakan:</strong> listaccts, passwd. Hanya untuk akun cPanel/WHM yang sudah ada.</p>
        <script>
            function togglePassword() {
                const passwordField = document.getElementById("password");
                const toggleIcon = document.getElementById("togglePasswordIcon");
                if (passwordField.type === "password") {
                    passwordField.type = "text";
                    toggleIcon.classList.remove("bi-eye");
                    toggleIcon.classList.add("bi-eye-slash");
                } else {
                    passwordField.type = "password";
                    toggleIcon.classList.remove("bi-eye-slash");
                    toggleIcon.classList.add("bi-eye");
                }
            }

            document.getElementById("resetPasswordForm").addEventListener("submit", function(e) {
                const submitButton = document.getElementById("submitButton");
                const submitText = document.getElementById("submitText");
                const loadingSpinner = document.getElementById("loadingSpinner");
                
                submitButton.disabled = true;
                submitText.classList.add("d-none");
                loadingSpinner.classList.remove("d-none");
                
                // Submit form secara asinkronus (opsional, jika ingin validasi lebih lanjut via AJAX)
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitText.classList.remove("d-none");
                    loadingSpinner.classList.add("d-none");
                }, 2000); // Simulasi delay, hapus jika tidak diperlukan
            });
        </script>
    ';
    echo render_template($content, "Reset Password", $message);
    exit;
}

// Logout
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    session_destroy();
    header('Location: ?page=login');
    exit;
}

// Redirect ke login jika halaman tidak dikenali
header('Location: ?page=login');
exit;
?>