# 需求管理系统

### 一、应用功能

1、应用的定位为网页应用，为了实现需求提交与处理的记录，方便团队记录需求，避免出现“遗漏处理、没有看到”等问题。

2、代码为PHP编写，实现了基本的“需求提交、BUG提交、文件上传、图片上传、文件照片的预览、角色分类”等功能，

### 二、应用安装环境：

​	操作系统：Linux或WindowsSever

​	运行环境：PHP7.4及以上

​	数据库：MySQL5.7及以上

​	安装方法：使用Git拉取代码，拉取地址：

```git
git clone https://gitee.com/jiqingzhe/XuQiuGuanLi.git
```

拉取代码后，访问：域名/install.php或 IP/install.php

然后输入MySQL数据库对应的信息，点击安装即可，安装完成后自动跳转至首页

打开服务器，cd到网站根目录，更改uploads文件夹的权限为777

```linux
sudo chmod 777 uploads
```
### 三、使用说明

1、初始管理员账号：admin

​	初始管理员密码：123

2、除分享界面外，所有页面均需要登录访问，分享界面需要在用户分享后才有链接，其他链接访问无效。

3、所有账户的用户名均不可以重复，用户名和姓名不同，登陆时需要使用用户名。

4、用户角色分为管理员和普通用户，普通用户查看需求和BUG时，只能看到自己提交的，管理员则可以看到所有。

5、如需要数据测试，访问：域名/pl.php或 IP/pl.php  ，此页面也需要登录访问，且身份为管理员。

6、如需更改导航栏图标与文字，图片请到根目录下的path/to文件夹下替换logo.png，文字请到网站根目录的header.php文件内第288行进行更改；首页登陆前显示的文字大标题，请到网站根目录的index.php文件内第50行进行更改；底部页尾，请到网站根目录footer.php文件内更改链接或文字。

7、如需更换首页模板下载文件，请将文件上传至

### 四、代码目录

点击查看：[代码目录](list.txt)
            



###### V1.0.0 版本内容更新

新功能：分享功能，实现了用户分享后，未登录用户也可以查看。

###### V1.0.8 版本内容更新

修复部分功能的BUG，添加一键更新功能。