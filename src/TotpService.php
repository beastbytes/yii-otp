<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use Override;
use ReflectionException;
use ReflectionProperty;
use Throwable;
use Yiisoft\Db\Exception\Exception as DbException;
use Yiisoft\Db\Exception\InvalidConfigException;

final class TotpService extends OtpService
{
    /**
     * Return the OTP parameters for a user.
     * @return array OTP parameters as key=>value pairs, empty if OTP not enabled for the user
     * @psalm-return array{digest?: string, digits?: int, leeway?: int, period?: int}
     * @throws DbException
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    #[Override]
    public function getParameters(string $userId): array
    {
        /** @var ?Totp $otp */
        $otp = $this->getOtp($userId);

        if ($otp instanceof Totp) {
            return [
                'digest' => $otp->getDigest(),
                'digits' => $otp->getDigits(),
                'leeway' => $otp->getLeeway(),
                'period' => $otp->getPeriod(),
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
            'digest' => $this->otp->getDigest(),
            'digits' => $this->otp->getDigits(),
            'last_code' => $this->otp->getLastCode(),
            'leeway' => $this->otp->getLeeway(),
            'period' => $this->otp->getPeriod(),
            'secret' => $this->otp->getSecret(),
            'user_id' => $userId,
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
            'digest' => 'digest',
            'digits' => 'digits',
            'leeway' => 'leeway',
            'last_code' => 'lastCode',
            'period' => 'period',
            'secret' => 'secret',
        ] as $key => $property) {
            $reflectionProperty = new ReflectionProperty($this->otp, $property);
            $reflectionProperty->setValue($this->otp, $data[$key]);
        };

        return $this->otp;
    }
}