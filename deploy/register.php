<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>註冊</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: 'Noto Sans TC', Arial, sans-serif; background: #f7f8fa; margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .register-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 300px; }
        h1 { text-align: center; color: #ff69b4; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #333; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #ff69b4; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #ff4da6; }
        .login-link { text-align: center; margin-top: 15px; }
        .login-link a { color: #ff69b4; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>註冊</h1>
        <form action="register_process.php" method="post">
            <div class="form-group">
                <label for="username">帳號</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密碼</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">確認密碼</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">註冊</button>
        </form>
        <div class="login-link">
            已經有帳號？<a href="login.php">登入</a>
        </div>
    </div>
</body>
</html> 