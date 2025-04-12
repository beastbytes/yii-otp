<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use OTPHP\TOTP as OtphpTotp;
use OTPHP\TOTPInterface;
use Psr\Clock\ClockInterface;

final class Totp extends Otp
{
    /**
     * Number of seconds that an OTP code is valid before or after the period
     * to allow for clock drift between client and server
     */
    const DEFAULT_LEEWAY = 2;

    public function __construct(
        private ClockInterface $clock,
        private int $period,
        private int $leeway,
        string $digest,
        int $digits,
        int $secretLength,
    )
    {
        parent::__construct($digest, $digits, $secretLength);
    }

    public function getLeeway(): int
    {
        return $this->leeway;
    }

    public function getPeriod(): int
    {
        return $this->period;
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

        /** @var OtphpTotp $otp */
        $otp = $this->createOtp();

        if ($otp->verify(otp: $code, leeway: $this->leeway)) {
            $this->lastCode = $code;
            return true;
        }

        return false;
    }

    protected function createOtp(): TOTPInterface
    {
        return OtphpTotp::create(
            secret: $this->secret,
            period: $this->period,
            digest: $this->digest,
            digits: $this->digits,
            clock: $this->clock,
        );
    }
}