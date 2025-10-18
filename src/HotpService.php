<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use ReflectionException;
use ReflectionProperty;
use Throwable;
use Yiisoft\Db\Exception\InvalidConfigException;

final class HotpService extends OtpService
{
    /**
     * Return the OTP parameters for a user
     *
     * @param string $userId ID of the user.
     * @return array OTP parameters as key=>value pairs, empty if OTP not enabled for the user
     * @psalm-return array{counter?: int, digest?: string, digits?: int}
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getOtpParameters(string $userId): array
    {
        $parameters = [];

        /** @var Hotp $otp */
        $otp = $this->getOtp($userId);

        if ($otp !== null) {
            $parameters['counter'] = $otp->getCounter();
            $parameters['digest'] = $otp->getDigest();
            $parameters['digits'] = $otp->getDigits();
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
    protected function hydrate(array $data, string $userId): ?OtpInterface
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