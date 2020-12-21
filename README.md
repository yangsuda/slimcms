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

##apache伪静态规则
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
