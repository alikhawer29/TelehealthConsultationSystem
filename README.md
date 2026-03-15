# Telehealth Consultation System

A comprehensive telehealth platform built with Laravel 10, supporting multi-role healthcare management, secure authentication, and real-time communication features for seamless healthcare service delivery.

## System Overview

**TelehealthConsultationSystem** is a full-featured healthcare platform that connects patients with healthcare providers through digital consultations, featuring real-time video/audio calls, secure payments, and comprehensive medical record management.

### Core Actors & Roles
- **Admin / Web**: Platform administration, user management, system oversight
- **User / Web**: Patients seeking healthcare services, appointments, and consultations  
- **Employee / Web**: Healthcare providers (Doctors, Nurses, Physicians) delivering services

## Key Features

### Real-Time Communication
- **WebEx Integration**: Professional video/audio consultations
- **Meeting Management**: Automated meeting creation and joining
- **Guest Access**: Secure patient meeting access without WebEx accounts
- **Session Recording**: Optional consultation recording capabilities

### Chat & Support
- **Zoho SalesIQ Integration**: Real-time customer support and chat
- **Department Routing**: Automatic chat routing to appropriate departments
- **Operator Management**: Multiple support operators with role-based access
- **Conversation History**: Complete chat transcripts and analytics

### Advanced Payment System
- **Stripe Integration**: Secure payment processing
- **Stripe Connect**: Split payments for healthcare providers
- **Multi-Currency Support**: AED and other currencies
- **Automatic Payouts**: Direct transfers to provider accounts

### Healthcare Management
- **Appointment Scheduling**: Comprehensive booking system with time slots
- **Medical Records**: Complete patient history and documentation
- **Prescriptions**: Digital prescription management
- **Insurance Integration**: Insurance provider management
- **Service Bundles**: Package deals for multiple services

## Technology Stack

### Backend Framework
- **Laravel**: 10.31.0
- **PHP**: 8.1.13
- **Database**: MySQL with comprehensive migrations
- **Authentication**: Laravel Sanctum 3.2

### Core Dependencies
```json
{
    "stripe/stripe-php": "^9.6",
    "laravel/sanctum": "^3.2",
    "laravel/passport": "^11.9",
    "kreait/laravel-firebase": "^5.9",
    "maatwebsite/excel": "*",
    "barryvdh/laravel-dompdf": "*",
    "mpdf/mpdf": "*",
    "phpoffice/phpspreadsheet": "*"
}
```

### Third-Party Integrations
- **WebEx**: Video/audio calling platform
- **Zoho SalesIQ**: Customer support and chat
- **Stripe**: Payment processing and Connect
- **Firebase**: Push notifications
- **Pusher**: Real-time websockets

## API Architecture

### Multi-Tenant API Structure
The system provides separate API endpoints for each actor type:

#### Admin API (`/admin-api`)
- **Authentication**: Login/logout with role-based access
- **User Management**: Complete CRUD for all user types
- **Health Professionals**: Doctor, nurse, physician management
- **Appointments**: System-wide appointment oversight
- **Services**: Service and bundle management
- **Payments**: Revenue tracking and financial reports
- **Medical Records**: System-wide medical data access
- **Chat Support**: Admin chat interface

#### User API (`/user-api`)
- **Patient Authentication**: Secure patient login/registration
- **Appointment Booking**: Schedule and manage appointments
- **Service Catalog**: Browse and book healthcare services
- **Cart Management**: Shopping cart for multiple services
- **Payment Processing**: Secure payment handling
- **Medical History**: Personal medical records access
- **Family Members**: Manage family accounts
- **Prescriptions**: View and manage prescriptions

#### Doctor API (`/doctor-api`)
- **Provider Authentication**: Healthcare provider login
- **Schedule Management**: Available time slots and calendar
- **Appointment Management**: Patient consultation handling
- **WebEx Integration**: Video meeting creation and management
- **Zoho Chat**: Customer support integration
- **Prescriptions**: Digital prescription creation
- **Medical Reports**: Patient documentation
- **Revenue Tracking**: Provider earnings dashboard

#### Nurse API (`/nurse-api`)
- **Nurse Authentication**: Nursing staff login
- **Patient Care**: Nursing service management
- **Appointment Handling**: Nurse-specific appointments
- **Medical Documentation**: Nursing notes and records
- **Chat Support**: Patient communication

#### Physician API (`/physician-api`)
- **Physician Authentication**: Doctor login system
- **Consultation Management**: Physician appointments
- **Medical Expertise**: Specialized care services
- **Documentation**: Medical reports and analysis

## Security & Authentication

### Laravel Sanctum Implementation
- **Token-Based Authentication**: Secure API access
- **Multi-Guard System**: Separate guards for each user type
- **Stateful Domains**: Configured for SPA compatibility
- **Token Expiration**: Automatic token refresh system

### Security Features
- **Role-Based Access Control**: Granular permissions
- **Input Validation**: Comprehensive request validation
- **SQL Injection Protection**: Eloquent ORM security
- **CORS Configuration**: Cross-origin request handling
- **File Upload Security**: Validated file handling

## Payment System Details

### Stripe Integration
```php
// Payment Processing
$stripe = new \Stripe\StripeClient(config('gateway.stripe.credentials.private_key'));

// Split Payment Support
$account = $stripe->accounts->create([
    'type' => 'custom',
    'country' => 'US',
    'capabilities' => [
        'card_payments' => ['requested' => true],
        'transfers' => ['requested' => true],
    ]
]);
```

### Split Payment Flow
1. **Patient Payment**: Payment received by platform
2. **Provider Allocation**: Automatic distribution to healthcare providers
3. **Platform Fee**: Commission retention
4. **Payout Processing**: Scheduled transfers to connected accounts

## WebEx Video Integration

### Meeting Creation Workflow
```php
// WebEx Meeting Setup
$meetingData = [
    'title' => 'Medical Consultation - Appointment #' . $id,
    'start' => now()->toIso8601String(),
    'end' => now()->addMinutes(30)->toIso8601String(),
    'agenda' => 'Telehealth consultation',
    'enabledAutoRecordMeeting' => false,
    'allowAnyUserToBeCoHost' => false
];
```

### Features
- **Guest Access**: Patients join without WebEx accounts
- **Secure Tokens**: JWT-based meeting access
- **Session Management**: Real-time meeting status
- **Quality Control**: HD video/audio quality

## Zoho Chat Integration

### Real-Time Support System
```php
// Zoho Visitor Creation
$visitorData = [
    'name' => $user->name,
    'email' => $user->email,
    'phone_number' => $user->phone,
    'department_id' => $this->getDepartmentId($user->role)
];
```

### Chat Features
- **Department Routing**: Automatic chat assignment
- **Operator Management**: Multiple support agents
- **Conversation History**: Complete chat transcripts
- **Analytics**: Chat performance metrics

## Database Schema

### Core Tables
- **Users**: Multi-role user management
- **Appointments**: Comprehensive booking system
- **Services**: Healthcare service catalog
- **Payments**: Transaction and split payment records
- **Medical Records**: Patient health documentation
- **Prescriptions**: Digital prescription management
- **WebEx Tokens**: Video platform integration
- **Zoho Tokens**: Chat platform authentication

### Relationships
- **User → Appointments**: One-to-many relationship
- **User → Medical Records**: Patient history
- **Appointments → Payments**: Financial tracking
- **Services → Appointments**: Service booking

## Installation & Setup

### Prerequisites
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Node.js & NPM
- Redis (optional, for caching)

### Environment Configuration
```bash
# Clone Repository
git clone https://github.com/yourusername/TelehealthConsultationSystem.git
cd TelehealthConsultationSystem

# Install Dependencies
composer install
npm install

# Environment Setup
cp .env.example .env
php artisan key:generate

# Database Setup
php artisan migrate
php artisan db:seed

# Link Storage
php artisan storage:link

# Build Assets
npm run build

# Start Server
php artisan serve
```

### Configuration Files

#### `.env` Setup
```env
# Application Configuration
APP_NAME=TELEHEALTH
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=telehealth
DB_USERNAME=root
DB_PASSWORD=

# Stripe Configuration
PRIVATE_KEY=pk_test_your_stripe_private_key
SECRET_KEY=sk_test_your_stripe_secret_key
PUBLIC_KEY=pk_test_your_stripe_public_key

# WebEx Configuration
WEBEX_CLIENT_ID=your_webex_client_id
WEBEX_CLIENT_SECRET=your_webex_client_secret
WEBEX_REDIRECT_URI=http://localhost/telehealth_backend/doctor-api/webex/callback
WEBEX_GUEST_ISSUER_ID=your_guest_issuer_id
WEBEX_GUEST_SECRET=your_guest_secret

# Zoho Configuration
ZOHO_CLIENT_ID=your_zoho_client_id
ZOHO_CLIENT_SECRET=your_zoho_client_secret
ZOHO_REDIRECT_URI=http://localhost/telehealth_backend/doctor-api/zoho/callback

# Firebase Configuration
FIREBASE_CREDENTIALS=serviceAccountKey.json
```

## Development Workflow

### API Testing
```bash
# Run Tests
php artisan test

# API Documentation
php artisan route:list --api

# Database Operations
php artisan migrate:fresh --seed
php artisan db:show
```

### Queue Management
```bash
# Start Queue Worker
php artisan queue:work --daemon

# Failed Jobs
php artisan queue:failed
php artisan queue:retry all
```

## API Endpoints Reference

### Authentication Endpoints
```
POST /admin-api/auth/login          # Admin login
POST /user-api/auth/register        # User registration
POST /doctor-api/auth/login         # Doctor login
POST /nurse-api/auth/login          # Nurse login
POST /physician-api/auth/login      # Physician login
```

### Appointment Management
```
GET  /user-api/appointments         # User appointments
POST /user-api/appointments         # Create appointment
GET  /doctor-api/appointments       # Doctor appointments
POST /doctor-api/appointments/generate-token  # Start video call
```

### Payment Processing
```
POST /user-api/payments             # Process payment
GET  /admin-api/payments           # Payment history
POST /admin-api/payments/transfer   # Split payment transfer
```

### Real-Time Features
```
POST /user-api/webex/guest-token   # Get meeting access
GET  /doctor-api/webex/callback    # WebEx OAuth callback
GET  /doctor-api/zoho/departments  # Zoho departments
POST /doctor-api/zoho/chat/send    # Send chat message
```

## Deployment

### Production Setup
```bash
# Environment Configuration
APP_ENV=production
APP_DEBUG=false

# Optimization Commands
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Queue Setup
php artisan queue:restart
```

### Server Requirements
- **Web Server**: Nginx or Apache
- **PHP Version**: 8.1+ with required extensions
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **SSL Certificate**: Required for production
- **Redis**: Recommended for caching and queues

## Monitoring & Analytics

### System Monitoring
- **Application Logs**: Comprehensive error tracking
- **Performance Metrics**: API response times
- **Database Monitoring**: Query optimization
- **Payment Tracking**: Revenue analytics

### Healthcare Analytics
- **Appointment Statistics**: Booking trends
- **Provider Performance**: Healthcare provider metrics
- **Patient Satisfaction**: Service quality tracking
- **Revenue Reports**: Financial analytics

## Compliance & Security

### Healthcare Compliance
- **Data Protection**: Patient data encryption
- **Access Control**: Role-based permissions
- **Audit Trails**: Complete action logging
- **Data Retention**: Configurable retention policies

### Security Measures
- **HTTPS Enforcement**: SSL/TLS required
- **Input Sanitization**: XSS protection
- **SQL Injection Prevention**: Parameterized queries
- **Rate Limiting**: API abuse prevention

## Contributing Guidelines

### Development Standards
- **Code Style**: Follow PSR-12 standards
- **Testing**: Write comprehensive tests
- **Documentation**: Update API documentation
- **Security**: Follow security best practices

### Pull Request Process
1. Fork the repository
2. Create feature branch
3. Implement changes with tests
4. Update documentation
5. Submit pull request

## License & Support

### License
This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

### Support & Documentation
- **Documentation**: Complete API documentation
- **Issue Tracking**: GitHub issues for bug reports
- **Community**: Developer community support
- **Updates**: Regular feature updates and security patches

## Version History

### Current Version: 1.3.0
- **Enhanced Security**: Improved authentication system
- **Video Integration**: WebEx real-time consultations
- **Chat Support**: Zoho SalesIQ integration
- **Payment System**: Stripe Connect split payments
- **Mobile Optimization**: Responsive design improvements

### Previous Versions
- **v1.2.0**: Added real-time chat support
- **v1.1.0**: Implemented split payments
- **v1.0.0**: Initial platform release

---

## Future Roadmap

### Upcoming Features
- **Mobile Applications**: Native iOS and Android apps
- **AI Integration**: Symptom checker and recommendations
- **Advanced Analytics**: Predictive healthcare analytics
- **Expanded Integrations**: More payment and communication options
- **Multi-Language Support**: International localization

### Technology Enhancements
- **Microservices Architecture**: Scalable system design
- **Real-Time Notifications**: WebSocket implementation
- **Advanced Security**: Biometric authentication
- **Cloud Deployment**: AWS/Azure optimization

---

**Built with ❤️ for the future of digital healthcare**

*TelehealthConsultationSystem - Transforming healthcare delivery through technology*
