<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php
session_start();
// 数据库连接
include 'config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 处理登录表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // 修改查询语句，选择所有字段
    $stmt = $conn->prepare("SELECT id, username, password, name, role FROM users WHERE username=?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $username, $hashed_password, $name, $role);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {
        // 验证密码
        if (password_verify($pass, $hashed_password)) {
            $_SESSION['id'] = $id;          // 存储 id 到会话中
            $_SESSION['username'] = $username; // 存储 username 到会话中
            $_SESSION['name'] = $name;      // 存储 name 到会话中
            $_SESSION['role'] = $role;      // 存储 role 到会话中

            // 调试信息：记录会话变量
            error_log("会话变量已存储: " . print_r($_SESSION, true));

            // 登录成功后刷新页面
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "用户名或密码错误";
        }
    } else {
        $error = "用户名或密码错误";
    }

    $stmt->close();
}

// 处理注销
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 获取搜索类型和查询
$type = isset($_GET['type']) ? $_GET['type'] : 'request';
$query = isset($_GET['query']) ? $_GET['query'] : '';

// 根据搜索类型执行不同的查询
if ($type == 'request') {
    $stmt = $conn->prepare("SELECT * FROM requests WHERE title LIKE ? OR description LIKE ?");
} else {
    $stmt = $conn->prepare("SELECT * FROM bugs WHERE title LIKE ? OR description LIKE ?");
}

$search_query = '%' . $query . '%';
$stmt->bind_param("ss", $search_query, $search_query);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>需求收集</title>
    <link href="path/to/logo.png" rel="icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'CustomFont';
            src: url('path/zt/logo.otf') format('opentype');
        }
        @font-face {
            font-family: 'Douyu';
            src: url('path/zt/douyuFont.otf') format('opentype');
        }
        .custom-font {
            font-family: 'CustomFont', sans-serif;
            background: linear-gradient(270deg, #FF0000, #00FF00, #0000FF, #FF0000);
            background-size: 800% 800%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientAnimation 10s ease infinite;
        }
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .navbar-brand {
            margin-left: 20px;
        }
        .navbar-nav {
            margin-right: 20px;
        }
        .navbar-nav .nav-item {
            display: flex;
            align-items: center;
            font-family: 'Douyu', sans-serif;
        }
        .navbar-brand img {
            margin-right: 10px;
        }
        .btn-wide {
            width: 100px; /* 调整按钮宽度 */
        }
        .nav-link:hover, .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #FFD700; /* 鼠标悬停时的颜色 */
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .navbar-text-username {
            color: #D3D3D3; /* 登录后的用户名显示为白灰色 */
        }
        .modal {
            z-index: 100000;
        }
        .modal-dialog {
            font-family: 'Douyu', sans-serif;
        }
        .modal-content {
            background-color: #fff; /* 背景色为白色 */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* 浅黑色阴影 */
        }
        .modal {
            margin-top: 20%;
        }
        .modal-title, .form-label {
            color: #2e317c;
        }
        /* 滚动条美化 */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: linear-gradient(45deg, #f1f1f1, #e1e1e1);
            border-radius: 10px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #ff6b6b, #f06595);
            border-radius: 10px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
            transition: background 0.3s ease, transform 0.3s ease, height 0.3s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #ff4757, #e84393);
            transform: scale(1.1);
        }
        .back-button {
            margin-top: 10px;
            display: inline-block;
            width: 70px;
            height: 40px;
            border-radius: 50px;
            background-color: #ccccd6;
            color: #1e131d;
            text-align: center;
            line-height: 40px;
            transition: transform 0.3s ease, opacity 0.3s ease;
            animation: bounce 1s infinite;
        }

        .back-button svg {
            vertical-align: middle;
        }

        .back-button:hover {
            transform: scale(1.1);
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .back-button-clicked {
            animation: slideOut 0.5s forwards;
        }

        @keyframes slideOut {
            0% {
                transform: translateX(0);
                opacity: 1;
            }
            100% {
                transform: translateX(-100%);
                opacity: 0;
            }
        }

        .back-button-slide-in {
            animation: slideIn 0.5s forwards;
        }

        @keyframes slideIn {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .nav-link {
            position: relative;
            transition: background-color 0.3s ease, border-radius 0.3s ease;
        }
        
        .nav-link.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #c35691;
            border-radius: 5px;
            z-index: -1;
            transition: transform 0.3s ease;
        }
        
        .nav-link.active {
            z-index: 1;
        }
        .lebie, .sousuo, .sousuoan {
            height: 40px; /* 设置相同的高度 */
            padding: 0.375rem 1.75rem;
            outline: none; /* 去掉聚焦框 */
            border: none;
        }
        .lebie {
            border-radius: 50px 0 0 50px ;
        }
        
        .sousuo {
            border-left: 0.5px solid #000;
            width: 40px;
        }
        
        .sousuoan {
            border-radius: 0 50px 50px 0;
            /*背景色*/
            background-color: #007bff;
            color: #fff;
        }
        .sousuoan:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body class="bg-dark text-white">
<!-- 导航栏 -->
<nav class="navbar navbar-expand-lg navbar-light bg-primary">
    <div class="container">
        <a class="navbar-brand text-white d-flex align-items-center" href="./">
            <img src="path/to/logo.png" alt="Logo" width="40" height="40" class="d-inline-block align-top">
            <span class="custom-font">CINDY</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="切换导航">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/>
                        </svg> 首页
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $current_page == 'lookbug.php' ? 'active' : ''; ?>" href="lookbug.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bug" viewBox="0 0 16 16">
                            <path d="M4.355.522a.5.5 0 0 1 .623.333l.291.956A5 5 0 0 1 8 1c1.007 0 1.946.298 2.731.811l.29-.956a.5.5 0 1 1 .957.29l-.41 1.352A5 5 0 0 1 13 6h.5a.5.5 0 0 0 .5-.5V5a.5.5 0 0 1 1 0v.5A1.5 1.5 0 0 1 13.5 7H13v1h1.5a.5.5 0 0 1 0 1H13v1h.5a1.5 1.5 0 0 1 1.5 1.5v.5a.5.5 0 1 1-1 0v-.5a.5.5 0 0 0-.5-.5H13a5 5 0 0 1-10 0h-.5a.5.5 0 0 0-.5.5v.5a.5.5 0 1 1-1 0v-.5A1.5 1.5 0 0 1 2.5 10H3V9H1.5a.5.5 0 0 1 0-1H3V7h-.5A1.5 1.5 0 0 1 1 5.5V5a.5.5 0 0 1 1 0v.5a.5.5 0 0 0 .5.5H3c0-1.364.547-2.601 1.432-3.503l-.41-1.352a.5.5 0 0 1 .333-.623M4 7v4a4 4 0 0 0 3.5 3.97V7zm4.5 0v7.97A4 4 0 0 0 12 11V7zM12 6a4 4 0 0 0-1.334-2.982A3.98 3.98 0 0 0 8 2a3.98 3.98 0 0 0-2.667 1.018A4 4 0 0 0 4 6z"/>
                        </svg> 查看BUG
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $current_page == 'submit.php' ? 'active' : ''; ?>" href="submit.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-send-plus" viewBox="0 0 16 16">
                            <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855a.75.75 0 0 0-.124 1.329l4.995 3.178 1.531 2.406a.5.5 0 0 0 .844-.536L6.637 10.07l7.494-7.494-1.895 4.738a.5.5 0 1 0 .928.372zm-2.54 1.183L5.93 9.363 1.591 6.602z"/>
                            <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-3.5-2a.5.5 0 0 0-.5.5v1h-1a.5.5 0 0 0 0 1h1v1a.5.5 0 0 0 1 0v-1h1a.5.5 0 0 0 0-1h-1v-1a.5.5 0 0 0-.5-.5"/>
                        </svg> 提交需求
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $current_page == 'lookxq.php' ? 'active' : ''; ?>" href="lookxq.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-headset-vr" viewBox="0 0 16 16">
                            <path d="M8 1.248c1.857 0 3.526.641 4.65 1.794a5 5 0 0 1 2.518 1.09C13.907 1.482 11.295 0 8 0 4.75 0 2.12 1.48.844 4.122a5 5 0 0 1 2.289-1.047C4.236 1.872 5.974 1.248 8 1.248"/>
                            <path d="M12 12a4 4 0 0 1-2.786-1.13l-.002-.002a1.6 1.6 0 0 0-.276-.167A2.2 2.2 0 0 0 8 10.5c-.414 0-.729.103-.935.201a1.6 1.6 0 0 0-.277.167l-.002.002A4 4 0 1 1 4 4h8a4 4 0 0 1 0 8"/>
                        </svg> 查看需求
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $current_page == 'user.php' ? 'active' : ''; ?>" href="user.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-badge" viewBox="0 0 16 16">
                            <path d="M6.5 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zM11 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                            <path d="M4.5 0A2.5 2.5 0 0 0 2 2.5V14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2.5A2.5 2.5 0 0 0 11.5 0zM3 2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5v10.795a4.2 4.2 0 0 0-.776-.492C11.392 12.387 10.063 12 8 12s-3.392.387-4.224.803a4.2 4.2 0 0 0-.776.492z"/>
                        </svg> 个人中心
                    </a>
                </li>
                <li class="nav-item">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a class="nav-link text-white <?php echo $current_page == 'usergl.php' ? 'active' : ''; ?>" href="usergl.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                              <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                            </svg> 用户管理
                        </a>
                    <?php endif; ?>
                </li>
                <?php if(isset($_SESSION['name'])): ?>
                    <li class="nav-item">
                        <span class="navbar-text navbar-text-username me-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-emoji-wink-fill" viewBox="0 0 16 16">
                        <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0M7 6.5C7 5.672 6.552 5 6 5s-1 .672-1 1.5S5.448 8 6 8s1-.672 1-1.5M4.285 9.567a.5.5 0 0 0-.183.683A4.5 4.5 0 0 0 8 12.5a4.5 4.5 0 0 0 3.898-2.25.5.5 0 1 0-.866-.5A3.5 3.5 0 0 1 8 11.5a3.5 3.5 0 0 1-3.032-1.75.5.5 0 0 0-.683-.183m5.152-3.31a.5.5 0 0 0-.874.486c.33.595.958 1.007 1.687 1.007s1.356-.412 1.687-1.007a.5.5 0 0 0-.874-.486.93.93 0 0 1-.813.493.93.93 0 0 1-.813-.493"/>
                        </svg> <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a href="?logout=true" class="btn btn-outline-light btn-wide">退出</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-wide" data-bs-toggle="modal" data-bs-target="#loginModal">登录</button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<?php if (isset($_SESSION['id'])): ?>
<?php
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'index.php' && isset($_SERVER['HTTP_REFERER'])): ?>
    <div class="container text-center d-flex justify-content-between align-items-center mt-2">
        <a href="javascript:history.back()" class="back-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-90deg-left" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1.146 4.854a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H12.5A2.5 2.5 0 0 1 15 6.5v8a.5.5 0 0 1-1 0v-8A1.5 1.5 0 0 0 12.5 5H2.707l3.147 3.146a.5.5 0 1 1-.708.708z"/>
            </svg>
        </a>
        <form class="d-flex align-items-center" action="search.php" method="get" style="max-width: 600px; width: 100%;">
            <select name="type" class="lebie">
                <option value="request">需求</option>
                <option value="bug">BUG</option>
            </select>
            <input class="sousuo" type="search" placeholder="请输入关键字" aria-label="搜索" name="query" style="flex-grow: 1;">
            <button class="sousuoan" type="submit"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
              <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
            </svg> 搜索</button>
        </form>
    </div>
<?php endif; ?>
<?php endif; ?>
<div id="loading" class="d-none d-flex justify-content-center align-items-center" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(48, 22, 28, 0.5); z-index: 10050;">
  <div class="spinner-border" role="status">
    <span class="visually-hidden">Loading...</span>
  </div>
</div>
<script>
function showLoading() {
    document.getElementById('loading').classList.remove('d-none');
}

function hideLoading() {
    document.getElementById('loading').classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            showLoading();
        });
    });
});
</script>
<script>
function checkLogin() {
    <?php if (!isset($_SESSION['id'])): ?>
        alert("请先登录！");
        return false;
    <?php endif; ?>
    return true;
}
</script>


<?php if(!isset($_SESSION['rname'])): ?>
<!-- 登录模态框 -->
<div class="modal fade<?php if(isset($error)) { echo ' show'; } ?>" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true" style="<?php if(isset($error)) { echo 'display: block;'; } ?>">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">用户登录</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="请输入用户名" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="请输入密码" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100">登录</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link');
    const currentPage = '<?php echo $current_page; ?>';
    const activeLink = document.querySelector(`.nav-link[href="${currentPage}"]`);

    if (activeLink) {
        activeLink.classList.add('active');
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            navLinks.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>
</body>
</html>