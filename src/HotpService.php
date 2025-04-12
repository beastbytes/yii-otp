<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use ReflectionException;
use ReflectionProperty;
use Throwable;

final class HotpService extends OtpService
{
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
            'secret' => $this->crypt->encryptByKey($this->otp->getSecret(), $this->encryptionKey, $userId),
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