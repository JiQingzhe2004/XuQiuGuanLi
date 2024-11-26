<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = $_POST['servername'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $dbname = $_POST['dbname'];

    // 更新 config.php 文件
    $config_content = "<?php\n";
    $config_content .= "// 数据库配置\n";
    $config_content .= "\$servername = '$servername'; // 数据库服务器地址\n";
    $config_content .= "\$username = '$username'; // 数据库用户名\n";
    $config_content .= "\$password = '$password'; // 数据库密码\n";
    $config_content .= "\$dbname = '$dbname'; // 数据库名称\n";
    $config_content .= "?>\n";
    file_put_contents('config.php', $config_content);

    // 更新 includes/db.php 文件
    $db_content = "<?php\n";
    $db_content .= "// 数据库配置\n";
    $db_content .= "\$host = '$servername'; // 数据库地址\n";
    $db_content .= "\$dbname = '$dbname'; // 数据库名称\n";
    $db_content .= "\$user = '$username'; // 数据库用户名\n";
    $db_content .= "\$password = '$password'; // 数据库密码\n\n";
    $db_content .= "// 创建 PDO 连接\n";
    $db_content .= "try {\n";
    $db_content .= "    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8\", \$user, \$password);\n";
    $db_content .= "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
    $db_content .= "} catch (PDOException \$e) {\n";
    $db_content .= "    die(\"数据库连接失败：\" . \$e->getMessage());\n";
    $db_content .= "}\n";
    $db_content .= "?>\n";
    file_put_contents('includes/db.php', $db_content);


    // 检测数据库连接
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }

// 创建 users 表
$sql = "
create table if not exists users (
	id int(11) not null auto_increment,
	username varchar(255) not null,
	password varchar(255) not null,
	name varchar(255) not null,
	role varchar(50) not null,
	primary key (id)
)";
if ($conn->query($sql) === FALSE) {
	die("创建 users 表失败: " . $conn->error);
}

// 创建 requests 表
$sql = "
create table if not exists requests (
	id int(11) not null auto_increment,
	user_id int(11) not null,
	name varchar(255) not null,
	role varchar(50) not null,
	title varchar(255) not null,
	description text not null,
	document_path json null default null,
	image_path json null default null,
	created_at timestamp null default current_timestamp,
	processed tinyint(1) null default 0,
	deleted tinyint(1) null default 0,
	urgent enum('可延期', '一般', '紧急', '非常紧急') not null default '可延期',
	is_configured enum('是', '否') not null default '否',
	primary key (id)
)";
if ($conn->query($sql) === FALSE) {
	die("创建 requests 表失败: " . $conn->error);
}

// 创建 bugs 表
$sql = "
create table if not exists bugs (
	id int(11) not null auto_increment,
	user_id int(11) not null,
	name varchar(255) not null,
	role varchar(50) not null,
	title varchar(255) not null,
	description text not null,
	document_path json null default null,
	image_path json null default null,
	created_at timestamp null default current_timestamp,
	processed tinyint(1) null default 0,
	deleted tinyint(1) null default 0,
	primary key (id)
)";
if ($conn->query($sql) === FALSE) {
	die("创建 bugs 表失败: " . $conn->error);
}

// 创建 shares 表
$sql = "
create table if not exists shares (
	id int(11) not null auto_increment,
	share_type varchar(50) not null,
	share_id int(11) not null,
	shared_by int(11) not null,
	shared_at datetime not null,
	primary key (id)
)";
if ($conn->query($sql) === FALSE) {
	die("创建 shares 表失败: " . $conn->error);
}

// 插入初始用户数据
$sql = "
insert into users (id, username, password, name, role)
values
(1, 'admin', '\$2y\$10\$moyyyV1rEzz7yIwK32xVvuN9nppJG8BKohBUo/Vl4RI4Tkp.z3vwO', '管理员', 'admin')
on duplicate key update
username = values(username), password = values(password), name = values(name), role = values(role)
";
if ($conn->query($sql) === FALSE) {
	die("插入初始用户数据失败: " . $conn->error);
}


    echo "配置更新成功！5秒后跳转到首页...";
    echo "<div id='progress-container' class='progress mt-3'>
            <div id='progress-bar' class='progress-bar progress-bar-striped progress-bar-animated' role='progressbar' style='width: 0%'></div>
          </div>";
    echo "<script>
            var seconds = 5;
            var progressBar = document.getElementById('progress-bar');
            setInterval(function() {
                document.getElementById('countdown').innerText = seconds;
                progressBar.style.width = ((5 - seconds) / 5 * 100) + '%';
                seconds--;
                if (seconds < 0) {
                    window.location.href = 'index.php';
                }
            }, 1000);
          </script>";
    echo "<div id='countdown' class='mt-3'>5</div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>安装程序</title>
    <link href="path/to/logo.png" rel="icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /*背景图片*/
            background-image: url('path/to/install.png');
            background-size: cover;
            background-position: center;
        }
        .container {
            width: 50%;
            margin: 0 auto;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .progress {
            height: 30px;
        }
        .progress-bar {
            font-size: 16px;
            line-height: 30px;
        }
    </style>
</head>
<body class="bg-light text-dark">
<div class="container mt-5">
    <h1 class="mb-4">需求收集系统安装程序</h1>
    <form id="installForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="mb-3">
            <label for="servername" class="form-label">服务器名称</label>
            <input type="text" class="form-control" id="servername" name="servername" value="localhost" required>
        </div>
        <div class="mb-3">
            <label for="username" class="form-label">数据库用户名</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">数据库密码</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="dbname" class="form-label">数据库名称</label>
            <input type="text" class="form-control" id="dbname" name="dbname" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">安装</button>
    </form>
</div>

<!-- 模态框 -->
<div class="modal fade" id="progressModal" tabindex="-1" aria-labelledby="progressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="progressModalLabel">安装进度</h5>
            </div>
            <div class="modal-body">
                <div id="progress-container" class="progress">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="countdown" class="mt-3 text-center">5</div>
            </div>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // 显示模态框
    var progressModal = new bootstrap.Modal(document.getElementById('progressModal'), {
        backdrop: 'static',
        keyboard: false
    });

    // 在表单提交时显示模态框并通过 AJAX 处理表单数据
    document.getElementById('installForm').addEventListener('submit', function(event) {
        event.preventDefault();
        progressModal.show();

        $.ajax({
            type: 'POST',
            url: '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>',
            data: $(this).serialize(),
            success: function(response) {
                // 更新进度条和倒计时
                var seconds = 5;
                var progressBar = document.getElementById('progress-bar');
                setInterval(function() {
                    document.getElementById('countdown').innerText = seconds;
                    progressBar.style.width = ((5 - seconds) / 5 * 100) + '%';
                    seconds--;
                    if (seconds < 0) {
                        window.location.href = 'index.php';
                    }
                }, 1000);
            },
            error: function() {
                alert('安装失败，请重试。');
                progressModal.hide();
            }
        });
    });
</script>
<!-- JavaScript 重写 URL 参数 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 获取用户的 ID
    const userId = "<?php echo htmlspecialchars($_SESSION['id']); ?>"; // 从 PHP 变量中获取用户的 ID

    // 添加自定义参数，例如 home=id=用户的id
    const url = new URL(window.location);
    if (!url.searchParams.has('home')) {
        url.searchParams.append('home', `id=${userId}`);
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