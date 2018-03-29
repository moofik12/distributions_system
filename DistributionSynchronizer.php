<?php

namespace MommyCom\Common\Service\Distribution;

use MommyCom\Common\Model\Db\CartRecord;
use MommyCom\Common\Model\Db\MailingDataRecord;
use MommyCom\Common\Model\Db\MailingEventRecord;
use MommyCom\Common\Model\Db\UserRecord;
use MommyCom\Common\Service\Distribution\Action\DistributionMassImport;
use MommyCom\Common\Service\MailerServices\MailerServiceAbstract;
use MommyCom\Common\Service\MailerServices\MailerServiceUserModel;

/**
 * Class DistributionSynchronizer
 * @package MommyCom\Common\Service\Distribution
 */
class DistributionSynchronizer
{
    /**
     * @var int
     */
    const CHUNK_LIMIT = 500;

    /**
     * @var MailerServiceAbstract $mailingService
     */
    private $mailingService;

    /**
     * @var DistributionMassImport $massImportAction
     */
    private $massImportAction;

    /**
     * @var string $country
     */
    private $subdomain;

    /**
     * @var MailerServiceUserModel[] $intersectionUserRecord
     */
    private $intersectionMailerUserRecords = [];

    /**
     * DistributionSynchronizer constructor.
     *
     * @param MailerServiceAbstract $mailingService
     * @param DistributionMassImport $distributionMassImport
     * @param string $subdomain
     */
    public function __construct(
        MailerServiceAbstract $mailingService,
        DistributionMassImport $distributionMassImport,
        string $subdomain
    ) {
        $this->mailingService = $mailingService;
        $this->subdomain = $subdomain;
        $this->massImportAction = $distributionMassImport;
    }

    /**
     * Синхронизирует записи в БД сайта и затем синхронизиует БД сайта с БД Unisender
     *
     * @throws \CDbException
     */
    public function sync()
    {
        $this->syncInternalRecords();
        $this->syncExternalRecords();
    }

    /**
     * Синхронизирует внутренние записи о событиях в БД сайта руководствуясь приоритетами событий
     */
    private function syncInternalRecords()
    {
        $this->syncLostCarts();
        MailingDataRecord::model()->deleteLowPriorityRecords();
    }

    /**
     * Синхронизирует БД сайта со списками рассылок Unisender
     *
     * @throws \CDbException
     */
    private function syncExternalRecords()
    {
        $lists = $this->mailingService->getLists();

        foreach ($lists[$this->subdomain] as $eventName => $listId) {
            $this->mailingService->init($listId);

            $i = 0;
            do {
                $externalRecords = $this->mailingService->exportContacts($i, self::CHUNK_LIMIT, '', true);
                $distributionEvent = MailingEventRecord::model()->find([
                    'condition' => 'name=:name',
                    'params' => [':name' => $eventName]
                ]);

                if (count($externalRecords) > 0) {
                    $intersection = MailingDataRecord::model()->fromMailerUserArray($externalRecords, 'email');
                    $this->deleteExternalAbsentUsers($intersection, $externalRecords);
                    $this->deleteInternalAbsentUsers($distributionEvent);
                }

                $this->massImportAction->process(
                    MailingDataRecord::model(),
                    $distributionEvent,
                    $this->mailingService
                );

                $i += self::CHUNK_LIMIT;
            } while (count($externalRecords) >= self::CHUNK_LIMIT);
        }

    }

    /**
     * Синхронизирует таблицу корзин пользователей с таблицей информации о событиях
     */
    private function syncLostCarts()
    {
        $time = strtotime('-1 day');

        $userIds = CartRecord::model()
            ->createdAtLower($time)
            ->isSentNotification(false)
            ->findColumnDistinct('user_id');

        $event = MailingEventRecord::model()->withName(MailingEventRecord::LOST_CART)->find();

        $users = UserRecord::model()->idIn($userIds)->findAll();

        foreach ($users as $user) {
            $eventData = MailingDataRecord::model()->getModelFromParams($event, (int)$user->id, null, null);

            if ($eventData->distribution_event_id === $event->id) {
                continue;
            }

            $eventData->user_id = $user->id;
            $eventData->email = $user->email;
            $eventData->is_registered = true;
            $eventData->distribution_event_id = $event->id;
            $eventData->save();
        }
    }

    /**
     * Массово удаляет из рассылок тех пользователей, которые отстутствуют в нашей БД
     *
     * @param array $intersection
     * @param MailerServiceUserModel[] $externalRecords
     */
    private function deleteExternalAbsentUsers(array $intersection, array $externalRecords)
    {
        $externalRecords = array_filter($externalRecords, function ($value) use ($intersection) {
            if (array_key_exists($value->email, $intersection)) {
                $this->intersectionMailerUserRecords[] = $value;
                return false;
            }

            return true;
        });

        $this->mailingService->importContacts($externalRecords, true);
    }

    /**
     * Удаляет записи об отписавшихся с конкретных рассылок пользователей из нашей БД
     *
     * @param MailingEventRecord $distributionEvent
     * @throws \CDbException
     */
    private function deleteInternalAbsentUsers(MailingEventRecord $distributionEvent)
    {
        foreach ($this->intersectionMailerUserRecords as $record) {
            if (!$record->isSubscribed && !$record->isPossiblySubscribe) {
                /**
                 * @var MailingDataRecord|null $dataRecord
                 */
                $dataRecord = MailingDataRecord::model()->withEvent($distributionEvent->id)
                    ->withEmail($record->email)->find();
                if (!is_null(!$dataRecord)) {
                    $dataRecord->delete();
                }
            }
        }
    }
}