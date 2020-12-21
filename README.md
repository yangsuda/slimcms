# 说明

基于最新的Slim 4、PSR-7和PHP-DI容器实现。

目前还没发布稳定版，只有开发版

## 开发版下载

```bash
composer create-project yangsuda/slimcms [my-app-name] dev-master
```

将“[my app name]”替换为新应用程序所需的目录名。

建议将虚拟主机文档根目录指向新应用程序的“public/”目录。

## 安装配置
通过运行“public/install/”进行安装

## 路由规则

控制层：app/Control，Control中的文件夹名称由入口文件中CURSCRIPT常量决定，如默认前端入口对应是main文件夹

URL通过?p=CLASS/METHOD方式访问控制层相应的方法，如没在相应类中找到相应方法会在当前文件夹中的DefaultControl.php中寻找，
如果还没找到，会到上app/Control/"CURSCRIPT"/DefaultControl中寻找，如还没找到是到app/DefaultControl寻找，再找不到就报错

如访问/?p=view/abc/test

会先找app/Control/main/view/AbcControl中test方法，如果没找到再上面说的方式一级级去找，如果找不到就报错

如访问/?p=view/test

会先找app/Control/main/ViewControl中test方法，如果没找到再上面说的方式一级级去找，如果找不到就报错

如访问/?p=test

会先找app/Control/main/TestControl中test方法，如果没找到再上面说的方式一级级去找，如果找不到就报错

## 数据获取

通过self::input()获取外部传参

## 数据输出

共有5种输出方式

1、$this->view(),用于模板渲染加载输出

2、$this->directTo(),直接跳转，提示信息cookie保存，如需提示，通过cookie获取

3、$this->json(),返回json数据

4、$this->jsonCallback(),用于跨域请求

5、self::response(),根据请求的content-type返回相应的数据类型

## apache伪静态规则
RewriteEngine On

RewriteRule ^(.*)/(ueditor|resources|uploads|install)/(.*) - [L]

RewriteRule ^(.*)/(ueditor|resources|uploads|install)(.*) - [L]

RewriteRule ^(.*)/XXX/([\w\/]+)/([\w-.%`]+).html?$ $1/MQ5qCU.php?p=$2&q=$3 [QSA,L]

RewriteRule ^(.*)/XXX/([\w\/]+)(/)?$ $1/MQ5qCU.php?p=$2 [QSA,L]

RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w]+)/([\w]+)/([\w-.%`]+).html?$ $1/index.php?p=$2/$3/$4/$5&q=$6 [QSA,L]

RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w]+)/([\w]+)(/)?$ $1/index.php?p=$2/$3/$4/$5 [QSA,L]

RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w]+)/([\w-.%`]+).html?$ $1/index.php?p=$2/$3/$4&q=$5 [QSA,L]

RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w]+)(/)?$ $1/index.php?p=$2/$3/$4 [QSA,L]

RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w-.%`]+).html?$ $1/index.php?p=$2/$3&q=$4 [QSA,L]

RewriteRule ^(.*)/([\w]+)/([\w]+)(/)?$ $1/index.php?p=$2/$3 [QSA,L]

RewriteRule ^(.*)/([\w]+)/([\w-.%`]+).html?$ $1/index.php?p=$2&q=$3 [QSA,L]

RewriteRule ^(.*)/([\w]+)(/)?$ $1/index.php?p=$2 [QSA,L]

注：“XXX”为后台入口文件名称，请自行修改，此规则是在将根目录指向到“public/”目录的情况下的规则
另外，如果想开启URL加密，请先关闭伪静态开启状态
