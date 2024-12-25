<?php

namespace App\Scheduler;

use App\Message\UnassignedTicketMessage;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('UnassignedTicket')]
final class UnassignedTicketSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {

        $startTime = new DateTimeImmutable('00:00', new DateTimeZone('Europe/Paris'));

        return (new Schedule())
            ->add(
                RecurringMessage::every(
                    '1 day',
                    new UnassignedTicketMessage(),
                    from: $startTime,
                ),
            )
            ->stateful($this->cache)
        ;
    }
}
