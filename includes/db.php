<?php
// 数据库配置
$host = ''; // 数据库地址
$dbname = ''; // 数据库名称
$user = ''; // 数据库用户名
$password = ''; // 数据库密码

// 创建 PDO 连接
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("数据库连接失败：" . $e->getMessage());
}
?>
