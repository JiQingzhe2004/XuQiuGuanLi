<?php
// 确保只有管理员可以访问
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("权限不足。");
}

// 从配置文件获取当前版本
require_once 'version.php'; // 假设 $current_version 在 version.php 中定义

if (!isset($current_version)) {
    die("当前版本未定义。请检查 version.php 文件。");
}

// 获取最新版本
$latest_version_url = 'https://update.aiqji.cn/latest_version.txt';
$latest_version = @file_get_contents($latest_version_url);

if ($latest_version === false) {
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
$update_data = @file_get_contents($update_package_url);
if ($update_data === false) {
    die("下载更新包失败。");
}

$download = @file_put_contents($update_package_path, $update_data);
if (!$download) {
    die("保存更新包失败。");
}

// 创建临时目录
$temp_dir = __DIR__ . '/temp_update';
if (!file_exists($temp_dir)) {
    if (!mkdir($temp_dir, 0755, true)) {
        die("创建临时目录失败。");
    }
}

// 解压更新包
$zip = new ZipArchive;
if ($zip->open($update_package_path) === TRUE) {
    $zip->extractTo($temp_dir);
    $zip->close();
} else {
    die("解压更新包失败。");
}

// 获取解压后的 update_package 文件夹路径
$update_package_dir = $temp_dir . '/update_package';

// 检查 update_package 文件夹是否存在
if (!file_exists($update_package_dir)) {
    die("解压后的 update_package 文件夹不存在。");
}

// 函数：递归复制文件，排除 .git 目录
function recursiveCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                // 排除 .git 目录
                if ($file === '.git') {
                    continue;
                }
                recursiveCopy($src . '/' . $file, $dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file, $dst . '/' . $file);
                
                // 设置文件权限为 644
                chmod($dst . '/' . $file, 0644);
            }
        }
    }
    closedir($dir);
}

// 复制更新文件
recursiveCopy($update_package_dir, __DIR__);

// 删除临时目录和更新包
function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
        return;
    }
    $objects = scandir($dirPath);
    foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
            $filePath = $dirPath . DIRECTORY_SEPARATOR . $object;
            if (is_dir($filePath)) {
                deleteDir($filePath);
            } else {
                unlink($filePath);
            }
        }
    }
    rmdir($dirPath);
}

deleteDir($temp_dir);
unlink($update_package_path);

// 更新版本号
$version_file = __DIR__ . '/version.php';
$version_content = "<?php\n\$current_version = '{$latest_version}';\n?>";
if (file_put_contents($version_file, $version_content) === false) {
    die("无法写入版本号文件。");
}

echo "系统更新成功！当前版本：{$latest_version}";
?>