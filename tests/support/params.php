<?php

declare(strict_types=1);

use BeastBytes\Yii\Otp\CryptCipher;
use BeastBytes\Yii\Otp\CryptKdfAlgorithm;
use BeastBytes\Yii\Otp\Hotp;
use BeastBytes\Yii\Otp\Otp;
use BeastBytes\Yii\Otp\OtpDigest;
use BeastBytes\Yii\Otp\OtpService;
use BeastBytes\Yii\Otp\Tests\HotpTest;
use BeastBytes\Yii\Otp\Tests\TestCase;
use BeastBytes\Yii\Otp\Totp;
use OTPHP\OTPInterface;
use OTPHP\TOTPInterface;

return [
    'backupCode' => [
        'count' => TestCase::BACKUP_CODE_COUNT,
        'length' => TestCase::BACKUP_CODE_LENGTH,
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
        'hotpTable' => 'hotp',
        'totpTable' => 'totp',
    ],
    'hotp' => [
        'counter' => HotpTest::COUNTER,
        'window' => Hotp::DEFAULT_WINDOW,
        'digest' => OtpDigest::sha1->name,
        'digits' => OTPInterface::DEFAULT_DIGITS,
        'period' => TOTPInterface::DEFAULT_PERIOD,
        'secretLength' => Otp::DEFAULT_SECRET_LENGTH,
    ],
    'totp' => [
        'digest' => OtpDigest::sha1->name,
        'digits' => OTPInterface::DEFAULT_DIGITS,
        'leeway' => Totp::DEFAULT_LEEWAY,
        'period' => TOTPInterface::DEFAULT_PERIOD,
        'secretLength' => Otp::DEFAULT_SECRET_LENGTH,
        'attempts'
    ],
];