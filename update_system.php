<?php
// 确保只有管理员可以访问
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("权限不足。");
}

// 从配置文件获取当前版本
require_once 'version.php'; // 假设$current_version在config.php中定义

// 当前版本
if (!isset($current_version)) {
    die("当前版本未定义。请检查config.php文件。");
}

// 获取最新版本
$latest_version_url = 'https://update.aiqji.cn/latest_version.txt';
$latest_version = @file_get_contents($latest_version_url);

if ($latest_version === FALSE) {
    die("无法获取最新版本信息。");
}

$latest_version = trim($latest_version);

// 比较版本
if (version_compare($latest_version, $current_version, '<=')) {
    die("当前已是最新版本。");
}

// 获取更新包下载链接
$update_package_url = 'https://update.aiqji.cn/update_package.zip';

// 下载更新包
$update_package_path = __DIR__ . '/update_package.zip';
$download = @file_put_contents($update_package_path, file_get_contents($update_package_url));
if (!$download) {
    die("下载更新包失败。");
}

// 解压更新包
$zip = new ZipArchive;
if ($zip->open($update_package_path) === TRUE) {
    // 解压到临时目录
    $temp_dir = __DIR__ . '/temp_update';
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    $zip->extractTo($temp_dir);
    $zip->close();
    
    // 替换文件
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($temp_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if ($file->isDir()) continue;
        
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($temp_dir) + 1);
        $destPath = __DIR__ . '/' . $relativePath;
        
        // 创建目标目录
        $destDir = dirname($destPath);
        if (!file_exists($destDir)) {
            mkdir($destDir, 0755, true);
        }
        
        // 替换文件
        if (!copy($filePath, $destPath)) {
            // 记录日志或处理错误
            echo "无法替换文件: " . htmlspecialchars($relativePath);
            // 可选择继续或中断
        }
    }
    
    // 删除临时目录
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($temp_dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    rmdir($temp_dir);
    
    // 删除更新包
    unlink($update_package_path);
    
    // 更新当前版本
    // 假设在config.php中定义$current_version
    // 可以通过写入到config.php或其他方式更新版本
    // 示例（请确保config.php具有写入权限和正确的格式）：
    $config_file = __DIR__ . '/version.php';
    
    // 创建临时文件
    $temp_config_file = tempnam(sys_get_temp_dir(), 'version');
    
    $new_config = "<?php\n\$current_version = '{$latest_version}';\n?>";
    if (file_put_contents($temp_config_file, $new_config) === false) {
        die("无法写入临时 version 文件");
    }
    
    // 使用临时文件替换目标文件
    if (!rename($temp_config_file, $config_file)) {
        die("无法替换 $config_file 文件");
    }
    
    echo "系统更新成功！当前版本：{$latest_version}";
    // 可选：重启服务或进行其他操作
} else {
    unlink($update_package_path);
    die("解压更新包失败。");
}
?>