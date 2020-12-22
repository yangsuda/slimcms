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

## 数据结构

Model层数据统一以Output对象形式返回，

通过getCode方法获取状态码，200为正确，其它为错误，如：$output->getCode()

通过withCode方法设置状态码，如：$output->withCode(24024)

通过getData方法获取返回值，如：$output->getData()

通过withData方法设置返回值，如：$output->withData(['value'=>'test'])

通过getTemplate方法获取模板，如：$output->getTemplate()

通过withTemplate方法设置设置解析的模板，如：$output->withTemplate('main/index')

通过getReferer方法获取来路URL，如：$output->getReferer()

通过withReferer方法设置来路URL，如：$output->withReferer('?p=test')

## 数据输出

共有5种输出方式

1、$this->view(),用于模板渲染加载输出

2、$this->directTo(),直接跳转，提示信息cookie保存，如需提示，通过cookie获取

3、$this->json(),返回json数据

4、$this->jsonCallback(),用于跨域请求

5、self::response(),根据请求的content-type返回相应的数据类型

## 数据接口

数据接口文档和数据接口根据后台表单开放接口权限自动生成，分“列表、展示、添加修改、删除、导出、表单结构、审核、统计”8种权限，
如不能满足需求，可自定义接口，数据统一以json格式返回，状态码200为正常，其它为错误

```bash
{
      "code": 200,
      "data": {},
      "msg": 操作成功
}  
```

## 前端web页面

前端web页面默认根据后台表单开放前端WEB功能权限自动生成，分“列表、展示、添加修改、删除、导出、表单结构、审核”7种权限，
对应列表、详细、编辑添加三种页面。

默认模板在template/forms/中，如自定义模板，先创建相应的文件夹，再根据formid创建相应的模板。

如自定义formid为1的模板，在template/forms/中创建dataList文件夹，在其中创建1.htm模板，系统会自动加载此模板，
如不能满足需求，可自定义开发

## 模板标签

模板中可直接加载参数
$code(状态码)、$msg(提示信息)、$referer(来路URL)、$data(数据)、$cfg(系统配置参数)

条件判断标签
```bash
{if ...}
...
{elseif ...}
...
{else}
....
{/if}
```
或
```bash
<!--{if ...}-->
...
<!--{elseif ...}-->
...
<!--{else}-->
....
<!--{/if}-->
```

遍历循环标签
```bash
{loop $XXX $k $v}
$v[...]
...
{/loop}
```
或
```bash
<!--{loop $XXX $k $v}-->
$v[...]
...
<!--{/loop}-->
```
注：$k也可以不写，写成{loop $XXX $v}

表单列表数据标签
```bash
{list fid=表单ID}
$v[...]
...
{/list}
```
更多标签说明具体参见Template.php

## apache伪静态规则
```bash
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
```

注：“XXX”为后台入口文件名称，请自行修改，此规则是在将根目录指向到“public/”目录的情况下的规则
另外，如果想开启URL加密，请先关闭伪静态开启状态
