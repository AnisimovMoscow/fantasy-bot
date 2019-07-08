<?php
return [
    'id' => 'fantasybot',
    'language' => 'ru-RU',
    'timeZone' => 'Europe/Moscow',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['send'],
                    'logFile' => '@runtime/logs/send.log',
                    'logVars' => [],
                ],
                [
                    'class' => 'notamedia\sentry\SentryTarget',
                    'dsn' => 'https://2336486e157d4a1d8fcbf302de72cde2@sentry.io/1499743',
                    'levels' => ['error', 'warning'],
                    'context' => true,
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
    ],
    'params' => require(__DIR__ . '/params.php'),
];
