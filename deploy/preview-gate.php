<?php
declare(strict_types=1);
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow, noarchive');
$settings=require '/srv/enroute-preview/preview-gate-config.php';
$token=$_COOKIE['enroute_preview']??'';
$parts=explode('.', $token, 2);
$valid=count($parts)===2 && ctype_digit($parts[0]) && (int)$parts[0]>time() && (int)$parts[0]<=time()+28800 && hash_equals(hash_hmac('sha256',$parts[0],$settings['secret']),$parts[1]);
if(($_SERVER['PREVIEW_AUTH_CHECK']??'')==='1'){
    http_response_code($valid?204:401);exit;
}
if($valid){header('Location: /jewelry/',true,303);exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(password_verify((string)($_POST['password']??''),$settings['password_hash'])){
        $expiry=(string)(time()+28800);
        setcookie('enroute_preview',$expiry.'.'.hash_hmac('sha256',$expiry,$settings['secret']),['expires'=>(int)$expiry,'path'=>'/jewelry/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
        header('Location: /jewelry/',true,303);exit;
    }
    $error='密码不正确，请重试。';
}
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>开发预览 · 访问验证</title><style>*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f3ef;color:#292724;font:15px system-ui,sans-serif;padding:24px}main{width:100%;max-width:410px;background:white;padding:38px;border:1px solid #e3dfd8}h1{font-size:25px;font-weight:500;margin:0 0 12px}p{color:#777;line-height:1.7;font-size:14px}label{display:block;margin-top:25px}input{display:block;width:100%;padding:13px;border:1px solid #bbb;font:inherit;margin:9px 0 16px}button{width:100%;padding:14px;background:#292724;color:white;border:0;font:inherit;cursor:pointer}.error{color:#a32b32}</style></head><body><main><h1>开发站预览</h1><p>输入访问密码，即可查看正在开发中的饰品站点。</p><form action="/jewelry/__preview-login" method="post"><label for="password">访问密码</label><input id="password" name="password" type="password" autocomplete="current-password" required autofocus><?php if($error): ?><p class="error" role="alert"><?php echo htmlspecialchars($error,ENT_QUOTES,'UTF-8'); ?></p><?php endif; ?><button type="submit">进入预览</button></form></main></body></html>
