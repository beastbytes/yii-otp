<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

Interface OtpServiceInterface
{
    /**
     * Creates an OTP for a user and returns the provisioning QR code as a string
     * that can be used as the src attribute of an <img/> tag and the secret value for the OTP.
     * The application should verify that the user can enter a valid OTP code after creating an OTP.
     *
     * @param string $userId IS of the user.
     * @param string $label Label for the TOTP. Usually the organisation or application name.
     * @param ?string $issuer Issuer of the TOTP.
     * @param array $params Additional parameters for the provisioning URI as key=>value pairs.
     * @psalm-return [qrcode: string, secret: string] QR code and OTP secret
     */
    public function createOtp(string $userId, string $label, ?string $issuer = null, array $params = []): array;

    /**
     * Disable OTP for a user.
     *
     * @param string $userId ID of the user.
     * @return void
     */
    public function disableOtp(string $userId): void;

    /**
     * Returns the OTP parameters for a user
     *
     * @param string $userId ID of the user.
     * @return array OTP parameters as key=>value pairs, empty if OTP not enabled for the user
     */
    public function getOtpParameters(string $userId): array;

    /**
     * Whether OTP is enabled for a user.
     *
     * @param string $userId ID of the user.
     * @return bool true if OTP is enabled, false if OTP is not enabled.
     */
    public function isOtpEnabled(string $userId): bool;

    /**
     * Verifies an OTP or backup code.
     * If a backup code is being verified and verification is successful, the backup code is deleted to prevent reuse.
     *
     * @param string $code the code to verify.
     * @param string $userId ID of the user.
     * @return bool true is verification is successful, false if it fails.
     */
    public function verify(string $code, string $userId): bool;
}
