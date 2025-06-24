# 🚀 DataSync Best Practices & Field Mapping Guide

**Purpose**: Essential patterns and principles for maintaining the MSSQL-EspoCRM synchronization  
**Applies to**: DataSync Job, DataSync Controller, API integrations

---

## 🔍 **Field Mapping Principles**

### **1. API Field Name Consistency**
**Principle**: Always verify exact field names returned by APIs

```php
// ✅ CORRECT: Match API response exactly
$account->set('legacyCompanyId', $data->legacyCompanyId);

// ❌ WRONG: Assuming different field names
$account->set('legacyCompanyId', $data->legacyId);  // Field doesn't exist!
```

**Best Practice**: Log sample API responses during development:
```php
error_log("API Response: " . json_encode($data));
```

### **2. Defensive Field Mapping**
**Principle**: Handle field name variations gracefully

```php
// Handle both camelCase variations
if (!empty($data['publicationEditionIdSales'])) {
    $entity->set('publicationEditionIdSales', $data['publicationEditionIdSales']);
} elseif (!empty($data['publicationEditionIDSales'])) {
    $entity->set('publicationEditionIdSales', $data['publicationEditionIDSales']);
}

// Fallback for missing fields
$legacyId = $data['legacyId'] 
    ?? $data['legacy_id'] 
    ?? $data['legacyCompanyId'] 
    ?? null;
```

### **3. Field Existence Validation**
**Principle**: Never assume fields exist in API responses

```php
// ✅ CORRECT: Check before accessing
if (!empty($data->companyId)) {
    $account = $this->findAccountByLegacyId($data->companyId);
}

// ❌ WRONG: Direct access without validation
$account = $this->findAccountByLegacyId($data->companyId);  // May be null/missing
```

---

## 🔄 **Sync Timestamp Management**

### **1. Timestamp File Best Practices**
**Location**: `data/datasync-last-run.txt`

```php
private function getLastSyncTime(): string
{
    $syncTimeFile = 'data/datasync-last-run.txt';
    
    if (file_exists($syncTimeFile)) {
        $lastTime = trim(file_get_contents($syncTimeFile));
        // Validate timestamp format
        if ($lastTime && strtotime($lastTime) !== false) {
            return $lastTime;
        }
    }
    
    // Safe default for initial sync
    return '2024-01-01 00:00:00';
}
```

**Critical Rules**:
- ✅ Use past dates for initial sync
- ❌ Never use future dates (blocks all sync)
- ✅ Update only after successful sync
- ✅ Create directory if missing

### **2. Incremental Sync Strategy**
```php
// Only update timestamp after successful sync
if ($results['success'] > 0) {
    $this->updateLastSyncTime();
    $this->log->info("Sync successful: {$results['success']} records");
} else {
    $this->log->warning("No records synced - keeping existing timestamp");
}
```

---

## 📝 **Error Handling & Logging**

### **1. Comprehensive Error Context**
**Principle**: Log enough context to debug without accessing production

```php
try {
    $this->syncEntity($data);
} catch (\Exception $e) {
    $this->log->error("DataSync failed for {$entityType}", [
        'error' => $e->getMessage(),
        'legacyId' => $data['legacyId'] ?? 'unknown',
        'entityName' => $data['name'] ?? 'unknown',
        'apiData' => json_encode($data),
        'stackTrace' => $e->getTraceAsString()
    ]);
}
```

### **2. Validation Logging**
```php
// Log why relationships fail
if (!$account) {
    error_log("DataSync: No account found for legacyCompanyId: {$data->companyId}");
    
    // Add diagnostic information
    $totalAccounts = $this->entityManager->getRepository('Account')
        ->where(['legacyCompanyId!=' => null])
        ->count();
    error_log("DataSync: Total accounts with legacy IDs: {$totalAccounts}");
}
```

---

## 🔗 **Link Field Relationship Patterns**

### **1. Proper Link Field Assignment**
**Critical**: EspoCRM link fields require `relate()` method

```php
// ✅ CORRECT: Use relate() for link fields
$this->entityManager->getRepository('Opportunity')
    ->getRelation($opportunity, 'account')
    ->relate($account);

// ❌ WRONG: Direct assignment doesn't work for links
$opportunity->set('accountId', $account->getId());
```

### **2. Relationship Verification**
```php
// After relate(), verify the relationship
$this->entityManager->refreshEntity($opportunity);
$linkedAccount = $opportunity->get('account');

if ($linkedAccount) {
    $this->log->info("Relationship created successfully");
} else {
    $this->log->error("Relate() succeeded but relationship not found!");
}
```

---

## 🏗️ **Import Order Dependencies**

### **Critical Sequence**
**Must follow this order to ensure relationships work**:

1. **Accounts** (Companies)
   - No dependencies
   - Populate `legacyCompanyId`

2. **Users** (Staff)
   - No dependencies
   - Populate `legacyStaffId`

3. **Publications**
   - Depends on Users (for salesManager)
   - Two-pass sync recommended

4. **Opportunities**
   - Depends on ALL above entities
   - Validate all relationships exist

### **Two-Pass Sync Pattern**
```php
// Pass 1: Create entities without relationships
$this->syncPublicationsBasic($data);
$this->syncStaffBasic($data);

// Pass 2: Update relationships after all entities exist
$this->syncPublicationsRelationships($data);
$this->syncStaffRelationships($data);
```

---

## 🧪 **Testing & Validation**

### **1. Pre-Sync Validation**
```php
// Before syncing opportunities, verify foundation
$readyToSync = 
    $this->hasAccountsWithLegacyIds() &&
    $this->hasUsersWithLegacyIds() &&
    $this->hasPublicationsWithLegacyIds();

if (!$readyToSync) {
    throw new \Exception("Foundation entities not ready for opportunity sync");
}
```

### **2. Debug Scripts**
Create standalone scripts for testing:
```php
// public/testing/test_datasync.php
require_once 'bootstrap.php';

$app = new \Espo\Core\Application();
$entityManager = $app->getContainer()->get('entityManager');

// Test specific sync scenarios
echo "Accounts with legacy IDs: " . 
    $entityManager->getRepository('Account')
        ->where(['legacyCompanyId!=' => null])
        ->count();
```

---

## 📋 **Common Pitfalls & Solutions**

### **Pitfall 1: Silent Field Mapping Failures**
**Symptom**: Legacy IDs not populated, no errors  
**Solution**: Add explicit logging for all field assignments

### **Pitfall 2: Future Timestamp Blocking**
**Symptom**: No records sync despite data changes  
**Solution**: Reset timestamp to past date

### **Pitfall 3: Relationship Creation Failures**
**Symptom**: Links show as NULL despite data existing  
**Solution**: Use `relate()` method, not `set()`

### **Pitfall 4: Case-Sensitive Field Names**
**Symptom**: Some fields populate, others don't  
**Solution**: Implement fallback field name checking

---

## ✅ **DataSync Health Checklist**

Before deploying any DataSync changes:

- [ ] API field names verified against actual responses
- [ ] Error handling logs sufficient context
- [ ] Timestamp management prevents blocking
- [ ] Import order dependencies respected
- [ ] Link fields use `relate()` method
- [ ] Test scripts created for validation
- [ ] Fallback handling for field variations
- [ ] Legacy ID population verified

---

*For implementation details, see [MSSQL-EspoCRM Sync](MSSQL-EspoCRM-sync.md)* 