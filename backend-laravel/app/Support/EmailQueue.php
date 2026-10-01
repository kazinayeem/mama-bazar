<?php

namespace App\Support;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Routes email jobs to the configured email queue. With a "sync"
 * connection, web requests defer the job until after the response is
 * sent so checkout and registration never wait on SMTP.
 */
class EmailQueue
{
    public static function connection(): string
    {
        return (string) config('email_system.queue_connection', 'database');
    }

    public static function isBackground(): bool
    {
        return self::connection() !== 'sync';
    }

    public static function dispatch(ShouldQueue $job, ?\DateTimeInterface $delay = null): bool
    {
        $bus = app(Dispatcher::class);

        try {
            if (! self::isBackground()) {
                if (app()->runningInConsole()) {
                    $bus->dispatchSync($job);
                } else {
                    $bus->dispatchAfterResponse($job);
                }

                return true;
            }

            if (method_exists($job, 'onConnection')) {
                $job->onConnection(self::connection());
                $job->onQueue((string) config('email_system.queue_name', 'emails'));
                if ($delay) {
                    $job->delay($delay);
                }
            }

            $bus->dispatch($job);

            return true;
        } catch (Throwable $e) {
            Log::error('Email job dispatch failed: '.get_class($job).' — '.$e->getMessage());

            return false;
        }
    }
}
