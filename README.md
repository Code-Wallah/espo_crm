# EspoCRM Publication Management System

Custom EspoCRM modules for managing publications, advertising, and email synchronization.

## 🏢 About Cape Media

This is a custom EspoCRM implementation for **Cape Media's** publication and advertising management system.

## 📋 Features

### 📰 Publication Management
- **CPublications**: Manage different publications (newspapers, magazines, etc.)
- **CPublicationEditions**: Track specific editions/issues with publish dates
- **CAdverts**: Comprehensive advertisement management with pricing and commissions
- **CPublicationEditionAdverts**: Link advertisements to specific publication editions
- **CBusinessTypes**: Categorize different types of businesses

### 📧 Email Management
- **EmailSyncSimple**: Custom email synchronization controller
- **Email Refresh Button**: One-click email sync from the UI
- **User-specific sync**: Only syncs the current user's email accounts

## 🚀 Installation

1. **Install EspoCRM 9.1.6** (not included in this repository)
2. **Copy custom files** to your EspoCRM installation:
   ```bash
   # Copy server-side customizations
   cp -r custom/* /path/to/espocrm/custom/
   
   # Copy client-side customizations  
   cp -r client/custom/* /path/to/espocrm/client/custom/
   ```
3. **Clear EspoCRM cache**: Administration → Clear Cache
4. **Rebuild**: Administration → Rebuild

## 📁 File Structure

```
custom/Espo/Custom/
├── Controllers/
│   ├── EmailSyncSimple.php      # Email sync API
│   ├── CAdverts.php             # Adverts controller
│   ├── CPublications.php        # Publications controller
│   └── ...
└── Resources/
    ├── metadata/
    │   ├── entityDefs/          # Entity field definitions
    │   ├── clientDefs/          # UI configurations
    │   └── scopes/              # Entity permissions
    └── i18n/                    # Multi-language support

client/custom/src/handlers/
└── email-refresh-handler.js     # Email refresh button logic
```

## 🎯 Custom Entities

### CPublications
- Publication management with sales manager tracking
- Fields: name, description, salesManager, publicationEditionIDSales, publicationEditionIDProd

### CAdverts  
- Advertisement management with full business workflow
- Fields: price, agentCommission, terms, comments, orderConfirmDate, companyID, contactID, etc.

### CPublicationEditions
- Specific publication edition/issue tracking
- Fields: name, description, publicationID, publishDate

### CPublicationEditionAdverts
- Links adverts to specific publication editions
- Tracks confirmation, cancellation, commission payments
- Fields: confirmedDate, cancelledDate, commissionPaidDate, comments, etc.

### CBusinessTypes
- Business categorization for targeting
- Fields: businessTypeID, businessType

## 🔧 API Endpoints

### Email Sync
```bash
POST /api/v1/EmailSyncSimple/action/manualSync
Headers: X-Api-Key: YOUR_API_KEY
```

**Response:**
```json
{
  "success": true,
  "message": "Email sync jobs queued", 
  "results": [
    {
      "account": "Account Name",
      "status": "queued",
      "job_id": "job_id_here"
    }
  ]
}
```

## 🌍 Multi-Language Support

Includes translations for 30+ languages:
- English (en_US, en_GB)
- Spanish (es_ES, es_MX)  
- French (fr_FR)
- German (de_DE)
- And many more...

## ⚙️ Requirements

- **EspoCRM**: 9.1.6+
- **PHP**: 8.0+
- **Database**: MySQL 5.7+ or MariaDB
- **Web Server**: Apache/Nginx

## 🛠️ Development

### Adding New Features
1. Create new entities in `custom/Espo/Custom/Resources/metadata/entityDefs/`
2. Add controllers in `custom/Espo/Custom/Controllers/`
3. Add client-side handlers in `client/custom/src/handlers/`
4. Clear cache and rebuild

### Testing
- Use the EmailSyncSimple API endpoint to test email functionality
- Check EspoCRM logs in `data/logs/` for debugging

## 📝 License

This is proprietary software for Cape Media. All rights reserved.

## 👥 Contributors

- **Andrew Fehrsen** - Initial development and customization

## 🆘 Support

For support with this custom EspoCRM implementation, contact the development team.

---

**Note**: This repository contains only the custom modifications. The base EspoCRM installation is required separately. 