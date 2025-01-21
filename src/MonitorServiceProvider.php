<?php

namespace Codecycler\SentryQueueMonitor;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MonitorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerPublishes();
    }

    public function boot()
    {
        $this->bootSchedule();

        $this->bootConfig();
    }

    private function registerPublishes()
    {
        $this->publishes([
            __DIR__ . '/../config/sentry-queue-monitor.php' => config_path('sentry-queue-monitor.php'),
        ], 'sentry-queue-monitor-config');
    }

    private function bootSchedule()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->app->afterResolving(Schedule::class, static function (Schedule  $schedule) {
            $schedule
                ->call(function () {
                    foreach (Config::get('sentry-queue-monitor.queues') as $queue) {
                        Bus::dispatch((new CheckInJob())->onQueue($queue));
                    }
                })
                ->everyMinute();
        });
    }

    private function bootConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sentry-queue-monitor.php', 'sentry-queue-monitor');
    }
}