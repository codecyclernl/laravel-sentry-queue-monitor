<?php

namespace Codecycler\SentryQueueMonitor;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Sentry\CheckIn;
use Sentry\CheckInStatus;
use Sentry\Event;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\SentrySdk;

class CheckInJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function __construct()
    {
    }

    public function handle()
    {
        $options = SentrySdk::getCurrentHub()->getClient()->getOptions();

        $slug = Config::get('sentry-queue-monitor.slug');
        $id = null;

        $checkIn = new CheckIn(
            $slug,
            CheckInStatus::inProgress(),
            $id,
            $options->getRelease(),
            $options->getEnvironment()
        );

        $checkIn->setMonitorConfig(
            new MonitorConfig(
                MonitorSchedule::crontab('* * * * *'),
                null,
                null,
                Config::get('app.timezone', 'UTC')
            )
        );

        $event = Event::createCheckIn();
        $event->setCheckIn($checkIn);

        SentrySdk::getCurrentHub()->captureEvent($event);

        // Finish check-in
        $checkIn->setStatus(CheckInStatus::ok());

        $event = Event::createCheckIn();
        $event->setCheckIn($checkIn);

        SentrySdk::getCurrentHub()->captureEvent($event);
    }
}