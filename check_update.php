<?php
header('Content-Type: application/json');

// 当前版本
$current_version = '1.0.0';

// 获取最新版本（假设从远程服务器获取）
$latest_version_url = 'https://update.aiqji.cn/latest_version.txt';
$latest_version = @file_get_contents($latest_version_url);

if ($latest_version === FALSE) {
    echo json_encode(['error' => '无法获取最新版本信息']);
    exit;
}

// 清理版本号
$latest_version = trim($latest_version);

// 比较版本
if (version_compare($latest_version, $current_version, '>')) {
    echo json_encode(['update_available' => true, 'latest_version' => $latest_version]);
} else {
    echo json_encode(['update_available' => false]);
}
?>