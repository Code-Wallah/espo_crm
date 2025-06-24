---
description: Implementation roadmap for Cape Media EspoCRM opportunity system
globs: ["docs/**/*.md"]
alwaysApply: false
last_updated: "2025-01-22"
maintainers: ["Andrew Fehrsen"]
dependencies: ["opportunity-system.md", "migration-plan.md", "MSSQL-EspoCRM-sync.md"]
status: "updated"
---

# 🚀 Cape Media EspoCRM Implementation Roadmap

## 🎯 **Project Overview**
**Objective**: Implement professional CRM opportunity management system with parallel migration from legacy MSSQL system to EspoCRM by March 2026.

**Timeline**: January 2025 → March 2026 (14 months)  
**Current Status**: DataSync foundation complete, basic opportunity sync operational

## 📊 **Progress Overview**
```
[████████████████████████████████████████] 100% Phase 0: DataSync Foundation ✅
[████████████████████████░░░░░░░░░░░░░░░░] 60%  Phase 1: Opportunity System 🟡
[░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░] 0%   Phase 2: Data Integration 🔵
[░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░] 0%   Phase 3: Testing & Validation 🔵
[░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░] 0%   Phase 4: Reporting & Analytics 🔵
[░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░] 0%   Phase 5: Full Production 🔵

Overall Project Progress: ████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ 32%
```

### **🚀 Major Achievements (January 2025)**
- ✅ **DataSync Production Ready**: All entities syncing every 5 minutes
- ✅ **Field Mapping Fixed**: Critical legacyCompanyId issue resolved
- ✅ **Opportunity Sync Working**: Opportunities creating with proper account links
- ✅ **Documentation Audit**: Clean, focused documentation structure
- ✅ **Territory Foundation**: User home publications system ready

## ✅ **Phase 0: DataSync Foundation** (COMPLETE)
**Duration**: December 2024 - January 2025  
**Status**: ✅ **Production Ready**

### **Completed Deliverables**
```
✅ MSSQL-EspoCRM Sync System:
├── [x] Incremental sync every 5 minutes
├── [x] Companies (Account entity) - 6 additional fields
├── [x] Contacts (Contact entity) - with company relationships
├── [x] Publications sync with sales manager relationships
├── [x] Users sync with home publication assignments
├── [x] Opportunities basic sync (January 2025)
├── [x] Legacy ID preservation strategy
├── [x] Field mapping fix (legacyCompanyId correction)
├── [x] Comprehensive error handling & logging
└── [x] Production stable (>99% success rate)

✅ Infrastructure:
├── [x] Three-server architecture operational
├── [x] API endpoints functional (ai.capemedia.co.za/api/)
├── [x] EspoCRM scheduled jobs system
├── [x] Monitoring and logging systems
├── [x] Development workflows established
└── [x] Documentation audit complete (January 2025)
```

### **Technical Achievements**
- ✅ **Legacy ID Strategy**: Enables bidirectional sync and relationship mapping
- ✅ **Incremental Sync**: Timestamp-based, efficient data transfer  
- ✅ **Duplicate Detection**: Robust logic prevents data duplication
- ✅ **Relationship Preservation**: Contact-Company links maintained across systems
- ✅ **Extended Field Mapping**: Additional business data (booking dates, LTV, etc.)
- ✅ **Field Mapping Fix**: Critical legacyCompanyId correction applied
- ✅ **Link Field Patterns**: Proper relate() method implementation
- ✅ **Import Order**: Accounts → Users → Publications → Opportunities

## 📋 **Phase 1: Opportunity System Foundation** 🟡 In Progress
**Duration**: February - March 2025 (8 weeks)  
**Status**: ✅ Partially Complete - Basic sync working, validation hooks ready to implement

### **✅ Completed Foundation Work (January 2025)**
```
✅ Core Opportunity Sync:
├── [x] Opportunity entity properly configured
├── [x] Account link field working with relate() method
├── [x] Publication link field implemented
├── [x] User assignments via assignedUserId
├── [x] Legacy ID fields for sync (legacyLeadId, legacyCompanyId, etc.)
├── [x] Basic opportunity creation from MSSQL data
└── [x] Account relationship resolution working

✅ User Home Publication System:
├── [x] homePublicationId field added to User entity
├── [x] User interface for home publication selection
├── [x] Publications linked to sales managers
└── [x] Foundation for territory validation ready
```

### **Sub-Task 1.1: Basic Publication Link** ✅ COMPLETE
```
Goal: Add Publication field to Opportunities and test basic relationship

Steps:
├── [x] Add Publication field (link to CPublications) to Opportunity entity
├── [x] Update Opportunity detail/edit layouts
├── [x] Add basic translations (en_US)
└── [x] Clear cache & rebuild

Test Success:
├── [x] Can create opportunity with publication selected
├── [x] Publication shows in opportunity detail view
├── [x] Can filter opportunities by publication
└── [x] No console errors or UI issues

Status: ✅ Complete - Working in production via DataSync
```

### **Sub-Task 1.2: Account Type Classification** (Week 2)
```
Goal: Add agency classification to Account entity

Steps:
├── Add "Account Type" enum field (Client/Agency/Both)
├── Add "Is Agency" boolean field for quick filtering
├── Update Account layouts (detail/edit/list)
└── Add translations

Test Success:
├── Can set account type when creating/editing accounts
├── Can filter accounts by type
├── Agency flag works correctly
└── Existing accounts remain functional
```

### **Sub-Task 1.3: Billing Account Relationship** (Week 3)
```
Goal: Link opportunities to billing accounts (agencies)

Steps:
├── Add "Billing Account" field (link to Account) to Opportunity
├── Update Opportunity layouts to show billing account
├── Add translations
└── Test with sample data

Test Success:
├── Can select billing account when creating opportunity
├── Can have different Account (end client) and Billing Account (agency)
├── Both relationships display correctly in opportunity view
└── Can report/filter by billing account
```

### **Sub-Task 1.4: Contact Date Tracking** (Week 4)
```
Goal: Add date tracking for sales activities

Steps:
├── Add "Last Contact Date" field to Opportunity
├── Update layouts and translations
├── Configure field as user-editable
└── Test data entry

Test Success:
├── Can set last contact date manually
├── Date displays correctly in all views
├── Can sort/filter opportunities by last contact
└── Field is optional (not required)
```

### **Sub-Task 1.5: Contact Role Fields** (Week 5)
```
Goal: Track multiple contact roles for agency deals

Steps:
├── Add "Material Contact" field (varchar for now - person name)
├── Add "Billing Contact" field (varchar for now - person name)  
├── Update layouts and translations
└── Test with agency scenarios

Test Success:
├── Can enter material and billing contact names
├── Fields display properly in opportunity views
├── Can handle empty fields (direct client deals)
└── Agency workflow works end-to-end
```

### **Sub-Task 1.6: Pipeline Stage Configuration** (Week 6)
```
Goal: Configure standard CRM pipeline stages

Steps:
├── Review/configure Opportunity stages in Entity Manager
├── Map to Cape Media sales process
├── Update stage colors and probabilities
└── Test stage progression

Test Success:
├── All stages appear in opportunity stage dropdown
├── Can move opportunities through pipeline stages
├── Probability calculations work correctly
└── Reports show pipeline distribution
```

### **Sub-Task 1.7: Business Rules Implementation** 🔥 NEXT UP
```
Goal: Territory validation & duplicate Account + Publication prevention

Steps:
├── [ ] Implement territory validation hooks (BeforeCreate/BeforeUpdate)
├── [ ] Add duplicate Company + Publication prevention
├── [ ] Add user-friendly error messages  
├── [ ] Add translations for error messages
└── [ ] Test all business rules

Test Success:
├── [ ] Salespeople can only create on home publication
├── [ ] Managers can create on managed publications
├── [ ] Super admin can create anywhere
├── [ ] Cannot create duplicate Account + Publication combination
├── [ ] Clear error messages when rules violated
└── [ ] Existing opportunities remain editable

Status: 🔥 Ready to implement - Code prepared in OPPORTUNITY_IMPLEMENTATION_GUIDE.md
```

## 📊 **Phase 2: Data Integration & Sync Expansion** 🟡 Planned
**Duration**: April - May 2025 (8 weeks)  
**Status**: Depends on Phase 1

### **Sub-Task 2.1: Opportunities/Leads Sync** (Week 1-3)
```
Goal: Sync legacy leads as EspoCRM opportunities

MSSQL → EspoCRM Mapping:
├── LeadID → legacy_lead_id
├── StaffID → assignedUserId (via legacy_staff_id lookup)
├── PublicationID → publicationId (via publication_i_d lookup)
├── CompanyID → accountId (via legacy_company_id lookup)
├── LStatusID → stage (mapped to standard stages)
├── Amount tracking → amount (estimated values)
└── Auth/Reason → description/notes

Technical Implementation:
├── Add sync_opportunities query to MSSQL espo_queries.php
├── Create syncOpportunities() method in DataSync.php
├── Implement complex relationship mapping
├── Add opportunity-specific duplicate detection
└── Test with small data set first

Success Criteria:
├── Active leads sync as opportunities
├── All relationships preserved (company, publication, staff)
├── Pipeline stages mapped correctly
├── No duplicate opportunities created
└── Business rule compliance (one active per company+publication)
```

### **Sub-Task 2.2: Staff/User Sync** (Week 4-5)
```
Goal: Import legacy staff as EspoCRM users

MSSQL → EspoCRM Mapping:
├── StaffID → legacy_staff_id
├── FirstName → firstName
├── LastName → lastName
├── Email → emailAddress
├── Telephone → phoneNumber
├── Extension → custom field
└── HomePublicationID → custom field (default publication)

Implementation:
├── Add sync_staff query to MSSQL
├── Create syncStaff() method in DataSync
├── Handle user permissions and roles
├── Set default publication assignments
└── Validate email uniqueness

Success Criteria:
├── All salespeople imported as users
├── Proper permissions for opportunity management
├── Default publication assignments work
├── User profiles complete with contact info
└── Can log in and access system
```

### **Sub-Task 2.3: Publication Enhancement** (Week 6-7)
```
Goal: Enhance CPublications with legacy data

MSSQL → EspoCRM Mapping:
├── PublicationID → publication_i_d
├── Publication → name
├── SalesManager → assignedUserId
├── Status tracking → custom fields
└── Additional metadata

Implementation:
├── Update existing CPublications entity
├── Add legacy ID field for sync
├── Create syncPublications() method
├── Map sales managers to users
└── Preserve existing publication data

Success Criteria:
├── All publications synced with legacy IDs
├── Sales manager assignments correct
├── Opportunity publication links work
├── No disruption to existing functionality
└── Enhanced reporting capabilities
```

### **Sub-Task 2.4: Historical Data Import** (Week 8)
```
Goal: Import historical opportunities for reporting

Process:
├── Export closed leads from legacy system (last 2 years)
├── Map to opportunity structure with new fields
├── Import as closed opportunities with historical dates
└── Validate data integrity and relationships

Validation:
├── Historical data doesn't interfere with active sync
├── Reporting includes historical context
├── Relationships preserved across historical records
└── Performance impact acceptable
```

## 🔄 **Phase 3: Advanced Sync & Business Logic** 🟡 Planned
**Duration**: June - July 2025 (8 weeks)  
**Status**: Depends on Phase 2

### **Month 1: Bidirectional Sync (EspoCRM → MSSQL)**
```
Implementation:
├── Create MSSQL update/insert endpoints
├── Add EspoCRM hooks for data changes
├── Implement conflict resolution logic
├── Add sync status tracking
└── Build manual sync override capabilities

Key Features:
├── Real-time opportunity updates to MSSQL
├── Contact information sync back to legacy
├── Stage change notifications
└── Comprehensive error handling

Validation:
├── Data consistency between systems
├── Performance under load
├── Error recovery procedures
└── User notification systems
```

### **Month 2: Advanced Business Logic**
```
Features:
├── Advanced duplicate prevention
├── Automated lead scoring
├── Publication-specific workflows
├── Agency relationship automation
└── Custom reporting dashboards

Integration:
├── Email integration for contact tracking
├── Calendar integration for activities
├── Document management for proposals
└── Mobile access optimization
```

## 👥 **Phase 4: User Training & Rollout** 🟡 Planned
**Duration**: August - September 2025 (8 weeks)  
**Status**: User adoption critical

### **Month 1: Training Program**
```
Training Components:
├── CRM concepts and best practices
├── EspoCRM opportunity management
├── Agency relationship handling
├── Reporting and forecasting
└── DataSync system understanding

Delivery Methods:
├── Interactive workshops (2-day sessions)
├── One-on-one coaching sessions
├── Video tutorials and documentation
├── Practice environment with sample data
└── Ongoing support during transition
```

### **Month 2: Gradual Rollout**
```
Rollout Strategy:
├── Week 1-2: Power users and early adopters
├── Week 3-4: Department by department
├── Week 5-6: Full sales team
└── Week 7-8: Support and refinement

Success Metrics:
├── Daily active users >90%
├── Opportunity creation rate matches legacy
├── User satisfaction scores >4/5
├── Data accuracy maintained
└── Sync system stability >99%
```

## 🎯 **Phase 5: Full Migration & Optimization** 🟡 Planned
**Duration**: October 2025 - March 2026 (6 months)  
**Status**: Final transition

### **October 2025 - February 2026: Parallel Operation**
```
Activities:
├── All new opportunities in EspoCRM
├── Legacy system for historical reference only
├── Continuous data validation and sync monitoring
├── Process optimization based on user feedback
└── Performance tuning and system optimization

Monitoring:
├── Performance metrics tracking
├── User feedback collection and analysis
├── System stability monitoring
├── Business impact assessment
└── Sync system reliability validation
```

### **March 2026: Legacy Decommission**
```
Final Steps:
├── Complete historical data transfer
├── Legacy system backup and archive
├── User access revocation from legacy system
├── Infrastructure decommission
└── Final documentation and handover

Validation:
├── 100% business continuity maintained
├── All reporting capabilities preserved or enhanced
├── User training completion verified
├── Documentation updated and finalized
└── Success metrics achieved
```

## 📈 **Updated Success Milestones**

### **Technical Milestones**
- [x] **Phase 0**: DataSync foundation operational (COMPLETE)
- [ ] **Phase 1**: Custom opportunity fields operational (Feb-Mar 2025)
- [ ] **Phase 2**: Complete entity sync system active (Apr-May 2025)  
- [ ] **Phase 3**: Bidirectional sync functioning reliably (Jun-Jul 2025)
- [ ] **Phase 4**: 90% user adoption achieved (Aug-Sep 2025)
- [ ] **Phase 5**: Legacy system successfully decommissioned (Mar 2026)

### **Business Milestones**
- [x] **Q4 2024**: Data sync foundation established
- [ ] **Q1 2025**: Opportunity management system operational
- [ ] **Q2 2025**: Complete CRM functionality with sync
- [ ] **Q3 2025**: User adoption and training complete
- [ ] **Q1 2026**: Full migration and legacy decommission

## 🚨 **Risk Management**

### **Critical Dependencies**
```
Technical Dependencies:
├── EspoCRM platform stability (LOW RISK - proven)
├── DataSync system reliability (LOW RISK - operational)
├── MSSQL API availability (MEDIUM RISK - monitor)
├── Development resource availability (MEDIUM RISK)
└── Testing environment stability (LOW RISK)

Business Dependencies:
├── User training participation (MEDIUM RISK)
├── Management support and buy-in (LOW RISK - committed)
├── Legacy system maintenance (LOW RISK - stable)
└── Business operation continuity (LOW RISK - parallel operation)
```

### **Contingency Plans**
```
If Behind Schedule:
├── Extend parallel running period (6-month buffer available)
├── Prioritize core functionality over advanced features
├── Defer bidirectional sync if necessary
└── Phase implementation to reduce risk

If Technical Issues:
├── DataSync system provides rollback capability
├── Legacy system remains fully operational
├── Emergency support escalation procedures
├── Extended testing periods if needed
└── Vendor support engagement available
```

## 📊 **Progress Tracking**

### **Weekly Metrics**
- Development task completion rate
- DataSync system performance and reliability
- User training session attendance (when applicable)
- Data sync accuracy percentage
- System performance benchmarks

### **Monthly Reviews**
- Milestone achievement assessment
- Budget and timeline adherence
- User feedback analysis (when applicable)
- Risk mitigation effectiveness
- DataSync system optimization opportunities

## 🎯 **Key Advantages of Current Approach**

### **DataSync Foundation Benefits**
- **Proven Reliability**: >99% success rate in production
- **Incremental Approach**: Low-risk, gradual implementation
- **Data Integrity**: Legacy ID strategy ensures relationship preservation
- **Business Continuity**: Parallel operation with no disruption
- **Performance**: Sub-second sync cycles with comprehensive monitoring

### **Reduced Migration Risk**
- **Established Infrastructure**: Core sync system operational
- **Proven Patterns**: Replicable approach for additional entities
- **Comprehensive Logging**: Full visibility into system operations
- **Rollback Capability**: Can revert to legacy system if needed
- **User Confidence**: Demonstrated system reliability

---
*Detailed technical specifications in {@link MSSQL-EspoCRM-sync.md} and migration procedures in {@link migration-plan.md}* 