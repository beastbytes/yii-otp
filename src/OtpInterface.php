<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

interface OtpInterface
{
    /**
     * @return string The digest algorithm being used
     */
    public function getDigest(): string;
    /**
     * @return int Number of digits in the OTP code
     */
    public function getDigits(): int;
    /**
     * @return string Last OTP code verified
     */
    public function getLastCode(): string;
    /**
     * Get the provisioning URI.
     *
     * @return non-empty-string
     */
    public function getProvisioningUri(string $label, ?string $issuer = null, array $parameters = []): string;
    /**
     * @return string|null The OTP secret code
     */
    public function getSecret(): ?string;
    /**
     * Verify the OTP code.
     * @param string $code The OTP code to verify
     * @psalm-param string $code The OTP code to verify
     * @return bool True if the OTP code is valid, false otherwise
     */
    public function verify(string $code): bool;
}