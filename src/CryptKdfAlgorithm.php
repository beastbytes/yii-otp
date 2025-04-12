<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

enum CryptKdfAlgorithm
{
    case sha256;
    case sha384;
    case sha512;
}
