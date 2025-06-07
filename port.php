<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>unitrack 入口網站</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;500&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f4f6fb 0%, #e9e4f7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
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
        .main-theme-box {
            background: rgba(255,255,255,0.8);
            border-radius: 32px;
            box-shadow: 0 8px 32px rgba(60,60,120,0.10);
            padding: 48px 40px 36px 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 320px;
            max-width: 350px;
            position: relative;
            z-index: 1;
            border: 2.5px solid #bcb8f8;
        }
        .logo-text {
            font-family: 'Poppins', 'Noto Sans TC', sans-serif;
            font-size: 2.6rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            color: #bcb8f8;
            margin-bottom: 8px;
            text-shadow: 0 2px 8px rgba(188,184,248,0.12);
            text-transform: uppercase;
        }
        .logo-sub {
            font-size: 1.1rem;
            color: #888;
            margin-bottom: 32px;
            letter-spacing: 0.04em;
        }
        .entry-btns {
            display: flex;
            gap: 18px;
            width: 100%;
            justify-content: center;
        }
        .entry-btn {
            flex: 1;
            padding: 18px 0;
            min-width: 120px;
            font-size: 1.15rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(60,60,120,0.08);
        }
        .entry-btn.login {
            background: #bcb8f8;
            color: #222;
        }
        .entry-btn.login:hover {
            background: #a89af5;
        }
        .entry-btn.register {
            background: #fff;
            color: #bcb8f8;
            border: 2px solid #bcb8f8;
        }
        .entry-btn.register:hover {
            background: #bcb8f8;
            color: #222;
        }
        @media (max-width: 600px) {
            .main-theme-box {
                padding: 24px 6px 18px 6px;
                min-width: 0;
                max-width: 98vw;
            }
            .logo-text {
                font-size: 2rem;
            }
            .logo-sub {
                font-size: 0.95rem;
                margin-bottom: 20px;
            }
            .entry-btns {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-theme-box">
        <div class="logo-text">UNITRACK</div>
        <div class="logo-sub">一個紀錄學習歷程的網站</div>
        <div class="entry-btns">
            <a href="login.php"><button class="entry-btn login">登入</button></a>
            <a href="register.php"><button class="entry-btn register">註冊</button></a>
        </div>
    </div>
</body>
</html> 