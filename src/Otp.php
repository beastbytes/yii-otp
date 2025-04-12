<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use OTPHP\OTPInterface as OTPHPInterface;
use ParagonIE\ConstantTime\Base32;
use Random\RandomException;

abstract class Otp implements OtpInterface
{
    /**
     * Number of bytes used to generate OTP secret.
     */
    public const DEFAULT_SECRET_LENGTH = 24;

    /** @var string $lastCode Last validated OTP code */
    protected string $lastCode = '';
    /** @var ?string $secret Secret value to generate OTP code */
    protected ?string $secret = null;

    public function __construct(
        protected string $digest,
        protected int $digits,
        int $secretLength,
    )
    {
        if ($this->secret === null) {
            $this->generateSecret($secretLength);
        }
    }

    public function getDigest(): string
    {
        return $this->digest;
    }

    public function getDigits(): int
    {
        return $this->digits;
    }

    /**
     * @param string $label Label for the TOTP. Usually the organisation or application name.
     * @param ?string $issuer Issuer of the TOTP.
     * @psalm-param <string, string> $parameters Additional parameters as key=>value pairs
     * @return string The provisioning URI for the TOTP. Can be used to generate a QR code for the TOTP.
     * @throws Exception
     */
    public function getProvisioningUri(string $label, ?string $issuer = null, array $parameters = []): string
    {
        if ($this->secret === null) {
            throw new Exception('OTP not enabled');
        }

        $otp = $this->createOtp();

        $otp->setLabel($label);
        if (is_string($issuer)) {
            $otp->setIssuer($issuer);
        }
        foreach ($parameters as $parameter => $value) {
            $otp->setParameter($parameter, $value);
        }

        return $otp->getProvisioningUri();
    }

    public function getSecret(): ?string
    {
        return trim($this->secret, '=');
    }

    public function getLastCode(): string
    {
        return $this->lastCode;
    }

    /**
     * @throws RandomException
     */
    private function generateSecret(int $length): void
    {
        $this->secret = Base32::encodeUpper(random_bytes($length));
    }

    abstract public function verify(string $code): bool;
    abstract protected function createOtp(): OTPHPInterface;
}