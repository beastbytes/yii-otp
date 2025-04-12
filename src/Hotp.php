<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use OTPHP\HOTP as OtphpHotp;
use OTPHP\HOTPInterface;

final class Hotp extends Otp
{
    public const DEFAULT_WINDOW = 0;

    public function __construct(
        private int $counter,
        private int $window,
        string $digest,
        int $digits,
        int $secretLength,
    )
    {
        parent::__construct($digest, $digits, $secretLength);
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function getWindow(): int
    {
        return $this->window;
    }

    /**
     * @param string $code The OTP code to verify
     * @psalm-param string $code The OTP code to verify
     * @return bool True if the OTP code is valid, false otherwise
     * @throws Exception
     */
    public function verify(string $code): bool
    {
        if ($code === $this->lastCode) {
            return false;
        }

        /** @var OtphpHotp $otp */
        $otp = $this->createOtp();

        if ($otp->verify(otp: $code, window: $this->window)) {
            $this->counter++;
            $this->lastCode = $code;
            return true;
        }

        return false;
    }

    protected function createOtp(): HOTPInterface
    {
        return OtphpHotp::create(
            secret: $this->secret,
            counter: $this->counter,
            digest: $this->digest,
            digits: $this->digits,
        );
    }
}