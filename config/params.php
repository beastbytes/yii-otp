<?php

declare(strict_types=1);

use BeastBytes\Yii\Otp\Otp;
use BeastBytes\Yii\Otp\OtpService;
use BeastBytes\Yii\Otp\Totp;
use BeastBytes\Yii\Otp\OtpDigest;
use OTPHP\OTPInterface;
use OTPHP\TOTPInterface;

return [
    'beastbytes/yii-otp' => [
        'otpBackupCode' => [
            'count' => OtpService::BACKUP_CODE_COUNT,
            'length' => OtpService::BACKUP_CODE_LENGTH,
        ],
        'database' => [
            'backupCodeTable' => 'otp_backup_code',
            'otpTable' => 'otp',
        ],
        'otp' => [ // The default values work for authenticator apps like Google Authenticator
            'digest' => OtpDigest::sha1->name,
            'digits' => OTPInterface::DEFAULT_DIGITS,
            'leeway' => TOTP::DEFAULT_LEEWAY,
            'period' => TOTPInterface::DEFAULT_PERIOD,
            'secretLength' => OTP::DEFAULT_SECRET_LENGTH,
        ]
    ],
];
