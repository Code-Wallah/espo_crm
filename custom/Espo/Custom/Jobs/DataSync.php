<?php

namespace Espo\Custom\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;

class DataSync implements JobDataLess
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
        $this->log->info('DataSync job started');
        
        try {
            // Test basic functionality first
            $this->log->info('DataSync: Testing API connectivity...');
            
            $this->syncData();
            $this->log->info('DataSync job completed successfully');
        } catch (\Exception $e) {
            $this->log->error('DataSync job failed: ' . $e->getMessage());
            $this->log->error('DataSync stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function syncData(): void
    {
        // Configuration - Call MSSQL API directly
        // Two-pass sync to handle circular dependencies between Publications and Staff
        $syncEndpoints = [
            // Pass 1: Create entities without cross-references
            'companies' => 'https://ai.capemedia.co.za/api/?var=sync_companies',
            'contacts' => 'https://ai.capemedia.co.za/api/?var=sync_contacts',
            'publications_basic' => 'https://ai.capemedia.co.za/api/?var=sync_publications',
            'staff_basic' => 'https://ai.capemedia.co.za/api/?var=sync_staff',
            
            // Pass 2: Update relationships
            'publications_relationships' => 'https://ai.capemedia.co.za/api/?var=sync_publications',
            'staff_relationships' => 'https://ai.capemedia.co.za/api/?var=sync_staff',
            
            // Pass 3: Opportunities (after all relationships established)
            'opportunities' => 'https://ai.capemedia.co.za/api/?var=sync_opportunities'
        ];

        // Get last sync time
        $lastSyncTime = $this->getLastSyncTime();
        $this->log->info("DataSync: Last sync time: $lastSyncTime");

        $totalSynced = 0;

        foreach ($syncEndpoints as $type => $url) {
            $this->log->info("DataSync: Starting $type sync");
            
            try {
                $rawData = $this->callSyncAPI($url, $lastSyncTime);
                
                // MSSQL API returns data directly, not wrapped
                $data = [
                    'count' => count($rawData),
                    'data' => $rawData
                ];
                
                $this->log->info("DataSync: Retrieved {$data['count']} $type records");
                
                if ($data['count'] > 0) {
                    $synced = $this->syncRecords($type, $data);
                    $this->log->info("DataSync: Synced $synced $type records");
                    $totalSynced += $synced;
                }
                
            } catch (\Exception $e) {
                $this->log->error("DataSync: Error syncing $type: " . $e->getMessage());
            }
        }

        // Only update sync time if we actually synced records successfully
        if ($totalSynced > 0) {
            $this->updateLastSyncTime();
            $this->log->info("DataSync: Successfully synced $totalSynced records - Updated sync timestamp");
        } else {
            $this->log->info("DataSync: No new records to sync - Keeping existing sync timestamp");
        }
    }

    private function callSyncAPI(string $url, ?string $lastModified = null): array
    {
        $ch = curl_init();
        
        // Prepare POST data (API expects POST, not GET)
        $postData = [
            'lastModified' => $lastModified
        ];
        
        $this->log->debug("DataSync: Calling API with lastModified: " . ($lastModified ?: 'null'));
        $this->log->debug("DataSync: POST data: " . http_build_query($postData));
        $this->log->debug("DataSync: URL: " . $url);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: EspoCRM-DataSync/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("CURL Error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP Error $httpCode: " . substr($response, 0, 200));
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response: " . json_last_error_msg());
        }
        
        return $data;
    }

    private function syncRecords(string $type, array $data): int
    {
        $count = 0;
        
        switch ($type) {
            case 'companies':
                $count = $this->syncCompanies($data);
                break;
            case 'contacts':
                $count = $this->syncContacts($data);
                break;
            case 'publications_basic':
                $count = $this->syncPublicationsBasic($data);
                break;
            case 'staff_basic':
                $count = $this->syncStaffBasic($data);
                break;
            case 'publications_relationships':
                $count = $this->syncPublicationsRelationships($data);
                break;
            case 'staff_relationships':
                $count = $this->syncStaffRelationships($data);
                break;
            case 'opportunities':
                $count = $this->syncOpportunities($data);
                break;
        }
        
        return $count;
    }

    private function syncCompanies(array $data): int
    {
        $count = 0;
        
        foreach ($data['data'] as $company) {
            try {
                // Find existing company by legacy ID first, then by name as fallback
                $existing = null;
                if (!empty($company['legacyCompanyId'])) {
                    $existing = $this->entityManager->getRepository('Account')
                        ->where(['legacyCompanyId' => $company['legacyCompanyId']])
                        ->findOne();
                }
                
                // Fallback to name-based search if not found by legacy ID
                if (!$existing) {
                    $existing = $this->entityManager->getRepository('Account')
                        ->where(['name' => $company['name']])
                        ->findOne();
                }
                
                $this->log->debug("DataSync: Checking for existing company: {$company['name']} - " . ($existing ? "Found" : "Not found"));
                
                if ($existing) {
                    // Update existing
                    $existing->set('name', $company['name']);
                    $existing->set('phoneNumber', $company['telephone']);
                    $existing->set('emailAddress', $company['email']);
                    $existing->set('legacyCompanyId', $company['legacyCompanyId']);
                    
                    // Set additional fields
                    if (!empty($company['webAddress']) && $company['webAddress'] !== 'na') {
                        $existing->set('website', $company['webAddress']);
                    }
                    
                    // Set custom fields for the additional data
                    try {
                        if (!empty($company['lastBookingDate']) && $company['lastBookingDate'] !== '1900-01-01 00:00:00') {
                            $existing->set('cLastBookingDate', $company['lastBookingDate']);
                        }
                        if (!empty($company['lastDealDate']) && $company['lastDealDate'] !== '1900-01-01 00:00:00') {
                            $existing->set('cLastDealDate', $company['lastDealDate']);
                        }
                        if (!empty($company['ltv'])) {
                            $existing->set('cLtv', $company['ltv']);
                        }
                        if (!empty($company['agency'])) {
                            $existing->set('cAgency', $company['agency']);
                        }
                        if (!empty($company['businessTypeId'])) {
                            $existing->set('cBusinessTypeID', $company['businessTypeId']);
                        }
                    } catch (\Exception $e) {
                        $this->log->debug("DataSync: Some custom company fields not available: " . $e->getMessage());
                    }
                    
                    $this->entityManager->saveEntity($existing);
                    $this->log->debug("DataSync: Updated company: {$company['name']} (Legacy ID: {$company['legacyCompanyId']})");
                } else {
                    // Create new company
                    $accountData = [
                        'name' => $company['name'],
                        'phoneNumber' => $company['telephone'],
                        'emailAddress' => $company['email'],
                        'legacyCompanyId' => $company['legacyCompanyId']
                    ];
                    
                    // Add website if available
                    if (!empty($company['webAddress']) && $company['webAddress'] !== 'na') {
                        $accountData['website'] = $company['webAddress'];
                    }
                    
                    // Add custom fields
                    try {
                        if (!empty($company['lastBookingDate']) && $company['lastBookingDate'] !== '1900-01-01 00:00:00') {
                            $accountData['cLastBookingDate'] = $company['lastBookingDate'];
                        }
                        if (!empty($company['lastDealDate']) && $company['lastDealDate'] !== '1900-01-01 00:00:00') {
                            $accountData['cLastDealDate'] = $company['lastDealDate'];
                        }
                        if (!empty($company['ltv'])) {
                            $accountData['cLtv'] = $company['ltv'];
                        }
                        if (!empty($company['agency'])) {
                            $accountData['cAgency'] = $company['agency'];
                        }
                        if (!empty($company['businessTypeId'])) {
                            $accountData['cBusinessTypeID'] = $company['businessTypeId'];
                        }
                    } catch (\Exception $e) {
                        $this->log->debug("DataSync: Some custom company fields not available for new record: " . $e->getMessage());
                    }
                    
                    $account = $this->entityManager->createEntity('Account', $accountData);
                    $this->log->debug("DataSync: Created company: {$company['name']} (Legacy ID: {$company['legacyCompanyId']})");
                }
                $count++;
            } catch (\Exception $e) {
                $this->log->error("DataSync: Error syncing company {$company['name']} (Legacy ID: {$company['legacyCompanyId']}): " . $e->getMessage());
            }
        }
        
        return $count;
    }

    private function syncContacts(array $data): int
    {
        $count = 0;
        
        foreach ($data['data'] as $contact) {
            try {
                // Find existing contact by legacy ID first, then by name as fallback
                $existing = null;
                if (!empty($contact['legacyId'])) {
                    $existing = $this->entityManager->getRepository('Contact')
                        ->where(['legacyContactId' => $contact['legacyId']])
                        ->findOne();
                }
                
                // Fallback to name+email combination if not found by legacy ID
                if (!$existing && !empty($contact['email'])) {
                    $existing = $this->entityManager->getRepository('Contact')
                        ->where([
                            'firstName' => $contact['firstName'],
                            'lastName' => $contact['lastName'],
                            'emailAddress' => $contact['email']
                        ])
                        ->findOne();
                }
                
                // Final fallback to just name combination
                if (!$existing) {
                    $existing = $this->entityManager->getRepository('Contact')
                        ->where([
                            'firstName' => $contact['firstName'],
                            'lastName' => $contact['lastName']
                        ])
                        ->findOne();
                }
                
                $this->log->debug("DataSync: Checking for existing contact: {$contact['firstName']} {$contact['lastName']} - " . ($existing ? "Found" : "Not found"));
                
                // Try to find the associated company account by legacy company ID
                $account = null;
                if (!empty($contact['companyId'])) {
                    $account = $this->entityManager->getRepository('Account')
                        ->where(['legacyCompanyId' => $contact['companyId']])
                        ->findOne();
                    
                    if ($account) {
                        $this->log->debug("DataSync: Found company for contact: {$account->get('name')} (Legacy ID: {$contact['companyId']})");
                    } else {
                        $this->log->debug("DataSync: Company not found for companyId {$contact['companyId']} - contact will be created without company association");
                    }
                }
                
                if ($existing) {
                    // Update existing
                    $existing->set('firstName', $contact['firstName']);
                    $existing->set('lastName', $contact['lastName']);
                    $existing->set('phoneNumber', $contact['telephone']);
                    $existing->set('phoneNumberMobile', $contact['cell']);
                    $existing->set('emailAddress', $contact['email']);
                    
                    if ($account) {
                        $existing->set('accountId', $account->getId());
                    }
                    
                    // Set salutation and legacy ID
                    if (!empty($contact['salutation'])) {
                        $existing->set('salutationName', trim($contact['salutation']));
                    }
                    $existing->set('legacyContactId', $contact['legacyId']);
                    
                    $this->entityManager->saveEntity($existing);
                    $this->log->debug("DataSync: Updated contact: {$contact['firstName']} {$contact['lastName']} (Legacy ID: {$contact['legacyId']})");
                } else {
                    // Create new
                    $contactData = [
                        'firstName' => $contact['firstName'],
                        'lastName' => $contact['lastName'],
                        'phoneNumber' => $contact['telephone'],
                        'phoneNumberMobile' => $contact['cell'],
                        'emailAddress' => $contact['email']
                    ];
                    
                    if ($account) {
                        $contactData['accountId'] = $account->getId();
                    }
                    
                    // Add salutation and legacy ID
                    if (!empty($contact['salutation'])) {
                        $contactData['salutationName'] = trim($contact['salutation']);
                    }
                    $contactData['legacyContactId'] = $contact['legacyId'];
                    
                    $newContact = $this->entityManager->createEntity('Contact', $contactData);
                    $this->log->debug("DataSync: Created contact: {$contact['firstName']} {$contact['lastName']} (Legacy ID: {$contact['legacyId']})");
                }
                $count++;
            } catch (\Exception $e) {
                $this->log->error("DataSync: Error syncing contact {$contact['firstName']} {$contact['lastName']}: " . $e->getMessage());
            }
        }
        
        return $count;
    }

    private function syncOpportunities(array $data): int
    {
        $count = 0;
        
        foreach ($data['data'] as $opportunity) {
            try {
                // Find existing opportunity by legacy ID
                $existing = null;
                if (!empty($opportunity['legacyId'])) {
                    $existing = $this->entityManager->getRepository('Opportunity')
                        ->where(['legacyLeadId' => $opportunity['legacyId']])
                        ->findOne();
                }
                
                $this->log->debug("DataSync: Checking for existing opportunity: {$opportunity['name']} - " . ($existing ? "Found" : "Not found"));
                
                // Find related entities
                $account = null;
                if (!empty($opportunity['companyId'])) {
                    $account = $this->entityManager->getRepository('Account')
                        ->where(['legacyCompanyId' => $opportunity['companyId']])
                        ->findOne();
                }
                
                $user = null;
                if (!empty($opportunity['staffId'])) {
                    $user = $this->entityManager->getRepository('User')
                        ->where(['legacyStaffId' => $opportunity['staffId']])
                        ->findOne();
                }
                
                $publication = null;
                if (!empty($opportunity['publicationId'])) {
                    $publication = $this->entityManager->getRepository('CPublications')
                        ->where(['legacyPublicationId' => $opportunity['publicationId']])
                        ->findOne();
                }
                
                // Map legacy status to EspoCRM stages
                $statusMap = [
                    1 => 'Prospecting',      // New
                    6 => 'Qualification',    // Contacted
                    7 => 'Needs Analysis',   // Qualified
                    8 => 'Value Proposition', // Proposal
                    9 => 'Negotiation',      // Negotiation
                    2 => 'Closed Won',       // Booked
                    4 => 'Closed Lost'       // Blown
                ];
                
                $stage = $statusMap[$opportunity['statusId']] ?? 'Prospecting';
                
                if ($existing) {
                    // Update existing opportunity
                    $existing->set('name', $opportunity['name']);
                    $existing->set('stage', $stage);
                    $existing->set('amount', $opportunity['amount'] ?? 25000);
                    $existing->set('closeDate', $opportunity['closeDate'] ?? date('Y-m-d', strtotime('+3 months')));
                    
                    // Set EspoCRM relationships using relate() for link fields
                    if ($account) {
                        $this->entityManager->getRepository('Opportunity')
                            ->getRelation($existing, 'account')
                            ->relate($account);
                    }
                    
                    if ($user) {
                        $existing->set('assignedUserId', $user->getId());
                    }
                    
                    if ($publication) {
                        $this->entityManager->getRepository('Opportunity')
                            ->getRelation($existing, 'publication')
                            ->relate($publication);
                    }
                    
                    // Store legacy foreign keys for bidirectional sync
                    $existing->set('legacyCompanyId', $opportunity['companyId']);
                    $existing->set('legacyStaffId', $opportunity['staffId']);
                    $existing->set('legacyPublicationId', $opportunity['publicationId']);
                    
                    $this->entityManager->saveEntity($existing);
                    $this->log->debug("DataSync: Updated opportunity: {$opportunity['name']} (Legacy ID: {$opportunity['legacyId']})");
                } else {
                    // Create new opportunity with proper relationships
                    $opportunityData = [
                        'name' => $opportunity['name'],
                        'stage' => $stage,
                        'amount' => $opportunity['amount'] ?? 25000,
                        'closeDate' => $opportunity['closeDate'] ?? date('Y-m-d', strtotime('+3 months')),
                        'legacyLeadId' => $opportunity['legacyId'],
                        'assignedUserId' => $user ? $user->getId() : null,
                        
                        // Store legacy foreign keys for bidirectional sync
                        'legacyCompanyId' => $opportunity['companyId'],
                        'legacyStaffId' => $opportunity['staffId'],
                        'legacyPublicationId' => $opportunity['publicationId']
                    ];
                    
                    $newOpportunity = $this->entityManager->createEntity('Opportunity', $opportunityData);
                    
                    // Set relationships after creation using relate() for link fields
                    if ($account) {
                        $this->entityManager->getRepository('Opportunity')
                            ->getRelation($newOpportunity, 'account')
                            ->relate($account);
                    }
                    
                    if ($publication) {
                        $this->entityManager->getRepository('Opportunity')
                            ->getRelation($newOpportunity, 'publication')
                            ->relate($publication);
                    }
                    
                    $this->log->debug("DataSync: Created opportunity: {$opportunity['name']} (Legacy ID: {$opportunity['legacyId']})");
                }
                $count++;
            } catch (\Exception $e) {
                $this->log->error("DataSync: Error syncing opportunity {$opportunity['name']}: " . $e->getMessage());
            }
        }
        
        return $count;
    }

    private function syncPublicationsBasic(array $data): int
    {
        $count = 0;
        
        foreach ($data['data'] as $publication) {
            try {
                // Find existing publication by legacy ID first
                $existing = null;
                if (!empty($publication['legacyId'])) {
                    $existing = $this->entityManager->getRepository('CPublications')
                        ->where(['legacyPublicationId' => $publication['legacyId']])
                        ->findOne();
                }
                
                // Fallback to name-based search if not found by legacy ID
                if (!$existing && !empty($publication['name'])) {
                    $existing = $this->entityManager->getRepository('CPublications')
                        ->where(['name' => $publication['name']])
                        ->findOne();
                }
                
                $this->log->debug("DataSync: Checking for existing publication: {$publication['name']} - " . ($existing ? "Found" : "Not found"));
                
                if ($existing) {
                    // Update existing publication (basic fields only)
                    $existing->set('name', $publication['name']);
                    $existing->set('legacyPublicationId', $publication['legacyId']);
                    
                    // Set additional fields if they exist (handle field name variations)
                    if (!empty($publication['publicationEditionIdSales'])) {
                        $existing->set('publicationEditionIdSales', $publication['publicationEditionIdSales']);
                    } elseif (!empty($publication['publicationEditionIDSales'])) {
                        $existing->set('publicationEditionIdSales', $publication['publicationEditionIDSales']);
                    }
                    if (!empty($publication['publicationEditionIdProd'])) {
                        $existing->set('publicationEditionIdProd', $publication['publicationEditionIdProd']);
                    } elseif (!empty($publication['publicationEditionIDProd'])) {
                        $existing->set('publicationEditionIdProd', $publication['publicationEditionIDProd']);
                    }
                    
                    $this->entityManager->saveEntity($existing);
                    $this->log->debug("DataSync: Updated publication (basic): {$publication['name']} (Legacy ID: {$publication['legacyId']})");
                } else {
                    // Create new publication (basic fields only)
                    $publicationData = [
                        'name' => $publication['name'],
                        'legacyPublicationId' => $publication['legacyId']
                    ];
                    
                    // Add additional fields if they exist (handle field name variations)
                    if (!empty($publication['publicationEditionIdSales'])) {
                        $publicationData['publicationEditionIdSales'] = $publication['publicationEditionIdSales'];
                    } elseif (!empty($publication['publicationEditionIDSales'])) {
                        $publicationData['publicationEditionIdSales'] = $publication['publicationEditionIDSales'];
                    }
                    if (!empty($publication['publicationEditionIdProd'])) {
                        $publicationData['publicationEditionIdProd'] = $publication['publicationEditionIdProd'];
                    } elseif (!empty($publication['publicationEditionIDProd'])) {
                        $publicationData['publicationEditionIdProd'] = $publication['publicationEditionIDProd'];
                    }
                    
                    $newPublication = $this->entityManager->createEntity('CPublications', $publicationData);
                    $this->log->debug("DataSync: Created publication (basic): {$publication['name']} (Legacy ID: {$publication['legacyId']})");
                }
                $count++;
            } catch (\Exception $e) {
                $this->log->error("DataSync: Error syncing publication (basic) {$publication['name']}: " . $e->getMessage());
            }
        }
        
        return $count;
    }

    private function syncPublicationsRelationships(array $data): int
    {
        $count = 0;
        
        foreach ($data['data'] as $publication) {
            try {
                // Find existing publication by legacy ID
                $existing = $this->entityManager->getRepository('CPublications')
                    ->where(['legacyPublicationId' => $publication['legacyId']])
                    ->findOne();
                
                if (!$existing) {
                    $this->log->warning("DataSync: Publication not found for relationships sync: {$publication['name']} (Legacy ID: {$publication['legacyId']})");
                    continue;
                }
                
                $this->log->info("DataSync: Processing publication relationships: {$publication['name']} (Legacy ID: {$publication['legacyId']})");
                
                // Find sales manager user by legacy staff ID
                $salesManager = null;
                if (!empty($publication['salesManagerId'])) {
                    $salesManager = $this->entityManager->getRepository('User')
                        ->where(['legacyStaffId' => $publication['salesManagerId']])
                        ->findOne();
                    
                    if ($salesManager) {
                        $this->log->info("DataSync: Found sales manager: {$salesManager->get('firstName')} {$salesManager->get('lastName')} (Legacy ID: {$publication['salesManagerId']})");
                    } else {
                        $this->log->warning("DataSync: Sales manager not found with legacyStaffId = {$publication['salesManagerId']} for publication {$publication['name']}");
                    }
                }
                
                // Update relationships only if manager found
                if ($salesManager) {
                    // Set EspoCRM relationship (for platform functionality)
                    $this->entityManager->getRepository('CPublications')
                        ->getRelation($existing, 'salesManager')
                        ->relate($salesManager);
                    
                    // Set direct field and legacy ID (for sync compatibility)
                    $existing->set('assignedUserId', $salesManager->getId());
                    $existing->set('legacySalesManagerId', $publication['salesManagerId']);
                    
                    // Set legacy edition IDs if available
                    if (!empty($publication['publicationEditionIdSales'])) {
                        $existing->set('legacyPublicationEditionIdSales', $publication['publicationEditionIdSales']);
                    }
                    if (!empty($publication['publicationEditionIdProd'])) {
                        $existing->set('legacyPublicationEditionIdProd', $publication['publicationEditionIdProd']);
                    }
                    
                    $this->entityManager->saveEntity($existing);
                    $count++;
                    $this->log->info("DataSync: SAVED publication relationships: {$publication['name']} → Manager: {$salesManager->get('firstName')} {$salesManager->get('lastName')}");
                } else {
                    $this->log->warning("DataSync: SKIPPING publication {$publication['name']} - Manager ID {$publication['salesManagerId']} not found in database");
                }
                
            } catch (\Exception $e) {
                $this->log->error("DataSync: Error syncing publication relationships {$publication['name']}: " . $e->getMessage());
            }
        }
        
        return $count;
    }

    private function syncStaffBasic(array $data): int
    {
        $count = 0;
        
        foreach ($data['data'] as $staff) {
            try {
                // Find existing user by legacy ID first
                $existing = null;
                if (!empty($staff['legacyId'])) {
                    $existing = $this->entityManager->getRepository('User')
                        ->where(['legacyStaffId' => $staff['legacyId']])
                        ->findOne();
                }
                
                // Fallback to email-based search if not found by legacy ID
                if (!$existing && !empty($staff['email'])) {
                    $existing = $this->entityManager->getRepository('User')
                        ->where(['emailAddress' => $staff['email']])
                        ->findOne();
                }
                
                $this->log->debug("DataSync: Checking for existing staff: {$staff['firstName']} {$staff['lastName']} - " . ($existing ? "Found" : "Not found"));
                
                if ($existing) {
                    // Update existing user (basic fields only)
                    $existing->set('firstName', $staff['firstName']);
                    $existing->set('lastName', $staff['lastName']);
                    $existing->set('emailAddress', $staff['email']);
                    $existing->set('phoneNumber', $staff['phoneNumber']);
                    $existing->set('phoneNumberMobile', $staff['cell'] ?? null);
                    $existing->set('legacyStaffId', $staff['legacyId']);
                    
                    // Generate userName if not exists (firstName.lastName format)
                    if (!$existing->get('userName')) {
                        $userName = strtolower($staff['firstName'] . '.' . $staff['lastName']);
                        $existing->set('userName', $userName);
                    }
                    
                    $this->entityManager->saveEntity($existing);
                    $this->log->debug("DataSync: Updated staff (basic): {$staff['firstName']} {$staff['lastName']} (Legacy ID: {$staff['legacyId']})");
                } else {
                    // Generate userName for new user (firstName.lastName format)
                    $userName = strtolower($staff['firstName'] . '.' . $staff['lastName']);
                    
                    // Create new user (basic fields only)
                    $userData = [
                        'firstName' => $staff['firstName'],
                        'lastName' => $staff['lastName'],
                        'userName' => $userName,
                        'emailAddress' => $staff['email'],
                        'phoneNumber' => $staff['phoneNumber'],
                        'phoneNumberMobile' => $staff['cell'] ?? null,
                        'legacyStaffId' => $staff['legacyId'],
                        'type' => 'regular',
                        'isActive' => true
                    ];
                    
                    $newUser = $this->entityManager->createEntity('User', $userData);
                    $this->log->debug("DataSync: Created staff (basic): {$staff['firstName']} {$staff['lastName']} (Legacy ID: {$staff['legacyId']})");
                }
                $count++;
            } catch (\Exception $e) {
                $this->log->error("DataSync: Error syncing staff (basic) {$staff['firstName']} {$staff['lastName']}: " . $e->getMessage());
            }
        }
        
        return $count;
    }

    private function syncStaffRelationships(array $data): int
    {
        $count = 0;
        
        foreach ($data['data'] as $staff) {
            try {
                // Find existing user by legacy ID
                $existing = $this->entityManager->getRepository('User')
                    ->where(['legacyStaffId' => $staff['legacyId']])
                    ->findOne();
                
                if (!$existing) {
                    $this->log->warning("DataSync: User not found for relationships sync: {$staff['firstName']} {$staff['lastName']} (Legacy ID: {$staff['legacyId']})");
                    continue;
                }
                
                $this->log->info("DataSync: Processing staff relationships: {$staff['firstName']} {$staff['lastName']} (Legacy ID: {$staff['legacyId']})");
                
                // Find home publication by legacy ID
                $homePublication = null;
                if (!empty($staff['homePublicationId'])) {
                    $homePublication = $this->entityManager->getRepository('CPublications')
                        ->where(['legacyPublicationId' => $staff['homePublicationId']])
                        ->findOne();
                    
                    if ($homePublication) {
                        $this->log->info("DataSync: Found home publication: {$homePublication->get('name')} (Legacy ID: {$staff['homePublicationId']})");
                    } else {
                        $this->log->warning("DataSync: Home publication not found with legacyPublicationId = {$staff['homePublicationId']} for staff {$staff['firstName']} {$staff['lastName']}");
                    }
                }
                
                // Update relationships only
                if ($homePublication) {
                    // Set EspoCRM relationship (for platform functionality)
                    $this->entityManager->getRepository('User')
                        ->getRelation($existing, 'homePublicationId')
                        ->relate($homePublication);
                    
                    // Set legacy ID (for sync compatibility)
                    $existing->set('legacyHomePublicationId', $staff['homePublicationId']);
                    
                    $this->entityManager->saveEntity($existing);
                    $count++;
                    $this->log->info("DataSync: SAVED staff relationships: {$staff['firstName']} {$staff['lastName']} → Home Publication: {$homePublication->get('name')}");
                } else {
                    $this->log->warning("DataSync: SKIPPING staff {$staff['firstName']} {$staff['lastName']} - Home publication ID {$staff['homePublicationId']} not found in database");
                }
            } catch (\Exception $e) {
                $this->log->error("DataSync: Error syncing staff relationships {$staff['firstName']} {$staff['lastName']}: " . $e->getMessage());
            }
        }
        
        return $count;
    }

    private function getLastSyncTime(): string
    {
        // Use a simple file-based approach for now
        $syncTimeFile = 'data/datasync-last-run.txt';
        
        if (file_exists($syncTimeFile)) {
            $lastTime = trim(file_get_contents($syncTimeFile));
            if ($lastTime) {
                return $lastTime;
            }
        }
        
        // Default to a recent date for initial sync
        return '2024-01-01 00:00:00';
    }

    private function updateLastSyncTime(): void
    {
        $currentTime = date('Y-m-d H:i:s');
        $syncTimeFile = 'data/datasync-last-run.txt';
        
        // Ensure data directory exists
        if (!is_dir('data')) {
            mkdir('data', 0755, true);
        }
        
        // Save to file
        file_put_contents($syncTimeFile, $currentTime);
    }
} 