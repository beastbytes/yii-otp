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
use Yiisoft\Security\Crypt;

/** @var array $params */

return [
    ClockInterface::class => Clock::class,
    Crypt::class => [
        'class' => Crypt::class,
        '__construct()' => [
            'cipher' => $params['beastbytes/yii-otp']['crypt']['cipher'],
        ],        
        'withDerivationIterations()' => [
            $params['beastbytes/yii-otp']['crypt']['iterations'],
        ],
        'withKdfAlgorithm()' => [
            $params['beastbytes/yii-otp']['crypt']['kdfAlgorithm'],
        ],    
        'withAuthorizationKeyInfo' => [
            $params['beastbytes/yii-otp']['crypt']['authorizationKeyInfo'],
        ],    
    ],
    OtpInterface::class => [
        'class' => Totp::class,
        '__construct()' => [
            'clock' => ClockInterface::class,
            'digest' => $params['beastbytes/yii-otp']['totp']['digest'],
            'digits' => $params['beastbytes/yii-otp']['totp']['digits'],
            'leeway' => $params['beastbytes/yii-otp']['totp']['leeway'],
            'period' => $params['beastbytes/yii-otp']['totp']['period'],
            'secretLength' => $params['beastbytes/yii-otp']['totp']['secretLength'],
        ],
    ],
    OtpServiceInterface::class => [
        'class' => TotpService::class,
        '__construct()' => [
            'backupCodeCount' => $params['beastbytes/yii-otp']['backupCode']['count'],
            'backupCodeLength' => $params['beastbytes/yii-otp']['backupCode']['length'],
            'crypt' => Reference::to(Crypt::class),
            'database' => ConnectionInterface::class,
            'encryptionKey' => $params['beastbytes/yii-otp']['crypt']['key'],
            'otpBackupCodesTable' => $params['beastbytes/yii-otp']['database']['otpBackupCodeTable'],
            'otp' => Reference::to(OtpInterface::class),
            'otpTable' => $params['beastbytes/yii-otp']['database']['totpTable'],
        ],
    ]
];