<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

enum OtpDigest
{
    case sha1;
    case sha256;
    case sha512;
}
