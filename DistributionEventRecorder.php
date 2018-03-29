<?php

namespace MommyCom\Common\Service\Distribution;

use MommyCom\Common\Model\Db\MailingDataRecord;
use MommyCom\Common\Model\Db\MailingEventRecord;

/**
 * Class EventDispatcher
 * @package MommyCom\Common\Service\Distribution
 */
class DistributionEventRecorder
{
    use DistributionValidatorTrait;

    /**
     * Создание записи о пользователе и событии на основании данных полученых в запросе
     */
    public function createRecordFromRequest()
    {
        $eventCode = \Yii::app()->request->getParam('event');
        $anonymousId = \Yii::app()->request->getParam('anonymousId');
        $userId = \Yii::app()->getUser()->getId();
        $email = \Yii::app()->request->getParam('email') ?? null;

        $event = MailingEventRecord::model()->find([
            'condition' => 'code=:code',
            'params' => [':code' => $eventCode],
        ]);

        $eventData = MailingDataRecord::model()->getModelFromParams($event, $userId, $email, $anonymousId);

        if (!is_null($email) && $this->validateEmail($email)) {
            $eventData->email = $email;
        }

        if (!is_null($userId)) {
            $eventData->is_registered = true;
        }

        $eventData->distribution_event_id = $event->id;
        $eventData->anonymous_id = $anonymousId;
        $eventData->user_id = $userId;
        $eventData->country = \Yii::app()->request->getParam('country') ?? null;
        $eventData->source = \Yii::app()->request->getParam('source') ?? null;
        $eventData->save();
    }
}