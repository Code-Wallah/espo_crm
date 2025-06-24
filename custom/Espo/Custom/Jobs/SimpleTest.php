<?php

namespace Espo\Custom\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\Utils\Log;

class SimpleTest implements JobDataLess
{
    private $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }

    public function run(): void
    {
        $this->log->info('SimpleTest Job Started');
        $this->log->debug('This is a debug message');
        $this->log->warning('This is a warning message');
        $this->log->error('This is an error message (for testing)');
        
        // Also try to write to data directory
        $dataDir = 'data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        $logFile = $dataDir . '/simple_test.log';
        $timestamp = date('Y-m-d H:i:s');
        $message = "[$timestamp] SimpleTest Job executed successfully\n";
        
        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
        
        $this->log->info('SimpleTest Job Completed - Check data/simple_test.log');
    }
} 