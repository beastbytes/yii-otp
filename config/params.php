<?php

declare(strict_types=1);

use BeastBytes\Yii\Otp\CryptCipher;
use BeastBytes\Yii\Otp\CryptKdfAlgorithm;
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
        'crypt' => [
            'authorizationKeyInfo' => 'TotpAuthorizationKey',
            'cipher' => CryptCipher::AES_128_CBC, // Encryption cipher: AES-128-CBC, AES-192-CBC, AES-256-CBC
            'iterations' => OtpService::CRYPT_ITERATIONS,
            'kdfAlgorithm' => CryptKdfAlgorithm::sha256->name, // sha256, sha384, sha512
            'key' => 'rstswSLBfzMKLyELbvKSkfT3qkHjAVoSQtVNSkWimeiGbOABqJXA3NqFYtXyewpS',
        ],
        'database' => [
            'otpBackupCodeTable' => 'otp_backup_code',
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
