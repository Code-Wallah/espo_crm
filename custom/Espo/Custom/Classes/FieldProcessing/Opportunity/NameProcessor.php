<?php

namespace Espo\Custom\Classes\FieldProcessing\Opportunity;

use Espo\ORM\Entity;
use Espo\Core\FieldProcessing\Processor;
use Espo\Core\FieldProcessing\Processor\Params;

class NameProcessor implements Processor
{
    private $entityManager;

    public function __construct(\Espo\ORM\EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function process(Entity $entity, Params $params): void
    {
        // Only process if account is set and name is empty or needs update
        $accountId = $entity->get('accountId');
        
        if (!$accountId) {
            return;
        }

        // Get the account to fetch its name
        $account = $this->entityManager->getRepository('Account')->getById($accountId);
        
        if (!$account) {
            return;
        }

        $accountName = $account->get('name');
        
        if (!$accountName) {
            return;
        }

        // Set opportunity name based on account name
        // Format: "Account Name - Opportunity"
        $opportunityName = $accountName . ' - Opportunity';
        
        // Only update if name is empty, or if account changed and we want to update
        $currentName = $entity->get('name');
        
        if (!$currentName || $entity->isAttributeChanged('accountId')) {
            $entity->set('name', $opportunityName);
        }
    }
} 