<?php

declare(strict_types=1);

namespace Application\EventListeners;

use Datetime;
use Domain\Entities\Reservation;
use Domain\Entities\ActivityLog;
use Domain\Entities\User;
use Domain\Repositories\ReservationEditsRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Psr\Container\ContainerInterface;

class OnFlushListener 
{
    private User $user;

    public function __construct(
        ContainerInterface $c
    ) {
        $this->user = $c->get('SessionUser');
    }

    public function onFlush(OnFlushEventArgs $args)
    {

        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $updatedEntities = $unitOfWork->getScheduledEntityUpdates();

        foreach ($updatedEntities as $updatedEntity) {

            if ($updatedEntity instanceof Reservation) {

                $changeset = $unitOfWork->getEntityChangeSet($updatedEntity);
                
                if (array_key_exists('dateTime', $changeset)) {
                    if ($changeset['dateTime'][0] != $changeset['dateTime'][1]) {

                        $reservationEdit = new ActivityLog;
                        $reservationEdit->setWhen(new Datetime);
                        $reservationEdit->setWhat(sprintf(
                            "%s: -%s +%s", 
                                'datetime',
                                $changeset['dateTime'][0]->format('Y-m-d H:i:s'), 
                                $changeset['dateTime'][1]->format('Y-m-d H:i:s')
                        ));
                        $reservationEdit->setWho(sprintf("#%s %s",
                            $this->user->getId(), $this->user->getFullName()
                        ));
                    }
                }

                if (isset($reservationEdit)) {
                    $entityManager->persist($reservationEdit);
                    $metaData = $entityManager->getClassMetadata(ActivityLog::class);
                    $unitOfWork->computeChangeSet($metaData, $reservationEdit);
                }
            }
        }
    }
}