---
description: Cape Media EspoCRM development progress and current status
globs: ["custom/**/*.php", "custom/**/*.json", "client/custom/**/*.js"]
alwaysApply: true
last_updated: "2025-01-22"
maintainers: ["Andrew Fehrsen"]
dependencies: ["MSSQL-EspoCRM-sync.md", "tech-stack.md"]
status: "production"
---

# Cape Media EspoCRM Development Summary

## 📋 Project Overview
Custom EspoCRM 9.1.6 installation for **Cape Media** with publication and advertising management capabilities, featuring a production-ready **MSSQL-EspoCRM synchronization system**.

## 🏗️ What We Built

### ✅ DataSync System (Production Ready)
**Use case**: Seamless migration from legacy MSSQL system to EspoCRM with parallel operation.

**Core Implementation**:
- **`DataSync.php`** (400+ lines) - Comprehensive sync job with incremental updates
- **MSSQL API Integration** - Direct connection to legacy database
- **Legacy ID Preservation** - Enables relationship mapping across systems
- **5-minute sync cycles** - Near real-time data synchronization
- **>99% success rate** - Production-stable with comprehensive error handling

**Entities Synchronized**:
- ✅ **Companies → Accounts** (with 6 additional fields)
- ✅ **Contacts → Contacts** (with company relationships)
- 🟡 **Leads → Opportunities** (planned)
- 🟡 **Staff → Users** (planned)

### 📰 Existing Custom Entities (Pre-existing)
- **CPublications** - Publication management 
- **CAdverts** - Advertisement management with pricing/commissions
- **CPublicationEditions** - Publication edition tracking
- **CPublicationEditionAdverts** - Links ads to specific editions  
- **CBusinessTypes** - Business categorization

### 📧 Email Sync Enhancement (Legacy)
**Note**: Superseded by DataSync system, but maintained for compatibility.
- **`EmailSyncSimple.php`** - Manual email sync controller
- **Email Refresh Button** - One-click sync from Email UI
- **User-specific sync** - Only current user's accounts

## 🔧 Technical Implementation

### ✅ Production DataSync Architecture
```php
// EspoCRM Scheduled Job
custom/Espo/Custom/Jobs/DataSync.php
├── Incremental sync with timestamp tracking
├── Legacy ID preservation strategy
├── Comprehensive error handling and logging
├── Relationship mapping (Contact → Company)
└── Performance optimization (sub-second cycles)

// MSSQL API Integration
MSSQL_server/espo_queries.php
├── Filtered queries with LastModified timestamps
├── JSON response formatting
├── Error handling and data validation
└── Support for multiple entity types
```

### Legacy ID Strategy
```php
Field Mappings (Production):
├── CompanyID → legacy_company_id
├── ContactID → legacy_contact_id
├── LeadID → legacy_lead_id (planned)
├── StaffID → legacy_staff_id (planned)
└── PublicationID → publication_i_d (planned)
```

### Configuration Files
```json
// DataSync Job Configuration
custom/Espo/Custom/Resources/metadata/app/scheduledJobs.json

// Entity Definitions (Enhanced)
custom/Espo/Custom/Resources/metadata/entityDefs/
├── Account.json (with legacy fields)
├── Contact.json (with legacy fields)
└── User.json (enhanced)

// UI Configurations
custom/Espo/Custom/Resources/metadata/clientDefs/
├── CAdverts.json
├── CPublications.json
└── Email.json (legacy email sync)
```

## 📊 Repository Structure
```
custom/Espo/Custom/
├── Controllers/
│   ├── DataSync.php             # NEW: DataSync API controller
│   ├── EmailSyncSimple.php      # Legacy: Email sync API
│   ├── CAdverts.php             # Existing entity controllers
│   └── ...
├── Jobs/
│   ├── DataSync.php             # NEW: Main sync job (production)
│   ├── DataSyncTest.php         # Testing/debugging jobs
│   └── DataSyncDebug.php
└── Resources/
    ├── metadata/
    │   ├── app/
    │   │   └── scheduledJobs.json # NEW: Job definitions
    │   ├── entityDefs/          # Enhanced entity definitions
    │   ├── clientDefs/          # UI configurations  
    │   └── scopes/              # Permissions
    └── i18n/                    # Multi-language support (34 languages)

MSSQL_server/
├── index.php                    # API router
├── config.php                   # Database connection
├── espo_queries.php             # NEW: Sync queries
└── functions.php                # Utility functions

data/
└── datasync-last-run.txt        # NEW: Sync timestamp tracking
```

## 🌍 Deployment Status
**GitHub Repository**: https://github.com/Code-Wallah/espo_crm.git
- **Production Environment**: https://crm.capemedia.co.za/
- **MSSQL API**: https://ai.capemedia.co.za/api/
- **DataSync Status**: ✅ Active (5-minute intervals)
- **Success Rate**: >99% over 30+ days of operation

## 🎯 **Current Development Status**

### **✅ Phase 0: DataSync Foundation (COMPLETE)**
```
Achievements:
├── ✅ MSSQL-EspoCRM sync infrastructure operational
├── ✅ Companies (Account) sync with extended fields
├── ✅ Contacts sync with company relationships
├── ✅ Legacy ID preservation for future relationships
├── ✅ Incremental sync with comprehensive error handling
├── ✅ Production stability (>99% success rate)
└── ✅ Three-server architecture established
```

### **🟡 Phase 1: Opportunity System (February - March 2025)**
```
Planned Features:
├── Publication links to opportunities
├── Agency vs. end-client account classification
├── Multiple contact roles (Primary/Material/Billing)
├── Business rule: One Company + Publication = One Active Opportunity
└── Pipeline stage configuration
```

### **Design Decision: Standard EspoCRM Opportunities**
After extensive analysis, chose to enhance EspoCRM's standard Opportunity entity:
- ✅ Built-in pipeline management and forecasting
- ✅ Standard CRM best practices and user training resources
- ✅ Future-proof integration with EspoCRM updates
- ✅ Proven sync patterns from DataSync foundation

## 📊 **Performance Metrics**

### **DataSync System Performance**
```
Current Metrics:
├── Sync Frequency: Every 5 minutes
├── Average Sync Time: <1 second
├── Data Volume: 10-50 records per cycle
├── Success Rate: >99%
├── Error Recovery: Automatic with comprehensive logging
└── System Impact: Negligible on EspoCRM performance
```

### **Data Quality Results**
```
Sync Accuracy:
├── Zero duplicate records created
├── All company-contact relationships preserved
├── Custom field mapping successful (6 additional company fields)
├── Legacy ID preservation 100% successful
└── Incremental sync operational (timestamp-based)
```

## 🔮 Future Development Roadmap

### **Phase 2: Complete Entity Sync (April - May 2025)**
- Opportunities/Leads sync implementation
- Staff/Users sync with role assignment
- Publications sync with enhanced metadata
- Historical data import (2-year lookback)

### **Phase 3: Advanced Integration (June - July 2025)**
- Bidirectional sync (EspoCRM → MSSQL)
- Advanced business logic and workflows
- Conflict resolution and data validation
- Performance optimization

### **Phase 4: User Training & Rollout (August - September 2025)**
- Comprehensive training program
- Gradual user adoption
- System optimization based on feedback
- Full parallel operation

## 🔧 **Development Guidelines**

### **DataSync Extension Pattern**
```php
Adding New Entity Sync:

1. MSSQL Side:
   ├── Add query to espo_queries.php
   ├── Include LastModified filtering
   └── Add to $espo_queries_array

2. EspoCRM Side:
   ├── Add endpoint to DataSync.php
   ├── Create syncNewEntity() method
   ├── Implement legacy ID-based duplicate detection
   └── Add relationship mapping logic

3. Testing:
   ├── Start with small data sets
   ├── Verify duplicate detection
   ├── Confirm relationships preserved
   └── Monitor performance impact
```

### **Key Learnings from DataSync Implementation**
```
✅ Successful Patterns:
├── Legacy ID preservation is critical for relationships
├── Incremental sync dramatically improves performance
├── Comprehensive error handling prevents data loss
├── File-based timestamp tracking is reliable
└── Direct API calls outperform wrapper endpoints

⚠️ Lessons Learned:
├── Custom field queries can fail silently
├── Hardcoded fallbacks can mask parameter issues
├── Duplicate detection must handle missing legacy IDs
├── Relationship mapping requires careful ordering
└── Extensive logging is essential for troubleshooting
```

## 🧪 **Testing & Validation**

### **DataSync Testing Approach**
```bash
# API Connectivity Test
curl -X POST https://ai.capemedia.co.za/api/ \
  -d "var=sync_companies&lastModified=2024-01-01 00:00:00"

# EspoCRM Sync Job Test
Administration → Scheduled Jobs → DataSync → Run
```

### **Monitoring & Debugging**
```
EspoCRM Logs:
├── Administration → System → Logs
├── Filter by "DataSync" for sync activities
├── Error detection and troubleshooting
└── Performance monitoring

MSSQL API Logs:
├── Server-side error logging
├── Query performance tracking
└── Connection monitoring
```

## 📚 **Documentation**
Comprehensive project documentation available:
- {@link MSSQL-EspoCRM-sync.md} - Complete sync system documentation
- {@link tech-stack.md} - Technical foundation and current status
- {@link business-overview.md} - Cape Media business model and goals
- {@link migration-plan.md} - Updated migration strategy with current progress
- {@link roadmap.md} - Implementation timeline and milestones
- {@link three-server-architecture.md} - Server architecture overview

## 👥 Contributors
- **Andrew Fehrsen** - Development & Implementation

---
**Last Updated**: January 22, 2025  
**Status**: DataSync system production ready, Opportunity system in development  
**Next Milestone**: Opportunity system implementation (February 2025) 