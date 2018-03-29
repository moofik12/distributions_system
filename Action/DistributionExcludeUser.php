<?php

namespace MommyCom\Common\Service\Distribution\Action;

/**
 * Class DistributionExcludeUser
 * @package MommyCom\Common\Service\Distribution\Action
 */
class DistributionExcludeUser extends DistributionActon
{
    /**
     * Исключает пользователя из группы рассылок
     *
     * @throws \CException
     * @throws \RuntimeException
     */
    protected function done() {
        $dataRecord = $this->getDataRecord();
        $mailerService = $this->getMailerService();

        if ($dataRecord->checkUserSubscribed()) {
            $result = $mailerService->exclude($dataRecord);

            if (!$mailerService->isErrorResult($result)) {
                $dataRecord->markUserAsUnsubscribed();
            } else {
                throw new \RuntimeException(self::API_ERROR);
            }
        }
    }
}
