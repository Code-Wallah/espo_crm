<?php

namespace Espo\Custom\Controllers;

use Espo\Core\Controllers\Base;

class EmailSyncSimple extends Base
{
    public function actionManualSync()
    {
        try {
            $container = $this->getContainer();
            $entityManager = $container->get('entityManager');
            
            // Get current user's email accounts
            $userId = $this->getUser()->getId();
            $emailAccounts = $entityManager->getRepository('EmailAccount')
                ->where([
                    'assignedUserId' => $userId,
                    'status' => 'Active',
                    'useImap' => true
                ])
                ->find();
            
            $results = [];
            
            foreach ($emailAccounts as $account) {
                try {
                    // Create job for this specific account
                    $job = $entityManager->getEntity('Job');
                    $job->set('job', 'CheckEmailAccounts');
                    $job->set('status', 'Pending');
                    $job->set('executeTime', date('Y-m-d H:i:s'));
                    $job->set('targetType', 'EmailAccount');
                    $job->set('targetId', $account->getId());
                    
                    $entityManager->saveEntity($job);
                    
                    $results[] = [
                        'account' => $account->get('name'),
                        'status' => 'queued',
                        'job_id' => $job->getId()
                    ];
                    
                } catch (\Exception $e) {
                    $results[] = [
                        'account' => $account->get('name'),
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'success' => true,
                'message' => 'Email sync jobs queued',
                'results' => $results,
                'debug' => [
                    'user_id' => $userId,
                    'accounts_found' => count($emailAccounts),
                    'total_accounts' => $entityManager->getRepository('EmailAccount')->count()
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 