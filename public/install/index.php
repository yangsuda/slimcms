<?php
define('SLIMCMSINSTALL', str_replace("\\", '/', dirname(__FILE__)));
define('SLIMCMSROOT', str_replace("\\", '/', substr(SLIMCMSINSTALL, 0, -7)));

function writeTest($dir)
{
    $tfile = $dir . 'mtext.txt';
    $fp = @fopen($tfile, 'w');
    if (!$fp) {
        return false;
    }
    fclose($fp);
    $rs = @unlink($tfile);
    if ($rs) {
        return true;
    }
    return false;
}

function input($k)
{
    if (isset($_POST[$k])) {
        $var = &$_POST;
    } elseif (isset($_GET[$k])) {
        $var = &$_GET;
    }
    return isset($var[$k]) ? $var[$k] : NULL;
}

function random($length, $numeric = 0)
{
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    if ($numeric) {
        $hash = '';
    } else {
        $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
        $length--;
    }
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed[mt_rand(0, $max)];
    }
    return $hash;
}

$step = max(1, (int)input('step'));
if ($step == 1) {
    $filesize = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow';
    $tmp = function_exists('gd_info') ? gd_info() : array();
    $gd = empty($tmp['GD Version']) ? 'noext' : $tmp['GD Version'];
    unset($tmp);
    $disksize = function_exists('disk_free_space') ? floor(disk_free_space(SLIMCMSROOT) / (1024 * 1024)) . 'M' : 'unknow';
    $frameworkExist = is_file(SLIMCMSROOT . '../vendor/yangsuda/framework/src/function/Core.php');

    $dirs = ['../uploads/', '../../config/', '../../data/', '../../data/template/', '../../data/sessions/'];
    $isok = true;
    include('./template/step_' . $step . '.htm');
    exit();
} elseif ($step == 2) {
    if (input('check') != 'ok') {
        exit("步骤1检测不通过");
    }
    $_SERVER["REQUEST_SCHEME"] = !empty($_SERVER["REQUEST_SCHEME"]) ? $_SERVER["REQUEST_SCHEME"] : 'http';
    include('./template/step_' . $step . '.htm');
    exit();
} elseif ($step == 3) {
    $dbhost = input('dbhost');
    $dbport = input('dbport');
    $dbuser = input('dbuser');
    $dbpwd = input('dbpwd');
    $dbprefix = input('dbprefix');
    $dbname = input('dbname');
    $adminuser = input('adminuser');
    $adminpwd = input('adminpwd');
    $dbport = !empty($dbport) ? $dbport : 3306;
    $options = array();
    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary, sql_mode=\'\'';
    try {
        $link = new PDO('mysql:host=' . $dbhost . ':' . $dbport . ';', $dbuser, $dbpwd, $options);
    } catch (Exception $exc) {
        exit('数据库连接失败，请重新设置！');
    }
    $link->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    //获得数据库版本信息
    $query = $link->prepare('SELECT VERSION();');
    $query->execute();
    $version = $query->fetchColumn(0);
    if (version_compare($version, '5.0.1') < 0) {
        exit('MYSQL版本要大于5.0.1');
    }
    $query->closeCursor();

    $query = $link->query("CREATE DATABASE IF NOT EXISTS `" . $dbname . "`;");
    try {
        $link = new PDO('mysql:host=' . $dbhost . ':' . $dbport . ';dbname=' . $dbname, $dbuser, $dbpwd, $options);
    } catch (Exception $exc) {
        exit('选择数据库失败，可能是你没权限，请先创建一个数据库！');
    }

    $app_env = input('app_env');
    $app_debug = input('app_debug');
    $redis_host = input('redis_host');
    $redis_port = input('redis_port');
    $redis_pwd = input('redis_pwd');
    $redis_database = input('redis_database');
    $redis_prefix = input('redis_prefix');
    $cookie_prefix = random(5) . '_';
    $auth_key = random(21);
    $encrypt_key = random(8);
    $encrypt_iv = random(8);

    $code = <<<EOT
# 应用配置
APP_ENV={$app_env}
APP_DEBUG={$app_debug}

# 数据库配置
DB_HOST={$dbhost}
DB_PORT={$dbport}
DB_NAME={$dbname}
DB_USER={$dbuser}
DB_PASSWORD={$dbpwd}
DB_CHARSET=utf8mb4
DB_TABLEPRE={$dbprefix}
DB_CONNECTTYPE=:
DB_PCONNECT=0

# Redis配置
REDIS_HOST={$redis_host}
REDIS_PORT={$redis_port}
REDIS_PASSWORD={$redis_pwd}
REDIS_DATABASE={$redis_database}
REDIS_PREFIX={$redis_prefix}
REDIS_PCONNECT=0
REDIS_TIMEOUT=0
REDIS_SERIALIZER=1

# 缓存配置
CACHE_TYPE=file

# Session配置
SESSION_DOMAIN=
SESSION_LIFETIME=0

# Cookie配置
COOKIE_PREFIX={$cookie_prefix}
COOKIE_DOMAIN=
COOKIE_PATH=/

# 上传配置
UPLOAD_DIR=/uploads/
UPLOAD_DIR_RULE={Y}/{m}
UPLOAD_FORBID_FILE=php|pl|cgi|asp|aspx|jsp|php3|shtm|shtml|js
UPLOAD_CHECK_WORDS=

# 安全配置
AUTH_KEY={$auth_key}
ENCRYPT_KEY={$encrypt_key}
ENCRYPT_IV={$encrypt_iv}
ATTACK_EVASIVE=0
CSRF_TOKEN_EXPIRE=3600

# 输出配置
OUTPUT_GZIP=0

# 内存缓存前缀
MEMORY_PREFIX=b7H6Sa_
EOT;
    file_put_contents('../../.env', $code) or exit('配置文件创建失败，请检查根目录是否可写入！');

    //创建数据表
    $content = file_get_contents('./installsql.txt');
    $content = str_replace('#@#', $dbprefix, $content);
    foreach (explode('; ', $content) as $v) {
        $v = trim($v);
        if ($v) {
            $link->query($v);
        }
    }
    $_SERVER["REQUEST_SCHEME"] = !empty($_SERVER["REQUEST_SCHEME"]) ? $_SERVER["REQUEST_SCHEME"] : 'http';
    $basehost = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER['HTTP_HOST'] . str_replace('\\', '', dirname(dirname($_SERVER['SCRIPT_NAME']))) . '/';
    $link->query('update ' . $dbprefix . 'sysconfig set value=\'' . $basehost . '\' where varname=\'basehost\'');
    $link->query('update ' . $dbprefix . 'sysconfig set value=\'' . $basehost . 'resources/\' where varname=\'resourceUrl\'');
    $link->query('update ' . $dbprefix . 'sysconfig set value=\'' . $_SERVER['HTTP_HOST'] . '\' where varname=\'domain\'');
    $link->query('update ' . $dbprefix . 'sysconfig set value=\'' . $basehost . '\' where varname=\'attachmentHost\'');

    $cfg = require '../../data/ConfigCache.php';
    $cfg['cfg']['basehost'] = $basehost;
    $cfg['cfg']['resourceUrl'] = $basehost . 'resources/';
    $cfg['cfg']['domain'] = $_SERVER['HTTP_HOST'];
    $cfg['cfg']['attachmentHost'] = $basehost;
    $config = "<?php\n\r" . 'return ' . var_export($cfg, true) . ';';
    file_put_contents('../../data/ConfigCache.php', $config);

    //增加管理员帐号
    $adminuser = input('adminuser');
    $adminpwd = input('adminpwd');
    $pwd = password_hash($adminpwd . $auth_key, PASSWORD_DEFAULT);
    $link->query("INSERT INTO `" . $dbprefix . "admin` VALUES (null, '1', '" . $adminuser . "', '" . $pwd . "', '0', '', '0', '', '0', '', '1', '');");

    unlink('./index.php');
    unlink('./installsql.txt');
    include('./template/step_' . $step . '.htm');
    exit();
}
