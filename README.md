# SlimCMS

[![Latest Stable Version](http://poser.pugx.org/yangsuda/slimcms/v)](https://packagist.org/packages/yangsuda/slimcms) [![Total Downloads](http://poser.pugx.org/yangsuda/slimcms/downloads)](https://packagist.org/packages/yangsuda/slimcms) [![PHP Version Require](http://poser.pugx.org/yangsuda/slimcms/require/php)](https://packagist.org/packages/yangsuda/slimcms)

基于 **Slim 4 + PSR-7 + PHP-DI 容器** 的轻量级 CMS / API 框架，提供路由、中间件、表单、模板、附件、插件等基础构建能力，业务功能以插件形式组装。

### 预览

| 列表     | 添加编辑    | 接口文档  |
| ------------- |:-------------:| --------------:|
| ![alt tag](https://www.cs090.com/uploads/userup/a/7004/2022/1A40FA0-96292c.jpg) | ![alt tag](https://www.cs090.com/uploads/userup/a/7004/2021/1625A0H3-Y13508.png) | ![alt tag](https://www.cs090.com/uploads/userup/a/7004/2022/1A40FJ4-19CI9.jpg) |

---

## 一、环境要求

- PHP **>= 8.1**（框架在 DTO 中使用了 PHP 8.1 引入的 `readonly` 属性，需 8.1+）
- 扩展：`ext-curl`、`ext-json`、`ext-redis`
- Composer
- Web 服务器：Nginx / Apache（建议 Nginx）
- 可选：MySQL/MariaDB、Redis（生产建议）

完整依赖见 `composer.json`：

```json
"require": {
    "php": "^8.1",
    "ext-curl": "*",
    "ext-json": "*",
    "ext-redis": "*",
    "respect/validation": "^2.2",
    "vlucas/phpdotenv": "^5.6",
    "yangsuda/framework": "^6.0"
}
```

---

## 二、快速开始

### 1. 安装

```bash
# 通过 Composer 创建项目（6.x 分支）
composer create-project yangsuda/slimcms my-app

cd my-app
```

将虚拟主机文档根目录指向 `my-app/public/` 目录，所有请求通过 `public/index.php` 入口。

> **生产环境建议**：开启 OPcache

### 2. 环境配置

项目根目录的 `.env` 文件用于存放环境变量

`.env` 中可配置以下分组（详见文件内注释）：

| 分组 | 关键变量 |
| --- | --- |
| 应用 | `APP_ENV`、`APP_DEBUG` |
| 数据库 | `DB_HOST`、`DB_PORT`、`DB_NAME`、`DB_USER`、`DB_PASSWORD`、`DB_CHARSET`、`DB_TABLEPRE`、`DB_CONNECTTYPE`、`DB_PCONNECT` |
| Redis | `REDIS_HOST`、`REDIS_PORT`、`REDIS_PASSWORD`、`REDIS_DATABASE`、`REDIS_PREFIX`、`REDIS_PCONNECT` |
| 缓存 | `CACHE_TYPE`（默认 file） |
| Session | `SESSION_DOMAIN`、`SESSION_LIFETIME`、`CSRF_TOKEN_EXPIRE` |
| Cookie | `COOKIE_PREFIX`、`COOKIE_DOMAIN`、`COOKIE_PATH` |
| 上传 | `UPLOAD_DIR`、`UPLOAD_DIR_RULE`、`UPLOAD_FORBID_FILE`、`UPLOAD_CHECK_WORDS` |
| 安全 | `AUTH_KEY`、`ENCRYPT_KEY`、`ENCRYPT_IV`、`ATTACK_EVASIVE` |
| CORS | `CORS_ALLOW_ORIGIN`（逗号分隔白名单） |
| 输出 | `OUTPUT_GZIP`、`MEMORY_PREFIX` |

> `config/settings.php` 内部读取 `$_ENV` 并组装为 `settings` 配置项；如需新增/覆盖键，可直接修改该文件。
> `data/ConfigCache.php` 由系统生成，存放后台"系统配置"中的运行期参数（如 `cfg.basehost`、`cfg.imgtype` 等），请勿手动维护。

### 3. 安装系统

通过浏览器访问 `http://你的域名/install/`，按向导完成数据库连接、管理员账号等初始化。

安装完成后建议删除或限制访问 `public/install/` 目录。

---

## 三、目录结构

```
slimcms/
├── app/                   应用代码（PSR-4 命名空间 app\）
│   ├── Controller/        控制器（main / admin / plugin 三个子模块）
│   ├── Core/              容器配置（settings.php）
│   ├── Middleware/        中间件（Session、AdminAuth、Csrf、Cors、ErrorLog…）
│   ├── Model/             模型层（含校验器 vali/）
│   ├── Repository/        仓储层（基于 Table）
│   ├── routes/            路由配置（main.php / admin.php / plugin/*.php）
│   ├── Service/           业务服务（admin/、aliyun/、common/、plugin/）
│   ├── Table/             数据表层（含表分片支持）
│   └── init.php           应用引导入口
├── config/                静态配置（settings.php）
├── data/                  运行时数据（缓存、会话、日志、插件、安装包…）
├── public/                Web 入口（index.php、install/、resources/、uploads/、ueditor/）
├── template/              模板文件（.htm / .html）
├── vendor/                Composer 依赖
├── composer.json
└── .env / .env.example    环境变量
```

---

## 四、路由

### 1. 路由注册

路由文件位于 `app/routes/`：

- `main.php` — 前台路由
- `admin.php` — 后台路由（整体位于 `/admin` 分组下，挂载 `SessionMiddleware`）
- `plugin/*.php` — 插件自动加载的路由文件

通过 `SlimCMS\Core\RouteAction`（简称 `Route`）门面注册，类似 ThinkPHP / Laravel 风格：

```php
use SlimCMS\Core\RouteAction as Route;

Route::get('/path',  'Controller@method');   // GET
Route::post('/path', 'Controller@method');   // POST
Route::put('/path',  'Controller@method');   // PUT
Route::delete('/path','Controller@method');  // DELETE
Route::patch('/path','Controller@method');   // PATCH
Route::gp('/path',   'Controller@method');   // GET + POST
Route::any('/path',  'Controller@method');   // 所有方法
Route::map(['GET','POST'], '/path', 'Controller@method'); // 自定义方法

// 路由分组，支持链式挂载中间件
Route::group('/admin', function () {
    Route::gp('/login', 'admin\LoginController@login');
    Route::group('', function () {
        Route::get('/index', 'admin\MainController@index');
        Route::gp('/{path1}/{path2}'); // 动态路由参数
    })->add(AdminAuthMiddleware::class)->add(CsrfMiddleware::class);
})->add(SessionMiddleware::class);
```

> - 控制器字符串 `模块\类名Controller@方法名` 会自动补全为完整类名 `app\Controller\模块\类名Controller`。
> - 动态参数占位符 `{path1}`、`{path2}` 会自动传入控制器方法（如未在方法签名声明则被忽略）。

### 2. 路由示例

`app/routes/main.php`（前台）：

```php
Route::get('/',         'main\MainController@index');
Route::get('/captcha',  'main\MainController@captcha');
```

`app/routes/admin.php`（后台）：

```php
Route::group('/admin', function () {
    // 无需登录即可访问
    Route::gp('/login',     'admin\LoginController@login');
    Route::get('/logout',    'admin\LoginController@logout');
    Route::get('/captcha',   'main\MainController@captcha');
    Route::get('/enumsData', 'main\MainController@enumsData');

    // 需要登录与 CSRF 校验
    Route::group('', function () {
        Route::get('/index',     'admin\MainController@index');
        Route::gp('/updatePwd',  'admin\MainController@updatePwd');
        Route::gp('/ueditor',    'admin\UeditorController@ueditor');
        Route::gp('/{path1}/{path2}');  // 通用后台操作
    })->add(AdminAuthMiddleware::class)->add(CsrfMiddleware::class);
})->add(SessionMiddleware::class);
```

### 3. 中间件

内置中间件（`app/Middleware/`）：

| 中间件 | 作用 |
| --- | --- |
| `SessionMiddleware` | Session 启动与管理（后台路由组统一挂载） |
| `AdminAuthMiddleware` | 后台登录态校验 |
| `CsrfMiddleware` | CSRF Token 校验 |
| `CorsMiddleware` | 跨域处理（白名单来自 `.env` 的 `CORS_ALLOW_ORIGIN`） |
| `ErrorLogMiddleware` | 异常日志记录 |
| `Middleware` | 通用处理（最先注册，最内层执行） |

`app/init.php` 中已挂载顺序为：

```php
$app->add(Middleware::class)
    ->add(ErrorLogMiddleware::class)
    // ... 路由注册 ...
    ->add(CorsMiddleware::class); // 跨域处理
```

---

## 五、控制器与数据访问

### 1. 控制器规范

控制器位于 `app/Controller/` 下，按模块分子目录：

- `app/Controller/main/` — 前台
- `app/Controller/admin/` — 后台
- `app/Controller/plugin/` — 插件

类名以 `Controller` 后缀结尾，继承 `SlimCMS\Abstracts\ControlAbstract`（或 `app\Controller\admin\AdminController`）。

```php
namespace app\Controller\main;

use Psr\Http\Message\ResponseInterface;
use Slim\App;
use SlimCMS\Abstracts\ControlAbstract;

class MainController extends ControlAbstract
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function index(): ResponseInterface
    {
        return $this->view($this->output, 'index');
    }
}
```

### 2. 数据获取（外部传参）

控制器继承自 `ControlAbstract`，提供如下方法：

| 方法 | 用途 |
| --- | --- |
| `$this->input($name, $type = 'string')` | 获取外部传参；支持单值或 `['name' => 'int']` 批量数组 |
| `$this->inputInt($name): int` | 强制 int |
| `$this->inputFloat($name): float` | 强制 float |
| `$this->inputString($name): string` | 强制 string |

底层来自 `app/Core/Request.php`（容器中由 `Request::class` 提供），请求参数会先经违禁词过滤（`cfg.notallowstr`、`cfg.replacestr`）。

```php
$id   = $this->inputInt('id');
$name = $this->inputString('name');
$data = $this->input(['page' => 'int', 'size' => 'int', 'kw' => 'string']);
```

### 3. 输出对象 `Output`

仓储层返回统一的 `SlimCMS\Interfaces\OutputInterface`（默认实现 `SlimCMS\Core\Output`）。常用方法：

| 方法 | 说明 |
| --- | --- |
| `$output->getCode(): int` | 获取状态码，**200** 表示成功 |
| `$output->withCode(int $code, $param = []): self` | 设置状态码，可传占位符参数（用于错误码文案） |
| `$output->getData(): array` | 获取业务数据 |
| `$output->withData(array $data): self` | 设置业务数据 |
| `$output->getTemplate(): string` | 获取模板名 |
| `$output->withTemplate(string $template): self` | 设置要渲染的模板 |
| `$output->getReferer(): string` | 获取跳转 URL |
| `$output->withReferer(string $url): self` | 设置跳转 URL |
| `$output->getMsg(): string` | 获取提示文本 |

### 4. 数据输出方式

控制器提供 4 种内置输出方式：

| 方法 | 用途 |
| --- | --- |
| `$this->view($output, $template = '')` | 模板渲染并输出 HTML；模板为空时按当前路由路径自动匹配 |
| `$this->directTo($output)` | 携带 `errorCode`/`errorMsg` flash 跳转到 `$output->getReferer()` |
| `$this->json($output)` | 以 JSON 输出（自动 `JSON_UNESCAPED_UNICODE`） |
| `$this->resp($output, $template = '')` | 根据请求 `Accept` 自动选择 HTML / JSON 输出 |

```php
// 模板渲染
return $this->view($this->output, 'admin/index');

// JSON 返回
return $this->json($this->output->withData(['list' => $list]));

// 重定向
return $this->directTo($res->withReferer('/admin/index'));

// 自动协商（推荐）
return $this->resp($this->output->withData($data), 'admin/form');
```

返回结构示例：

```json
{
  "code": 200,
  "msg":  "操作成功",
  "data": {}
}
```

---

## 六、表单（Form）服务

v6.0 表单相关逻辑拆分到一组服务中（`SlimCMS\Core\Form\*`），通过容器接口注入：

| 接口 | 实现 |
| --- | --- |
| `FormSchemaServiceInterface` | 表单/字段结构维护 |
| `FormQueryServiceInterface` | 列表查询 |
| `FormWriteServiceInterface` | 新增/更新/删除 |
| `FormViewRendererInterface` | 表单渲染 |
| `FormExportServiceInterface` | 导出（依赖 phpoffice/phpspreadsheet） |
| `TableHookDispatcher` | 表钩子分发器 |
| `OrderValidator` | 排序字段校验 |

控制器中通过构造函数注入即可使用，无需手动 `new`。

---

## 七、模板标签

模板位于 `template/` 目录，由 `SlimCMS\Core\Template` 解析。可直接加载以下变量：

- `$code` — 状态码
- `$msg` — 提示信息
- `$referer` — 来源 URL
- `$data` — 业务数据（关联数组）
- `$cfg` — 后台配置参数

**注意**：如果变量紧邻汉字/字母，可能导致解析失败，请用 `{}` 包裹：

```htm
{$data[xxx][xxx]}
```

### 1. 条件判断

```htm
{if $data['status'] == 1}
  <span>已发布</span>
{elseif $data['status'] == 0}
  <span>草稿</span>
{else}
  <span>未知</span>
{/if}
```

或使用 HTML 注释风格：

```htm
<!--{if $data['status'] == 1}-->
...
<!--{/if}-->
```

### 2. 遍历循环

```htm
{loop $data['list'] $k $v}
  <li>{$k} - {$v['title']}</li>
{/loop}
```

`$k` 可省略：`{loop $data['list'] $v}`。

### 3. 表单列表数据

```htm
{list fid=表单ID}
  <tr>{$v[...]}</tr>
{/list}
```

更多标签请参考 `vendor/yangsuda/framework/src/Core/Template.php`。

---

## 八、附件上传

默认使用本地存储（`SlimCMS\Core\Upload`）。可通过 `UploadInterface` 替换实现。

### 切换为阿里云 OSS

1. 安装 SDK（当前项目未默认安装，需手动引入）：
   ```bash
   composer require aliyuncs/oss-sdk-php
   ```

2. 在 `app/Service/aliyun/AliOssService.php` 中填写 `$accessKeyId`、`$accessKeySecret`、`$endpoint`、`$bucket`。

3. 在 `app/Core/settings.php` 中将 `UploadInterface::class => autowire(Upload::class)` 替换为 `AliOssService::class`：
   ```php
   use app\Service\aliyun\AliOssService;
   use SlimCMS\Interfaces\UploadInterface;
   // ...
   UploadInterface::class => autowire(AliOssService::class),
   ```

### 相关配置（`.env`）

- `UPLOAD_DIR` — 上传根目录（默认 `/uploads/`，相对 `public/`）
- `UPLOAD_DIR_RULE` — 子目录规则，支持 `{Y}`、`{m}`、`{d}` 占位符，默认 `{Y}/{m}`
- `UPLOAD_FORBID_FILE` — 禁止的扩展名（`|` 分隔）
- `UPLOAD_CHECK_WORDS` — 文件内容黑名单关键字

---

## 九、数据库与表分片

### 1. 配置

数据库连接通过 `.env` + `config/settings.php` 三层装配：

- `DB_CONNECTTYPE` — 默认 `;port=`，如需自定义连接串（如 `:port=`），修改 `.env`。
- 数据库连接失败提示 `connecttype` 时，可将 `DB_CONNECTTYPE` 置空字符串，使用 `mysql:host=XXX:port=XXX;` 形式。
- `DB_TABLEPRE` — 表前缀，框架会自动加到所有 `Table` 类操作的表名上。

### 2. 表分片

对于日志类大表，可按时间分表。

在 `app/Table/` 下新建 `XxxTable.php`，重写 `setTableName`：

```php
<?php
declare(strict_types=1);

namespace app\Table;

use SlimCMS\Core\Form\TableHookInterface;
use SlimCMS\Core\Table;

class AdminlogTable extends Table implements TableHookInterface
{
    public function setTableName(string $tableName, string $extendName = null): self
    {
        // 类名自动推导表名（不含 Table 后缀）
        $tableName = strtolower(substr(pathinfo(__CLASS__, PATHINFO_FILENAME), 0, -5));

        // 默认为当前年份分表，调用方也可显式传入
        if (!isset($extendName)) {
            $extendName = date('Y');
        }
        return parent::setTableName($tableName, $extendName);
    }
}
```

按上述示例，系统将自动使用 `adminlog2026`（或 `adminlog2025`）等表。

---

## 十、插件机制

框架仅提供基础构建，具体业务以插件形式存在，存放在 `data/plugins/` 下：

- 通过后台"插件市场"下载、安装、启用/停用、卸载；
- 卸载时可选择是否删除插件安装时新增的数据表，保留表可避免重复安装失败；
- 启用后插件路由由 `app/routes/plugin/*.php` 自动加载，控制器位于 `app/Controller/plugin/`。

---

## 十一、运维与缓存

### 1. OPcache（生产必开）

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=192
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0   ; 配合部署流程重载
```

### 2. PHP-DI 容器编译缓存

`app/init.php` 在 `APP_DEBUG=false` 时调用：

```php
$containerBuilder->enableCompilation(CSDATA);
```

编译缓存生成在 `data/` 目录下（如 `CompiledContainer.php`）。如修改容器定义后未生效：

- 删除 `data/CompiledContainer*.php`，由系统在下次请求时重建；
- 或在 `app/init.php` 中注释掉 `enableCompilation` 调用；
- `data/ConfigCache.php` 是后台"系统配置"生成的运行期参数缓存，可在后台"清空缓存"中刷新。

### 3. 路由兜底

未匹配到任何路由时，会由 `Main\MainController@notFound` 接管：

- `Accept: application/json` → 返回 JSON `code=21009`；
- 其它 → 渲染 `template/error.htm`。

---

## 十二、Nginx 配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/slimcms/public;
    index index.php index.html;

    location / {
        autoindex off;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 禁止访问敏感目录（可选）
    location ~ ^/(install|data|vendor|cli|tests)/ {
        deny all;
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }
}
```

> Apache 用户请使用等价的 `mod_rewrite` 规则将所有请求重写到 `index.php`。
