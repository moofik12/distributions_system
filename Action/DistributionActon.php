<?php

namespace MommyCom\Common\Service\Distribution\Action;

use MommyCom\Common\Model\Db\MailingDataRecord;
use MommyCom\Common\Model\Db\MailingEventRecord;
use MommyCom\Common\Service\MailerServices\MailerServiceAbstract;

/**
 * Class DistributionActon
 * @package MommyCom\Common\Service\Distribution\Action
 */
abstract class DistributionActon
{
    /**
     * @var string
     */
    protected const EVENT_NOT_SPECIFIED_ERROR = 'Event not specified.';

    /**
     * @var string
     */
    protected const QUERY_ERROR = 'Query error';

    /**
     * @var string
     */
    protected const API_ERROR = 'Invalid request data';

    /**
     * @var string
     */
    protected const SUCCESS = 'Success';

    /**
     * @var MailingDataRecord $dataRecord
     */
    private $dataRecord;

    /**
     * @var MailingEventRecord $eventRecord
     */
    private $eventRecord;

    /**
     * @var MailerServiceAbstract $mailerService
     */
    private $mailerService;

    /**
     * @return MailingDataRecord
     */
    public function getDataRecord(): MailingDataRecord
    {
        return $this->dataRecord;
    }

    /**
     * @return MailingEventRecord
     */
    public function getEventRecord(): MailingEventRecord
    {
        return $this->eventRecord;
    }

    /**
     * @return MailerServiceAbstract
     */
    public function getMailerService(): MailerServiceAbstract
    {
        return $this->mailerService;
    }


    /**
     * @param MailingDataRecord $dataRecord
     * @param MailingEventRecord $eventRecord
     * @param MailerServiceAbstract $mailerService
     */
    public function process(
        MailingDataRecord $dataRecord,
        MailingEventRecord $eventRecord,
        MailerServiceAbstract $mailerService
    ): void {
        $this->dataRecord = $dataRecord;
        $this->eventRecord = $eventRecord;
        $this->mailerService = $mailerService;

        try {
            $this->done();
        } catch (\CException $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }

    /**
     * @throws \CException
     */
    abstract protected function done();
}