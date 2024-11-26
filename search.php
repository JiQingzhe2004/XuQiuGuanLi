<?php
ob_start(); // 开启输出缓冲
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

// 从会话中读取用户ID和角色
$id = $_SESSION['id'];
$username = $_SESSION['username'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];

// 检查是否成功读取到会话变量
if ($id === null || $username === null || $name === null || $role === null) {
    // 记录错误日志
    error_log("会话变量读取失败: " . print_r($_SESSION, true));
    exit;
}

// 初始化消息数组
$messages = [
    'delete_success' => '',
    'delete_error' => '',
    'process_success' => '',
    'process_error' => ''
];

// 处理删除请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_request'])) {
    $delete_request_id = $_POST['request_id'];
    $type = $_POST['type'];

    // 软删除
    if ($type == 'request') {
        $stmt_delete = $conn->prepare("UPDATE requests SET deleted = 1 WHERE id = ?");
    } else {
        $stmt_delete = $conn->prepare("UPDATE bugs SET deleted = 1 WHERE id = ?");
    }
    $stmt_delete->bind_param("i", $delete_request_id);
    if ($stmt_delete->execute()) {
        $messages['delete_success'] = "删除成功。";
    } else {
        $messages['delete_error'] = "删除时出错。";
    }
    $stmt_delete->close();
}

// 处理标记为已处理或未处理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_request'])) {
    $process_request_id = $_POST['request_id'];
    $processed = $_POST['processed'];
    $type = $_POST['type'];

    if ($type == 'request') {
        $stmt_process = $conn->prepare("UPDATE requests SET processed = ? WHERE id = ?");
    } else {
        $stmt_process = $conn->prepare("UPDATE bugs SET processed = ? WHERE id = ?");
    }
    $stmt_process->bind_param("ii", $processed, $process_request_id);
    if ($stmt_process->execute()) {
        $messages['process_success'] = "状态已更新。";
    } else {
        $messages['process_error'] = "更新状态时出错。";
    }
    $stmt_process->close();
}

// 获取搜索类型和查询
$type = isset($_GET['type']) ? $_GET['type'] : 'request';
$query = isset($_GET['query']) ? $_GET['query'] : '';

// 根据用户角色调整查询
if ($role === 'admin') {
    if ($type == 'request') {
        $stmt = $conn->prepare("SELECT * FROM requests WHERE (title LIKE ? OR description LIKE ?) AND deleted = 0 ORDER BY processed ASC, created_at DESC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM bugs WHERE (title LIKE ? OR description LIKE ?) AND deleted = 0 ORDER BY processed ASC, created_at DESC");
    }
    $stmt->bind_param("ss", $search_query, $search_query);
} else {
    if ($type == 'request') {
        $stmt = $conn->prepare("SELECT * FROM requests WHERE (title LIKE ? OR description LIKE ?) AND deleted = 0 AND user_id = ? ORDER BY processed ASC, created_at DESC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM bugs WHERE (title LIKE ? OR description LIKE ?) AND deleted = 0 AND user_id = ? ORDER BY processed ASC, created_at DESC");
    }
    $stmt->bind_param("ssi", $search_query, $search_query, $id);
}

$search_query = '%' . $query . '%';
$stmt->execute();
$result = $stmt->get_result();

// 获取信息列表
$request_list = [];
while ($request = $result->fetch_assoc()) {
    $request_list[] = $request;
}

$stmt->close();
$conn->close();
?>

<style>
    /* 已有的样式 */
    .processed-row {
        background-color: #d4edda; /* 已处理 - 浅绿色 */
    }
    .unprocessed-row {
        background-color: #f8d7da; /* 未处理 - 浅红色 */
    }

    /* 新增的样式 */
    body {
        background-color: #f8f9fa; /* 修改背景颜色为浅灰色 */
        color: #343a40; /* 修改字体颜色为深灰色 */
    }

    .container {
        font-family: 'Douyu', sans-serif;
    }

    h3 {
        text-align: center;
        color: #000;
    }

    table {
        background-color: #ffffff; /* 表格背景颜色为白色 */
        border-radius: 10px; /* 添加圆角 */
        overflow: hidden; /* 确保圆角生效 */
    }

    table th, table td {
        vertical-align: middle;
        border: 1px solid #dee2e6; /* 修改边框颜色 */
    }

    .table-hover tbody tr:hover {
        background-color: #f1f1f1; /* 修改悬停时的背景颜色 */
    }

    .btn-sm {
        margin-bottom: 5px;
    }

    .alert {
        margin-top: 20px;
    }
    .request-list-container {
        margin-top: 10px;
        width: 80%;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-left: auto;
        margin-right: auto;
        font-family: 'Douyu', sans-serif;
    }
    .sltext {
        color: #131124; /* 黑色字体 */
    }
</style>
<div class="request-list-container">
<div class="container mt-2">
    <div class="d-flex align-items-center justify-content-between">
        <h3 class="custom-font mb-0">CINDY</h3>
            <div class="d-flex justify-content-center mb-3">
                <div class="p-2 border bg-light rounded sltext">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cone-striped" viewBox="0 0 16 16" style="color: #a61b29;">
                        <path d="m9.97 4.88.953 3.811C10.159 8.878 9.14 9 8 9s-2.158-.122-2.923-.309L6.03 4.88C6.635 4.957 7.3 5 8 5s1.365-.043 1.97-.12m-.245-.978L8.97.88C8.718-.13 7.282-.13 7.03.88L6.275 3.9C6.8 3.965 7.382 4 8 4s1.2-.036 1.725-.098m4.396 8.613a.5.5 0 0 1 .037.96l-6 2a.5.5 0 0 1-.316 0l-6-2a.5.5 0 0 1 .037-.96l2.391-.598.565-2.257c.862.212 1.964.339 3.165.339s2.303-.127 3.165-.339l.565 2.257z"/>
                    </svg> ： 
                    可延期 ⚪ 一般 🔵 紧急 🟡 非常紧急 🔴
                </div>
            </div>
        <h3 class="mb-0">搜索结果 - <?php echo $type == 'request' ? '需求' : 'BUG'; ?></h3>
    </div>
    <?php
    // 显示消息
    foreach ($messages as $message) {
        if (!empty($message)) {
            echo '<div class="alert alert-info">' . $message . '</div>';
        }
    }
    ?>

    <?php if (count($request_list) > 0): ?>
        <table class="table table-bordered table-hover table-striped mt-2">
            <thead>
                <tr>
                    <?php if ($type == 'request'): ?>
                        <th>紧急程度</th>
                        <th>标题</th>
                        <th>提交人</th>
                        <th>配置情况</th>
                        <th>状态</th>
                        <th>操作</th>
                    <?php else: ?>
                        <th>标题</th>
                        <th>提交人</th>
                        <th>提交时间</th>
                        <th>状态</th>
                        <th>操作</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($request_list as $request): ?>
                    <?php
                    $row_class = $request['processed'] ? 'processed-row' : 'unprocessed-row';
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <?php if ($type == 'request'): ?>
                            <td>
                                <?php
                                switch ($request['urgent']) {
                                    case '可延期':
                                        echo '⚪';
                                        break;
                                    case '一般':
                                        echo '🔵';
                                        break;
                                    case '紧急':
                                        echo '🟡';
                                        break;
                                    case '非常紧急':
                                        echo '🔴';
                                        break;
                                    default:
                                        echo htmlspecialchars($request['urgent']);
                                        break;
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($request['title']); ?></td>
                            <td><?php echo htmlspecialchars($request['name']); ?></td>
                            <td><?php echo htmlspecialchars($request['is_configured']); ?></td>
                            <td>
                                <?php echo $request['processed'] ? '已处理' : '未处理'; ?>
                            <td>
                                <!-- 操作按钮 -->
                                <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-info btn-sm me-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                </svg> 查看</a>
                                <?php if (!$request['processed']): ?>
                                    <a href="edit_request.php?id=<?php echo $request['id']; ?>" class="btn btn-warning btn-sm me-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-feather" viewBox="0 0 16 16">
                                      <path d="M15.807.531c-.174-.177-.41-.289-.64-.363a3.8 3.8 0 0 0-.833-.15c-.62-.049-1.394 0-2.252.175C10.365.545 8.264 1.415 6.315 3.1S3.147 6.824 2.557 8.523c-.294.847-.44 1.634-.429 2.268.005.316.05.62.154.88q.025.061.056.122A68 68 0 0 0 .08 15.198a.53.53 0 0 0 .157.72.504.504 0 0 0 .705-.16 68 68 0 0 1 2.158-3.26c.285.141.616.195.958.182.513-.02 1.098-.188 1.723-.49 1.25-.605 2.744-1.787 4.303-3.642l1.518-1.55a.53.53 0 0 0 0-.739l-.729-.744 1.311.209a.5.5 0 0 0 .443-.15l.663-.684c.663-.68 1.292-1.325 1.763-1.892.314-.378.585-.752.754-1.107.163-.345.278-.773.112-1.188a.5.5 0 0 0-.112-.172M3.733 11.62C5.385 9.374 7.24 7.215 9.309 5.394l1.21 1.234-1.171 1.196-.027.03c-1.5 1.789-2.891 2.867-3.977 3.393-.544.263-.99.378-1.324.39a1.3 1.3 0 0 1-.287-.018Zm6.769-7.22c1.31-1.028 2.7-1.914 4.172-2.6a7 7 0 0 1-.4.523c-.442.533-1.028 1.134-1.681 1.804l-.51.524zm3.346-3.357C9.594 3.147 6.045 6.8 3.149 10.678c.007-.464.121-1.086.37-1.806.533-1.535 1.65-3.415 3.455-4.976 1.807-1.561 3.746-2.36 5.31-2.68a8 8 0 0 1 1.564-.173"/>
                                    </svg> 编辑</a>
                                <?php endif; ?>
                                <?php if ($role === 'admin'): ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('确定要更改处理状态吗？');">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="processed" value="<?php echo $request['processed'] ? 0 : 1; ?>">
                                        <input type="hidden" name="type" value="request">
                                        <button type="submit" name="process_request" class="btn btn-secondary btn-sm me-2">
                                            <?php if ($request['processed']): ?>
                                                <!-- 已处理状态的按钮 -->
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                                </svg> 标记为未处理
                                            <?php else: ?>
                                                <!-- 未处理状态的按钮 -->
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-circle" viewBox="0 0 16 16">
                                                    <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0"/>
                                                    <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z"/>
                                                </svg> 标记为已处理
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('确定要删除此需求吗？');">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="type" value="request">
                                        <button type="submit" name="delete_request" class="btn btn-danger btn-sm"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                      <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                    </svg> 删除</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php else: ?> <!-- BUG 类型 -->
                            <td><?php echo htmlspecialchars($request['title']); ?></td>
                            <td><?php echo htmlspecialchars($request['name']); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($request['created_at']))); ?></td>
                            <td><?php echo $request['processed'] ? '已处理' : '未处理'; ?></td>
                            <td>
                                <!-- 操作按钮 -->
                                <a href="view_bug.php?id=<?php echo $request['id']; ?>" class="btn btn-info btn-sm me-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                </svg> 查看</a>
                                <?php if (!$request['processed']): ?>
                                    <a href="edit_bug.php?id=<?php echo $request['id']; ?>" class="btn btn-warning btn-sm me-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-feather" viewBox="0 0 16 16">
                                      <path d="M15.807.531c-.174-.177-.41-.289-.64-.363a3.8 3.8 0 0 0-.833-.15c-.62-.049-1.394 0-2.252.175C10.365.545 8.264 1.415 6.315 3.1S3.147 6.824 2.557 8.523c-.294.847-.44 1.634-.429 2.268.005.316.05.62.154.88q.025.061.056.122A68 68 0 0 0 .08 15.198a.53.53 0 0 0 .157.72.504.504 0 0 0 .705-.16 68 68 0 0 1 2.158-3.26c.285.141.616.195.958.182.513-.02 1.098-.188 1.723-.49 1.25-.605 2.744-1.787 4.303-3.642l1.518-1.55a.53.53 0 0 0 0-.739l-.729-.744 1.311.209a.5.5 0 0 0 .443-.15l.663-.684c.663-.68 1.292-1.325 1.763-1.892.314-.378.585-.752.754-1.107.163-.345.278-.773.112-1.188a.5.5 0 0 0-.112-.172M3.733 11.62C5.385 9.374 7.24 7.215 9.309 5.394l1.21 1.234-1.171 1.196-.027.03c-1.5 1.789-2.891 2.867-3.977 3.393-.544.263-.99.378-1.324.39a1.3 1.3 0 0 1-.287-.018Zm6.769-7.22c1.31-1.028 2.7-1.914 4.172-2.6a7 7 0 0 1-.4.523c-.442.533-1.028 1.134-1.681 1.804l-.51.524zm3.346-3.357C9.594 3.147 6.045 6.8 3.149 10.678c.007-.464.121-1.086.37-1.806.533-1.535 1.65-3.415 3.455-4.976 1.807-1.561 3.746-2.36 5.31-2.68a8 8 0 0 1 1.564-.173"/>
                                    </svg> 编辑</a>
                                <?php endif; ?>
                                <?php if ($role === 'admin'): ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('确定要更改处理状态吗？');">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="processed" value="<?php echo $request['processed'] ? 0 : 1; ?>">
                                        <input type="hidden" name="type" value="bug">
                                        <button type="submit" name="process_request" class="btn btn-secondary btn-sm me-2">
                                            <?php if ($request['processed']): ?>
                                                <!-- 已处理状态的按钮 -->
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                                </svg> 标记为未处理
                                            <?php else: ?>
                                                <!-- 未处理状态的按钮 -->
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-circle" viewBox="0 0 16 16">
                                                    <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0"/>
                                                    <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z"/>
                                                </svg> 标记为已处理
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('确定要删除此BUG吗？');">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="type" value="bug">
                                        <button type="submit" name="delete_request" class="btn btn-danger btn-sm"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                      <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                    </svg> 删除</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <h4 style="color: #000; text-align: center; margin-top: 10px;">未搜索到该关键词 '<span style="color: red;"><?php echo htmlspecialchars($query); ?></span>'，换个试试？</h4>
    <?php endif; ?>
</div>
</div>
<script src="js/bootstrap.bundle.min.js"></script>