# CAE - Learning Timeslot Management System

A web application for managing learning timeslots, allowing tutors to create slots for classes, students to book them, and administrators to manage the entire system.

## 🚀 Features

### 👨‍🏫 For Tutors:
- View all available timeslots
- Create new timeslots
- Manage your own timeslots
- View student bookings for your classes
- Delete timeslots

### 👨‍🎓 For Students:
- View available timeslots
- Book timeslots
- View your bookings
- Cancel bookings
- View class schedule

### 👨‍💼 For Administrators:
- Complete system management panel
- View statistics (students, tutors, courses, bookings)
- User management
- System activity monitoring
- Action logging

## 🛠️ Technologies

- **Backend:** PHP 8.2+
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Server:** XAMPP (Apache + MySQL)
- **Security:** Session-based authentication, Password hashing, SQL injection protection

## 📁 Project Structure

```
CAE/
├── backend/
│   ├── admin/           # Admin panel
│   │   ├── auth.php     # Admin authentication
│   │   ├── db.php       # Database connection
│   │   ├── login.php    # Admin login
│   │   ├── dashboard/   # Dashboard API
│   │   └── logout.php   # System logout
│   ├── common/          # Common functions
│   │   ├── auth.php     # User authentication
│   │   ├── db.php       # Database connection
│   │   ├── login.php    # User login
│   │   └── register.php # Registration
│   ├── student/         # Student functions
│   ├── tutor/           # Tutor functions
│   └── cae_structure.sql # Database structure
├── frontend/
│   ├── admin/           # Admin panel
│   │   ├── login.html   # Login page
│   │   ├── dashboard.html # Main dashboard
│   │   └── admin.css    # Admin panel styles
│   ├── common/          # Common pages
│   ├── student/         # Student pages
│   ├── tutor/           # Tutor pages
│   └── style.css        # Main styles
└── .htaccess            # Apache configuration
```

## 🗄️ Database

The system includes the following main tables:
- `Student` - students
- `Tutor` - tutors
- `Admin` - administrators
- `Course` - courses
- `Timeslot` - time slots
- `Appointment` - class bookings
- `Admin_Log` - administrator action logs

## 🔧 Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/nezhdanov2000/CAE.git
   ```

2. **Set up the database:**
   - Create a MySQL database
   - Import the structure from `backend/cae_structure.sql`
   - Configure the connection in `backend/common/db.php`

3. **Set up the web server:**
   - Place the files in the web server directory (e.g., `htdocs` for XAMPP)
   - Make sure PHP and MySQL are running

4. **Open the application in your browser:**
   ```
   http://localhost/CAE/frontend/common/login.html
   ```

## 🔐 System Access

### Test Accounts:

**Administrator:**
- Email: `admin@cae.com`
- Password: `admin123`

**Student:**
- Email: `student1@test.com`
- Password: `admin123`

**Tutor:**
- Email: `tutor1@test.com`
- Password: `admin123`

## 🛡️ Security

- All database queries use prepared statements
- User authorization and role verification
- SQL injection protection
- Password hashing using `password_hash()`
- Session-based authentication
- Administrator action logging

## 📊 Admin Panel

Access to admin panel: `http://localhost/CAE/frontend/admin/login.html`

Admin panel features:
- View system statistics
- User management
- Activity monitoring
- View action logs

## 🎨 Design

- Modern responsive design
- Separate styles for admin panel
- Mobile device support
- Intuitive interface

## 📝 License

This project is open source and available under the MIT license.

## 🤝 Contributing

Any contributions to the project development are welcome! Please create issues and pull requests.

---

**Author:** nezhdanov2000  
**Repository:** [https://github.com/nezhdanov2000/CAE](https://github.com/nezhdanov2000/CAE) 