<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use ReflectionException;
use ReflectionProperty;
use Throwable;


final class TotpService extends OtpService
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