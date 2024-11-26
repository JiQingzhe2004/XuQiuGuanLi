<?php
include 'header.php';
require_once 'includes/db.php'; // 引入封装的数据库操作函数

// 引入数据库配置
include 'config.php';

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

session_start();

// 确保用户已登录
if (!isset($_SESSION['id'])) {
    echo "请先登录！";
    exit;
}

// 获取BUG ID
$bug_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 获取BUG详细信息
$stmt = $conn->prepare("SELECT user_id, name, role, title, description, document_path, image_path, created_at, processed FROM bugs WHERE id = ?");
$stmt->bind_param("i", $bug_id);
$stmt->execute();
$bug_result = $stmt->get_result();
$bug = $bug_result->fetch_assoc();
$stmt->close();

// 检查BUG是否存在
if (!$bug) {
    echo "BUG不存在。";
    exit;
}

// 初始化消息变量
$success_message = '';
$error_message = '';

// 处理更新BUG请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($_POST['delete_file'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    // 验证必填项
    if (empty($title) || empty($description)) {
        $error_message = "标题和详细内容是必填项。";
    } else {
        $files_to_delete = isset($_POST['files_to_delete']) ? $_POST['files_to_delete'] : [];

        // 处理删除的文件
        if (!empty($files_to_delete)) {
            $document_paths = json_decode($bug['document_path'], true);
            $image_paths = json_decode($bug['image_path'], true);

            foreach ($files_to_delete as $filePath) {
                // 删除文件
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                // 从文档路径中移除
                $document_paths = array_filter($document_paths, function($path) use ($filePath) {
                    return $path['path'] !== $filePath;
                });

                // 从图片路径中移除
                $image_paths = array_filter($image_paths, function($path) use ($filePath) {
                    return $path['path'] !== $filePath;
                });
            }

            $document_paths_json = json_encode(array_values($document_paths));
            $image_paths_json = json_encode(array_values($image_paths));

            // 更新数据库中的文件路径
            $stmt = $conn->prepare("UPDATE bugs SET document_path = ?, image_path = ? WHERE id = ?");
            $stmt->bind_param("ssi", $document_paths_json, $image_paths_json, $bug_id);
            $stmt->execute();
            $stmt->close();

            // 更新BUG对象
            $bug['document_path'] = $document_paths_json;
            $bug['image_path'] = $image_paths_json;
        }

        // 处理新上传的文档和图片
        $documents = isset($_FILES['documents']) ? $_FILES['documents'] : [];
        $images = isset($_FILES['images']) ? $_FILES['images'] : [];
        $document_paths = json_decode($bug['document_path'], true);
        $image_paths = json_decode($bug['image_path'], true);

        // 处理文档上传
        for ($i = 0; $i < count($documents['name']); $i++) {
            if ($documents['error'][$i] == UPLOAD_ERR_OK) {
                $original_name = basename($documents['name'][$i]);
                $document_name = time() . '_' . $original_name;
                $document_path = 'uploads/bug/documents/' . $document_name;
                move_uploaded_file($documents['tmp_name'][$i], $document_path);
                $document_paths[] = ['original_name' => $original_name, 'path' => $document_path];
            }
        }

        // 处理图片上传
        for ($i = 0; $i < count($images['name']); $i++) {
            if ($images['error'][$i] == UPLOAD_ERR_OK) {
                $original_name = basename($images['name'][$i]);
                $image_name = time() . '_' . $original_name;
                $image_path = 'uploads/bug/images/' . $image_name;
                move_uploaded_file($images['tmp_name'][$i], $image_path);
                $image_paths[] = ['original_name' => $original_name, 'path' => $image_path];
            }
        }

        $document_paths_json = json_encode(array_values($document_paths));
        $image_paths_json = json_encode(array_values($image_paths));

        // 更新BUG信息到数据库
        $stmt = $conn->prepare("UPDATE bugs SET title = ?, description = ?, document_path = ?, image_path = ? WHERE id = ?");
        if ($stmt === false) {
            die("准备语句失败: " . $conn->error);
        }
        $stmt->bind_param("ssssi", $title, $description, $document_paths_json, $image_paths_json, $bug_id);

        if ($stmt->execute()) {
            $success_message = "BUG更新成功！";
            // 更新BUG对象以反映更改后的文件路径
            $bug['document_path'] = $document_paths_json;
            $bug['image_path'] = $image_paths_json;
        } else {
            // 添加错误信息显示
            $error_message = "BUG更新失败，请重试。 错误信息: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>编辑BUG</title>
    <script src="js/wangEditor.min.js"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .edit-body {
            display: relative;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            margin: 0;
        }
        .edit-container {
            margin-top: 10px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .alert {
            margin-bottom: 20px;
        }
        .mb-3 label {
            color: black;
        }
        .mb-3 input[type="radio"] {
            margin-right: 5px;
        }
        .file-card, .image-card {
            display: inline-block;
            max-width: 50%;
            margin: 10px;
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .file-card:hover .delete-btn, .image-card:hover .delete-btn {
            display: block;
        }
        .delete-btn {
            display: none;
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            text-align: center;
            line-height: 25px;
            cursor: pointer;
        }
        .image-preview {
            max-height: 100px;
            max-width: 100%;
            margin: 10px 0;
            border: 1px solid #131124; /* 添加描边 */
            border-radius: 8px; /* 可选：添加圆角 */
        }
        .w-e-text-container {
            background-color: #f4f4f4;
            color: #000000;
        }
        /* 自定义编辑器内带背景色文字的样式 */
        #editor span[style*="background-color"] {
            padding: 5px;
            border-radius: 5px;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
</head>
<body class="edit-body">
    <div class="edit-container">
        <!-- 显示成功或错误消息 -->
        <?php if (!empty($success_message)): ?>
            <div id="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div id="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
            </div>
        <?php endif; ?>

        <!-- BUG编辑区域 -->
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bug" viewBox="0 0 16 16">
                    <path d="M4.355.522a.5.5 0 0 1 .623.333l.291.956A5 5 0 0 1 8 1c1.007 0 1.946.298 2.731.811l.29-.956a.5.5 0 1 1 .957.29l-.41 1.352A5 5 0 0 1 13 6h.5a.5.5 0 0 0 .5-.5V5a.5.5 0 0 1 1 0v.5A1.5 1.5 0 0 1 13.5 7H13v1h1.5a.5.5 0 0 1 0 1H13v1h.5a1.5 1.5 0 0 1 1.5 1.5v.5a.5.5 0 1 1-1 0v-.5a.5.5 0 0 0-.5-.5H13a5 5 0 0 1-10 0h-.5a.5.5 0 0 0-.5.5v.5a.5.5 0 1 1-1 0v-.5A1.5 1.5 0 0 1 2.5 10H3V9H1.5a.5.5 0 0 1 0-1H3V7h-.5A1.5 1.5 0 0 1 1 5.5V5a.5.5 0 0 1 1 0v.5a.5.5 0 0 0 .5.5H3c0-1.364.547-2.601 1.432-3.503l-.41-1.352a.5.5 0 0 1 .333-.623M4 7v4a4 4 0 0 0 3.5 3.97V7zm4.5 0v7.97A4 4 0 0 0 12 11V7zM12 6a4 4 0 0 0-1.334-2.982A3.98 3.98 0 0 0 8 2a3.98 3.98 0 0 0-2.667 1.018A4 4 0 0 0 4 6z"/>
                </svg> BUG模块</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($bug['title']); ?>" required>
            </div>

            <!-- wangEditor 编辑器 -->
            <div class="mb-3">
                <label for="description" class="form-label"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-html" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M14 4.5V11h-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zm-9.736 7.35v3.999h-.791v-1.714H1.79v1.714H1V11.85h.791v1.626h1.682V11.85h.79Zm2.251.662v3.337h-.794v-3.337H4.588v-.662h3.064v.662zm2.176 3.337v-2.66h.038l.952 2.159h.516l.946-2.16h.038v2.661h.715V11.85h-.8l-1.14 2.596H9.93L8.79 11.85h-.805v3.999zm4.71-.674h1.696v.674H12.61V11.85h.79v3.325Z"/>
                </svg> BUG详细内容</label>
                <div id="editor"><?php echo $bug['description']; ?></div>
                <textarea id="description" name="description" style="display:none;" required><?php echo htmlspecialchars($bug['description']); ?></textarea>
            </div>

            <!-- 显示现有文件 -->
            <div class="mb-3">
                <label class="form-label"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-break" viewBox="0 0 16 16">
                  <path d="M14 4.5V9h-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v7H2V2a2 2 0 0 1 2-2h5.5zM13 12h1v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-2h1v2a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1zM.5 10a.5.5 0 0 0 0 1h15a.5.5 0 0 0 0-1z"/>
                </svg> 现有文档</label>
                <div>
                    <?php
                    $document_paths = json_decode($bug['document_path'], true);
                    foreach ($document_paths as $index => $document):
                        $elementId = 'document_' . $index;
                    ?>
                        <div class="file-card" id="<?php echo $elementId; ?>">
                            <a href="<?php echo htmlspecialchars($document['path']); ?>" download>
                                <?php echo htmlspecialchars($document['original_name']); ?>
                            </a>
                            <button type="button" class="delete-btn" onclick="deleteFile('<?php echo htmlspecialchars($document['path']); ?>', '<?php echo $elementId; ?>')">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 显示现有图片 -->
            <div class="mb-3">
                <label class="form-label"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-image" viewBox="0 0 16 16">
                  <path d="M6.502 7a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                  <path d="M14 14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zM4 1a1 1 0 0 0-1 1v10l2.224-2.224a.5.5 0 0 1 .61-.075L8 11l2.157-3.02a.5.5 0 0 1 .76-.063L13 10V4.5h-2A1.5 1.5 0 0 1 9.5 3V1z"/>
                </svg> 现有图片</label>
                <div>
                    <?php
                    $image_paths = json_decode($bug['image_path'], true);
                    foreach ($image_paths as $index => $image):
                        $elementId = 'image_' . $index;
                    ?>
                        <div class="image-card" id="<?php echo $elementId; ?>">
                            <a href="<?php echo htmlspecialchars($image['path']); ?>" data-lightbox="image-gallery">
                                <img src="<?php echo htmlspecialchars($image['path']); ?>" class="image-preview">
                            </a>
                            <button type="button" class="delete-btn" onclick="deleteFile('<?php echo htmlspecialchars($image['path']); ?>', '<?php echo $elementId; ?>')">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 上传新文件 -->
            <div class="mb-3">
                <label for="documents" class="form-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-paperclip" viewBox="0 0 16 16">
                        <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0z"/>
                    </svg> 上传文档</label>
                <input type="file" class="form-control" id="documents" name="documents[]" multiple>
            </div>

            <!-- 上传新图片 -->
            <div class="mb-3">
                <label for="images" class="form-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-images" viewBox="0 0 16 16">
                        <path d="M4.502 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                        <path d="M14.002 13a2 2 0 0 1-2 2h-10a2 2 0 0 1-2-2V5A2 2 0 0 1 2 3a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-1.998 2M14 2H4a1 1 0 0 0-1 1h9.002a2 2 0 0 1 2 2v7A1 1 0 0 0 15 11V3a1 1 0 0 0-1-1M2.002 4a1 1 0 0 0-1 1v8l2.646-2.354a.5.5 0 0 1 .63-.062l2.66 1.773 3.71-3.71a.5.5 0 0 1 .577-.094l1.777 1.947V5a1 1 0 0 0-1-1z"/>
                    </svg> 上传图片</label>
                <input type="file" class="form-control" id="images" name="images[]" multiple>
            </div>
            <input type="hidden" name="files_to_delete[]" id="files_to_delete">

            <button type="submit" class="btn btn-primary w-100">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-arrow-counterclockwise" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2z"/>
                      <path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466"/>
                </svg> 更新提交的BUG
            </button>
        </form>
    </div>

    <script>
    // 初始化 wangEditor 编辑器
    const E = window.wangEditor;
    const editor = new E('#editor');

    editor.config.onchange = function (html) {
        // 创建一个临时的 DOM 元素来处理 HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        // 查找所有带背景色的 <span> 元素
        const spans = tempDiv.querySelectorAll('span[style*="background-color"]');

        spans.forEach(function(span) {
            // 获取现有的样式
            let style = span.getAttribute('style') || '';
            // 添加内边距和圆角
            if (!style.includes('padding')) {
                style += ' padding: 5px;';
            }
            if (!style.includes('border-radius')) {
                style += ' border-radius: 5px;';
            }
            span.setAttribute('style', style);
        });

        // 设置处理后的 HTML 内容到隐藏的表单字段
        document.getElementById('description').value = tempDiv.innerHTML;
    };

    editor.create();

        // 删除文件
        function deleteFile(filePath, elementId) {
            if (confirm('确定要删除这个文件吗？')) {
                // 隐藏对应的文件或图片卡片
                document.getElementById(elementId).style.display = 'none';

                // 创建新的隐藏输入以存储待删除的文件路径
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'files_to_delete[]';
                input.value = filePath;

                // 添加到表单中
                document.getElementById('files_to_delete').parentNode.appendChild(input);
            }
        }
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>