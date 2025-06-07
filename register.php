<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>註冊 UNITRACK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: #111;
            font-family: 'Poppins', 'Noto Sans TC', sans-serif;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('bg.png') center center/cover no-repeat;
            opacity: 0.8;
            z-index: 0;
            pointer-events: none;
        }
        .register-card {
            background: rgba(255,255,255,0.85);
            border-radius: 28px;
            box-shadow: 0 8px 32px rgba(60,60,120,0.10);
            padding: 40px 32px 32px 32px;
            width: 360px;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%,-50%);
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            border: 2.5px solid #bcb8f8;
        }
        .register-title {
            font-size: 2rem;
            font-weight: 700;
            color: #bcb8f8;
            margin-bottom: 24px;
            letter-spacing: 0.08em;
        }
        .register-card label {
            font-size: 1rem;
            color: #333;
            margin-bottom: 6px;
            margin-top: 12px;
        }
        .register-card input, .register-card select {
            width: 100%;
            max-width: 220px;
            margin: 0 auto 8px auto;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1.5px solid #bcb8f8;
            font-size: 1rem;
            background: #fff;
        }
        .register-btn {
            width: 100%;
            padding: 12px 0;
            background: #bcb8f8;
            color: #222;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 18px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .register-btn:hover {
            background: #a89af5;
        }
        .to-login {
            margin-top: 18px;
            text-align: right;
            width: 100%;
        }
        .to-login a {
            color: #bcb8f8;
            text-decoration: none;
            font-size: 0.98rem;
        }
        .to-login a:hover {
            text-decoration: underline;
        }
        @media (max-width: 700px) {
            .register-card {
                right: 50%;
                left: 50%;
                top: 50%;
                transform: translate(50%,-50%);
                width: 92vw;
                min-width: 0;
                padding: 28px 8px 24px 8px;
            }
            .register-card input, .register-card select {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <form class="register-card" method="post" action="register_process.php">
        <div class="register-title">註冊 UNITRACK</div>
        <label for="username">帳號</label>
        <input type="text" id="username" name="username" required>
        <label for="password">密碼</label>
        <input type="password" id="password" name="password" required>
        <label for="confirm_password">確認密碼</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="role">註冊身分</label>
        <select id="role" name="role" required>
            <option value="">請選擇</option>
            <option value="student">學生</option>
            <option value="teacher">老師</option>
        </select>
        <button class="register-btn" type="submit">註冊</button>
        <div class="to-login">
            已有帳號？<a href="login.php">登入</a>
        </div>
    </form>
</body>
</html> 