<?php
// record_share.php
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '仅支持 POST 请求']);
    exit;
}

// 获取原始的 JSON 数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['share_type'], $data['share_id'], $data['shared_by'])) {
    echo json_encode(['success' => false, 'message' => '缺少必要参数']);
    exit;
}

$share_type = $data['share_type'];
$share_id = intval($data['share_id']);
$shared_by = intval($data['shared_by']);

// 验证用户是否已登录
if (!isset($_SESSION['id']) || $_SESSION['id'] !== $shared_by) {
    echo json_encode(['success' => false, 'message' => '未授权']);
    exit;
}

require_once 'includes/db.php'; // 引入数据库连接
include 'config.php';
include 'record_share_function.php'; // 包含上面定义的 recordShare 函数

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}

// 记录分享
$success = recordShare($conn, $share_type, $share_id, $shared_by);

$conn->close();

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '记录分享失败']);
}
?>