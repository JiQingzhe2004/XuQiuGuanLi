<?php
// record_share_function.php

/**
 * 记录分享行为
 *
 * @param mysqli $conn 数据库连接对象
 * @param string $share_type 分享类型，'bug' 或 'request'
 * @param int $share_id 分享的BUG或需求的ID
 * @param int $shared_by 分享者的用户ID
 * @return bool 成功返回 true，失败返回 false
 */
function recordShare($conn, $share_type, $share_id, $shared_by) {
    // 验证分享类型
    $allowed_types = ['bug', 'request'];
    if (!in_array($share_type, $allowed_types)) {
        return false;
    }

    // 准备插入语句
    $stmt = $conn->prepare("INSERT INTO shares (share_type, share_id, shared_by, shared_at) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        error_log("准备语句失败: " . $conn->error);
        return false;
    }

    $stmt->bind_param("sis", $share_type, $share_id, $shared_by);
    $result = $stmt->execute();
    if (!$result) {
        error_log("执行语句失败: " . $stmt->error);
    }
    $stmt->close();
    return $result;
}
?>