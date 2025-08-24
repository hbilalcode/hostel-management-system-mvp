# 🏠 Hostel Management System

A comprehensive web-based hostel management system built with PHP and MySQL. This system streamlines hostel operations with role-based access for administrators and students, providing complete management of rooms, allocations, fees, and student records.

## 🚀 Features

### 👨‍💼 Admin Features
- **Dashboard Analytics** - Real-time statistics on occupancy, revenue, and system overview
- **Room Management** - Complete CRUD operations for hostel rooms with capacity tracking
- **Student Management** - Comprehensive student records and profile management
- **Allocation System** - Intelligent room allocation with capacity validation
- **Fee Management** - Automated fee generation and payment tracking
- **Revenue Tracking** - Financial overview with detailed fee reports

### 👨‍🎓 Student Features
- **Personal Dashboard** - Overview of accommodation status and fee information
- **Room Details** - View current room assignment and roommate information
- **Fee History** - Track payment status and fee records
- **Profile Management** - Update personal information and contact details

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Security**: Password hashing, SQL injection prevention, session management
- **Design**: Responsive design for all devices

## 📁 Project Structure

```
hostel-management/
├── 📁 config/
│   └── 📄 db.php                 # Database configuration & helper functions
├── 📄 index.php                  # Landing page
├── 📄 login.php                  # Authentication system
├── 📄 register.php               # Student registration
├── 📄 admin_dashboard.php        # Admin dashboard with analytics
├── 📄 rooms.php                  # Room management (CRUD)
├── 📄 students.php               # Student management
├── 📄 allocations.php            # Room allocation system
├── 📄 fees.php                   # Fee management system
├── 📄 student_dashboard.php      # Student portal
├── 📄 logout.php                 # Logout functionality
├── 📄 database_setup.sql         # Complete database schema
└── 📄 README.md                  # Project documentation
```

## ⚙️ Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Web browser

### Step 1: Clone Repository
```bash
git clone https://github.com/yourusername/hostel-management-system.git
cd hostel-management-system
```

### Step 2: Database Setup
1. Create a new MySQL database:
```sql
CREATE DATABASE hostel_management;
```

2. Import the database schema:
```bash
mysql -u your_username -p hostel_management < database_setup.sql
```

3. Update database configuration in `config/db.php`:
```php
$host = 'localhost';
$username = 'your_username';
$password = 'your_password';
$database = 'hostel_management';
```

### Step 3: Web Server Configuration
1. Place the project files in your web server's document root
2. Ensure PHP has proper permissions to read/write files
3. Configure your web server to handle PHP files

### Step 4: Access the System
- Open your web browser and navigate to your domain/localhost
- Use the default admin credentials to get started

## 🔐 Default Credentials

### Administrator
- **Username**: `admin`
- **Password**: `admin123`

*⚠️ Important: Change the default admin password after first login for security*

## 📊 System Modules

### 🏘️ Room Management
- **Room Types**: Single and Double occupancy rooms
- **Capacity Control**: Automatic occupancy tracking
- **Status Management**: Vacant/Occupied status updates
- **Rent Management**: Flexible pricing per room type
- **Real-time Updates**: Live occupancy statistics

### 👥 Student Management
- **Registration System**: Self-service student registration
- **Profile Management**: Comprehensive student records
- **Search & Filter**: Advanced student lookup capabilities
- **Department Tracking**: Organize students by academic departments
- **Contact Management**: Maintain updated contact information

### 🔄 Allocation System
- **Smart Allocation**: Intelligent room assignment based on availability
- **Capacity Validation**: Prevents room over-allocation
- **Double-booking Prevention**: System-level allocation conflicts prevention
- **Real-time Tracking**: Live allocation status updates
- **History Maintenance**: Complete allocation audit trail

### 💰 Fee Management
- **Automated Generation**: Monthly/semester fee generation
- **Payment Tracking**: Paid/unpaid status management
- **Revenue Analytics**: Financial reporting and insights
- **Student Fee History**: Individual payment records
- **Flexible Fee Structure**: Customizable fee amounts

## 🎨 User Interface

### Design Principles
- **Responsive Design**: Optimized for desktop, tablet, and mobile devices
- **Clean Interface**: Modern, professional design aesthetic
- **Intuitive Navigation**: User-friendly menu and page structure
- **Interactive Elements**: Dynamic tables, forms, and search functionality
- **Accessibility**: Designed for users of all technical skill levels

### Browser Support
- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

## 🔒 Security Features

- **Password Security**: PHP password_hash() for secure password storage
- **SQL Injection Prevention**: Prepared statements for all database queries
- **Session Management**: Secure session handling and timeout
- **Role-based Access**: Strict permission controls for different user types
- **Input Validation**: Comprehensive data sanitization and validation

## 📱 Mobile Responsiveness

The system is fully optimized for mobile devices with:
- Responsive layouts that adapt to screen size
- Touch-friendly interface elements
- Optimized performance on mobile networks
- Mobile-first design approach


## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- Create an issue in the GitHub repository
- Contact: freelancer.hazratbilal@gmail.co,(mailto:freelancer.hazratbilal@gmail.com)
- Documentation: Check the wiki section for detailed guides

## 🎯 Roadmap

### Upcoming Features
- [ ] Email notifications for fee reminders
- [ ] Advanced reporting and analytics
- [ ] Mobile app development
- [ ] Integration with payment gateways
- [ ] Multi-hostel support
- [ ] Document management system

---

<div align="center">

**Built with ❤️ for efficient hostel management**

⭐ Star this repository if you found it helpful!

</div>
