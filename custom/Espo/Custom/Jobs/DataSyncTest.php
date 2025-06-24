<?php

namespace Espo\Custom\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\Utils\Log;

class DataSyncTest implements JobDataLess
{
    private Log $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }

    public function run(): void
    {
        $this->log->info('DataSyncTest: Job started successfully');
        
        try {
            // Test API call
            $this->log->info('DataSyncTest: Testing API call...');
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://crm.capemedia.co.za/api/sync/status.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new \Exception("CURL Error: $error");
            }
            
            if ($httpCode !== 200) {
                throw new \Exception("HTTP Error $httpCode");
            }
            
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON response");
            }
            
            $this->log->info('DataSyncTest: API call successful - Status: ' . $data['status']);
            $this->log->info('DataSyncTest: Job completed successfully');
            
        } catch (\Exception $e) {
            $this->log->error('DataSyncTest failed: ' . $e->getMessage());
            throw $e;
        }
    }
} 