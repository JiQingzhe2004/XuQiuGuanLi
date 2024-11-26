<?php
include 'header.php';
require_once 'includes/db.php'; // 引入封装的数据库操作函数

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

// 获取需求ID
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 获取需求详细信息
$stmt = $conn->prepare("SELECT user_id, name, role, title, description, document_path, image_path, is_configured, urgent, created_at, processed FROM requests WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request_result = $stmt->get_result();
$request = $request_result->fetch_assoc();
$stmt->close();

// 检查需求是否存在
if (!$request) {
    echo "需求不存在。";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_status'])) {
    // 更改处理状态
    $processed = $request['processed'] ? 0 : 1; // 切换处理状态
    $stmt = $conn->prepare("UPDATE requests SET processed = ? WHERE id = ?");
    if ($stmt === false) {
        die("准备语句失败: " . $conn->error);
    }
    $stmt->bind_param("ii", $processed, $request_id);
    
    if ($stmt->execute()) {
        $success_message = "处理状态更新成功！";
        // 更新请求对象中的处理状态
        $request['processed'] = $processed;
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
        <div class="d-flex justify-content-between align-items-center">
            <h3><?php echo htmlspecialchars($request['title']); ?></h3>
            <button class="btn btn-primary btn-sm d-flex align-items-center" id="shareButton" title="分享">
                分享
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up ms-2" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M3.5 6a.5.5 0 0 0-.5.5v8a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5h-2a.5.5 0 0 1 0-1h2A1.5 1.5 0 0 1 14 6.5v8a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 14.5v-8A1.5 1.5 0 0 1 3.5 5h2a.5.5 0 0 1 0 1z"/>
                    <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 1.707V10.5a.5.5 0 0 1-1 0V1.707L5.354 3.854a.5.5 0 1 1-.708-.708z"/>
                </svg>
            </button>
        </div>
        <div class="fwb"><?php echo $request['description']; ?></div>

        <?php if ($request['document_path']): ?>
            <?php
            $document_paths = json_decode($request['document_path'], true);
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
        
        <?php if ($request['image_path']): ?>
            <?php
            $image_paths = json_decode($request['image_path'], true);
            foreach ($image_paths as $image):
            ?>
                <a href="<?php echo htmlspecialchars($image['path']); ?>" data-lightbox="image-1">
                    <img src="<?php echo htmlspecialchars($image['path']); ?>" class="image-preview">
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="container">
            <div class="row mb-3 justify-content-center">
                <div class="col-md-2 p-2 border bg-light rounded mb-3"><strong>医院名称：</strong><?php echo htmlspecialchars($request['title']); ?></div>
                <div class="col-md-2 p-2 border bg-light rounded mb-3"><strong>提交人：</strong><?php echo htmlspecialchars($request['name']); ?></div>
                <div class="col-md-2 p-2 border bg-light rounded mb-3"><strong>是否加配置项：</strong><?php echo htmlspecialchars($request['is_configured']); ?></div>
                <div class="col-md-2 p-2 border bg-light rounded mb-3"><strong>紧急程度：</strong>
                    <?php
                    switch ($request['urgent']) {
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
                            echo htmlspecialchars($request['urgent']);
                            break;
                    }
                    ?>
                </div>
                <div class="col-md-4 p-2 border bg-light rounded mb-3"><strong>提交时间：</strong><?php echo htmlspecialchars($request['created_at']); ?></div>
            </div>
            <div class="row mb-3 justify-content-center">
                <div class="col-md-4 p-2 border bg-light rounded mb-3 d-flex align-items-center justify-content-between">
                    <strong>处理状态：</strong>
                    <span><?php echo $request['processed'] ? '已处理' : '未处理'; ?></span>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('确定要更改处理状态吗？');">
                        <input type="hidden" name="change_status" value="1">
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <button type="submit" class="btn btn-secondary ms-2">更改状态</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="container text-center mt-4">
            <a href="edit_request.php?id=<?php echo $request_id; ?>" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                  <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                  <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                </svg> 编辑需求
            </a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const shareButton = document.getElementById('shareButton');
            if (shareButton) {
                shareButton.addEventListener('click', function() {
                    const shareData = {
                        title: <?php echo json_encode($request['title']); ?>,
                        text: <?php echo json_encode("查看需求：" . $request['title']); ?>,
                        url: `view_request_share.php?id=<?php echo $request_id; ?>&share_type=request&shared_by=<?php echo $_SESSION['id']; ?>`
                    };
    
                    if (navigator.share) {
                        navigator.share(shareData)
                            .then(() => {
                                console.log('分享成功');
                                // 记录分享行为
                                recordShare('request', <?php echo $request_id; ?>);
                            })
                            .catch((error) => console.log('分享失败：', error));
                    } else {
                        // 复制链接到剪贴板作为回退方案
                        navigator.clipboard.writeText(shareData.url).then(() => {
                            alert('链接已复制到剪贴板');
                            // 记录分享行为
                            recordShare('request', <?php echo $request_id; ?>);
                        }).catch(() => {
                            alert('无法复制链接');
                        });
                    }
                });
            } else {
                console.error('未找到 shareButton 元素');
            }
        });
    
        /**
         * 通过 AJAX 调用 PHP 函数记录分享行为
         */
        function recordShare(shareType, shareId) {
            fetch('record_share.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    share_type: shareType,
                    share_id: shareId,
                    shared_by: <?php echo $_SESSION['id']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('分享行为已记录');
                } else {
                    console.error('记录分享行为失败');
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>