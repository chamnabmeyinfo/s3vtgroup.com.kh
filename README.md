# S3VGroup - Forklift & Equipment E-Commerce Website

A comprehensive, modern e-commerce platform for selling forklifts and factory equipment with advanced features, role management, and analytics.

## 👨‍💻 For Developers

- **Admin Panel:** Access the admin panel for managing products, orders, and settings
- **API Endpoints:** RESTful API available at `/api/` for integration

## 🚀 Features

### Frontend

- ✅ Modern, responsive design
- ✅ Advanced product search and filtering
- ✅ Product comparison tool
- ✅ Wishlist functionality
- ✅ Shopping cart & checkout
- ✅ Customer accounts
- ✅ Product reviews & ratings
- ✅ Newsletter subscription
- ✅ Order tracking
- ✅ Recently viewed products
- ✅ Quick view modal
- ✅ Image zoom
- ✅ Social sharing
- ✅ FAQ section
- ✅ Testimonials
- ✅ Blog system
- ✅ Live chat widget

### Admin Panel

- ✅ Advanced analytics dashboard
- ✅ Product management (CRUD)
- ✅ Category management
- ✅ Bulk operations
- ✅ Product duplication
- ✅ CSV export
- ✅ Image gallery management
- ✅ Quote request management
- ✅ Contact message management
- ✅ Review moderation
- ✅ Newsletter management
- ✅ FAQ management
- ✅ Testimonials management
- ✅ Blog post management
- ✅ Site settings
- ✅ Advanced filters & column visibility
- ✅ Role-based access control
- ✅ User & role management
- ✅ Automated backups
- ✅ System logs viewer
- ✅ API testing interface
- ✅ RESTful API endpoints

### Smart Features

- ✅ AI-powered search
- ✅ Intelligent product recommendations
- ✅ Personalized content
- ✅ Automated insights

### Advanced Backend

- ✅ Advanced analytics engine
- ✅ RESTful API system
- ✅ Automated backup system
- ✅ Advanced logging system
- ✅ Caching system
- ✅ Cron job scheduler

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- GD Library (for image processing)

## 🛠️ Installation

### Quick Setup

1. **Clone the repository:**

   ```bash
   git clone https://github.com/chamnabmeyinfo/s3vtgroup.com.kh.git
   cd s3vtgroup.com.kh
   ```

2. **Configure Database:**

   - Copy `config/database.php.example` to `config/database.php` (if exists)
   - Or edit `config/database.php` with your database credentials

3. **Configure Application:**

   - Edit `config/database.php` with your database credentials
   - Edit `config/app.php` with your base URL

4. **Import Database:**
   - Import the database schema and features (see Manual Setup below)

### Manual Setup

1. **Create Database:**

   ```sql
   CREATE DATABASE forklift_equipment;
   ```

2. **Import Schema:**

   ```bash
   mysql -u root -p forklift_equipment < database/schema.sql
   ```

3. **Import Additional Features:**

   ```bash
   mysql -u root -p forklift_equipment < database/more-features.sql
   mysql -u root -p forklift_equipment < database/even-more-features.sql
   mysql -u root -p forklift_equipment < database/smart-features.sql
   ```

4. **Setup Role Management:**

   ```bash
   mysql -u root -p forklift_equipment < database/role-management.sql
   ```

5. **Configure:**
   - Edit `config/database.php` with your credentials
   - Edit `config/app.php` with your base URL

## 📁 Project Structure

```
s3vgroup/
├── admin/              # Admin panel
├── api/                # API endpoints
├── app/                # Application core
│   ├── Core/          # Core classes
│   ├── Database/      # Database layer
│   ├── Helpers/       # Helper functions
│   ├── Models/        # Data models
│   ├── Services/      # Business logic
│   └── Support/       # Support files
├── assets/            # CSS, JS, images
├── bootstrap/         # Bootstrap file
├── config/            # Configuration files
├── cron/              # Cron job scripts
├── database/          # SQL files & scripts
├── includes/          # Shared includes
├── storage/           # Uploads, cache, logs, backups
└── *.php             # Frontend pages
```

## 🔐 Default Credentials

**Admin Panel:**

- Username: `admin`
- Password: `admin`
- **⚠️ Change this immediately after first login!**

## 📚 Documentation

For detailed documentation, please refer to the Git repository or contact the development team.

## 🌐 URLs

- **Frontend:** `http://localhost:8080/`
- **Admin Panel:** `http://localhost:8080/admin/`
- **Admin Login:** `http://localhost:8080/admin/login.php`
- **API Base:** `http://localhost:8080/api/`

## 🛡️ Security

- Password hashing with bcrypt
- SQL injection prevention (PDO)
- XSS protection
- CSRF protection (recommended to add)
- Role-based access control
- Secure file uploads

## 📝 License

This project is proprietary software. All rights reserved.

## 👨‍💻 Development

### Adding New Features

1. Follow MVC pattern
2. Use existing models and services
3. Add permissions for admin features
4. Update documentation

### Code Style

- Follow PSR-12 coding standards
- Use meaningful variable names
- Add comments for complex logic
- Keep functions focused and small

## 🐛 Troubleshooting

For troubleshooting assistance, please contact the development team.

## 📧 Support

For support, please contact the development team.

---

**Version:** 2.0
**Last Updated:** 2024
