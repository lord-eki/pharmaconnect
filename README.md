# PharmaConnect Kenya 🏥💊

> A comprehensive digital pharmaceutical supply chain platform revolutionizing healthcare delivery in Kenya

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![Filament](https://img.shields.io/badge/Filament-3.x-F59E0B?style=for-the-badge&logo=laravel)](https://filamentphp.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)

## 🎯 Overview

PharmaConnect Kenya is a modern, full-stack pharmaceutical supply chain management platform designed to bridge the gap between healthcare providers, pharmaceutical suppliers, insurance companies, and patients across Kenya. The system streamlines prescription management, order fulfillment, insurance claims processing, and last-mile delivery tracking.

### 🌟 Key Features

- **Multi-Role Authentication System** - Secure access for Physicians, Suppliers, Insurance Providers, Operations Teams, and Administrators
- **Digital Prescription Management** - Comprehensive prescription workflow with drug interaction checks
- **Medicine Catalog** - Extensive database of 20,000+ medicines with search and categorization
- **Intelligent Quotation System** - Automated supplier matching and competitive pricing
- **Insurance Integration** - Seamless claims processing and policy management
- **Real-time Logistics** - GPS tracking for delivery optimization and proof of delivery
- **Financial Management** - Commission tracking, invoicing, and payment processing
- **Regulatory Compliance** - Complete audit trails and PPB registration tracking

## 🏗️ System Architecture

### Technology Stack

- **Backend**: Laravel 10.x with PHP 8.2+
- **Admin Interface**: Filament 3.x for role-based panels
- **Database**: MySQL 8.0+ with spatial extensions
- **Authentication**: Laravel Sanctum with 2FA support
- **Queue Management**: Redis for background job processing
- **Storage**: Local/S3 for document and image storage

### Database Schema

The system is built on a comprehensive 31-table database structure covering:

- **User Management & Authentication** (5 tables)
- **Medicine & Catalog Management** (3 tables)
- **Prescription Management** (3 tables)
- **Order & Fulfillment** (6 tables)
- **Financial Management** (3 tables)
- **Insurance & Claims** (2 tables)
- **Logistics & Delivery** (3 tables)
- **System Configuration** (3 tables)
- **Audit & Reporting** (3 tables)

## 🚀 Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL 8.0+
- Redis (optional, for queues)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/pharmaconnect-kenya.git
   cd pharmaconnect-kenya
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your `.env` file**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pharmaconnect_kenya
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run migrations and seed the database**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Build assets**
   ```bash
   npm run build
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

### Default Admin Access

After seeding, you can access the admin panel at `/admin` with:
- **Email**: admin@pharmaconnect.ke
- **Password**: password

## 👥 User Roles & Access

### 🩺 Physician Panel (`/physician`)
- Patient management and medical records
- Digital prescription creation with drug interaction checks
- Commission tracking and earnings overview
- Prescription history and analytics

### 🏪 Supplier Panel (`/supplier`)
- Medicine inventory management
- Quotation and order processing
- Stock level monitoring
- Performance analytics and ratings

### 🛡️ Insurance Panel (`/insurance`)
- Policy management and verification
- Claims processing workflow
- Coverage analysis and reporting
- Provider network management

### 🚚 Operations Panel (`/operations`)
- Logistics coordination and tracking
- Delivery route optimization
- Rider management and performance
- Real-time GPS tracking dashboard

### ⚙️ Admin Panel (`/admin`)
- System configuration and settings
- User management and verification
- Comprehensive reporting and analytics
- Audit trail monitoring

## 📊 Core Modules

### Medicine Management
- **20,000+ Medicine Database** with comprehensive details
- **Drug Interaction Checks** for prescription safety
- **PPB Registration Tracking** for regulatory compliance
- **Full-text Search** across generic names, brands, and ingredients

### Prescription Workflow
```
Patient Registration → Prescription Creation → Drug Safety Check → 
Supplier Quotation → Order Processing → Insurance Claims → 
Delivery Tracking → Fulfillment Confirmation
```

### Financial Operations
- **Commission Management** for physician earnings
- **Dynamic Pricing Rules** with markup configurations
- **Invoice Generation** and payment tracking
- **Multi-currency Support** (KES primary)

### Logistics & Delivery
- **GPS Tracking** with real-time location updates
- **Proof of Delivery** with digital signatures
- **Route Optimization** for efficient delivery
- **SLA Monitoring** for performance tracking

## 🔐 Security Features

- **Multi-Factor Authentication (2FA)**
- **Role-based Access Control (RBAC)**
- **API Rate Limiting**
- **Comprehensive Audit Logging**
- **Data Encryption** for sensitive information
- **Professional License Verification**

## 📈 Reporting & Analytics

- **Prescription Analytics** - Trends, patterns, and insights
- **Supplier Performance** - Delivery times, quality ratings
- **Financial Reports** - Commission tracking, revenue analytics
- **Compliance Reports** - Audit trails, regulatory documentation
- **Logistics Metrics** - Delivery performance, route efficiency

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Generate coverage report
php artisan test --coverage
```

## 🚀 Deployment

### Production Setup

1. **Server Requirements**
   - PHP 8.2+ with required extensions
   - MySQL 8.0+ with spatial support
   - Redis for caching and queues
   - SSL certificate for HTTPS

2. **Environment Configuration**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

3. **Queue Workers**
   ```bash
   php artisan queue:work --daemon
   ```

4. **Scheduler Setup**
   ```bash
   # Add to crontab
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for any API changes
- Ensure all tests pass before submitting PR

## 📋 API Documentation

API documentation is available at `/docs/api` when running in development mode.

Key endpoints:
- `GET /api/medicines` - Search medicines
- `POST /api/prescriptions` - Create prescription
- `GET /api/orders/{id}/tracking` - Track delivery
- `POST /api/payments/callback` - Payment webhooks

## 🔧 Configuration

### Key Settings

| Setting | Description | Default |
|---------|-------------|---------|
| `COMMISSION_RATE` | Default physician commission % | 5.0 |
| `DELIVERY_SLA_HOURS` | Standard delivery time | 24 |
| `PRESCRIPTION_EXPIRY_DAYS` | Prescription validity | 30 |
| `MAX_INTERACTION_SEVERITY` | Allowed interaction level | moderate |

### Payment Integration

The system supports multiple payment methods:
- **M-Pesa Integration** - Mobile money payments
- **Card Payments** - Visa/Mastercard processing
- **Bank Transfers** - Direct bank integration
- **Insurance Coverage** - Automated claims processing

## 📞 Support

- **Documentation**: [docs.pharmaconnect.ke](https://docs.pharmaconnect.ke)
- **Email Support**: support@pharmaconnect.ke
- **Issue Tracker**: [GitHub Issues](https://github.com/yourusername/pharmaconnect-kenya/issues)

## 📄 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## 🙏 Acknowledgments

- **Pharmacy and Poisons Board (PPB)** - Regulatory guidance and compliance standards
- **Kenya Healthcare Federation** - Industry insights and requirements
- **Open Source Community** - Laravel, Filament, and supporting packages

---

<div align="center">
  <p><strong>Built with ❤️ for healthcare transformation in Kenya</strong></p>
  
  [![GitHub stars](https://img.shields.io/github/stars/yourusername/pharmaconnect-kenya?style=social)](https://github.com/yourusername/pharmaconnect-kenya/stargazers)
  [![GitHub forks](https://img.shields.io/github/forks/yourusername/pharmaconnect-kenya?style=social)](https://github.com/yourusername/pharmaconnect-kenya/network/members)
  [![GitHub issues](https://img.shields.io/github/issues/yourusername/pharmaconnect-kenya)](https://github.com/yourusername/pharmaconnect-kenya/issues)
</div>