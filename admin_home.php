<?php
// 啟用錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 移除會話檢查和身份驗證
// session_start();
// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
//     header('Location: index.html');
//     exit;
// }

try {
    require_once 'db.php';
} catch (Exception $e) {
    die('數據庫連接錯誤: ' . $e->getMessage());
}

// 獲取統計資料
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_students' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'total_teachers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn(),
    'total_admins' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
    'total_posts' => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'total_categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'total_comments' => $pdo->query("SELECT COUNT(*) FROM post_comments")->fetchColumn()
];

// 獲取最近註冊的用戶
$recent_users = $pdo->query("SELECT username, email, role FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

// 獲取最近的文章
$recent_posts = $pdo->query("
    SELECT p.title, p.created_at, u.username 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
")->fetchAll();

// 獲取活躍用戶（發文數最多的）
$active_users = $pdo->query("
    SELECT u.username, COUNT(p.id) as post_count 
    FROM users u 
    LEFT JOIN posts p ON u.id = p.user_id 
    WHERE u.role = 'student' 
    GROUP BY u.id 
    ORDER BY post_count DESC 
    LIMIT 5
")->fetchAll();

// 獲取所有學生列表
$all_students = $pdo->query("
    SELECT u.*, COUNT(p.id) as post_count 
    FROM users u 
    LEFT JOIN posts p ON u.id = p.user_id 
    WHERE u.role = 'student' 
    GROUP BY u.id 
    ORDER BY u.id DESC
")->fetchAll();

// 獲取所有教師列表
$all_teachers = $pdo->query("
    SELECT * FROM users 
    WHERE role = 'teacher' 
    ORDER BY id DESC
")->fetchAll();

// 獲取所有管理員列表
$all_admins = $pdo->query("SELECT * FROM users WHERE role = 'admin' ORDER BY id DESC")->fetchAll();

// 獲取所有文章列表
$all_posts = $pdo->query("
    SELECT p.*, u.username, c.name as category_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
")->fetchAll();

// 獲取所有分類列表
$all_categories = $pdo->query("
    SELECT c.*, u.username, COUNT(p.id) as post_count 
    FROM categories c 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN posts p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.id DESC
")->fetchAll();

// 獲取所有評論列表
$all_comments = $pdo->query("
    SELECT * FROM post_comments 
    ORDER BY created_at DESC
")->fetchAll();

// 獲取所有用戶列表
$all_users = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT p.id) as post_count
    FROM users u 
    LEFT JOIN posts p ON u.id = p.user_id 
    GROUP BY u.id 
    ORDER BY u.id DESC
")->fetchAll();

// 獲取用戶數據
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetchColumn();

// 獲取最近6個月的用戶數據
$months = [];
$userCounts = [];
$postCounts = [];
$commentCounts = [];

// 獲取最近6個月的數據
for ($i = 5; $i >= 0; $i--) {
    $startDate = date('Y-m-01', strtotime("-$i months"));
    $endDate = date('Y-m-t', strtotime("-$i months"));
    $monthName = date('m月', strtotime("-$i months"));
    
    // 用戶註冊數量
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM users 
        WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $userCounts[] = $stmt->fetchColumn();
    
    // 文章發布數量
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM posts 
        WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $postCounts[] = $stmt->fetchColumn();
    
    // 評論數量
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM post_comments 
        WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $commentCounts[] = $stmt->fetchColumn();
    
    $months[] = $monthName;
}

// 獲取分類分布數據
$stmt = $pdo->query("
    SELECT c.name, COUNT(p.id) as count 
    FROM categories c 
    LEFT JOIN posts p ON c.id = p.category_id 
    GROUP BY c.id, c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取用戶角色分布
$stmt = $pdo->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理面板</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f4f6fb;
            font-family: 'Poppins', 'Noto Sans TC', sans-serif;
            margin: 0;
        }
        .main-card {
            background: #fff;
            border-radius: 32px;
            box-shadow: 0 8px 32px rgba(60,60,120,0.08);
            padding: 40px 32px;
            margin: 40px auto 100px auto;
            max-width: 1400px;
            display: flex;
            gap: 32px;
        }
        .dashboard-main {
            flex: 2;
        }
        .dashboard-sidebar {
            flex: 1;
            min-width: 260px;
        }
        .dashboard-header {
            display: flex;
            align-items: center;
            margin-bottom: 32px;
        }
        .avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #eee;
            margin-right: 20px;
            object-fit: cover;
        }
        .greeting {
            font-size: 1.4rem;
            font-weight: 600;
            color: #333;
        }
        .stat-cards {
            display: flex;
            gap: 24px;
            margin-bottom: 32px;
        }
        .stat-card {
            flex: 1;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(60,60,120,0.08);
            background: #fff;
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 16px;
            background: #bcb8f8;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            color: #222;
        }
        .stat-info {
            text-align: left;
        }
        .stat-label {
            font-size: 1rem;
            color: #888;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #222;
        }
        .chart-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(60,60,120,0.08);
            padding: 32px 24px 24px 24px;
            margin-bottom: 32px;
        }
        .chart-card.small {
            padding: 16px 12px 12px 12px;
            margin-bottom: 20px;
            height: 150px;
            min-height: 0;
            overflow: hidden;
        }
        .chart-card.tiny {
            padding: 8px 8px 8px 8px;
            margin-bottom: 14px;
        }
        .sidebar-section {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(60,60,120,0.08);
            padding: 24px 20px;
            margin-bottom: 24px;
        }
        .sidebar-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }
        .user-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .user-list li {
            display: flex;
            align-items: center;
            margin-bottom: 14px;
            justify-content: space-between;
        }
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #eee;
            margin-right: 12px;
            object-fit: cover;
        }
        .user-name {
            font-size: 1rem;
            color: #333;
            flex: 1;
        }
        .user-meta {
            font-size: 0.85rem;
            color: #888;
            margin-left: 8px;
            min-width: 70px;
            text-align: right;
        }
        .bottom-nav {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            background: #bcb8f8;
            border-radius: 24px 24px 0 0;
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 64px;
            color: #fff;
            z-index: 100;
            max-width: 1200px;
            margin: 0 auto;
            right: 0; left: 0;
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 1.2rem;
            color: #fff;
            text-decoration: none;
            padding: 0 10px;
        }
        .nav-item .material-icons {
            font-size: 2rem;
            color: #222;
        }
        .nav-item span {
            font-size: 0.85rem;
            margin-top: 2px;
        }
        @media (max-width: 900px) {
            .main-card { flex-direction: column; padding: 20px 6px; }
            .dashboard-sidebar { min-width: 0; }
        }
        .charts-row {
            display: flex;
            gap: 32px;
            margin-bottom: 0;
        }
        .charts-row.center {
            justify-content: center;
            margin-top: 0;
        }
        .back-button {
            padding: 10px 24px;
            background-color: #bcb8f8;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .back-button:hover {
            background-color: #a89af5;
        }
        .primary {
            background-color: #bcb8f8;
            color: white;
        }
        .primary:hover {
            background-color: #a89af5;
        }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="dashboard-main">
            <div class="dashboard-header">
                <!-- <img src="avatar.png" class="avatar" alt="Admin"> -->
                <div class="greeting">管理員您好哈囉~</div>
            </div>
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-icons">group</span></div>
                    <div class="stat-info">
                        <div class="stat-label">總用戶數</div>
                        <div class="stat-value"><?php echo $totalUsers; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-icons">school</span></div>
                    <div class="stat-info">
                        <div class="stat-label">學生數</div>
                        <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-icons">person</span></div>
                    <div class="stat-info">
                        <div class="stat-label">教師數</div>
                        <div class="stat-value"><?php echo $stats['total_teachers']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="material-icons">admin_panel_settings</span></div>
                    <div class="stat-info">
                        <div class="stat-label">管理員數</div>
                        <div class="stat-value"><?php echo $stats['total_admins']; ?></div>
                    </div>
                </div>
            </div>
            <div class="charts-row">
                <div class="chart-card" style="flex:1;">
                    <div style="font-weight:600; margin-bottom:12px;">用戶成長趨勢</div>
                    <canvas id="userGrowthChart" height="80"></canvas>
                </div>
                <div class="chart-card small" style="flex:1;">
                    <div style="font-weight:600; margin-bottom:12px;">內容發布趨勢</div>
                    <canvas id="contentTrendChart" height="50"></canvas>
                </div>
            </div>
            <div class="charts-row center">
                <div class="chart-card tiny">
                    <div style="font-weight:600; margin-bottom:8px;">分類分布</div>
                    <canvas id="categoryDistributionChart" height="36"></canvas>
                </div>
                <div class="chart-card tiny">
                    <div style="font-weight:600; margin-bottom:8px;">用戶角色分布</div>
                    <canvas id="roleDistributionChart" height="36"></canvas>
                </div>
            </div>
        </div>
        <div class="dashboard-sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">最近註冊用戶</div>
                <ul class="user-list">
                    <?php foreach($recent_users as $user): ?>
                    <li>
                        <!-- <img src="avatar.png" class="user-avatar" alt="User"> -->
                        <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                        <span class="user-meta">
                            <?php
                            if (!empty($user['created_at']) && $user['created_at'] !== '1970-01-01') {
                                echo date('Y-m-d', strtotime($user['created_at']));
                            } else {
                                echo '未知';
                            }
                            ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">活躍學生</div>
                <ul class="user-list">
                    <?php foreach($active_users as $student): ?>
                    <li>
                        <!-- <img src="avatar.png" class="user-avatar" alt="Student"> -->
                        <span class="user-name"><?php echo htmlspecialchars($student['username']); ?></span>
                        <span class="user-meta"><?php echo $student['post_count']; ?> 篇文章</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="bottom-nav">
        <a href="admin_home.php" class="nav-item"><span class="material-icons">dashboard</span><span>Dashboard</span></a>
        <a href="show_details.php?type=users" class="nav-item"><span class="material-icons">group</span><span>Users</span></a>
        <a href="show_details.php?type=posts" class="nav-item"><span class="material-icons">article</span><span>Posts</span></a>
        <a href="show_details.php?type=categories" class="nav-item"><span class="material-icons">category</span><span>Categories</span></a>
        <a href="logout.php" class="nav-item"><span class="material-icons">logout</span><span>Logout</span></a>
    </div>
    <script>
    // 用戶成長趨勢圖表初始化
    <?php
    $months = $months ?? [];
    $userCounts = $userCounts ?? [];
    $postCounts = $postCounts ?? [];
    $commentCounts = $commentCounts ?? [];
    $categories = $categories ?? [];
    $roles = $roles ?? [];
    ?>
    // 用戶成長趨勢
    const ctx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: '新用戶數',
                data: <?php echo json_encode($userCounts); ?>,
                borderColor: '#bcb8f8',
                backgroundColor: 'rgba(188,184,248,0.15)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#bcb8f8',
                pointHoverRadius: 6
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#eee' }, beginAtZero: true }
            }
        }
    });
    // 內容發布趨勢
    const ctx2 = document.getElementById('contentTrendChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: '文章數',
                    data: <?php echo json_encode($postCounts); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: '評論數',
                    data: <?php echo json_encode($commentCounts); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            plugins: { legend: { display: true, position: 'top' } },
            maintainAspectRatio: false,
            layout: { padding: { top: 0, bottom: 0 } },
            scales: {
                x: {
                    grid: { display: false },
                    offset: true,
                    ticks: { padding: 8 }
                },
                y: { grid: { color: '#eee' }, beginAtZero: true }
            }
        }
    });
    // 分類分布
    const ctx3 = document.getElementById('categoryDistributionChart').getContext('2d');
    new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($categories, 'name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($categories, 'count')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            plugins: { legend: { display: true, position: 'right' } }
        }
    });
    // 用戶角色分布
    const ctx4 = document.getElementById('roleDistributionChart').getContext('2d');
    new Chart(ctx4, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($roles, 'role')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($roles, 'count')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            plugins: { legend: { display: true, position: 'right' } }
        }
    });
    </script>
</body>
</html> 