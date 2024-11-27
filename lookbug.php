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
$role = $_SESSION['role'];

// 初始化消息数组
$messages = [
    'delete_success' => '',
    'delete_error' => '',
    'process_success' => '',
    'process_error' => ''
];

// 获取当前日期
$current_date = date('Y-m-d');

// 处理日期过滤
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : $current_date;

// 处理删除需求请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_request'])) {
    $delete_request_id = $_POST['request_id'];

    // 软删除需求
    $stmt = $conn->prepare("UPDATE bugs SET deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $delete_request_id);
    if ($stmt->execute()) {
        $messages['delete_success'] = "需求删除成功！";
    } else {
        $messages['delete_error'] = "需求删除失败，请重试。";
    }
    $stmt->close();

    // 重定向以避免表单重新提交
    header("Location: " . $_SERVER['PHP_SELF'] . "?filter_date=" . $filter_date);
    exit;
}

// 处理标记需求为已处理或未处理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_request'])) {
    $process_request_id = $_POST['request_id'];
    $processed = $_POST['processed'];

    // 更新需求处理状态
    $stmt = $conn->prepare("UPDATE bugs SET processed = ? WHERE id = ?");
    $stmt->bind_param("ii", $processed, $process_request_id);
    if ($stmt->execute()) {
        $messages['process_success'] = "需求处理状态更新成功！";
    } else {
        $messages['process_error'] = "需求处理状态更新失败，请重试。";
    }
    $stmt->close();

    // 重定向以避免表单重新提交
    header("Location: " . $_SERVER['PHP_SELF'] . "?filter_date=" . $filter_date);
    exit;
}

// 获取需求信息
if ($role === 'admin') {
    // 管理员查看所有未删除的需求
    $request_query = "SELECT id, title, name, created_at, processed FROM bugs WHERE deleted = 0 AND DATE(created_at) = ? ORDER BY processed ASC, created_at DESC";
    $stmt = $conn->prepare($request_query);
    if (!$stmt) {
        echo "查询准备失败: " . $conn->error;
        exit;
    }
    $stmt->bind_param("s", $filter_date);
} else {
    // 普通用户查看自己的未删除的需求
    $request_query = "SELECT id, title, name, created_at, processed FROM bugs WHERE user_id = ? AND deleted = 0 AND DATE(created_at) = ? ORDER BY processed ASC, created_at DESC";
    $stmt = $conn->prepare($request_query);
    if (!$stmt) {
        echo "查询准备失败: " . $conn->error;
        exit;
    }
    $stmt->bind_param("is", $id, $filter_date);
}
// 在PHP部分处理批量删除和批量标记
// 处理批量删除请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['batch_delete'])) {
    $ids = $_POST['ids'];

    if (empty($ids)) {
        $messages['delete_error'] = "请至少选择一条记录进行删除。";
    } else {
        // 确保所有ID都是整数，防止SQL注入
        $ids = array_map('intval', $ids);
        $ids_list = implode(',', $ids);

        $stmt = $conn->prepare("UPDATE bugs SET deleted = 1 WHERE id IN ($ids_list)");
        if ($stmt->execute()) {
            $messages['delete_success'] = "批量删除成功！";
        } else {
            $messages['delete_error'] = "批量删除失败，请重试。";
        }
        $stmt->close();
    }

    // 重定向以避免表单重新提交
    header("Location: " . $_SERVER['PHP_SELF'] . "?filter_date=" . $filter_date);
    exit;
}

// 修改后的批量标记处理功能（toggle 状态）
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['batch_process'])) {
    $ids = $_POST['ids'];

    if (empty($ids)) {
        $messages['process_error'] = "请至少选择一条记录进行标记。";
    } else {
        // 确保所有ID都是整数，防止SQL注入
        $ids = array_map('intval', $ids);
        $ids_list = implode(',', $ids);

        // 切换 processed 状态
        $stmt = $conn->prepare("UPDATE bugs SET processed = 1 - processed WHERE id IN ($ids_list)");
        if ($stmt->execute()) {
            $messages['process_success'] = "批量标记成功！";
        } else {
            $messages['process_error'] = "批量标记失败，请重试。";
        }
        $stmt->close();
    }

    // 重定向以避免表单重新提交
    header("Location: " . $_SERVER['PHP_SELF'] . "?filter_date=" . $filter_date);
    exit;
}

$stmt->execute();
$request_result = $stmt->get_result();
$stmt->close();

// 检查查询是否成功
if ($request_result === false) {
    echo "查询失败: " . $conn->error;
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>需求列表</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <style>
        .request-list-body {
            padding-top: 50px;
            background-color: #f8f9fa;
        }
        .request-list-container {
            margin-top: 10px;
            width: 100%;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-left: auto;
            margin-right: auto;
            font-family: 'Douyu', sans-serif;
        }
        .processed-row {
            background-color: #d3d3d3; /* 灰色背景 */
            color: #131124; /* 黑色字体 */
        }
        .input-group {
            width: 200px;
            float: right;
            margin-right: 20px;
        }
        /* 添加旋转动画 */
        .rotating {
            animation: rotation 1s linear infinite;
        }
        
        @keyframes rotation {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
</head>
<body class="request-list-body">
    <div class="request-list-container">
            <form method="POST" id="batchForm">
            <input type="hidden" name="table_select" value="<?php echo htmlspecialchars($selected_table); ?>">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="custom-font">CINDY</h3>
            <div class="d-flex mb-3">
                <button type="submit" name="batch_delete" class="btn btn-danger me-2" onclick="return confirm('确定要批量删除选中的需求吗？');">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                      <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                    </svg> 批量删除
                </button>
                <?php if ($role === 'admin'): ?>
                    <button type="submit" name="batch_process" class="btn btn-secondary" onclick="return confirm('确定要批量更新选中的需求状态吗？');">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-repeat" viewBox="0 0 16 16">
                    <path d="M11 5.466V4H5a4 4 0 0 0-3.584 5.777.5.5 0 1 1-.896.446A5 5 0 0 1 5 3h6V1.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384l-2.36 1.966a.25.25 0 0 1-.41-.192m3.81.086a.5.5 0 0 1 .67.225A5 5 0 0 1 11 13H5v1.466a.25.25 0 0 1-.41.192l-2.36-1.966a.25.25 0 0 1 0-.384l2.36-1.966a.25.25 0 0 1 .41.192V12h6a4 4 0 0 0 3.585-5.777.5.5 0 0 1 .225-.67Z"/>
                    </svg> 切换状态
                    </button>
                <?php endif; ?>
            </div>
            <div class="input-group" style="width: 250px;">
            <span class="input-group-text">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-date" viewBox="0 0 16 16">
                <path d="M6.445 11.688V6.354h-.633A13 13 0 0 0 4.5 7.16v.695c.375-.257.969-.62 1.258-.777h.012v4.61zm1.188-1.305c.047.64.594 1.406 1.703 1.406 1.258 0 2-1.066 2-2.871 0-1.934-.781-2.668-1.953-2.668-.926 0-1.797.672-1.797 1.809 0 1.16.824 1.77 1.676 1.77.746 0 1.23-.376 1.383-.79h.027c-.004 1.316-.461 2.164-1.305 2.164-.664 0-1.008-.45-1.05-.82zm2.953-2.317c0 .696-.559 1.18-1.184 1.18-.601 0-1.144-.383-1.144-1.2 0-.823.582-1.21 1.168-1.21.633 0 1.16.398 1.16 1.23"/>
                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                </svg>
            </span>
            <input type="text" class="form-control" id="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <a href="bug.php" class="btn btn-primary">提交BUG</a>
            <!-- 仅包含刷新的SVG图标，不带背景色 -->
            <button id="refreshButton" class="btn p-0 border-0" type="button" aria-label="刷新">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16" style="color: #a61b29;">
            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
            <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
            </svg>
            </button>
        </div>
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

        <?php if ($messages['delete_success']): ?>
            <div class="alert alert-success"><?php echo $messages['delete_success']; ?></div>
        <?php elseif ($messages['delete_error']): ?>
            <div class="alert alert-danger"><?php echo $messages['delete_error']; ?></div>
        <?php endif; ?>
        <?php if ($messages['process_success']): ?>
            <div class="alert alert-success"><?php echo $messages['process_success']; ?></div>
        <?php elseif ($messages['process_error']): ?>
            <div class="alert alert-danger"><?php echo $messages['process_error']; ?></div>
        <?php endif; ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>BUG模块</th>
                    <th>提交人</th>
                    <th>提交时间</th>
                    <th>处理状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="request-list">
                <?php while ($request = $request_result->fetch_assoc()): ?>
                    <tr class="<?php echo $request['processed'] ? 'processed-row' : ''; ?>">
                        <td><input type="checkbox" name="ids[]" value="<?php echo $request['id']; ?>"></td>
                        <td><?php echo htmlspecialchars($request['title']); ?></td>
                        <td><?php echo htmlspecialchars($request['name']); ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($request['created_at']))); ?></td>
                        <td class="<?php echo $request['processed'] ? 'text-danger' : ''; ?>"><?php echo $request['processed'] ? '已处理' : '未处理'; ?></td>
                        <td>
                            <a href="view_bug.php?id=<?php echo $request['id']; ?>" class="btn btn-info btn-sm me-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                </svg> 查看</a>
                            <?php if (!$request['processed']): ?>
                                <a href="edit_bug.php?id=<?php echo $request['id']; ?>" class="btn btn-warning btn-sm me-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-feather" viewBox="0 0 16 16">
                                      <path d="M15.807.531c-.174-.177-.41-.289-.64-.363a3.8 3.8 0 0 0-.833-.15c-.62-.049-1.394 0-2.252.175C10.365.545 8.264 1.415 6.315 3.1S3.147 6.824 2.557 8.523c-.294.847-.44 1.634-.429 2.268.005.316.05.62.154.88q.025.061.056.122A68 68 0 0 0 .08 15.198a.53.53 0 0 0 .157.72.504.504 0 0 0 .705-.16 68 68 0 0 1 2.158-3.26c.285.141.616.195.958.182.513-.02 1.098-.188 1.723-.49 1.25-.605 2.744-1.787 4.303-3.642l1.518-1.55a.53.53 0 0 0 0-.739l-.729-.744 1.311.209a.5.5 0 0 0 .443-.15l.663-.684c.663-.68 1.292-1.325 1.763-1.892.314-.378.585-.752.754-1.107.163-.345.278-.773.112-1.188a.5.5 0 0 0-.112-.172M3.733 11.62C5.385 9.374 7.24 7.215 9.309 5.394l1.21 1.234-1.171 1.196-.027.03c-1.5 1.789-2.891 2.867-3.977 3.393-.544.263-.99.378-1.324.39a1.3 1.3 0 0 1-.287-.018Zm6.769-7.22c1.31-1.028 2.7-1.914 4.172-2.6a7 7 0 0 1-.4.523c-.442.533-1.028 1.134-1.681 1.804l-.51.524zm3.346-3.357C9.594 3.147 6.045 6.8 3.149 10.678c.007-.464.121-1.086.37-1.806.533-1.535 1.65-3.415 3.455-4.976 1.807-1.561 3.746-2.36 5.31-2.68a8 8 0 0 1 1.564-.173"/>
                                    </svg> 编辑</a>
                            <?php endif; ?>
                            <form method="post" style="display:inline;" onsubmit="return confirmDelete();" class="me-2">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" name="delete_request" class="btn btn-danger btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                      <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                    </svg> 删除</button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirmProcess();" class="me-2">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <input type="hidden" name="processed" value="<?php echo $request['processed'] ? 0 : 1; ?>">
                                <?php if ($role === 'admin'): ?>
                                    <button type="submit" name="process_request" class="btn btn-secondary btn-sm">
                                        <?php if ($request['processed']): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                            </svg> 标记为未处理
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-circle" viewBox="0 0 16 16">
                                                <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0"/>
                                                <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z"/>
                                            </svg> 标记为已处理
                                        <?php endif; ?>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <!-- 引入 Bootstrap Datepicker 的中文语言包 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#filter_date').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                language: 'zh-CN' // 设置语言为中文
            }).on('changeDate', function(e) {
                window.location.href = '?filter_date=' + e.format('yyyy-mm-dd');
            });

            // 定期获取最新的需求数据并更新页面内容
            function fetchRequests() {
                $.ajax({
                    url: 'fetch_bugs.php',
                    type: 'GET',
                    data: { filter_date: '<?php echo $filter_date; ?>' },
                    dataType: 'json',
                    beforeSend: function() {
                        // 开始刷新时，添加旋转动画到SVG
                        $('#refreshButton svg').addClass('rotating');
                    },
                    success: function(data) {
                        var requestList = $('#request-list');
                        requestList.empty();
                        data.forEach(function(request) {
                            var rowClass = request.processed ? 'processed-row' : '';
                            var processedText = request.processed ? '已处理' : '未处理';
                            var processedClass = request.processed ? 'text-danger' : '';
                            var viewButton = '<a href="view_bug.php?id=' + request.id + '" class="btn btn-info btn-sm me-2">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">' +
                                '<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>' +
                                '<path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>' +
                                '</svg> 查看</a>';
                            var editButton = request.processed ? '' : '<a href="edit_bug.php?id=' + request.id + '" class="btn btn-warning btn-sm me-2">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-feather" viewBox="0 0 16 16">' +
                                '<path d="M15.807.531c-.174-.177-.41-.289-.64-.363a3.8 3.8 0 0 0-.833-.15c-.62-.049-1.394 0-2.252.175C10.365.545 8.264 1.415 6.315 3.1S3.147 6.824 2.557 8.523c-.294.847-.44 1.634-.429 2.268.005.316.05.62.154.88q.025.061.056.122A68 68 0 0 0 .08 15.198a.53.53 0 0 0 .157.72.504.504 0 0 0 .705-.16 68 68 0 0 1 2.158-3.26c.285.141.616.195.958.182.513-.02 1.098-.188 1.723-.49 1.25-.605 2.744-1.787 4.303-3.642l1.518-1.55a.53.53 0 0 0 0-.739l-.729-.744 1.311.209a.5.5 0 0 0 .443-.15l.663-.684c.663-.68 1.292-1.325 1.763-1.892.314-.378.585-.752.754-1.107.163-.345.278-.773.112-1.188a.5.5 0 0 0-.112-.172M3.733 11.62C5.385 9.374 7.24 7.215 9.309 5.394l1.21 1.234-1.171 1.196-.027.03c-1.5 1.789-2.891 2.867-3.977 3.393-.544.263-.99.378-1.324.39a1.3 1.3 0 0 1-.287-.018Zm6.769-7.22c1.31-1.028 2.7-1.914 4.172-2.6a7 7 0 0 1-.4.523c-.442.533-1.028 1.134-1.681 1.804l-.51.524zm3.346-3.357C9.594 3.147 6.045 6.8 3.149 10.678c.007-.464.121-1.086.37-1.806.533-1.535 1.65-3.415 3.455-4.976 1.807-1.561 3.746-2.36 5.31-2.68a8 8 0 0 1 1.564-.173"/>' +
                                '</svg> 编辑</a>';
                            var processButton = '<?php if ($role === 'admin'): ?>' +
                                '<form method="post" style="display:inline;" onsubmit="return confirmProcess();" class="me-2">' +
                                '<input type="hidden" name="request_id" value="' + request.id + '">' +
                                '<input type="hidden" name="processed" value="' + (request.processed ? 0 : 1) + '">' +
                                '<button type="submit" name="process_request" class="btn btn-secondary btn-sm">' +
                                (request.processed ? 
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">' +
                                    '<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>' +
                                    '<path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>' +
                                    '</svg> 标记为未处理' :
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-circle" viewBox="0 0 16 16">' +
                                    '<path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0"/>' +
                                    '<path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z"/>' +
                                    '</svg> 标记为已处理') +
                                '</button>' +
                                '</form>' +
                                '<?php endif; ?>';
                            var deleteButton = '<form method="post" style="display:inline;" onsubmit="return confirmDelete();" class="me-2">' +
                                '<input type="hidden" name="request_id" value="' + request.id + '">' +
                                '<button type="submit" name="delete_request" class="btn btn-danger btn-sm">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">' +
                                '<path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>' +
                                '</svg> 删除</button>' +
                                '</form>';
            
                            var row = '<tr class="' + rowClass + '">' +
                                '<td><input type="checkbox" name="ids[]" value="' + request.id + '"></td>' +
                                '<td>' + request.title + '</td>' +
                                '<td>' + request.name + '</td>' +
                                '<td>' + request.created_at.split(' ')[0] + '</td>' +
                                '<td class="' + processedClass + '">' + processedText + '</td>' +
                                '<td>' + viewButton + editButton + deleteButton + processButton + '</td>' +
                                '</tr>';
                            requestList.append(row);
                        });
                        // 移除旋转动画，仅在刷新成功后停止旋转
                        $('#refreshButton svg').removeClass('rotating');
                        // 显示刷新成功消息
                        showRefreshSuccess();
                    },
                    error: function() {
                        // 保持旋转动画，不移除 'rotating' 类
                        // 显示刷新失败消息
                        showRefreshError();
                    }
                });
            }

            // 每5秒钟获取一次最新的需求数据
            //setInterval(fetchbugs, 5000);
            // 绑定刷新按钮点击事件
            $('#refreshButton').click(function() {
                fetchRequests();
            });

            // 函数：显示刷新成功的Bootstrap警告
            function showRefreshSuccess() {
                // 移除现有的刷新成功提示
                $('.refresh-alert').remove();
                // 创建新的警告元素
                var alertHtml = '<div class="alert alert-success alert-dismissible fade show refresh-alert mt-3" role="alert">' +
                    '刷新成功！' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>' +
                    '</div>';
                // 将警告插入到请求列表容器的顶部
                $('.request-list-container').prepend(alertHtml);
            }

            // 函数：显示刷新失败的Bootstrap警告
            function showRefreshError() {
                // 移除现有的刷新失败提示
                $('.refresh-error-alert').remove();
                // 创建新的警告元素
                var alertHtml = '<div class="alert alert-danger alert-dismissible fade show refresh-error-alert mt-3" role="alert">' +
                    '刷新失败，请重试。' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>' +
                    '</div>';
                // 将警告插入到请求列表容器的顶部
                $('.request-list-container').prepend(alertHtml);
            }

            // 辅助函数：转义HTML字符
            function htmlspecialchars(str) {
                return $('<div>').text(str).html();
            }
        });

        function confirmDelete() {
            return confirm('确定要删除这个BUG吗？');
        }

        function confirmProcess() {
            return confirm('确定要更新这个BUG的处理状态吗？');
        }
    </script>

    <script>
        $(document).ready(function() {
            // 全选复选框切换
            $('#selectAll').click(function() {
                var isChecked = $(this).prop('checked');
                $('input[name="ids[]"]').prop('checked', isChecked);
                $(this).prop('indeterminate', false);
            });
    
            // 取消全选时，如果有未选中的复选框，则取消全选
            $(document).on('change', 'input[name="ids[]"]', function() {
                var total = $('input[name="ids[]"]').length;
                var checked = $('input[name="ids[]"]:checked').length;
                var selectAll = $('#selectAll');
    
                if (checked === 0) {
                    selectAll.prop('checked', false);
                    selectAll.prop('indeterminate', false);
                } else if (checked === total) {
                    selectAll.prop('checked', true);
                    selectAll.prop('indeterminate', false);
                } else {
                    selectAll.prop('checked', false);
                    selectAll.prop('indeterminate', true);
                }
            });
        });
    </script>

    <!-- JavaScript 重写 URL 参数 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    // 获取用户的 ID
    const userId = "<?php echo htmlspecialchars($_SESSION['id']); ?>"; // 从 PHP 变量中获取用户的 ID

    // 添加自定义参数
    const url = new URL(window.location);
    if (!url.searchParams.has('userId')) {
        url.searchParams.set('userId', `${userId}`);
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
ob_end_flush(); // 发送输出缓冲区的内容并关闭缓冲区
?>