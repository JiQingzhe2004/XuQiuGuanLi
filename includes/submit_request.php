<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $document = $_FILES['document'];
    $image = $_FILES['image'];

    // 验证必填项
    if (empty($title) || empty($description)) {
        echo json_encode(['success' => false, 'message' => '标题和详细内容是必填项。']);
        exit;
    }

    // 处理文件上传
    $document_path = '';
    $image_path = '';

    if ($document['error'] == UPLOAD_ERR_OK) {
        $document_path = 'uploads/' . basename($document['name']);
        move_uploaded_file($document['tmp_name'], $document_path);
    }

    if ($image['error'] == UPLOAD_ERR_OK) {
        $image_path = 'uploads/' . basename($image['name']);
        move_uploaded_file($image['tmp_name'], $image_path);
    }

    // 保存到数据库
    $stmt = $conn->prepare("INSERT INTO requirements (username, title, description, document_path, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $title, $description, $document_path, $image_path);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '需求提交成功！']);
    } else {
        echo json_encode(['success' => false, 'message' => '需求提交失败，请重试。']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => '无效的请求方法。']);
}
?>