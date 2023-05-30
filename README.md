[![Latest Stable Version](http://poser.pugx.org/yangsuda/slimcms/v)](https://packagist.org/packages/yangsuda/slimcms) [![Total Downloads](http://poser.pugx.org/yangsuda/slimcms/downloads)](https://packagist.org/packages/yangsuda/slimcms) [![Latest Unstable Version](http://poser.pugx.org/yangsuda/slimcms/v/unstable)](https://packagist.org/packages/yangsuda/slimcms) [![PHP Version Require](http://poser.pugx.org/yangsuda/slimcms/require/php)](https://packagist.org/packages/yangsuda/slimcms)

### 预览
| 列表     | 添加编辑    | 接口文档  |
| ------------- |:-------------:| --------------:|
| ![alt tag](https://www.cs090.com/uploads/userup/a/7004/2022/1A40FA0-96292c.jpg) | ![alt tag](https://www.cs090.com/uploads/userup/a/7004/2021/1625A0H3-Y13508.png) | ![alt tag](https://www.cs090.com/uploads/userup/a/7004/2022/1A40FJ4-19CI9.jpg) |

# 说明

基于Slim4、PSR-7和PHP-DI容器实现。

debug默认是开启的，生产环境下CORE_DEBUG建议改成false

生产环境下建议opcache扩展开启，本地测试性能会有近10倍的提升

数据库连接默认通过“mysql:host=XXX:XXX;”方式连接，有些情况下会连接不成功，可改成“mysql:host=XXX;port=XXX;”试试，
操作方式：将config/settings.php中的connecttype参数置为空

CORE_DEBUG关闭情况下，系统配置默认会生成文件：/data/CompiledContainer.php，在部分情况下更新系统配置时，此文件不会更新，遇到此问题时，可将此文件直接删除，系统会重新再次生成，或将init.php中的$containerBuilder->enableCompilation(CSDATA);注释掉

## 开发版下载

```bash
composer create-project yangsuda/slimcms [my-app-name] dev-master
```

将“[my app name]”替换为新应用程序所需的目录名。

建议将虚拟主机文档根目录指向新应用程序的“public/”目录。

## 安装配置
通过运行“public/install/”进行安装

## 路由规则

控制层：app/Control，Control中的文件夹名称由入口文件中CURSCRIPT常量决定，如：默认前端入口对应是main文件夹

URL通过?p=CLASS/METHOD方式访问控制层相应的方法，如没在相应类中找到相应方法会在当前文件夹中的DefaultControl.php中寻找，
如果还没找到，会到上级app/Control/"CURSCRIPT"/DefaultControl中寻找，如还没找到，继续到app/DefaultControl寻找，再找不到就报错

如：访问/?p=view/abc/test

会先找app/Control/main/view/AbcControl中test方法，如果没找到相应的方法，则会按上面说的一级级去找，如果找不到就报错

如：访问/?p=view/test

会先找app/Control/main/ViewControl中test方法，如果没找到相应的方法，则会按上面说的一级级去找，如果找不到就报错

如：访问/?p=test

会先找app/Control/main/TestControl中test方法，如果没找到相应的方法，则会按上面说的一级级去找，如果找不到就报错

## 数据获取

通过self::input()获取外部传参

或

self::inputInt获取强转为int类型的外部传参

self::inputFloat获取强转为float类型的外部传参

self::inputString获取强转为string类型的外部传参

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

2、$this->directTo(),直接跳转，提示信息存储在cookie中，如需显示，在模板中通过$data[errorCode]、$data[errorMsg]加载

3、$this->json(),返回json数据

4、$this->jsonCallback(),用于跨域请求

5、self::response(),根据请求的content-type返回相应的数据类型

## 功能插件

框架本身只提供基础构建，一些具体功能通过插件化实现，目前有接口插件、支付插件、文件管理插件、文件安全校验插件四个插件，后期会根据实际需要增加更多插件。

通过在插件市场下载安装，对于已经下载的插件可开启或关闭，卸载插件时会提示是否删除安装时添加的相应表，如果不删除相应表，再次安装时将会提示安装失败

## 数据接口

需要先安装接口插件，接口文档和数据接口通过后台表单开放接口权限控制，分“列表、展示、添加修改、删除、导出、表单结构、审核、统计”8种权限，
如不能满足需求，可自定义接口。

数据统一以json格式返回，状态码200为正常，其它为错误

```bash
{
      "code": 200,
      "data": {},
      "msg": 操作成功
}  
```


## 默认增删改查定制化
可通过在/app/Table文件夹中创建对应表的类文件来实现数据输入输出的定制化，格式为“表名Table.php”，（注：第一个字母要大写），如admin表，在文件夹中创建AdminTable.php文件

数据列表（forms/dataList）

可在上面的类文件中定义 dataListInit、dataListBefore、dataListAfter 方法来实现列表的前、中、后三个阶段的数据输入输出控制，（注：状态码返回200为成功，其它为报错），具体参见Forms.php文件，如：
```bash
public function dataListBefore(&$param): int
{
    return 200;
} 
```


数据保存（forms/dataSave）

可在上面的类文件中定义 dataSaveInit、dataSaveBefore、dataSaveAfter 方法来实现数据保存的前、中、后三个阶段的数据输入输出控制，（注：状态码返回200为成功，其它为报错），具体参见Forms.php文件，如：
```bash
public function dataSaveBefore(&$data, $row = [], $options = []): int
{
    return 200;
}
```

数据详细（forms/dataView）

可在上面的类文件中定义 dataViewBefore、dataViewAfter 方法来实现数据详细的前、后两个阶段的数据输入输出控制，（注：状态码返回200为成功，其它为报错），具体参见Forms.php文件，如：
```bash
public function dataViewAfter(&$data): int
{
    return 200;
}
```

数据删除（forms/dataDel）

可在上面的类文件中定义 dataDelBefore、dataDelAfter 方法来实现数据删除的前、后两个阶段的数据输入输出控制，（注：状态码返回200为成功，其它为报错），具体参见Forms.php文件，如：
```bash
public function dataDelBefore($data, $options = []): int
{
    return 200;
}
```

数据审核（forms/dataCheck）

可在上面的类文件中定义 dataCheckBefore、dataCheckAfter 方法来实现数据审核的前、后两个阶段的数据输入输出控制，（注：状态码返回200为成功，其它为报错），具体参见Forms.php文件，如：
```bash
public function dataCheckBefore($ids, $ischeck, $options): int
{
    return 200;
}
```

数据统计（forms/dataCount）

可在上面的类文件中定义 dataCountBefore 方法来实现数据统计的前的数据输入控制，（注：状态码返回200为成功，其它为报错），具体参见Forms.php文件，如：
```bash
public function dataCountBefore($param)
{
    return 200;
}
```

生成的表单HTML（forms/dataFormHtml）

可在上面的类文件中定义 getFormHtmlBefore、getFormHtmlAfter 方法来实现生成的表单HTML的前、后两个阶段的数据输入输出控制，（注：状态码返回200为成功，其它为报错），具体参见Forms.php文件，如：
```bash
public function getFormHtmlBefore($fields, $row, $form, $options)
{
    return 200;
}
```

数据导出（forms/dataExport）

可在上面的类文件中定义 dataExportBefore、dataExportAfter 方法来实现数据导出的前、后两个阶段的数据输入输出控制，（注：状态码返回200为成功，其它为报错），具体参见Forms.php文件，如：
```bash
public function dataExportBefore($condition, $result)
{
    return 200;
}
```


## 前端web页面

前端web页面默认根据后台表单开放前端WEB功能权限控制显示，分“列表、展示、添加修改、删除、导出、表单结构、审核”7种权限，
对应列表、详细、编辑添加三种页面。

默认模板在template/forms/中，如自定义模板，先创建相应的文件夹，再根据表单ID(fid)创建相应的模板。

如：自定义fid为1的列表模板，在template/forms/中创建dataList文件夹，在其中创建1.htm模板，系统会自动加载此模板，
如不能满足需求，可自行开发

## 模板标签

模板中可直接加载变量
$code(状态码)、$msg(提示信息)、$referer(来路URL)、$data(数据)、$cfg(系统配置参数)

如加载数组中某一元素，模板中可通过$data[xxx][xxx]...加载。

注：在某些情况下，会导致变量加载失败，如在汉字或字母中间加载某一变量，变量需用{}括起来，如：{$data[xxx][xxx]}

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

## 附件上传

默认是上传附件本地存储，
如想改成阿里云的OSS存储

1、加载阿里云的SDK
```bash
composer require aliyuncs/oss-sdk-php
```
2、修改调用参数$accessKeyId、$accessKeySecret等：app/Model/aliyun/AliOss.php

3、修改加载的上传类：app/Core/settings.php

将new Upload()改成new AliOss(),注意相应的引用要改一下

## 数据库分表

对于像日志类的表如果长时间积累可能会导致表数据非常庞大，影响执行效率，所以可以考虑分表操作。

具体操作：

在app/Table/文件夹中创建相应表的类文件，文件名称格式：表名+'Table.php'(表名第一个字母大写)，如:AdminlogTable.php

在构造函数中增加$this->subtable($tableName, $extendName);

如：
```bash
public function __construct(Request $request, string $tableName, string $extendName = null)
{
        //根据年份进行分表
        if (!isset($extendName)) {
            $extendName = date('Y');
            $this->subtable($tableName, $extendName);
        }
        parent::__construct($request, $tableName, $extendName);
}
```

以示例为例

数据库中将自动创建表adminlog2022

数据调用方式:self::t('adminlog')->...（默认调用当前年份数据），如需调用2021数据，:self::t('adminlog','2021')->...

## apache伪静态规则
```bash
RewriteEngine On
RewriteRule ^(.*)/(ueditor|resources|uploads|install)/(.*) - [L]
RewriteRule ^(.*)/(ueditor|resources|uploads|install)(.*) - [L]
RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w]+)/([\w]+)/([\w-.%`]+).html?$ $1/index.php?p=$2/$3/$4/$5&q=$6 [QSA,L]
RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w]+)/([\w]+)(/)?$ $1/index.php?p=$2/$3/$4/$5 [QSA,L]
RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w]+)/([\w-.%`]+).html?$ $1/index.php?p=$2/$3/$4&q=$5 [QSA,L]
RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w]+)(/)?$ $1/index.php?p=$2/$3/$4 [QSA,L]
RewriteRule ^(.*)/([\w]+)/([\w]+)/([\w-.%`]+).html?$ $1/index.php?p=$2/$3&q=$4 [QSA,L]
RewriteRule ^(.*)/([\w]+)/([\w]+)(/)?$ $1/index.php?p=$2/$3 [QSA,L]
RewriteRule ^(.*)/([\w]+)/([\w-.%`]+).html?$ $1/index.php?p=$2&q=$3 [QSA,L]
RewriteRule ^(.*)/([\w]+)(/)?$ $1/index.php?p=$2 [QSA,L]
```

注：此规则是在将根目录指向到“public/”目录的情况下的规则.
