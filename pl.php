<?php
include 'header.php';
// 引入数据库配置
include 'config.php';

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接是否成功
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

session_start();

// 确保用户已登录
if (!isset($_SESSION['id'])) {
    echo "请先登录！";
    exit;
}

$name = $_SESSION['name'];
$role = $_SESSION['role'];
$user_id = $_SESSION['id'];

// 初始化消息变量
$success_message = '';
$error_message = '';

// 获取操作表
$allowed_tables = ['requests', 'bugs'];
$selected_table = 'requests'; // 默认表

// 是否执行查询
$execute_query = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['table_select'])) {
        $selected_table = $_POST['table_select'];
        if (!in_array($selected_table, $allowed_tables)) {
            $selected_table = 'requests';
        }
    }

    // 处理批量添加请求
    if (isset($_POST['batch_add'])) {
        $range_start = intval($_POST['range_start']);
        $range_end = intval($_POST['range_end']);
        $add_count = intval($_POST['add_count']);
        $timestamp = date("Y-m-d H:i:s");

        // 验证必填项
        if ($range_start > $range_end) {
            $error_message = "起始范围不能大于结束范围。";
        } else {
            // 动态选择表名，确保安全
            if (!in_array($selected_table, $allowed_tables)) {
                $error_message = "选择的表名不合法。";
            } else {
                // 根据表名准备不同的插入语句
                if ($selected_table === 'requests') {
                    $is_configured = isset($_POST['is_configured']) ? $_POST['is_configured'] : '否';
                    $urgent = $_POST['urgent'];

                    $stmt = $conn->prepare("INSERT INTO requests (user_id, name, role, title, description, is_configured, urgent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) {
                        die("准备 SQL 语句失败: " . $conn->error);
                    }

                    for ($i = 0; $i < $add_count; $i++) {
                        $title = "标题 " . ($range_start + $i);
                        $description = "详细内容 " . rand(1, 100); // 生成随机详细内容
                        $stmt->bind_param("isssssss", $user_id, $name, $role, $title, $description, $is_configured, $urgent, $timestamp);
                        if (!$stmt->execute()) {
                            $error_message = "批量添加失败，请重试。 错误信息: " . $stmt->error;
                            break;
                        }
                    }
                } elseif ($selected_table === 'bugs') {
                    // 如果bugs表没有is_configured和urgent字段，省略这些字段
                    $stmt = $conn->prepare("INSERT INTO bugs (user_id, name, role, title, description, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) {
                        die("准备 SQL 语句失败: " . $conn->error);
                    }

                    for ($i = 0; $i < $add_count; $i++) {
                        $title = "标题 " . ($range_start + $i);
                        $description = "详细内容 " . rand(1, 100); // 生成随机详细内容
                        $stmt->bind_param("isssss", $user_id, $name, $role, $title, $description, $timestamp);
                        if (!$stmt->execute()) {
                            $error_message = "批量添加失败，请重试。 错误信息: " . $stmt->error;
                            break;
                        }
                    }
                }

                if (empty($error_message)) {
                    $success_message = "批量添加成功！";
                }

                $stmt->close();
            }
        }
    }

    // 处理批量删除请求
    if (isset($_POST['batch_delete'])) {
        $ids = $_POST['ids'];

        if (empty($ids)) {
            $error_message = "请提供要删除的记录ID。";
        } else {
            // 确保所有ID都是整数，防止SQL注入
            $ids = array_map('intval', $ids);
            $ids_list = implode(',', $ids);

            if (!in_array($selected_table, $allowed_tables)) {
                $error_message = "选择的表名不合法。";
            } else {
                if ($role === 'admin') {
                    // 管理员可以删除任何请求
                    if ($selected_table === 'requests') {
                        $stmt = $conn->prepare("DELETE FROM requests WHERE id IN ($ids_list)");
                    } else { // bugs
                        $stmt = $conn->prepare("DELETE FROM bugs WHERE id IN ($ids_list)");
                    }
                } else {
                    // 普通用户只能删除自己的请求
                    if ($selected_table === 'requests') {
                        $stmt = $conn->prepare("DELETE FROM requests WHERE id IN ($ids_list) AND user_id = ?");
                    } else { // bugs
                        $stmt = $conn->prepare("DELETE FROM bugs WHERE id IN ($ids_list) AND user_id = ?");
                    }
                }

                if ($stmt === false) {
                    die("准备 SQL 语句失败: " . $conn->error);
                }

                if ($role !== 'admin') {
                    $stmt->bind_param("i", $user_id);
                }

                if ($stmt->execute()) {
                    $success_message = "批量删除成功！";
                } else {
                    $error_message = "批量删除失败，请重试。 错误信息: " . $stmt->error;
                }

                $stmt->close();
            }
        }
    }

    // 处理查询请求
    if (isset($_POST['query'])) {
        $execute_query = true;
    }
}

// 获取所有请求数据
if ($execute_query && isset($selected_table) && in_array($selected_table, $allowed_tables)) {
    if ($role === 'admin') {
        if ($selected_table === 'requests') {
            $result = $conn->query("SELECT id, title, description FROM requests");
        } else { // bugs
            $result = $conn->query("SELECT id, title, description FROM bugs");
        }
    } else {
        if ($selected_table === 'requests') {
            $stmt = $conn->prepare("SELECT id, title, description FROM requests WHERE user_id = ?");
        } else { // bugs
            $stmt = $conn->prepare("SELECT id, title, description FROM bugs WHERE user_id = ?");
        }
        if ($stmt === false) {
            die("准备 SQL 语句失败: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    }
} else {
    $result = null;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>批量操作</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .batch-body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
            margin: 0;
            width: 100%;
            color: black;
        }
        .batch-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            color: black;
        }
        .alert {
            margin-bottom: 20px;
        }
        .mb-3 label {
            color: black;
        }
        .form-check-label a {
            margin-left: 10px;
        }
    </style>
</head>
<body class="batch-body">
    <div class="batch-container request-list-container">
        <!-- 显示成功或错误消息 -->
        <?php if (!empty($success_message)): ?>
            <div id="successMessage" class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div id="errorMessage" class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- 选择操作表 -->
        <h3>选择操作表</h3>
        <form method="POST" id="tableSelectForm">
            <div class="mb-3">
                <?php
                    // 保持选择状态
                    $selected_requests = ($selected_table === 'requests') ? 'checked' : '';
                    $selected_bugs = ($selected_table === 'bugs') ? 'checked' : '';
                ?>
                <label class="form-check-label">
                    <input type="radio" name="table_select" value="requests" <?php echo $selected_requests; ?> onclick="toggleFields()"> 需求数据表
                </label>
                <label class="form-check-label ms-3">
                    <input type="radio" name="table_select" value="bugs" <?php echo $selected_bugs; ?> onclick="toggleFields()"> Bugs 数据表
                </label>
            </div>
            <button type="submit" name="query" class="btn btn-primary mb-4 ms-2">查询</button>
        </form>

        <!-- 批量添加区域 -->
        <h3>批量添加</h3>
        <form method="POST">
            <input type="hidden" name="table_select" value="<?php echo htmlspecialchars($selected_table); ?>">
            <div class="mb-3">
                <label for="range_start" class="form-label">起始范围</label>
                <input type="number" class="form-control" id="range_start" name="range_start" required>
            </div>
            <div class="mb-3">
                <label for="range_end" class="form-label">结束范围</label>
                <input type="number" class="form-control" id="range_end" name="range_end" required>
            </div>
            <div class="mb-3" id="isConfiguredField">
                <label for="is_configured" class="form-label">是否配置项</label><br>
                <label><input type="radio" name="is_configured" value="是" checked> 是</label>
                <label><input type="radio" name="is_configured" value="否"> 否</label>
            </div>
            <div class="mb-3" id="urgentField">
                <label for="urgent" class="form-label">紧急程度</label><br>
                <label><input type="radio" name="urgent" value="可延期" checked> 可延期</label>
                <label><input type="radio" name="urgent" value="一般"> 一般</label>
                <label><input type="radio" name="urgent" value="紧急"> 紧急</label>
                <label><input type="radio" name="urgent" value="非常紧急"> 非常紧急</label>
            </div>
            <div class="mb-3">
                <label for="add_count" class="form-label">添加条数</label>
                <input type="number" class="form-control" id="add_count" name="add_count" required>
            </div>
            <button type="submit" name="batch_add" class="btn btn-primary w-100">批量添加</button>
        </form>

        <!-- 显示所有请求或bugs数据 -->
        <?php if ($execute_query && $result): ?>
            <div class="d-flex justify-content-between align-items-center mt-5">
                <h3><?php echo ($selected_table === 'requests') ? '所有请求' : '所有 Bugs'; ?></h3>
                <button type="submit" form="deleteForm" name="batch_delete" class="btn btn-danger">批量删除</button>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="table_select" value="<?php echo htmlspecialchars($selected_table); ?>">
                <div class="mb-3">
                    <input type="checkbox" id="selectAll"> 全选
                </div>
                <div class="mb-3">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>">
                                <label class="form-check-label">
                                    <?php echo "ID: " . $row['id'] . " - 标题: " . htmlspecialchars($row['title']); ?>
                                    <?php if (mb_strlen($row['description'], 'UTF-8') > 20): ?>
                                        <span id="short-<?php echo $row['id']; ?>"><?php echo mb_substr($row['description'], 0, 20, 'UTF-8'); ?>...</span>
                                        <a href="#!" data-bs-toggle="collapse" data-bs-target="#full-<?php echo $row['id']; ?>" aria-expanded="false" aria-controls="full-<?php echo $row['id']; ?>" onclick="toggleDescription(<?php echo $row['id']; ?>)">显示更多</a>
                                        <div class="collapse" id="full-<?php echo $row['id']; ?>">
                                            <span><?php echo htmlspecialchars($row['description']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($row['description']); ?></span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>没有找到记录。</p>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
    // 初始字段显示状态
    document.addEventListener('DOMContentLoaded', function() {
        toggleFields();
    });

    // 切换表格时显示或隐藏特定字段
    function toggleFields() {
        var selectedTable = document.querySelector('input[name="table_select"]:checked').value;
        var isConfiguredField = document.getElementById('isConfiguredField');
        var urgentField = document.getElementById('urgentField');

        if (selectedTable === 'requests') {
            isConfiguredField.style.display = 'block';
            urgentField.style.display = 'block';
        } else if (selectedTable === 'bugs') {
            isConfiguredField.style.display = 'none';
            urgentField.style.display = 'none';
        }
    }

    document.getElementById('selectAll').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('input[name="ids[]"]');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });

    function toggleDescription(id) {
        var shortDescription = document.getElementById('short-' + id);
        var fullDescription = document.getElementById('full-' + id);
        var link = fullDescription.previousElementSibling;

        if (fullDescription.classList.contains('show')) {
            link.textContent = '显示更多';
            shortDescription.style.display = 'inline';
        } else {
            link.textContent = '收起';
            shortDescription.style.display = 'none';
        }
    }
</script>
</body>
</html>