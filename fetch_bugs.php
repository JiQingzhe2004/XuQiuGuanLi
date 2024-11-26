<?php
require_once 'includes/db.php'; // 引入封装的数据库操作函数
include 'config.php';

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);

session_start();

// 从会话中读取用户ID和角色
$id = $_SESSION['id'];
$role = $_SESSION['role'];

// 获取当前日期
$current_date = date('Y-m-d');

// 处理日期过滤
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : $current_date;

// 获取需求信息
if ($role === 'admin') {
    // 管理员查看所有未删除的需求
    $request_query = "SELECT id, title, name, created_at, processed FROM bugs WHERE deleted = 0 AND DATE(created_at) = ? ORDER BY processed ASC, created_at DESC";
    $stmt = $conn->prepare($request_query);
    $stmt->bind_param("s", $filter_date);
} else {
    // 普通用户查看自己的未删除的需求
    $request_query = "SELECT id, title, name, created_at, processed FROM bugs WHERE user_id = ? AND deleted = 0 AND DATE(created_at) = ? ORDER BY processed ASC, created_at DESC";
    $stmt = $conn->prepare($request_query);
    $stmt->bind_param("is", $id, $filter_date);
}

$stmt->execute();
$request_result = $stmt->get_result();
$stmt->close();

// 检查查询是否成功
if ($request_result === false) {
    echo "查询失败: " . $conn->error;
    exit;
}

// 输出需求信息
$bugs = [];
while ($request = $request_result->fetch_assoc()) {
    $bugs[] = $request;
}

echo json_encode($bugs);

$conn->close();
?>