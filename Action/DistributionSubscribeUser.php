<?php

namespace MommyCom\Common\Service\Distribution\Action;

use MommyCom\Common\Service\Distribution\DistributionValidatorTrait;

/**
 * Class DistributionSubscribeUser
 * @package MommyCom\Common\Service\Distribution\Action
 */
class DistributionSubscribeUser extends DistributionActon
{
    use DistributionValidatorTrait;

    /**
     * @throws \CException
     */
    protected function done()
    {
        $dataRecord = $this->getDataRecord();
        $mailerService = $this->getMailerService();
        $eventRecord = $this->getEventRecord();

        if ($eventRecord->canSubscribe() &&
            !$dataRecord->checkUserSubscribed() &&
            $this->validateEmail($dataRecord->email)) {
            $result = $mailerService->subscribe($dataRecord);

            if (!$mailerService->isErrorResult($result)) {
                $dataRecord->markUserAsSubscribed($eventRecord);
            } else {
                throw new \RuntimeException(self::API_ERROR);
            }
        }
    }
}
