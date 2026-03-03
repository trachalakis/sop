<?php

declare(strict_types=1);

namespace Application\EventListeners;

use Datetime;
use Domain\Entities\Reservation;
use Domain\Entities\User;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Psr\Container\ContainerInterface;

class OnFlushListener 
{
    private ?User $user;

    public function __construct(
        ContainerInterface $c
    ) {
        $this->user = $c->get('SessionUser');
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        //Log things only when someone is logged in
        if ($this->user != null) {
            $entityManager = $args->getObjectManager();
            $unitOfWork = $entityManager->getUnitOfWork();
            $updatedEntities = $unitOfWork->getScheduledEntityUpdates();

            foreach ($updatedEntities as $updatedEntity) {
                
                //only log reservation edits for now
                if ($updatedEntity instanceof Reservation) {
                    $changeset = $unitOfWork->getEntityChangeSet($updatedEntity);

                    $key = get_class($updatedEntity) . '#'. $updatedEntity->getId();
                    if (apcu_exists($key)) {
                        $cacheEntries = apcu_fetch($key);
                    } else {
                        $cacheEntries = [];
                    }
                    foreach($changeset as $field => $changes) {
                        if ($changes[0] != $changes[1]) {
                            if ($changes[0] instanceof Datetime) {
                                $changes[0] = $changes[0]->format('Y-m-d H:i');
                            }
                            if ($changes[1] instanceof Datetime) {
                                $changes[1] = $changes[1]->format('Y-m-d H:i');
                            }
                        
                            $cacheEntries[] = sprintf(
                                "%s|%s|%s --> %s|#%s",
                                (new Datetime)->format('Y/m/d H:i:s'),
                                $field,
                                $changes[0],
                                $changes[1],
                                $this->user->getId()
                            );
                        }
                    }
                    apcu_store($key, $cacheEntries, 604800);
                }

            }
        }
    }
}