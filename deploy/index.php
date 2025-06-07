<?php
session_start();
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: admin_home.php');
    } else {
        header('Location: student_home.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>登入</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: 'Noto Sans TC', Arial, sans-serif; background: #f7f8fa; margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 300px; }
        h1 { text-align: center; color: #ff69b4; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #333; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #ff69b4; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #ff4da6; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>登入</h1>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">帳號</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密碼</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">登入</button>
        </form>
    </div>
</body>
</html> 