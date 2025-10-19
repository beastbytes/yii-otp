<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use Override;
use ReflectionException;
use ReflectionProperty;
use Throwable;
use Yiisoft\Db\Exception\InvalidConfigException;

final class HotpService extends OtpService
{
    /**
     * Return the OTP parameters for a user.
     * @param string $userId User ID
     * @return array OTP parameters as key=>value pairs, empty if OTP not enabled for the user
     * @psalm-return array{counter?: int, digest?: string, digits?: int}
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    #[Override]
    public function getParameters(string $userId): array
    {
        /** @var ?Hotp $otp */
        $otp = $this->getOtp($userId);

        if ($otp instanceof Hotp) {
            return [
                'counter' => $otp->getCounter(),
                'digest' => $otp->getDigest(),
                'digits' => $otp->getDigits(),
            ];
        }

        return [];
    }

    /**
     * @return array Columns as key=>value pairs.
     * @psalm-return <string, string>
     * @throws Exception
     */
    #[Override]
    protected function columns(string $userId): array
    {
        return [
            'counter' => $this->otp->getCounter(),
            'digest' => $this->otp->getDigest(),
            'digits' => $this->otp->getDigits(),
            'last_code' => $this->otp->getLastCode(),
            'secret' => $this->otp->getSecret(),
            'user_id' => $userId,
            'window' => $this->otp->getWindow(),
        ];
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    #[Override]
    protected function hydrate(array $data): ?OtpInterface
    {
        foreach ([
            'counter' => 'counter',
            'digest' => 'digest',
            'digits' => 'digits',
            'window' => 'window',
            'last_code' => 'lastCode',
            'secret' => 'secret',
        ] as $key => $property) {
            $reflectionProperty = new ReflectionProperty($this->otp, $property);
            $reflectionProperty->setValue($this->otp, $data[$key]);
        };

        return $this->otp;
    }
}