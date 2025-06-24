<?php

namespace Espo\Custom\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;

class DataSyncDebug implements JobDataLess
{
    private EntityManager $entityManager;
    private Log $log;

    public function __construct(EntityManager $entityManager, Log $log)
    {
        $this->entityManager = $entityManager;
        $this->log = $log;
    }

    public function run(): void
    {
        $this->log->info('=== DataSyncDebug: Starting ===');
        
        try {
            // Step 1: Test EntityManager
            $this->log->info('DataSyncDebug: Step 1 - Testing EntityManager...');
            $accountCount = $this->entityManager->getRepository('Account')->count();
            $this->log->info("DataSyncDebug: EntityManager works - found $accountCount accounts");
            
            // Step 2: Test API call
            $this->log->info('DataSyncDebug: Step 2 - Testing API call...');
            $data = $this->testAPICall();
            $this->log->info("DataSyncDebug: API call successful - got {$data['count']} companies");
            
            // Step 3: Test single record creation
            $this->log->info('DataSyncDebug: Step 3 - Testing single record creation...');
            if ($data['count'] > 0) {
                $firstCompany = $data['data'][0];
                $this->log->info("DataSyncDebug: Attempting to create company: {$firstCompany['name']}");
                
                // Check if it already exists
                $existing = $this->entityManager->getRepository('Account')
                    ->where(['name' => $firstCompany['name']])
                    ->findOne();
                
                if ($existing) {
                    $this->log->info("DataSyncDebug: Company already exists: {$firstCompany['name']}");
                } else {
                    // Try to create minimal account
                    $account = $this->entityManager->createEntity('Account', [
                        'name' => $firstCompany['name']
                    ]);
                    $this->log->info("DataSyncDebug: Successfully created account: {$firstCompany['name']} with ID: " . $account->getId());
                }
            }
            
            $this->log->info('=== DataSyncDebug: Completed Successfully ===');
            
        } catch (\Exception $e) {
            $this->log->error('=== DataSyncDebug: Failed ===');
            $this->log->error('DataSyncDebug Error: ' . $e->getMessage());
            $this->log->error('DataSyncDebug File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->log->error('DataSyncDebug Stack: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function testAPICall(): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://crm.capemedia.co.za/api/sync/companies.php');
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
        
        return $data;
    }
} 