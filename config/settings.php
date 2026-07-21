<?php
/**
 * 系统配置文件
 * 从环境变量 (.env) 读取配置，提供向后兼容的数组格式
 * @author zhucy
 */
return array(
    'settings' => array(
        'db' => array(
            'dbhost' => $_ENV['DB_HOST'] ?? 'localhost',
            'dbport' => $_ENV['DB_PORT'] ?? '3306',
            'dbuser' => $_ENV['DB_USER'] ?? 'root',
            'dbpw' => $_ENV['DB_PASSWORD'] ?? '',
            'dbcharset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'pconnect' => $_ENV['DB_PCONNECT'] ?? '0',
            'dbname' => $_ENV['DB_NAME'] ?? 'slimcms',
            'tablepre' => $_ENV['DB_TABLEPRE'] ?? 'cs_',
            'connecttype' => $_ENV['DB_CONNECTTYPE'] ?? ';port=',
        ),
        'redis' => array(
            'prefix' => $_ENV['REDIS_PREFIX'] ?? '',
            'server' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? '',
            'pconnect' => (int)($_ENV['REDIS_PCONNECT'] ?? 0),
            'timeout' => $_ENV['REDIS_TIMEOUT'] ?? '0',
            'serializer' => (int)($_ENV['REDIS_SERIALIZER'] ?? 1),
            'dbindex' => (int)($_ENV['REDIS_DATABASE'] ?? 0),
        ),
        'cache' => array(
            'type' => $_ENV['CACHE_TYPE'] ?? 'file',
        ),
        'output' => array(
            'gzip' => $_ENV['OUTPUT_GZIP'] ?? '0',
        ),
        'cookie' => array(
            'cookiepre' => $_ENV['COOKIE_PREFIX'] ?? 'slimcms_',
            'cookiedomain' => $_ENV['COOKIE_DOMAIN'] ?? '',
            'cookiepath' => $_ENV['COOKIE_PATH'] ?? '/',
        ),
        'attachment' => array(
            'dirname' => $_ENV['UPLOAD_DIR'] ?? '/uploads/',
            'dirrule' => $_ENV['UPLOAD_DIR_RULE'] ?? '{Y}/{m}',
        ),
        'security' => array(
            'authkey' => $_ENV['AUTH_KEY'] ?? bin2hex(random_bytes(16)),
            'attackevasive' => $_ENV['ATTACK_EVASIVE'] ?? '0',
            'uploadForbidFile' => $_ENV['UPLOAD_FORBID_FILE'] ?? 'php|pl|cgi|asp|aspx|jsp|php3|shtm|shtml|js',
            'uploadCheckWords' => $_ENV['UPLOAD_CHECK_WORDS'] ?? '',
            'querysafe' => array(
                'status' => (int)($_ENV['QUERY_SAFE_STATUS'] ?? 1),
                'dfunction' => array(
                    'load_file',
                    'hex',
                    'substring',
                    'if',
                    'ord',
                    'char',
                ),
                'daction' => array(
                    'intooutfile',
                    'intodumpfile',
                    'uniondistinct',
                ),
                'dnote' => array(
                    '/*',
                    '*/',
                    '#',
                    '--',
                    '"',
                ),
                'dlikehex' => 1,
                'afullnote' => '0',
                'exceptFunction' => 'concat|distinct',
            ),
        ),
        'memory' => array(
            'prefix' => $_ENV['MEMORY_PREFIX'] ?? 'slimcms_',
        ),
        'keys' => array(
            'key' => $_ENV['ENCRYPT_KEY'] ?? substr(md5($_ENV['AUTH_KEY'] ?? ''), 0, 8),
            'iv' => $_ENV['ENCRYPT_IV'] ?? substr(md5($_ENV['AUTH_KEY'] ?? ''), 8, 8),
        ),
    ),
);
