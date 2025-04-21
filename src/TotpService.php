<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use ReflectionException;
use ReflectionProperty;
use Throwable;
use Yiisoft\Db\Exception\InvalidConfigException;

final class TotpService extends OtpService
{
    /**
     * Return the OTP parameters for a user
     *
     * @param string $userId ID of the user.
     * @return array OTP parameters as key=>value pairs, empty if OTP not enabled for the user
     * @psalm-return array{digest?: string, digits?: int, leeway?: int, period?: int}
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getOtpParameters(string $userId): array
    {
        $parameters = [];

        /** @var Totp $otp */
        $otp = $this->getOtp($userId);

        if ($otp !== null) {
            $parameters['digest'] = $otp->getDigest();
            $parameters['digits'] = $otp->getDigits();
            $parameters['leeway'] = $otp->getLeeway();
            $parameters['period'] = $otp->getPeriod();
        }

        return $parameters;
    }

    /**
     * @param string $userId ID of the user.
     * @return array Columns as key=>value pairs.
     * @psalm-return <string, string>
     * @throws Exception
     */
    protected function columns(string $userId): array
    {
        return [
            'digest' => $this->otp->getDigest(),
            'digits' => $this->otp->getDigits(),
            'last_code' => $this->otp->getLastCode(),
            'leeway' => $this->otp->getLeeway(),
            'period' => $this->otp->getPeriod(),
            'secret' => $this->crypt->encryptByKey($this->otp->getSecret(), $this->encryptionKey, $userId),
            'user_id' => $userId,
        ];
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function hydrate(array $data, string $userId): ?OtpInterface
    {
        foreach ([
            'digest' => 'digest',
            'digits' => 'digits',
            'leeway' => 'leeway',
            'last_code' => 'lastCode',
            'period' => 'period',
            'secret' => 'secret',
        ] as $key => $property) {
            $reflectionProperty = new ReflectionProperty($this->otp, $property);
            $reflectionProperty->setValue(
                $this->otp,
                $property === 'secret'
                    ? $this->crypt->decryptByKey($data[$key], $this->encryptionKey, $userId)
                    : $data[$key]
            );
        };

        return $this->otp;
    }
}