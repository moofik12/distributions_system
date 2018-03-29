<?php

namespace MommyCom\Common\Service\Distribution\Action;

/**
 * Class DistributionMassImport
 * @package MommyCom\Common\Service\Distribution\Action
 */
class DistributionMassImport extends DistributionActon
{
    /**
     * @return mixed|void
     * @throws \CException
     */
    protected function done()
    {
        if (is_null($this->getEventRecord()->id)) {
            throw new \RuntimeException(self::EVENT_NOT_SPECIFIED_ERROR);
        }

        $mailingService = $this->getMailerService();
        $eventRecord = $this->getEventRecord();
        $dataRecord = $this->getDataRecord();

        $mailerUsers = $dataRecord->withEvent($eventRecord->id)
            ->notSubscribed()->toMailerUserModels();

        $mailingService->importContacts($mailerUsers);
        $dataRecord->markMailerUsersAsSubscribed($mailerUsers);
    }
}