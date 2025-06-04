# Yoga Studio Management System

A comprehensive web-based management system for yoga studios, built with PHP and MySQL. This system helps manage clients, instructors, classes, attendance, and more.

## 🌟 Features

### Client Management
- Client registration and profile management
- Client batch assignment
- Client restrictions and limitations
- Demo class registration
- Client attendance tracking
- Membership management

### Studio & Batch Management
- Studio registration and management
- Batch creation and scheduling
- Batch assignment to studios
- Schedule management
- Asana (yoga pose) scheduling

### Staff Management
- Employee registration and management
- Instructor batch assignment
- Staff attendance tracking
- Role-based access control

### Operations
- Attendance tracking with QR code support
- Membership management
- Plan management
- Fee collection and tracking
- Client enquiry management

### Additional Features
- Responsive design for all devices
- Secure authentication system
- Session management
- QR code generation for attendance
- Report generation
- Multi-studio support

## 🛠️ Technical Stack

- **Frontend:**
  - HTML5
  - CSS3
  - JavaScript
  - Bootstrap 5.3.3
  - Bootstrap Icons

- **Backend:**
  - PHP
  - MySQL
  - Apache Server

- **Additional Libraries:**
  - PHP QR Code Library
  - Face Recognition Models

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Server
- XAMPP (recommended for local development)
- Web browser with JavaScript enabled

## 🚀 Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/mananbhimjiyani/Yogarp-PHP.git
   ```

2. Set up your web server (XAMPP recommended):
   - Place the project in your web server's root directory
   - For XAMPP: `/Applications/XAMPP/xamppfiles/htdocs/yogarp`

3. Import the database:
   - Create a new database in MySQL
   - Import the database schema (SQL file)

4. Configure the database connection:
   - Open `db.php`
   - Update the database credentials

5. Configure paths:
   - Open `Path.php`
   - Update the necessary paths

## 🔧 Configuration

1. Database Configuration (`db.php`):
   ```php
   $host = 'localhost';
   $username = 'your_username';
   $password = 'your_password';
   $database = 'yoga_studio_db';
   ```

2. Path Configuration (`Path.php`):
   ```php
   define('BASE_URL', 'http://localhost/yogarp/');
   define('LOGO_PATH', 'path/to/logo.png');
   ```

## 👥 User Roles

1. **Administrator**
   - Full system access
   - User management
   - System configuration

2. **Instructor**
   - Client management
   - Attendance tracking
   - Batch management

3. **Staff**
   - Basic operations
   - Limited access

## 📁 Project Structure

```
yogarp/
├── includes/           # Common includes
├── models/            # Face recognition models
├── uploads/           # Uploaded files
├── phpqrcode/        # QR code library
├── Attendance/        # Attendance related files
└── [PHP files]        # Main application files
```

## 🔐 Security Features

- Password hashing
- Session management
- SQL injection prevention
- XSS protection
- CSRF protection
- Input validation
- Secure file uploads

## 📱 Responsive Design

- Mobile-first approach
- Responsive layouts
- Dynamic sizing
- Cross-browser compatibility
- Touch-friendly interfaces

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👨‍💻 Author

- **Manan Bhimjiyani**
  - Email: mananbhimjiyani@gmail.com
  - GitHub: [mananbhimjiyani](https://github.com/mananbhimjiyani)

## 🙏 Acknowledgments

- Bootstrap team for the amazing framework
- PHP QR Code library contributors
- All contributors and supporters of the project

## 📞 Support

For support, email info@yogarp.com or create an issue in the repository. 