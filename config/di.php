<?php

declare(strict_types=1);

use BeastBytes\Yii\Otp\OtpInterface;
use BeastBytes\Yii\Otp\OtpServiceInterface;
use BeastBytes\Yii\Otp\Totp;
use BeastBytes\Yii\Otp\TotpService;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\Clock;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    ClockInterface::class => Clock::class,
    OtpInterface::class => [
        'class' => Totp::class,
        '__construct()' => [
            'clock' => Reference::to(ClockInterface::class),
            'digest' => $params['beastbytes/yii-otp']['otp']['digest'],
            'digits' => $params['beastbytes/yii-otp']['otp']['digits'],
            'leeway' => $params['beastbytes/yii-otp']['otp']['leeway'],
            'period' => $params['beastbytes/yii-otp']['otp']['period'],
            'secretLength' => $params['beastbytes/yii-otp']['otp']['secretLength'],
        ],
    ],
    OtpServiceInterface::class => [
        'class' => TotpService::class,
        '__construct()' => [
            'database' => Reference::to(ConnectionInterface::class),
            'otp' => Reference::to(OtpInterface::class),
            'table' => $params['beastbytes/yii-otp']['database']['otpTable'],
        ],
    ],
    BackupCodeServiceInterface::class => [
        'class' => BackupCodeService::class,
        '__construct()' => [
            'count' => $params['beastbytes/yii-otp']['backupCode']['count'],
            'length' => $params['beastbytes/yii-otp']['backupCode']['length'],
            'table' => $params['beastbytes/yii-otp']['database']['backupCodeTable'],
            'database' => Reference::to(ConnectionInterface::class),
        ],
    ]
];
