<?php
include 'header.php';
require_once 'includes/db.php'; // 引入封装的数据库操作函数

// 引入数据库配置
include 'config.php';

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);

session_start();

// 确保用户已登录且为管理员
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo "请先登录并确保您有管理员权限！";
    exit;
}

// 初始化消息数组
$messages = [
    'add_success' => '',
    'add_error' => '',
    'edit_success' => '',
    'edit_error' => '',
    'delete_success' => '',
    'delete_error' => ''
];

// 处理添加用户表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $new_username = $_POST['username'];
    $new_password = $_POST['password'];
    $new_name = $_POST['name'];
    $new_role = $_POST['role'];

    // 验证必填项
    if (empty($new_username) || empty($new_password) || empty($new_name) || empty($new_role)) {
        $messages['add_error'] = "所有字段都是必填项。";
    } else {
        // 检查用户名或姓名是否已存在
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR name = ?");
        $stmt->bind_param("ss", $new_username, $new_name);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $messages['add_error'] = "用户名或姓名已存在，无法创建用户。";
        } else {
            // 添加用户到数据库
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $new_username, $hashed_password, $new_name, $new_role);
            if ($stmt->execute()) {
                $messages['add_success'] = "用户添加成功！";
            } else {
                $messages['add_error'] = "用户添加失败，请重试。";
            }
            $stmt->close();
        }
    }
}

// 处理编辑用户表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $edit_user_id = $_POST['user_id'];
    $edit_username = $_POST['username'];
    $edit_name = $_POST['name'];
    $edit_role = $_POST['role'];
    $edit_password = $_POST['password'];

    // 验证必填项
    if (empty($edit_username) || empty($edit_name) || empty($edit_role)) {
        $messages['edit_error'] = "所有字段都是必填项。";
    } else {
        // 更新用户信息
        if (!empty($edit_password)) {
            $hashed_password = password_hash($edit_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $edit_username, $edit_name, $edit_role, $hashed_password, $edit_user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $edit_username, $edit_name, $edit_role, $edit_user_id);
        }
        if ($stmt->execute()) {
            $messages['edit_success'] = "用户信息更新成功！";
        } else {
            $messages['edit_error'] = "用户信息更新失败，请重试。";
        }
        $stmt->close();
    }
}

// 处理删除用户请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $delete_user_id = $_POST['user_id'];

    // 删除用户
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_user_id);
    if ($stmt->execute()) {
        $messages['delete_success'] = "用户删除成功！";
    } else {
        $messages['delete_error'] = "用户删除失败，请重试。";
    }
    $stmt->close();
}

// 获取所有用户信息，排除用户名为 'admin' 的用户
$user_query = "SELECT id, username, name, role FROM users WHERE username != 'admin'";
$user_result = $conn->query($user_query);
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>用户管理</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .user-management-body {
            padding-top: 50px;
            background-color: #f8f9fa;
        }
        .user-management-container {
            margin-top: 50px;
            max-width: 800px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-left: auto;
            margin-right: auto;
            font-family: 'Douyu', sans-serif;
        }
    </style>
    <script>
        function confirmDelete() {
            return confirm('确定要删除这个用户吗？');
        }

        function editUser(id, username, name, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_role').value = role;
        }

        function confirmEdit() {
            return confirm('确定要保存修改吗？');
        }
    </script>
</head>
<body class="user-management-body">
    <div class="user-management-container">
        <h3 class="custom-font">CINDY</h3>
        <?php if ($messages['add_success']): ?>
            <div class="alert alert-success"><?php echo $messages['add_success']; ?></div>
        <?php elseif ($messages['add_error']): ?>
            <div class="alert alert-danger"><?php echo $messages['add_error']; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">用户名</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">密码</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">姓名</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">角色</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="user">普通用户</option>
                    <option value="admin">管理员</option>
                </select>
            </div>
            <button type="submit" name="add_user" class="btn btn-primary w-100">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-person-add" viewBox="0 0 16 16">
                  <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0m-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                  <path d="M8.256 14a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10q.39 0 .74.025c.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z"/>
                </svg> 添加用户</button>
        </form>

        <h3 class="custom-font mt-5">USER</h3>
        <?php if ($messages['edit_success']): ?>
            <div class="alert alert-success"><?php echo $messages['edit_success']; ?></div>
        <?php elseif ($messages['edit_error']): ?>
            <div class="alert alert-danger"><?php echo $messages['edit_error']; ?></div>
        <?php endif; ?>
        <?php if ($messages['delete_success']): ?>
            <div class="alert alert-success"><?php echo $messages['delete_success']; ?></div>
        <?php elseif ($messages['delete_error']): ?>
            <div class="alert alert-danger"><?php echo $messages['delete_error']; ?></div>
        <?php endif; ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>用户名</th>
                    <th>姓名</th>
                    <th>角色</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $user_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirmDelete();">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm">删除</button>
                            </form>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal" onclick="editUser('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo htmlspecialchars($user['role']); ?>')">编辑</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- 编辑用户模态框 -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑用户</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post" onsubmit="return confirmEdit();">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">用户名</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">姓名</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">角色</label>
                            <select class="form-control" id="edit_role" name="role" required>
                                <option value="user">普通用户</option>
                                <option value="admin">管理员</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">密码（留空则不修改）</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <button type="submit" name="edit_user" class="btn btn-primary w-100">保存修改</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript 重写 URL 参数 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 获取用户名和角色
        const userName = "<?php echo htmlspecialchars($_SESSION['name']); ?>";
        const userRole = "<?php echo htmlspecialchars($_SESSION['role']); ?>";
    
        // 添加自定义参数，例如 username=用户名&role=角色
        const url = new URL(window.location);
        if (!url.searchParams.has(userName)) {
            url.searchParams.append(userName, userRole);
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
</body>
</html>

<?php
$conn->close();
?>