<?php

namespace MommyCom\Common\Service\Distribution;

/**
 * Trait DistributionValidatorTrait
 * @package MommyCom\Common\Service\Distribution
 */
trait DistributionValidatorTrait
{
    /**
     * Валидирует e-mail
     *
     * @param string $email
     * @return bool
     */
    private function validateEmail(string $email)
    {
        if (!empty($email)) {
            $email = str_replace(' ', '', $email);
            $regex = \Yii::app()->params['validatorRegex']['email']['default']['pattern'];

            if (preg_match('/' . $regex . '/', $email)) {
                return true;
            }
        }

        return false;
    }
}