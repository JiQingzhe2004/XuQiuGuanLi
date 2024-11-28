<?php
// This script outputs a copyright notice.
// @author JiQingzhe
// @version 0.0.1
// @license All rights reserved
// @copyright © 2024 JiQingzhe

// 包含头部文件
include 'header.php';
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=0.9">
    <title>需求收集</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .text-center {
            font-family: 'CustomFont', sans-serif;
            margin-top: 100px;
        }
        .zt-form {
            font-family: 'Douyu', sans-serif;
        }
        .btn-custom {
            margin: 10px;
            width: 150px;
        }
        .ggl-1 {
            font-size: 20px;
            margin-top: 100px;
        }
        .ggl-2 {
            margin-top: 20px;
            font-size: 12px;
        }
        .usernametxt {
            color: #000000;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 5px;
            display: inline-block;
            padding: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="bg-dark text-white">

<div class="text-center">
    <h1>CINDY</h1>
</div>

<!-- 表单 -->
<div class="container mt-5 zt-form text-center">
    <?php if(isset($_SESSION['name'])): ?>
        <h1>你好, <span class="usernametxt"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-person-raised-hand" viewBox="0 0 16 16">
            <path d="M6 6.207v9.043a.75.75 0 0 0 1.5 0V10.5a.5.5 0 0 1 1 0v4.75a.75.75 0 0 0 1.5 0v-8.5a.25.25 0 1 1 .5 0v2.5a.75.75 0 0 0 1.5 0V6.5a3 3 0 0 0-3-3H6.236a1 1 0 0 1-.447-.106l-.33-.165A.83.83 0 0 1 5 2.488V.75a.75.75 0 0 0-1.5 0v2.083c0 .715.404 1.37 1.044 1.689L5.5 5c.32.32.5.754.5 1.207"/>
            <path d="M8 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
            </svg><?php echo htmlspecialchars($_SESSION['name']); ?></span> , 欢迎使用需求收集！</h1>
        <div class="mt-4">
            <a href="lookxq.php" class="btn btn-primary btn-custom btn-lg w-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-folder2-open" viewBox="0 0 16 16">
                    <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.374 3.334 5.82 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/>
                </svg> 查看需求
            </a>
            <a href="submit.php" class="btn btn-secondary btn-custom btn-lg w-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-folder-plus" viewBox="0 0 16 16">
                    <path d="m.5 3 .04.87a2 2 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2m5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19q-.362.002-.683.12L1.5 2.98a1 1 0 0 1 1-.98z"/>
                    <path d="M13.5 9a.5.5 0 0 1 .5.5V11h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V12h-1.5a.5.5 0 0 1 0-1H13V9.5a.5.5 0 0 1 .5-.5"/>
                </svg> 提交需求
            </a>
        </div>
        <p class="ggl-1">在你提交需求的时候，请注意上传的文档是否编辑完毕！<br>如果你需要“需求文档”模板，请点击下方按钮进行下载</p>
        <a href="path/doc/血透2.0-医院名称-模块名称.docx" class="btn btn-warning btn-custom w-50">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-file-earmark-arrow-down" viewBox="0 0 16 16">
              <path d="M8.5 6.5a.5.5 0 0 0-1 0v3.793L6.354 9.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 10.293z"/>
              <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
            </svg> 文档模板下载 <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-filetype-docx" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M14 4.5V11h-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zm-6.839 9.688v-.522a1.5 1.5 0 0 0-.117-.641.86.86 0 0 0-.322-.387.86.86 0 0 0-.469-.129.87.87 0 0 0-.471.13.87.87 0 0 0-.32.386 1.5 1.5 0 0 0-.117.641v.522q0 .384.117.641a.87.87 0 0 0 .32.387.9.9 0 0 0 .471.126.9.9 0 0 0 .469-.126.86.86 0 0 0 .322-.386 1.55 1.55 0 0 0 .117-.642m.803-.516v.513q0 .563-.205.973a1.47 1.47 0 0 1-.589.627q-.381.216-.917.216a1.86 1.86 0 0 1-.92-.216 1.46 1.46 0 0 1-.589-.627 2.15 2.15 0 0 1-.205-.973v-.513q0-.569.205-.975.205-.411.59-.627.386-.22.92-.22.535 0 .916.22.383.219.59.63.204.406.204.972M1 15.925v-3.999h1.459q.609 0 1.005.235.396.233.589.68.196.445.196 1.074 0 .634-.196 1.084-.197.451-.595.689-.396.237-.999.237zm1.354-3.354H1.79v2.707h.563q.277 0 .483-.082a.8.8 0 0 0 .334-.252q.132-.17.196-.422a2.3 2.3 0 0 0 .068-.592q0-.45-.118-.753a.9.9 0 0 0-.354-.454q-.237-.152-.61-.152Zm6.756 1.116q0-.373.103-.633a.87.87 0 0 1 .301-.398.8.8 0 0 1 .475-.138q.225 0 .398.097a.7.7 0 0 1 .273.26.85.85 0 0 1 .12.381h.765v-.073a1.33 1.33 0 0 0-.466-.964 1.4 1.4 0 0 0-.49-.272 1.8 1.8 0 0 0-.606-.097q-.534 0-.911.223-.375.222-.571.633-.197.41-.197.978v.498q0 .568.194.976.195.406.571.627.375.216.914.216.44 0 .785-.164t.551-.454a1.27 1.27 0 0 0 .226-.674v-.076h-.765a.8.8 0 0 1-.117.364.7.7 0 0 1-.273.248.9.9 0 0 1-.401.088.85.85 0 0 1-.478-.131.83.83 0 0 1-.298-.393 1.7 1.7 0 0 1-.103-.627zm5.092-1.76h.894l-1.275 2.006 1.254 1.992h-.908l-.85-1.415h-.035l-.852 1.415h-.862l1.24-2.015-1.228-1.984h.932l.832 1.439h.035z"/>
            </svg>
        </a>
        <!-- 下载按钮的小提示 -->
        <p class="ggl-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
            </svg> 如果你无法下载文档模板，请联系管理员！</p>
        <?php else: ?>
        <h2 class="text-center">请先登录以使用！</h2>
    <?php endif; ?>
</div>

<script src="js/bootstrap.bundle.min.js"></script>

<!-- JavaScript 重写 URL 参数 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 获取用户的 ID
    const userId = "<?php echo htmlspecialchars($_SESSION['id']); ?>"; // 从 PHP 变量中获取用户的 ID

    // 添加自定义参数，例如 home=id=用户的id
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

<?php
// 包含页尾文件
include 'footer.php';
?>
</body>
</html>