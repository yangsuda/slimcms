<?php

return [
    'settings' => [
        'db' =>
            [
                'dbhost' => '127.0.0.1',
                'dbport' => '3306',
                'dbuser' => 'root',
                'dbpw' => 'root',
                'dbcharset' => 'utf8',
                'pconnect' => '0',
                'dbname' => 'slimcms',
                'tablepre' => 'cs_',
                'connecttype' => ':',
            ],
        'redis' =>
            [
                'prefix' => 'y1cdF6_',
                'server' => '',
                'port' => 6379,
                'password' => 's5RY-D4aKuz',
                'pconnect' => 0,
                'timeout' => '0',
                'serializer' => 1,
            ],
        'cache' =>
            [
                'type' => 'file',
            ],
        'output' =>
            [
                'charset' => 'utf-8',
                'gzip' => '0',
                'language' => 'zh_cn',
            ],
        'cookie' =>
            [
                'cookiepre' => 'QfCwk_',
                'cookiedomain' => '',
                'cookiepath' => '/',
            ],
        'attachment' =>
            [
                'dirname' => '/uploads/',
                'dirrule' => '{Y}/{m}',
            ],
        'security' =>
            [
                'authkey' => 'lD2kk8EaO8DAoPkd2kE2Z',
                'attackevasive' => '0',
                'uploadForbidFile' => 'php|pl|cgi|asp|aspx|jsp|php3|shtm|shtml|js',
                'uploadCheckWords' => '_GET|_POST|_COOKIE|assert|call_|create_|eval|_SERVER|function|defined|global|base64_',
                'querysafe' =>
                    [
                        'status' => 1,
                        'dfunction' =>
                            [
                                0 => 'load_file',
                                1 => 'hex',
                                2 => 'substring',
                                3 => 'if',
                                4 => 'ord',
                                5 => 'char',
                            ],
                        'daction' =>
                            [
                                0 => 'intooutfile',
                                1 => 'intodumpfile',
                                2 => 'unionselect',
                                3 => '(select',
                                4 => 'unionall',
                                5 => 'uniondistinct',
                            ],
                        'dnote' =>
                            [
                                0 => '/*',
                                1 => '*/',
                                2 => '#',
                                3 => '--',
                                4 => '"',
                            ],
                        'dlikehex' => 1,
                        'afullnote' => '0',
                    ],
            ],
        'keys' =>
            [
                'prikey' => 'HW8k1f8D',
                'pubkey' => 'P0595R50',
            ],
    ]
];