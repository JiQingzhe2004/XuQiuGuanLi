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

// 检查数据库连接
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

session_start();

// 获取分享参数
$share_type = isset($_GET['share_type']) ? $_GET['share_type'] : 'bug'; // 默认为 'bug'
$share_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$shared_by = isset($_GET['shared_by']) ? intval($_GET['shared_by']) : 0;

// 验证分享类型
$allowed_types = ['bug', 'request'];
if (!in_array($share_type, $allowed_types)) {
    echo "无效的分享类型。";
    exit;
}

// 验证链接参数是否完整
if (empty($_GET['share_type']) || empty($_GET['shared_by'])) {
    echo "无效的链接。";
    exit;
}

// 可选：验证 'shared_by' 是否为有效的用户ID
if (!is_numeric($_GET['shared_by']) || intval($_GET['shared_by']) <= 0) {
    echo "无效的分享者信息。";
    exit;
}

// 根据 share_type 设置表名和查询语句
if ($share_type === 'bug') {
    $table = 'bugs';
    $query = "SELECT user_id, name, role, title, description, document_path, image_path, created_at, processed FROM bugs WHERE id = ?";
} else { // 'request'
    $table = 'requests';
    $query = "SELECT user_id, name, role, title, description, is_configured, urgent, document_path, image_path, created_at, processed FROM requests WHERE id = ?";
}

// 获取详细信息
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo "查询准备失败: " . $conn->error;
    exit;
}
$stmt->bind_param("i", $share_id);
$stmt->execute();
$result = $stmt->get_result();
$instance = $result->fetch_assoc();
$stmt->close();

// 检查记录是否存在
if (!$instance) {
    echo "记录不存在。";
    exit;
}

// 记录分享行为（仅在 GET 请求时记录）
if ($_SERVER["REQUEST_METHOD"] === "GET" && $share_type && $share_id && $shared_by) {
    // 包含记录分享的函数
    include 'record_share_function.php'; // 确保此文件包含 recordShare 函数
    
    // 记录分享
    recordShare($conn, $share_type, $share_id, $shared_by);
}

// 处理更改处理状态的请求（仅针对 BUG 或需求，视需求而定）
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_status'])) {
    // 更改处理状态
    $processed = $instance['processed'] ? 0 : 1; // 切换处理状态
    $update_query = "UPDATE {$table} SET processed = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    if ($stmt === false) {
        die("准备语句失败: " . $conn->error);
    }
    $stmt->bind_param("ii", $processed, $share_id);
    
    if ($stmt->execute()) {
        $success_message = "处理状态更新成功！";
        // 更新实例中的处理状态
        $instance['processed'] = $processed;
    } else {
        // 添加错误信息显示
        $error_message = "处理状态更新失败，请重试。 错误信息: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>查看需求</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <style>
        .view-request-body {
            padding-top: 50px;
            background-color: #f8f9fa;
        }
        .view-request-container {
            margin-top: 10px;
            width: 100%;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-left: auto;
            margin-right: auto;
            /* 字体颜色 */
            color: #11659a;
        }
        .file-card {
            display: inline-block;
            max-width: 50%;
            margin: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .file-card a {
            text-decoration: none;
            color: #007bff;
        }
        .image-preview {
            max-height: 100px;
            max-width: 40%;
            margin: 10px 0;
            border: 1px solid #131124; /* 添加描边 */
            border-radius: 8px; /* 可选：添加圆角 */
        }
        .fwb {
            padding: 10px;
            background-color: #e9ecef;
            font-weight: bold;
            border-radius: 8px;
        }
    </style>
</head>
<body class="view-request-body">
    <div class="view-request-container">
        <h3><?php echo htmlspecialchars($instance['title']); ?></h3>
        <div class="fwb"><?php echo nl2br($instance['description']); ?></div>

        <?php if ($instance['document_path']): ?>
            <?php
            $document_paths = json_decode($instance['document_path'], true);
            foreach ($document_paths as $document):
            ?>
                <div class="file-card">
                    <a href="<?php echo htmlspecialchars($document['path']); ?>" download>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-font" viewBox="0 0 16 16">
                        <path d="M10.943 6H5.057L5 8h.5c.18-1.096.356-1.192 1.694-1.235l.293-.01v5.09c0 .47-.1.582-.898.655v.5H9.41v-.5c-.803-.073-.903-.184-.903-.654V6.755l.298.01c1.338.043 1.514.14 1.694 1.235h.5l-.057-2z"/>
                        <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5z"/>
                        </svg> <?php echo htmlspecialchars($document['original_name']); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($instance['image_path']): ?>
            <?php
            $image_paths = json_decode($instance['image_path'], true);
            foreach ($image_paths as $image):
            ?>
                <a href="<?php echo htmlspecialchars($image['path']); ?>" data-lightbox="image-1">
                    <img src="<?php echo htmlspecialchars($image['path']); ?>" class="image-preview">
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="container">
            <div class="row mb-3 justify-content-center">
                <div class="col-md-2 p-2 border bg-light rounded mb-3"><strong>医院名称：</strong><?php echo htmlspecialchars($instance['title']); ?></div>
                <div class="col-md-2 p-2 border bg-light rounded mb-3"><strong>提交人：</strong><?php echo htmlspecialchars($instance['name']); ?></div>
                <div class="col-md-2 p-2 border bg-light rounded mb-3"><strong>是否加配置项：</strong><?php echo htmlspecialchars($instance['is_configured']); ?></div>
                <div class="col-md-2 p-2 border bg-light rounded mb-3"><strong>紧急程度：</strong>
                    <?php
                    switch ($instance['urgent']) {
                        case '可延期':
                            echo '可延期 ⚪';
                            break;
                        case '一般':
                            echo '一般 🔵';
                            break;
                        case '紧急':
                            echo '紧急 🟡';
                            break;
                        case '非常紧急':
                            echo '非常紧急 🔴';
                            break;
                        default:
                            echo htmlspecialchars($instance['urgent']);
                            break;
                    }
                    ?>
                </div>
                <div class="col-md-4 p-2 border bg-light rounded mb-3"><strong>提交时间：</strong><?php echo htmlspecialchars($instance['created_at']); ?></div>
            </div>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="row mb-3 justify-content-center">
                <div class="col-md-4 p-2 border bg-light rounded mb-3 d-flex align-items-center justify-content-between">
                    <strong>处理状态：</strong>
                    <span><?php echo $instance['processed'] ? '已处理' : '未处理'; ?></span>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('确定要更改处理状态吗？');">
                        <input type="hidden" name="change_status" value="1">
                        <button type="submit" class="btn btn-secondary ms-2">更改状态</button>
                    </form>
                </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="container text-center mt-4">
            <a href="edit_request.php?id=<?php echo htmlspecialchars($share_id); ?>" class="btn btn-primary">
                编辑需求
            </a>
        </div>
        <?php endif; ?>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>