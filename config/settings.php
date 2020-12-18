<?php

return [
    'settings' => [
        'db' =>
            [
                'dbhost' => '',
                'dbport' => '',
                'dbuser' => '',
                'dbpw' => '',
                'dbcharset' => 'utf8',
                'pconnect' => '0',
                'dbname' => '',
                'tablepre' => '',
                'connecttype' => ':',
            ],
        'redis' =>
            [
                'prefix' => '',
                'server' => '',
                'port' => 6379,
                'password' => '',
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
                'gzip' => '0',
            ],
        'cookie' =>
            [
                'cookiepre' => '',
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
                'authkey' => '',
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
                'prikey' => '',
                'pubkey' => '',
            ],
    ]
];