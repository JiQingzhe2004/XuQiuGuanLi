<?php
include 'header.php';
// 创建 PDO 连接
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("数据库连接失败：" . $e->getMessage());
}

// 引入数据库配置
include 'config.php';

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);

session_start();

// 确保用户已登录
if (!isset($_SESSION['id'])) {
    echo "请先登录！";
    exit;
}

// 从会话中读取用户ID
$id = $_SESSION['id'];
$role = $_SESSION['role'];

// 初始化消息数组
$messages = [
    'info_success' => '',
    'info_error' => '',
    'password_success' => '',
    'password_error' => ''
];

// 功能函数：修改密码
function changePassword($id, $current_password, $new_password, $confirm_password, &$messages) {
    global $conn; // 使用 mysqli 连接
    if ($new_password !== $confirm_password) {
        $messages['password_error'] = "新密码和确认密码不一致！";
        return;
    }

    // 检查用户ID是否为空
    if (empty($id)) {
        $messages['password_error'] = "用户ID不能为空！";
        return;
    }

    // 查询数据库中密码
    $password_query = "SELECT password FROM users WHERE id = ?";
    $password_stmt = $conn->prepare($password_query);
    $password_stmt->bind_param('i', $id); // 绑定参数
    $password_stmt->execute();
    $result = $password_stmt->get_result();

    // 如果没有找到用户
    if ($result->num_rows === 0) {
        $messages['password_error'] = "用户不存在！";
        return;
    }

    $user_password = $result->fetch_assoc()['password'];

    if (!$user_password || !password_verify($current_password, $user_password)) {
        $messages['password_error'] = "当前密码错误！";
        return;
    }

    // 更新密码
    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
    $update_password_stmt = $conn->prepare($update_password_query);
    $update_password_stmt->bind_param('si', $hashed_new_password, $id); // 绑定参数
    if ($update_password_stmt->execute()) {
        $messages['password_success'] = "密码修改成功！";
    } else {
        $messages['password_error'] = "密码修改失败，请重试！错误: " . $conn->error;
    }
}

// 功能函数：更新用户信息
function updateUserInfo($id, $name, &$messages) {
    global $conn; // 使用 mysqli 连接
    if (empty($name)) {
        $messages['info_error'] = "姓名不能为空！";
        return;
    }

    // 更新用户信息
    $update_info_query = "UPDATE users SET name = ? WHERE id = ?";
    $update_info_stmt = $conn->prepare($update_info_query);
    $update_info_stmt->bind_param('si', $name, $id); // 绑定参数
    if ($update_info_stmt->execute()) {
        $messages['info_success'] = "信息更新成功！";
    } else {
        $messages['info_error'] = "信息更新失败，请重试！错误: " . $conn->error;
    }
}

// 获取用户信息
$user_info_query = "SELECT username, name FROM users WHERE id = ?";
$user_info_stmt = $conn->prepare($user_info_query);
$user_info_stmt->bind_param('i', $id); // 绑定参数
$user_info_stmt->execute();
$user_info_result = $user_info_stmt->get_result();

if ($user_info_result->num_rows > 0) {
    $user_info = $user_info_result->fetch_assoc();
    $username = $user_info['username'];
    $name = $user_info['name'];
} else {
    echo "用户信息不存在！";
    exit;
}

// 处理 POST 请求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['change_password'])) {
        changePassword(
            $id, // 从会话中获取用户ID
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? '',
            $_POST['confirm_password'] ?? '',
            $messages
        );
    } elseif (isset($_POST['update_info'])) {
        updateUserInfo(
            $id, // 从会话中获取用户ID
            $_POST['name'] ?? '',
            $messages
        );
    }
}
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>用户信息</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .user-info-body {
            padding-top: 50px;
            background-color: #f8f9fa;
        }
        .user-info-container {
            margin-top: 200px;
            max-width: 600px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-left: auto;
            margin-right: auto;
            font-family: 'Douyu', sans-serif;
        }
    </style>
</head>
<body class="user-info-body">
    <div class="user-info-container">
        <h3 class="custom-font">CINDY</h3>
        <table class="table table-striped">
            <tr>
                <th>用户名</th>
                <td><?php echo htmlspecialchars($username); ?></td>
            </tr>
            <tr>
                <th>姓名</th>
                <td><?php echo htmlspecialchars($name); ?></td>
            </tr>
            <tr>
                <th>角色</th>
                <td>
                    <?php 
                    if ($role === 'admin') {
                        echo '管理员';
                    } elseif ($role === 'user') {
                        echo '普通用户';
                    } else {
                        echo htmlspecialchars($role);
                    }
                    ?>
                </td>
            </tr>
        </table>
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-primary"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-houses" viewBox="0 0 16 16">
              <path d="M5.793 1a1 1 0 0 1 1.414 0l.647.646a.5.5 0 1 1-.708.708L6.5 1.707 2 6.207V12.5a.5.5 0 0 0 .5.5.5.5 0 0 1 0 1A1.5 1.5 0 0 1 1 12.5V7.207l-.146.147a.5.5 0 0 1-.708-.708zm3 1a1 1 0 0 1 1.414 0L12 3.793V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3.293l1.854 1.853a.5.5 0 0 1-.708.708L15 8.207V13.5a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 4 13.5V8.207l-.146.147a.5.5 0 1 1-.708-.708zm.707.707L5 7.207V13.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5V7.207z"/>
            </svg> 返回首页</a>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateInfoModal"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-person-vcard" viewBox="0 0 16 16">
              <path d="M5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4m4-2.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5M9 8a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4A.5.5 0 0 1 9 8m1 2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5"/>
              <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM1 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H8.96q.04-.245.04-.5C9 10.567 7.21 9 5 9c-2.086 0-3.8 1.398-3.984 3.181A1 1 0 0 1 1 12z"/>
            </svg> 修改信息</button>
            <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-key" viewBox="0 0 16 16">
              <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8m4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5"/>
              <path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
            </svg> 修改密码</button>
        </div>
    </div>
    <div class="text-center mt-4"><!-- 获取更新按钮 -->
    <button id="checkUpdateBtn" class="btn btn-primary">获取系统更新</button>
    
    <!-- 更新状态显示区域 -->
    <div id="updateStatus" class="mt-3"></div></div>    

    <!-- 修改信息模态框 -->
    <div class="modal fade" id="updateInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">修改信息</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($messages['info_success']): ?>
                        <div class="alert alert-success"><?php echo $messages['info_success']; ?></div>
                    <?php elseif ($messages['info_error']): ?>
                        <div class="alert alert-danger"><?php echo $messages['info_error']; ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">姓名</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <button type="submit" name="update_info" class="btn btn-primary w-100"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-floppy" viewBox="0 0 16 16">
                          <path d="M11 2H9v3h2z"/>
                          <path d="M1.5 0h11.586a1.5 1.5 0 0 1 1.06.44l1.415 1.414A1.5 1.5 0 0 1 16 2.914V14.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13A1.5 1.5 0 0 1 1.5 0M1 1.5v13a.5.5 0 0 0 .5.5H2v-4.5A1.5 1.5 0 0 1 3.5 9h9a1.5 1.5 0 0 1 1.5 1.5V15h.5a.5.5 0 0 0 .5-.5V2.914a.5.5 0 0 0-.146-.353l-1.415-1.415A.5.5 0 0 0 13.086 1H13v4.5A1.5 1.5 0 0 1 11.5 7h-7A1.5 1.5 0 0 1 3 5.5V1H1.5a.5.5 0 0 0-.5.5m3 4a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5V1H4zM3 15h10v-4.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5z"/>
                        </svg> 保存修改</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 修改密码模态框 -->
    <div class="modal fade<?php if ($messages['password_success'] || $messages['password_error']) { echo ' show'; } ?>" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true" style="<?php if ($messages['password_success'] || $messages['password_error']) { echo 'display: block;'; } ?>">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">修改密码</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                </div>
                <div class="modal-body">
                    <?php if ($messages['password_success']): ?>
                        <div class="alert alert-success"><?php echo $messages['password_success']; ?></div>
                    <?php elseif ($messages['password_error']): ?>
                        <div class="alert alert-danger"><?php echo $messages['password_error']; ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">当前密码</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">新密码</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认密码</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary w-100"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-floppy" viewBox="0 0 16 16">
                          <path d="M11 2H9v3h2z"/>
                          <path d="M1.5 0h11.586a1.5 1.5 0 0 1 1.06.44l1.415 1.414A1.5 1.5 0 0 1 16 2.914V14.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13A1.5 1.5 0 0 1 1.5 0M1 1.5v13a.5.5 0 0 0 .5.5H2v-4.5A1.5 1.5 0 0 1 3.5 9h9a1.5 1.5 0 0 1 1.5 1.5V15h.5a.5.5 0 0 0 .5-.5V2.914a.5.5 0 0 0-.146-.353l-1.415-1.415A.5.5 0 0 0 13.086 1H13v4.5A1.5 1.5 0 0 1 11.5 7h-7A1.5 1.5 0 0 1 3 5.5V1H1.5a.5.5 0 0 0-.5.5m3 4a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5V1H4zM3 15h10v-4.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5z"/>
                        </svg> 保存修改</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js</script>
    <script>https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js</script>
    <!-- JavaScript 重写 URL 参数 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 获取用户名和角色
        const userName = "<?php echo htmlspecialchars($_SESSION['name']); ?>";
        const userRole = "<?php echo htmlspecialchars($_SESSION['role']); ?>";
        
        // 根据角色值显示为普通用户或管理员
        const displayRole = userRole === 'admin' ? '管理员' : '普通用户';
        
        // 添加自定义参数，例如 username=用户名&role=角色
        const url = new URL(window.location);
        if (!url.searchParams.has(userName)) {
            url.searchParams.append(userName, displayRole);
            window.history.replaceState({}, document.title, url);
        }
    
        // 示例：在点击“退出”按钮时重写 URL
        const logoutButton = document.querySelector('a.btn-outline-light');
        if (logoutButton) {
            logoutButton.addEventListener('click', function(e) {
                e.preventDefault(); // 阻止默认跳转
                // 发送退出请求（假设通过 GET 参数处理）
                fetch(window.location.pathname + '?logout=true', {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        // 重写 URL
                        const newUrl = new URL(window.location);
                        newUrl.searchParams.delete('logout');
                        window.history.replaceState({}, document.title, newUrl);
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
    
        // 示例：在点击“登录”按钮后重写 URL
        const loginButton = document.querySelector('button[data-bs-target="#loginModal"]');
        if (loginButton) {
            loginButton.addEventListener('click', function() {
                // 重写 URL，添加 login 参数
                const url = new URL(window.location);
                url.searchParams.set('login', 'true');
                window.history.replaceState({}, document.title, url);
            });
        }
    });
    </script>
    <script>
        document.getElementById('checkUpdateBtn').addEventListener('click', function() {
            fetch('check_update.php')
                .then(response => response.json())
                .then(data => {
                    const updateStatus = document.getElementById('updateStatus');
                    updateStatus.innerHTML = '';
                    
                    if (data.update_available) {
                        // 显示最新版本号
                        const versionInfo = document.createElement('p');
                        versionInfo.textContent = `最新版本：${data.latest_version}`;
                        updateStatus.appendChild(versionInfo);
                        
                        // 显示“更新系统”按钮
                        const updateBtn = document.createElement('button');
                        updateBtn.className = 'btn btn-success mt-2';
                        updateBtn.textContent = '更新系统';
                        updateBtn.addEventListener('click', function() {
                            if(confirm(`检测到版本 ${data.latest_version} 可用，是否立即更新？`)){
                                window.location.href = 'update_system.php';
                            }
                        });
                        updateStatus.appendChild(updateBtn);
                    } else {
                        // 显示“暂无更新”消息
                        updateStatus.textContent = '暂无更新';
                    }
                })
                .catch(error => {
                    console.error('错误:', error);
                    document.getElementById('updateStatus').textContent = '更新检查失败';
                });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>