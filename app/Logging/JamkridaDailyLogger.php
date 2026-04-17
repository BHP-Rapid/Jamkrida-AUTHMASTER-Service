<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class JamkridaDailyLogger
{
    public function __invoke(array $config): Logger
    {
        $date = now()->format('dmY');
        $path = storage_path('logs/'.$date.'-jamkrida-log.log');
        $level = Logger::toMonologLevel($config['level'] ?? 'debug');

        $handler = new StreamHandler($path, $level);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        return new Logger('jamkrida-daily', [$handler]);
    }
}
