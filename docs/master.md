---
description: Main documentation hub for Cape Media EspoCRM project
globs: ["*.md", "docs/**/*.md"]
alwaysApply: true
last_updated: "2025-01-23"
maintainers: ["Andrew Fehrsen"]
dependencies: []
status: "production"
---

# 📚 Cape Media EspoCRM Documentation Hub

Welcome to the central documentation for the **Cape Media EspoCRM** project. This document serves as the primary entry point for all project-related information.

## 🎯 **Project Status: DataSync Production Ready**
- ✅ **Production Ready**: MSSQL-EspoCRM synchronization fully operational
- ✅ **Active Sync**: Companies, Contacts, Opportunities syncing every 5 minutes
- ✅ **Field Mapping Fixed**: All legacy IDs properly captured (January 2025)
- 🟡 **Next Phase**: Opportunity validation hooks (February 2025)

## 📋 **Core Documentation**

### **🔧 Technical Implementation**
- [**MSSQL-EspoCRM Sync**](MSSQL-EspoCRM-sync.md) - Production sync system documentation
- [**DataSync Best Practices**](DATASYNC_BEST_PRACTICES.md) - Field mapping, error handling, sync patterns
- [**Tech Stack**](tech-stack.md) - Technical architecture and infrastructure
- [**Three-Server Architecture**](three-server-architecture.md) - Server topology and file placement

### **📊 Business & Planning**
- [**Business Overview**](business-overview.md) - Cape Media business model and requirements
- [**Migration Plan**](migration-plan.md) - Phased approach with current progress
- [**Implementation Roadmap**](roadmap.md) - Timeline and milestones

### **🎯 Feature Implementation**
- [**Opportunity Implementation Guide**](OPPORTUNITY_IMPLEMENTATION_GUIDE.md) - Territory control and validation rules
- [**Field Naming Policy**](FIELD_NAMING_POLICY.md) - EspoCRM field standards and conventions

### **📖 Reference Documentation**
- [**Legacy Leads System**](EXISTING_LEADS_SYSTEM.md) - MSSQL database schema reference
- [**Development Summary**](development-summary.md) - Progress tracking and achievements
- [**Things to Remember**](things_to_remember.md) - Quick reference for development
- [**Terminal Guide**](terminal.md) - PowerShell commands for Windows development
- [**Phone Intelligence**](phone_intelligence.md) - Future phone system integration notes

### **📁 SQL Schemas** (Reference Only)
Located in `docs/sql-schemas/`:
- `account.sql` - Account table structure
- `contact.sql` - Contact table structure
- `opportunity.sql` - Opportunity table structure
- `user.sql` - User table structure
- `c_publications.sql` - Publications table structure

## 🚀 **Quick Start Guide**

### **For Developers**
1. **Current System**: Review [MSSQL-EspoCRM Sync](MSSQL-EspoCRM-sync.md) for active implementation
2. **Best Practices**: Follow [DataSync Best Practices](DATASYNC_BEST_PRACTICES.md) for field mapping
3. **Architecture**: Understand [Three-Server Architecture](three-server-architecture.md) before changes
4. **Standards**: Apply [Field Naming Policy](FIELD_NAMING_POLICY.md) for all new fields

### **For Project Managers**
1. **Status**: Check [Development Summary](development-summary.md) for current progress
2. **Timeline**: Review [Implementation Roadmap](roadmap.md) for milestones
3. **Business Context**: Understand [Business Overview](business-overview.md) for requirements

### **For System Administrators**
1. **Monitoring**: DataSync runs every 5 minutes via scheduled job
2. **Logs**: Administration → System → Logs (filter "DataSync")
3. **Troubleshooting**: See [DataSync Best Practices](DATASYNC_BEST_PRACTICES.md)

## 📊 **Document Categories**

### **Production Documentation** (Live System)
- MSSQL-EspoCRM Sync ✅
- DataSync Best Practices ✅
- Tech Stack ✅
- Three-Server Architecture ✅
- Field Naming Policy ✅

### **Implementation Guides** (In Progress)
- Opportunity Implementation Guide 🟡
- Migration Plan 🟡
- Development Summary 🟡

### **Reference Documentation** (Stable)
- Business Overview ✅
- Legacy Leads System ✅
- Terminal Guide ✅
- Things to Remember ✅

### **Future Planning** (Conceptual)
- Phone Intelligence 🔵
- Implementation Roadmap 🔵

## 🎯 **Critical Implementation Notes**

### **Field Mapping Fix Applied** 
[As per memory:819316447262062183]: Fixed critical field mapping between MSSQL API and DataSync:
- MSSQL returns `legacyCompanyId` → DataSync now correctly uses `$data->legacyCompanyId`
- Ensures account relationships work properly in opportunities

### **EspoCRM Best Practices**
[As per memory:7831527790703931635]: Link fields require `relate()` method:
```php
// ✅ Correct for link fields
$entityManager->getRepository('Opportunity')
    ->getRelation($opportunity, 'account')
    ->relate($account);

// ❌ Wrong for link fields
$opportunity->set('accountId', $account->getId());
```

### **Import Order Critical**
[As per memory:5727275463357839446]: Must follow sequence:
1. Accounts → 2. Users → 3. Publications → 4. Opportunities

## 📞 **Support & Contact**
- **Developer**: Andrew Fehrsen
- **Production System**: https://crm.capemedia.co.za/
- **MSSQL API**: https://ai.capemedia.co.za/api/
- **Repository**: https://github.com/Code-Wallah/espo_crm.git

---
*This documentation is actively maintained and reflects the current production state of the Cape Media EspoCRM system.* 