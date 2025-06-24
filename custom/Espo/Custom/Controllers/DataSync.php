<?php

namespace Espo\Custom\Controllers;

use Espo\Core\Controllers\Base;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;

class DataSync extends Base
{
    /**
     * Sync account data from legacy system
     */
    public function postActionSyncAccounts(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        $data = $request->getParsedBody();
        $results = ['success' => 0, 'errors' => 0, 'details' => []];

        foreach ($data->accounts ?? [] as $accountData) {
            try {
                $this->syncSingleAccount($accountData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'id' => $accountData->legacyId ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $response->writeBody([
            'status' => 'completed',
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Sync contact data from legacy system
     */
    public function postActionSyncContacts(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        $data = $request->getParsedBody();
        $results = ['success' => 0, 'errors' => 0, 'details' => []];

        foreach ($data->contacts ?? [] as $contactData) {
            try {
                $this->syncSingleContact($contactData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'id' => $contactData->legacyId ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $response->writeBody([
            'status' => 'completed',
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Sync opportunity data from legacy system
     */
    public function postActionSyncOpportunities(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        $data = $request->getParsedBody();
        $results = ['success' => 0, 'errors' => 0, 'details' => []];

        foreach ($data->opportunities ?? [] as $opportunityData) {
            try {
                $this->syncSingleOpportunity($opportunityData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'id' => $opportunityData->legacyId ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $response->writeBody([
            'status' => 'completed',
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Sync sales team assignments from legacy system
     */
    public function postActionSyncSalesTeams(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        $data = $request->getParsedBody();
        $results = ['success' => 0, 'errors' => 0, 'details' => []];

        foreach ($data->salesTeams ?? [] as $teamData) {
            try {
                $this->syncSalesTeamAssignment($teamData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'staffId' => $teamData->staffId ?? 'unknown',
                    'publicationId' => $teamData->publicationId ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $response->writeBody([
            'status' => 'completed',
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Sync opportunity status/stage changes back to legacy system
     */
    public function postActionSyncOpportunityUpdates(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        // Get opportunities modified since last sync
        $repository = $this->entityManager->getRepository('Opportunity');
        $lastSyncTime = $this->getLastSyncTime('opportunityUpdates');
        
        $opportunities = $repository
            ->where(['modifiedAt>' => $lastSyncTime])
            ->where(['legacyLeadId!=' => null])
            ->find();

        $updates = [];
        foreach ($opportunities as $opportunity) {
            $updates[] = [
                'legacyLeadId' => $opportunity->get('legacyLeadId'),
                'legacyCompanyId' => $opportunity->get('legacyCompanyId'),
                'legacyStaffId' => $opportunity->get('legacyStaffId'),
                'legacyPublicationId' => $opportunity->get('legacyPublicationId'),
                'stage' => $opportunity->get('stage'),
                'amount' => $opportunity->get('amount'),
                'closeDate' => $opportunity->get('closeDate'),
                'assignedUserId' => $opportunity->get('assignedUserId'),
                'modifiedAt' => $opportunity->get('modifiedAt')
            ];
        }

        $this->updateSyncTime('opportunityUpdates');

        return $response->writeBody([
            'status' => 'completed',
            'updates' => $updates,
            'count' => count($updates),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get sync status and last sync times
     */
    public function getActionStatus(Request $request, Response $response): Response
    {
        return $response->writeBody([
            'status' => 'active',
            'lastSync' => [
                'accounts' => $this->getLastSyncTime('accounts'),
                'contacts' => $this->getLastSyncTime('contacts'),
                'opportunities' => $this->getLastSyncTime('opportunities')
            ],
            'counts' => [
                'accounts' => $this->entityManager->getRepository('Account')->count(),
                'contacts' => $this->entityManager->getRepository('Contact')->count(),
                'opportunities' => $this->entityManager->getRepository('Opportunity')->count()
            ]
        ]);
    }

    /**
     * Pull and sync company changes from MSSQL server
     */
    public function postActionPullCompanies(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        try {
            $lastSync = $this->getLastSyncTime('companies');
            $companies = $this->fetchFromMSSQLServer('sync_companies', $lastSync);
            
            $results = ['success' => 0, 'errors' => 0, 'details' => []];
            
            foreach ($companies as $companyData) {
                try {
                    $this->syncSingleAccount((object)$companyData);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'legacyId' => $companyData['legacyCompanyId'] ?? 'unknown',
                        'name' => $companyData['name'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            if ($results['success'] > 0) {
                $this->updateSyncTime('companies');
            }
            
            return $response->writeBody([
                'status' => 'completed',
                'source' => 'MSSQL Server',
                'endpoint' => 'sync_companies',
                'results' => $results,
                'lastSync' => $lastSync,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return $response->writeBody([
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Pull and sync contact changes from MSSQL server
     */
    public function postActionPullContacts(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        try {
            $lastSync = $this->getLastSyncTime('contacts');
            $contacts = $this->fetchFromMSSQLServer('sync_contacts', $lastSync);
            
            $results = ['success' => 0, 'errors' => 0, 'details' => []];
            
            foreach ($contacts as $contactData) {
                try {
                    $this->syncSingleContact((object)$contactData);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'legacyId' => $contactData['legacyId'] ?? 'unknown',
                        'name' => ($contactData['firstName'] ?? '') . ' ' . ($contactData['lastName'] ?? ''),
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            if ($results['success'] > 0) {
                $this->updateSyncTime('contacts');
            }
            
            return $response->writeBody([
                'status' => 'completed',
                'source' => 'MSSQL Server',
                'endpoint' => 'sync_contacts',
                'results' => $results,
                'lastSync' => $lastSync,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return $response->writeBody([
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Pull and sync opportunity changes from MSSQL server
     */
    public function postActionPullOpportunities(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        try {
            $lastSync = $this->getLastSyncTime('opportunities');
            $opportunities = $this->fetchFromMSSQLServer('sync_opportunities', $lastSync);
            
            $results = ['success' => 0, 'errors' => 0, 'details' => []];
            
            foreach ($opportunities as $opportunityData) {
                try {
                    $this->syncSingleOpportunity((object)$opportunityData);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'legacyId' => $opportunityData['legacyId'] ?? 'unknown',
                        'name' => $opportunityData['name'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            if ($results['success'] > 0) {
                $this->updateSyncTime('opportunities');
            }
            
            return $response->writeBody([
                'status' => 'completed',
                'source' => 'MSSQL Server',
                'endpoint' => 'sync_opportunities',
                'results' => $results,
                'lastSync' => $lastSync,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return $response->writeBody([
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Pull and sync staff changes from MSSQL server
     */
    public function postActionPullStaff(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        try {
            $lastSync = $this->getLastSyncTime('staff');
            $staff = $this->fetchFromMSSQLServer('sync_staff', $lastSync);
            
            $results = ['success' => 0, 'errors' => 0, 'details' => []];
            
            foreach ($staff as $staffData) {
                try {
                    $this->syncStaffMember($staffData);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'legacyId' => $staffData['legacyId'] ?? 'unknown',
                        'name' => ($staffData['firstName'] ?? '') . ' ' . ($staffData['lastName'] ?? ''),
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            if ($results['success'] > 0) {
                $this->updateSyncTime('staff');
            }
            
            return $response->writeBody([
                'status' => 'completed',
                'source' => 'MSSQL Server',
                'endpoint' => 'sync_staff',
                'results' => $results,
                'lastSync' => $lastSync,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return $response->writeBody([
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Run full sync - pull all entity types from MSSQL server
     */
    public function postActionPullAll(Request $request, Response $response): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Admin access required for data sync');
        }

        $overallResults = [
            'companies' => null,
            'contacts' => null,
            'opportunities' => null,
            'staff' => null,
            'totalSuccess' => 0,
            'totalErrors' => 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Sync companies
        try {
            $lastSync = $this->getLastSyncTime('companies');
            $companies = $this->fetchFromMSSQLServer('sync_companies', $lastSync);
            $results = $this->processSyncResults($companies, 'syncSingleAccount');
            $overallResults['companies'] = $results;
            $overallResults['totalSuccess'] += $results['success'];
            $overallResults['totalErrors'] += $results['errors'];
            if ($results['success'] > 0) $this->updateSyncTime('companies');
        } catch (\Exception $e) {
            $overallResults['companies'] = ['error' => $e->getMessage()];
        }

        // Sync contacts
        try {
            $lastSync = $this->getLastSyncTime('contacts');
            $contacts = $this->fetchFromMSSQLServer('sync_contacts', $lastSync);
            $results = $this->processSyncResults($contacts, 'syncSingleContact');
            $overallResults['contacts'] = $results;
            $overallResults['totalSuccess'] += $results['success'];
            $overallResults['totalErrors'] += $results['errors'];
            if ($results['success'] > 0) $this->updateSyncTime('contacts');
        } catch (\Exception $e) {
            $overallResults['contacts'] = ['error' => $e->getMessage()];
        }

        // Sync opportunities
        try {
            $lastSync = $this->getLastSyncTime('opportunities');
            $opportunities = $this->fetchFromMSSQLServer('sync_opportunities', $lastSync);
            $results = $this->processSyncResults($opportunities, 'syncSingleOpportunity');
            $overallResults['opportunities'] = $results;
            $overallResults['totalSuccess'] += $results['success'];
            $overallResults['totalErrors'] += $results['errors'];
            if ($results['success'] > 0) $this->updateSyncTime('opportunities');
        } catch (\Exception $e) {
            $overallResults['opportunities'] = ['error' => $e->getMessage()];
        }

        // Sync staff
        try {
            $lastSync = $this->getLastSyncTime('staff');
            $staff = $this->fetchFromMSSQLServer('sync_staff', $lastSync);
            $results = $this->processSyncResults($staff, 'syncStaffMember');
            $overallResults['staff'] = $results;
            $overallResults['totalSuccess'] += $results['success'];
            $overallResults['totalErrors'] += $results['errors'];
            if ($results['success'] > 0) $this->updateSyncTime('staff');
        } catch (\Exception $e) {
            $overallResults['staff'] = ['error' => $e->getMessage()];
        }

        return $response->writeBody([
            'status' => 'completed',
            'source' => 'MSSQL Server - Full Sync',
            'results' => $overallResults
        ]);
    }

    private function syncSingleAccount($data)
    {
        $repository = $this->entityManager->getRepository('Account');
        
        // Try to find existing account by legacy ID - FIXED: Use correct field name
        $account = $repository->where(['legacyCompanyId' => $data->legacyCompanyId])->findOne();
        
        if (!$account) {
            $account = $this->entityManager->getNewEntity('Account');
            $account->set('legacyCompanyId', $data->legacyCompanyId);
        }

        // Update account fields - ENSURE legacy ID is always set for existing accounts too
        $account->set([
            'name' => $data->name,
            'phoneNumber' => $data->telephone ?? null,
            'emailAddress' => $data->email ?? null,
            'industry' => $data->businessType ?? null,
            'isAgency' => (bool)($data->agency ?? false),  // Map Agency boolean field
            'accountType' => $data->agency ? 'Agency' : 'Client', // Set account type
            'legacyCompanyId' => $data->legacyCompanyId,  // FIXED: Ensure legacy ID is always set
            'modifiedAt' => $data->lastUpdated ?? date('Y-m-d H:i:s')
        ]);

        $this->entityManager->saveEntity($account);
        $this->updateSyncTime('accounts');
        
        return $account;
    }

    private function syncSingleContact($data)
    {
        $repository = $this->entityManager->getRepository('Contact');
        
        // Try to find existing contact by legacy ID
        $contact = $repository->where(['legacyContactId' => $data->legacyId])->findOne();
        
        if (!$contact) {
            $contact = $this->entityManager->getNewEntity('Contact');
            $contact->set('legacyContactId', $data->legacyId);
        }

        // Find related account
        $account = null;
        if ($data->companyId) {
            $account = $this->entityManager->getRepository('Account')
                ->where(['legacyCompanyId' => $data->companyId])
                ->findOne();
        }

        // Update contact fields
        $contact->set([
            'salutationName' => $data->salutation ?? null,
            'firstName' => $data->firstName,
            'lastName' => $data->lastName,
            'phoneNumber' => $data->telephone ?? null,
            'phoneNumberMobile' => $data->cell ?? null,
            'emailAddress' => $data->email ?? null,
            'accountId' => $account ? $account->getId() : null,
            'modifiedAt' => $data->lastUpdated ?? date('Y-m-d H:i:s')
        ]);

        $this->entityManager->saveEntity($contact);
        $this->updateSyncTime('contacts');
        
        return $contact;
    }

    private function syncSingleOpportunity($data)
    {
        $repository = $this->entityManager->getRepository('Opportunity');
        
        // Try to find existing opportunity by legacy ID
        $opportunity = $repository->where(['legacyLeadId' => $data->legacyId])->findOne();
        
        if (!$opportunity) {
            $opportunity = $this->entityManager->getNewEntity('Opportunity');
            $opportunity->set('legacyLeadId', $data->legacyId);
        }

        // Find related entities with debugging
        $account = null;
        $user = null;
        $publication = null;
        
        // Account lookup with logging
        if (!empty($data->companyId)) {
            $account = $this->entityManager->getRepository('Account')
                ->where(['legacyCompanyId' => $data->companyId])
                ->findOne();
            
            if (!$account) {
                error_log("DataSync: No account found for legacyCompanyId: {$data->companyId} (Opportunity: {$data->name})");
                
                // Check if ANY accounts exist with legacy IDs
                $totalAccounts = $this->entityManager->getRepository('Account')
                    ->where(['legacyCompanyId!=' => null])
                    ->count();
                error_log("DataSync: Total accounts with legacy IDs: {$totalAccounts}");
            } else {
                error_log("DataSync: Found account '{$account->get('name')}' for legacyCompanyId: {$data->companyId}");
            }
        } else {
            error_log("DataSync: Empty companyId for opportunity: {$data->name}");
        }
            
        // User lookup with logging
        if (!empty($data->staffId)) {
            $user = $this->entityManager->getRepository('User')
                ->where(['legacyStaffId' => $data->staffId])
                ->findOne();
                
            if (!$user) {
                error_log("DataSync: No user found for legacyStaffId: {$data->staffId}");
            } else {
                error_log("DataSync: Found user '{$user->get('name')}' for legacyStaffId: {$data->staffId}");
            }
        }
            
        // Publication lookup with logging
        if (!empty($data->publicationId)) {
            $publication = $this->entityManager->getRepository('CPublications')
                ->where(['legacyPublicationId' => $data->publicationId])
                ->findOne();
                
            if (!$publication) {
                error_log("DataSync: No publication found for legacyPublicationId: {$data->publicationId}");
            } else {
                error_log("DataSync: Found publication '{$publication->get('name')}' for legacyPublicationId: {$data->publicationId}");
            }
        }

        // Update basic opportunity fields
        $opportunity->set([
            'name' => $data->name,
            'assignedUserId' => $user ? $user->getId() : null, // This is a direct field, not a link
            'stage' => $this->mapLegacyStatus($data->statusId),
            'amount' => $data->amount ?? 25000,
            'closeDate' => $data->closeDate ?? date('Y-m-d', strtotime('+3 months')),
            'modifiedAt' => $data->lastUpdated ?? date('Y-m-d H:i:s'),
            // Store legacy foreign keys for bidirectional sync
            'legacyCompanyId' => $data->companyId,
            'legacyStaffId' => $data->staffId,
            'legacyPublicationId' => $data->publicationId
        ]);

        // Save the opportunity first
        $this->entityManager->saveEntity($opportunity);
        error_log("DataSync: Saved opportunity '{$opportunity->get('name')}' with legacyCompanyId: {$data->companyId}");

        // Now handle link field relationships using relate() method
        if ($account) {
            try {
                $this->entityManager->getRepository('Opportunity')
                    ->getRelation($opportunity, 'account')
                    ->relate($account);
                error_log("DataSync: Successfully related account '{$account->get('name')}' to opportunity '{$opportunity->get('name')}'");
                
                // Verify the relationship was created
                $this->entityManager->refreshEntity($opportunity);
                $linkedAccount = $opportunity->get('account');
                if ($linkedAccount) {
                    error_log("DataSync: Verified account link - opportunity now linked to '{$linkedAccount->get('name')}'");
                } else {
                    error_log("DataSync: ERROR - Account relate() succeeded but no link found after refresh!");
                }
            } catch (\Exception $e) {
                error_log("DataSync: Account relate() failed: " . $e->getMessage());
            }
        } else {
            error_log("DataSync: Skipping account relation - no account found");
        }

        if ($publication) {
            try {
                $this->entityManager->getRepository('Opportunity')
                    ->getRelation($opportunity, 'publication')
                    ->relate($publication);
                error_log("DataSync: Successfully related publication '{$publication->get('name')}' to opportunity '{$opportunity->get('name')}'");
            } catch (\Exception $e) {
                error_log("DataSync: Publication relate() failed: " . $e->getMessage());
            }
        } else {
            error_log("DataSync: Skipping publication relation - no publication found");
        }

        $this->updateSyncTime('opportunities');
        
        return $opportunity;
    }

    private function mapLegacyStatus($statusId)
    {
        $statusMap = [
            1 => 'Prospecting',      // New
            6 => 'Qualification',    // Contacted
            7 => 'Needs Analysis',   // Qualified
            8 => 'Value Proposition', // Proposal
            9 => 'Negotiation',      // Negotiation
            2 => 'Closed Won',       // Booked
            4 => 'Closed Lost'       // Blown
        ];
        
        return $statusMap[$statusId] ?? 'Prospecting';
    }

    private function getLastSyncTime($entity)
    {
        // Implementation to track last sync times
        // Could use Settings or custom table
        return date('Y-m-d H:i:s');
    }

    private function updateSyncTime($entity)
    {
        // Implementation to update last sync times
        // Could use Settings or custom table
    }

    private function syncSalesTeamAssignment($data)
    {
        // Find user by legacy staff ID
        $user = $this->entityManager->getRepository('User')
            ->where(['legacyStaffId' => $data->staffId])
            ->findOne();
            
        if (!$user) {
            throw new \Exception("User not found for StaffID: " . $data->staffId);
        }

        // Find publication
        $publication = $this->entityManager->getRepository('CPublications')
            ->where(['legacyPublicationId' => $data->publicationId])
            ->findOne();
            
        if (!$publication) {
            throw new \Exception("Publication not found for PublicationID: " . $data->publicationId);
        }

        // Find or create team for this publication
        $teamName = $publication->get('name') . ' Team';
        $team = $this->entityManager->getRepository('Team')
            ->where(['name' => $teamName])
            ->findOne();
            
        if (!$team) {
            $team = $this->entityManager->getNewEntity('Team');
            $team->set([
                'name' => $teamName,
                'description' => 'Sales team for ' . $publication->get('name'),
                'publicationId' => $publication->getId()
            ]);
            $this->entityManager->saveEntity($team);
        }

        // Add user to team if not already a member
        $teamUser = $this->entityManager->getRepository('TeamUser')
            ->where([
                'teamId' => $team->getId(),
                'userId' => $user->getId()
            ])
            ->findOne();
            
        if (!$teamUser) {
            $teamUser = $this->entityManager->getNewEntity('TeamUser');
            $teamUser->set([
                'teamId' => $team->getId(),
                'userId' => $user->getId()
            ]);
            $this->entityManager->saveEntity($teamUser);
        }

        // Set as home team if this is marked as home publication
        if ($data->homePub) {
            $user->set([
                'defaultTeamId' => $team->getId(),
                'homePublicationId' => $publication->getId()
            ]);
            $this->entityManager->saveEntity($user);
        }

        return $team;
    }

    /**
     * Fetch data from MSSQL server using the proven API format
     */
    private function fetchFromMSSQLServer($endpoint, $lastModified = null)
    {
        $url = 'https://ai.capemedia.co.za/api/?var=' . $endpoint;
        
        // Use the format that works in your independent tests
        $postData = [
            'lastModified' => $lastModified ?: '2020-01-01 00:00:00'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: EspoCRM-DataSync/1.0'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            throw new \Exception("CURL Error: " . $curl_error);
        }
        
        if ($http_code !== 200) {
            throw new \Exception("HTTP Error {$http_code}: " . substr($response, 0, 200));
        }
        
        // Handle the response - clean and decode
        $response = trim($response);
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Handle the special case where MSSQL returns "[]" string instead of [] array
            if ($response === '[]' || $response === '"[]"') {
                return [];
            }
            throw new \Exception("Invalid JSON response: " . json_last_error_msg() . " - Response: " . substr($response, 0, 200));
        }
        
        return $data;
    }

    /**
     * Process sync results for a given method
     */
    private function processSyncResults($dataArray, $syncMethodName)
    {
        $results = ['success' => 0, 'errors' => 0, 'details' => []];
        
        foreach ($dataArray as $itemData) {
            try {
                $this->$syncMethodName((object)$itemData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'data' => $itemData,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Sync a staff member to User entity
     */
    private function syncStaffMember($data)
    {
        $repository = $this->entityManager->getRepository('User');
        
        // Try to find existing user by legacy ID
        $user = $repository->where(['legacyStaffId' => $data['legacyId']])->findOne();
        
        if (!$user) {
            $user = $this->entityManager->getNewEntity('User');
            $user->set('legacyStaffId', $data['legacyId']);
            $user->set('type', 'regular'); // Set default user type
        }

        // Update user fields
        $user->set([
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'emailAddress' => $data['email'],
            'phoneNumber' => $data['phoneNumber'],
            'phoneNumberMobile' => $data['cell'] ?? null,
            'homePublicationId' => $data['homePublicationId'] ?? null,
            'position' => $data['position'] ?? null,
            'modifiedAt' => $data['lastUpdated'] ?? date('Y-m-d H:i:s')
        ]);

        $this->entityManager->saveEntity($user);
        
        return $user;
    }
} 