<?php
include 'header.php';
// 引入数据库配置
include 'config.php';

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);
// 获取当前日期时间
$timestamp = date("Y-m-d H:i:s");  // 格式：2023-11-24 02:37:44

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $is_configured = isset($_POST['is_configured']) ? $_POST['is_configured'] : '否';
    $urgent = $_POST['urgent'];

    // 验证必填项
    if (empty($title) || empty($description)) {
        $error_message = "标题和详细内容是必填项。";
    } else {
        $documents = isset($_FILES['documents']) ? $_FILES['documents'] : [];
        $images = isset($_FILES['images']) ? $_FILES['images'] : [];
        $document_paths = [];
        $image_paths = [];

        // 处理文档上传
        for ($i = 0; $i < count($documents['name']); $i++) {
            if ($documents['error'][$i] == UPLOAD_ERR_OK) {
                $original_name = basename($documents['name'][$i]);
                $document_name = $timestamp . '_' . $original_name;
                $document_path = 'uploads/documents/' . $document_name;
                move_uploaded_file($documents['tmp_name'][$i], $document_path);
                $document_paths[] = ['original_name' => $original_name, 'path' => $document_path];
            }
        }

        // 处理图片上传
        for ($i = 0; $i < count($images['name']); $i++) {
            if ($images['error'][$i] == UPLOAD_ERR_OK) {
                $original_name = basename($images['name'][$i]);
                $image_name = $timestamp . '_' . $original_name;
                $image_path = 'uploads/images/' . $image_name;
                move_uploaded_file($images['tmp_name'][$i], $image_path);
                $image_paths[] = ['original_name' => $original_name, 'path' => $image_path];
            }
        }

        $document_paths_json = json_encode($document_paths);
        $image_paths_json = json_encode($image_paths);

        // 保存需求信息到数据库
        $stmt = $conn->prepare("INSERT INTO requests (user_id, name, role, title, description, document_path, image_path, is_configured, urgent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            die("准备 SQL 语句失败: " . $conn->error);
        }
        $stmt->bind_param("isssssssss", $user_id, $name, $role, $title, $description, $document_paths_json, $image_paths_json, $is_configured, $urgent, $timestamp);
        
        if ($stmt->execute()) {
            $success_message = "需求提交成功！";
        } else {
            // 添加错误信息显示
            $error_message = "需求提交失败，请重试。 错误信息: " . $stmt->error;
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>提交需求</title>
    <script src="js/wangEditor.min.js"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .submit-body {
            display: relative;
            justify-content: center;
            align-items: center;
            height: 110vh;
            background-color: #f8f9fa;
            margin: 0;
        }
        .submit-container {
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
    </style>
</head>
<body class="submit-body">
    <div class="submit-container">
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

        <!-- 需求提交区域 -->
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="timestamp" class="form-label">当前时间</label>
                <input type="text" class="form-control" id="timestamp" name="timestamp" readonly>
            </div>
            <div class="mb-3">
                <label for="title" class="form-label"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hospital" viewBox="0 0 16 16">
                  <path d="M8.5 5.034v1.1l.953-.55.5.867L9 7l.953.55-.5.866-.953-.55v1.1h-1v-1.1l-.953.55-.5-.866L7 7l-.953-.55.5-.866.953.55v-1.1zM13.25 9a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25zM13 11.25a.25.25 0 0 1 .25-.25h.5a.25.25 0 0 1 .25.25v.5a.25.25 0 0 1-.25.25h-.5a.25.25 0 0 1-.25-.25zm.25 1.75a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25zm-11-4a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5A.25.25 0 0 0 3 9.75v-.5A.25.25 0 0 0 2.75 9zm0 2a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25zM2 13.25a.25.25 0 0 1 .25-.25h.5a.25.25 0 0 1 .25.25v.5a.25.25 0 0 1-.25.25h-.5a.25.25 0 0 1-.25-.25z"/>
                  <path d="M5 1a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1a1 1 0 0 1 1 1v4h3a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h3V3a1 1 0 0 1 1-1zm2 14h2v-3H7zm3 0h1V3H5v12h1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1zm0-14H6v1h4zm2 7v7h3V8zm-8 7V8H1v7z"/>
                </svg> 医院名称 <span style="color: red;">*</span></label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>

            <div class="mb-3">
                <label for="is_configured" class="form-label"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-square" viewBox="0 0 16 16">
                  <path d="M3 14.5A1.5 1.5 0 0 1 1.5 13V3A1.5 1.5 0 0 1 3 1.5h8a.5.5 0 0 1 0 1H3a.5.5 0 0 0-.5.5v10a.5.5 0 0 0 .5.5h10a.5.5 0 0 0 .5-.5V8a.5.5 0 0 1 1 0v5a1.5 1.5 0 0 1-1.5 1.5z"/>
                  <path d="m8.354 10.354 7-7a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0"/>
                </svg> 是否配置项</label><br>
                <label><input type="radio" name="is_configured" value="是"> 是</label>
                <label><input type="radio" name="is_configured" value="否" checked> 否</label>
            </div>

            <div class="mb-3">
                <label for="urgent" class="form-label"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                  <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                </svg> 紧急程度</label><br>
                <label><input type="radio" name="urgent" value="可延期"> 可延期⚪</label>
                <label><input type="radio" name="urgent" value="一般" checked> 一般🔵</label>
                <label><input type="radio" name="urgent" value="紧急"> 紧急🟡</label>
                <label><input type="radio" name="urgent" value="非常紧急"> 非常紧急🔴</label>
            </div>

            <!-- wangEditor 编辑器 -->
            <div class="mb-3">
                <label for="description" class="form-label"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-html" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M14 4.5V11h-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zm-9.736 7.35v3.999h-.791v-1.714H1.79v1.714H1V11.85h.791v1.626h1.682V11.85h.79Zm2.251.662v3.337h-.794v-3.337H4.588v-.662h3.064v.662zm2.176 3.337v-2.66h.038l.952 2.159h.516l.946-2.16h.038v2.661h.715V11.85h-.8l-1.14 2.596H9.93L8.79 11.85h-.805v3.999zm4.71-.674h1.696v.674H12.61V11.85h.79v3.325Z"/>
                </svg> 详细内容 <span style="color: red;">*</span></label>
                <div id="editor"></div>
                <textarea id="description" name="description" style="display:none;" required></textarea>
            </div>

            <div class="mb-3">
                <label for="documents" class="form-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-paperclip" viewBox="0 0 16 16">
                        <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0z"/>
                    </svg> 上传文档
                </label>
                <input type="file" class="form-control" id="documents" name="documents[]" multiple>
                <div id="documentPreview" class="mt-2"></div>
            </div>

            <div class="mb-3">
                <label for="images" class="form-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-images" viewBox="0 0 16 16">
                        <path d="M4.502 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                        <path d="M14.002 13a2 2 0 0 1-2 2h-10a2 2 0 0 1-2-2V5A2 2 0 0 1 2 3a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-1.998 2M14 2H4a1 1 0 0 0-1 1h9.002a2 2 0 0 1 2 2v7A1 1 0 0 0 15 11V3a1 1 0 0 0-1-1M2.002 4a1 1 0 0 0-1 1v8l2.646-2.354a.5.5 0 0 1 .63-.062l2.66 1.773 3.71-3.71a.5.5 0 0 1 .577-.094l1.777 1.947V5a1 1 0 0 0-1-1z"/>
                    </svg> 上传图片
                </label>
                <input type="file" class="form-control" id="images" name="images[]" multiple>
                <div id="imagePreview" class="mt-2 d-flex flex-wrap"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-send-check" viewBox="0 0 16 16">
              <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855a.75.75 0 0 0-.124 1.329l4.995 3.178 1.531 2.406a.5.5 0 0 0 .844-.536L6.637 10.07l7.494-7.494-1.895 4.738a.5.5 0 1 0 .928.372zm-2.54 1.183L5.93 9.363 1.591 6.602z"/>
              <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-1.993-1.679a.5.5 0 0 0-.686.172l-1.17 1.95-.547-.547a.5.5 0 0 0-.708.708l.774.773a.75.75 0 0 0 1.174-.144l1.335-2.226a.5.5 0 0 0-.172-.686"/>
            </svg> 提交需求</button>
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
</script>

<script>
    // 初始化 DataTransfer 对象
    const documentDT = new DataTransfer();
    const imageDT = new DataTransfer();

    // 文件预览
    document.getElementById('documents').addEventListener('change', function(event) {
        const preview = document.getElementById('documentPreview');
        const files = Array.from(event.target.files);
        
        // 清空预览并 DataTransfer 对象
        documentDT.items.clear();
        preview.innerHTML = '';

        // 过滤并添加允许的文件
        files.forEach(file => {
            const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'json', 'csv', 'ppt', 'pptx'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            if (allowedExtensions.includes(fileExtension)) {
                documentDT.items.add(file);
                const fileCard = document.createElement('div');
                fileCard.className = 'card me-2 mb-2 position-relative';
                fileCard.style.flex = '0 1 auto';
                fileCard.style.whiteSpace = 'nowrap';
                fileCard.style.display = 'inline-block';

                fileCard.innerHTML = `
                    <div class="card-body p-2 d-flex align-items-center">
                        <p class="card-text mb-0 text-truncate" title="${file.name}" style="max-width: 150px;">${file.name}</p>
                        <button type="button" class="btn-close ms-2" aria-label="删除" onclick="removeDocument(${documentDT.items.length - 1})"></button>
                    </div>
                `;
                preview.appendChild(fileCard);
            } else {
                alert(`文件 "${file.name}" 类型不被允许。`);
            }
        });

        // 更新文件输入的 FileList
        document.getElementById('documents').files = documentDT.files;
    });

    function removeDocument(index) {
        documentDT.items.remove(index);
        document.getElementById('documents').files = documentDT.files;
        updateDocumentPreview();
    }

    function updateDocumentPreview() {
        const preview = document.getElementById('documentPreview');
        preview.innerHTML = '';

        Array.from(documentDT.files).forEach((file, index) => {
            const fileCard = document.createElement('div');
            fileCard.className = 'card me-2 mb-2 position-relative';
            fileCard.style.flex = '0 1 auto';
            fileCard.style.whiteSpace = 'nowrap';
            fileCard.style.display = 'inline-block';

            fileCard.innerHTML = `
                <div class="card-body p-2 d-flex align-items-center">
                    <p class="card-text mb-0 text-truncate" title="${file.name}" style="max-width: 300px;">${file.name}</p>
                    <button type="button" class="btn-close ms-2" aria-label="删除" onclick="removeDocument(${index})"></button>
                </div>
            `;
            preview.appendChild(fileCard);
        });
    }

    // 图片预览
    document.getElementById('images').addEventListener('change', function(event) {
        const preview = document.getElementById('imagePreview');
        const files = Array.from(event.target.files);
        
        // 清空预览并 DataTransfer 对象
        imageDT.items.clear();
        preview.innerHTML = '';

        files.forEach(file => {
            if (file.type.startsWith('image/')) {
                imageDT.items.add(file);
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgCard = document.createElement('div');
                    imgCard.className = 'position-relative me-2 mb-2';
                    imgCard.style.width = '100px';
                    imgCard.style.height = '100px';

                    imgCard.innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                        <button type="button" class="btn-close position-absolute top-0 end-0 me-1 mt-1" aria-label="删除" onclick="removeImage(${imageDT.items.length - 1})"></button>
                    `;
                    preview.appendChild(imgCard);
                }
                reader.readAsDataURL(file);
            } else {
                alert(`图片文件 "${file.name}" 类型不被允许。`);
            }
        });

        // 更新图片输入的 FileList
        document.getElementById('images').files = imageDT.files;
    });

    function removeImage(index) {
        imageDT.items.remove(index);
        document.getElementById('images').files = imageDT.files;
        updateImagePreview();
    }

    function updateImagePreview() {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';

        Array.from(imageDT.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgCard = document.createElement('div');
                imgCard.className = 'position-relative me-2 mb-2';
                imgCard.style.width = '100px';
                imgCard.style.height = '100px';

                imgCard.innerHTML = `
                    <img src="${e.target.result}" class="img-thumbnail" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                    <button type="button" class="btn-close position-absolute top-0 end-0 me-1 mt-1" aria-label="删除" onclick="removeImage(${index})"></button>
                `;
                preview.appendChild(imgCard);
            }
            reader.readAsDataURL(file);
        });
    }
</script>

<style>
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
<!-- JavaScript 重写 URL 参数 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 获取当前日期时间
    const timestamp = "<?php echo $timestamp; ?>"; // 从 PHP 变量中获取时间戳

    // 添加自定义参数，例如 time=时间
    const url = new URL(window.location);
    if (!url.searchParams.has('time')) {
        url.searchParams.append('time', timestamp);
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
document.addEventListener('DOMContentLoaded', function() {
    // 实时更新当前时间
    function updateTimestamp() {
        const now = new Date();
        const formattedTimestamp = now.getFullYear() + '-' +
            String(now.getMonth() + 1).padStart(2, '0') + '-' +
            String(now.getDate()).padStart(2, '0') + ' ' +
            String(now.getHours()).padStart(2, '0') + ':' +
            String(now.getMinutes()).padStart(2, '0') + ':' +
            String(now.getSeconds()).padStart(2, '0');
        document.getElementById('timestamp').value = formattedTimestamp;
    }

    // 初始调用一次
    updateTimestamp();

    // 每秒更新一次时间
    setInterval(updateTimestamp, 1000);
});
</script>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
