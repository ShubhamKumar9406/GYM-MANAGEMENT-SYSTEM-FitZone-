**⚠️ PROPRIETARY SOFTWARE ⚠️**  
No commercial use, distribution, or modification permitted without written consent. See [LICENSE](LICENSE) for details.

<a href="https://buymeacoffee.com/vivek.raj" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" width="195" height="55"></a>


# 🏋️ FitZone - Gym Management System

A complete, modern Gym Management System built with HTML, CSS, JavaScript, PHP, and MySQL. Features a beautiful, responsive UI with smooth animations and comprehensive admin/user management capabilities.

---
## 🚀 Demo Video <a href="https://github.com/Its-vivek-sharma/FitZone-Web/blob/main/Gym-Management-System-Preview.mp4">here</a>

<div align="center">
<video poster="https://via.placeholder.com/800x450/FFDD00/000000?text=Demo+Loading..." width="100%" max-width="800px" controls>
  <source src="https://raw.githubusercontent.com/Its-vivek-sharma/FitZone-Web/main/Gym-Management-System-Preview.mp4" type="video/mp4">
</video>
</div>

## 📋 Features

### 🎨 Landing Page

- Modern, responsive design with smooth scroll animations
- Hero section with call-to-action buttons
- About section highlighting gym features
- Membership plans showcase (4-column grid layout)
- Equipment showcase with images
- Available time slots display
- Contact section with information

### 👤 User System

- **Registration**: New users can sign up with name, email, phone, gender, and password
- **Login**: Secure authentication with password hashing (bcrypt)
- **Dashboard**:
  - View purchased membership plan
  - Check assigned time slot
  - Request slot changes (auto-assign or admin approval based on capacity)
  - View plan expiry date and days remaining
  - Check payment status
  - Purchase new plans with payment method selection
  - Interactive slot selection with capacity indicators
- **Profile Management**:
  - Upload profile photo (stored in user_uploads/)
  - Update personal information (name, email, phone, gender)
  - Change password securely
  - View account status
- **Attendance**: View attendance information and assigned slot details

### 🔐 Admin System

- **Secure Admin Login**: Separate admin authentication
- **Dashboard**:
  - Overview statistics (users, members, revenue, pending requests, etc.)
  - Recent user registrations
  - Quick action buttons
  - Slot request notifications
- **User Management**:
  - View all registered users
  - Approve/Block users
  - Assign time slots to users (workflow: payment → plan activation → slot assignment)
  - Activate membership plans (only for paid users with matching amounts)
  - Edit attendance within last 15 days
  - Search and export user data to CSV
  - Compact table view with fixed column widths
- **Slot Request Management**:
  - View and manage pending slot change requests
  - Approve/reject user slot requests
  - Check slot capacity before approval
- **Plan Management**:
  - Add/Edit/Delete membership plans
  - Set price, duration, and description
- **Time Slot Management**:
  - Create fixed time slots
  - Set maximum capacity per slot
  - Track current member count
  - Activate/Deactivate slots
  - Prevent duplicate slot assignments
- **Staff Management**:
  - Add/Edit/Delete staff members
  - Track roles, salary, join date
  - Manage staff status
- **Equipment Management**:
  - Add/Edit/Delete gym equipment
  - Upload equipment images (stored in assets/images/equipment/)
  - Track purchase date and price
  - Monitor equipment status (Working/Maintenance/Damaged)
  - Display equipment on landing page
- **Payment Management**:
  - View all payment records
  - Update payment status (Paid/Pending/Due)
  - Track revenue statistics
  - Export payment data to CSV (server-side with UTF-8 BOM)
- **Profile Management**:
  - Upload profile photo
  - Update admin account details
  - Change password securely

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP (No framework)
- **Database**: MySQL
- **Security**:
  - Password hashing with bcrypt
  - Prepared statements (SQL injection prevention)
  - Session management
  - Input sanitization

---

## 📁 Project Structure

```
gym/
├── admin/
│   ├── includes/
│   │   ├── header.php (responsive hamburger menu)
│   │   └── sidebar.php
│   ├── dashboard.php
│   ├── login.php
│   ├── logout.php
│   ├── profile.php (admin profile with photo upload)
│   ├── manage_users.php
│   ├── manage_plans.php
│   ├── manage_slots.php
│   ├── manage_staff.php
│   ├── manage_equipment.php
│   ├── payments.php
│   ├── slot_requests.php (handle user slot change requests)
│   ├── export_payments.php (CSV export)
│   └── export_users.php (CSV export)
├── assets/
│   ├── css/
│   │   └── style.css (responsive with mobile breakpoints)
│   ├── js/
│   │   └── main.js (hamburger menu, modals, validations)
│   └── images/
│       └── equipment/ (equipment images storage)
├── includes/
│   ├── config.php
│   ├── db.php
│   └── functions.php
├── user/
│   ├── user_uploads/ (profile images storage)
│   ├── dashboard.php (slot selection with capacity indicators)
│   ├── profile.php (photo upload, password change)
│   ├── attendance.php
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── index.php
├── contact_handler.php
├── gym_management.sql (master database file)
├── .htaccess
├── SETUP_GUIDE.md
└── README.md
```

---

## 🚀 Installation Guide

### Prerequisites

- XAMPP/WAMP/LAMP (Apache, PHP 7.4+, MySQL)
- Web browser (Chrome, Firefox, Edge, etc.)

### Step 1: Setup XAMPP

1. Download and install XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
2. Start Apache and MySQL from XAMPP Control Panel

### Step 2: Clone/Copy Project

1. Copy the `gym` folder to your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\gym
   ```

### Step 3: Create Database

1. Open phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Create a new database named `gym_management`
3. Import the `gym_management.sql` file:
   - Click on `gym_management` database
   - Go to "Import" tab
   - Choose `gym_management.sql` file
   - Click "Go"
   - This master file contains all tables and necessary columns

### Step 3b: Set Folder Permissions

Ensure `user/user_uploads/` and `assets/images/equipment/` directories have write permissions for uploading images.

### Step 4: Configure Database Connection

1. Open `includes/config.php`
2. Update database credentials if needed (default values work with XAMPP):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'gym_management');
   ```

### Step 5: Access the Application

- **Landing Page**: [http://localhost/gym/](http://localhost/gym/)
- **User Login**: [http://localhost/gym/user/login.php](http://localhost/gym/user/login.php)
- **Admin Login**: [http://localhost/gym/admin/login.php](http://localhost/gym/admin/login.php)

---

## 🔑 Default Login Credentials

### Admin Account

- **Email**: admin@gym.com
- **Password**: admin123

### Test User Account

Create new user accounts by registering through the user registration page. Users can log in using registered email & password.

---

## 📊 Database Schema

### Users Table

Stores user authentication and profile information.

- Added `gender` field (male/female/other)
- Added `profile_image` field (VARCHAR 255) for user photos

### Members Table

Links users to their membership plans and time slots.

### Plans Table

Contains all membership plan details (price, duration, description).

### Time Slots Table

Manages gym time slots with capacity limits and current member count.

### Slot Requests Table

Tracks user slot change requests with approval workflow.

- Fields: user_id, requested_slot_id, current_slot_id, status, notes, created_at

### Payments Table

Records all payment transactions and their status.

### Staff Table

Manages gym staff information.

### Equipment Table

Tracks gym equipment inventory and status.

- Added `equipment_image` field (VARCHAR 255) for equipment photos

---

## 🎯 User Workflow

1. **Registration**: User signs up with personal details (name, email, phone, gender, password)
2. **Approval**: Admin reviews and approves the user account
3. **Purchase Plan**: User selects and purchases a membership plan with payment method
4. **Payment Pending**: Status shows "Pending Activation" until admin verifies payment
5. **Admin Activation**: Admin verifies payment and activates the membership plan (only for paid users with matching amounts)
6. **Slot Assignment**: Admin assigns a time slot OR user can request slot change
7. **Slot Request Workflow**:
   - If slot capacity < 50%: Auto-assigned instantly
   - If slot capacity ≥ 50%: Request sent to admin for approval
8. **Access**: User can now access the gym during their assigned time slot
9. **Profile Management**: User can update photo and personal information anytime

---

## 👨‍💼 Admin Workflow

1. **Login**: Admin logs in with credentials
2. **Dashboard**: View overview statistics and pending slot requests
3. **Approve Users**: Review and approve pending registrations
4. **Manage Plans**: Create/update membership plans
5. **Verify Payments**: Check payment records and update status
6. **Activate Plans**: Activate membership plans only for users with verified payments (amount must match plan price)
7. **Assign Slots**: Assign time slots to users (workflow enforced: must activate plan first)
8. **Handle Slot Requests**: Approve/reject user slot change requests from slot_requests page
9. **Manage Attendance**: Edit attendance records within last 15 days
10. **Export Data**: Export users and payments to CSV for reporting
11. **Monitor**: Track staff, equipment, and overall gym operations
12. **Profile**: Update admin profile photo and account details

---

## ✨ Key Features Explanation

### Responsive Design

- Works seamlessly on desktop, tablet, and mobile devices
- **Hamburger menu** for mobile navigation (both admin and user interfaces)
- Flexible grid layouts
- Mobile-optimized sidebars with slide-in animation and overlay
- Responsive text (truncated on mobile)
- Breakpoint at 968px for tablet/mobile view

### Animations

- Smooth scroll animations on landing page
- Fade-in effects for cards and sections
- Hamburger menu animation (3-span to X transformation)
- Sidebar slide-in transitions
- Hover effects on buttons and cards
- Loading spinners for operations

### Security

- Password hashing using bcrypt
- SQL injection prevention with prepared statements
- Session-based authentication
- Input sanitization and validation
- File upload validation (type, size)
- Workflow enforcement (payment → activation → slot assignment)

### User Experience

- Clean, modern UI design with gradient accents
- Intuitive navigation with icon indicators
- Real-time form validation
- Success/error flash notifications
- Search functionality in admin tables
- **CSV Export** with proper UTF-8 BOM encoding
- **Slot capacity indicators** with color-coded progress bars
- **Duplicate slot prevention** validation
- Live image preview before upload
- Compact tables for single-screen view (fixed column widths)

---

## 🔧 Configuration Options

### Modify Site Name

Edit `includes/config.php`:

```php
define('SITE_NAME', 'Your Gym Name');
```

### Change Color Scheme

Edit `assets/css/style.css` CSS variables:

```css
:root {
  --primary-color: #ff4444;
  --secondary-color: #1a1a1a;
  /* Modify other colors */
}
```

### Adjust Time Slots

Use Admin Panel → Manage Time Slots to add/edit/delete time slots.

### Set Membership Plans

Use Admin Panel → Manage Plans to configure plan prices and durations.

---

## 📱 Pages Overview

### Public Pages

- **index.php**: Landing page with gym information
- **contact_handler.php**: Process contact form submissions

### User Pages

- **user/register.php**: User registration form with gender field
- **user/login.php**: User login page
- **user/dashboard.php**: User dashboard with membership details, slot selection modal
- **user/profile.php**: Profile management with photo upload, password change
- **user/attendance.php**: View attendance records and slot information
- **user/user_uploads/**: Directory for storing user profile images

### Admin Pages

- **admin/login.php**: Admin login page
- **admin/dashboard.php**: Admin overview dashboard with statistics
- **admin/profile.php**: Admin profile management with photo upload
- **admin/manage_users.php**: User management, approval, slot assignment, plan activation
- **admin/manage_plans.php**: Membership plan management
- **admin/manage_slots.php**: Time slot management
- **admin/slot_requests.php**: Handle user slot change requests (approve/reject)
- **admin/manage_staff.php**: Staff management
- **admin/manage_equipment.php**: Equipment inventory
- **admin/payments.php**: Payment tracking and status updates
- **admin/export_payments.php**: Server-side CSV export for payments
- **admin/export_users.php**: Server-side CSV export for users

---

## 🐛 Troubleshooting

### Database Connection Error

- Ensure MySQL is running in XAMPP
- Verify database credentials in `includes/config.php`
- Check if `gym_management` database exists

### Page Not Found (404)

- Ensure project is in `htdocs/gym/` folder
- Check Apache is running
- Verify file names and paths

### Can't Login as Admin

- Check default credentials: admin@gym.com / admin123
- Verify users table has admin record
- Clear browser cache and cookies

### CSS/JS Not Loading

- Check file paths in HTML files
- Ensure Apache is serving static files
- Clear browser cache

---

## 🔄 Future Enhancements (Optional)

- Email notifications for registration approval and membership expiry
- SMS alerts for membership expiry
- QR code-based attendance tracking
- Online payment gateway integration (Razorpay, PayPal, Stripe)
- Member progress tracking and workout reports
- Workout plan assignment by trainers
- Diet plan recommendations
- Mobile application (React Native / Flutter)
- Multi-gym branch support
- Biometric attendance system
- Advanced analytics dashboard with charts
- Member referral system
- Trainer booking system
- Class schedule management

---

## 🆕 Recent Updates

### Version 2.0 Features

✅ **Mobile Responsive Navigation**

- Hamburger menu for both admin and user interfaces
- Slide-in sidebar with overlay backdrop
- Mobile-optimized header with truncated text

✅ **Profile Photo Upload**

- Users and admins can upload profile pictures
- Images stored in `user/user_uploads/` directory
- Automatic old image deletion to save space
- Filename format: `user_{id}_{name}.{ext}`
- File validation (JPG/PNG/GIF, max 5MB)
- Live preview before submission
- Default gradient avatar with initial letter

✅ **Enhanced Slot Management**

- User slot change requests with auto-approve or admin approval
- Capacity-based workflow (< 50% = auto, ≥ 50% = admin)
- Duplicate slot prevention validation
- Visual capacity indicators with progress bars
- Slot request management page for admin

✅ **Workflow Enforcement**

- Payment verification before plan activation
- Plan activation required before slot assignment
- Amount matching validation (paid amount must match plan price)
- Pending status display until admin approval

✅ **CSV Export Improvements**

- Server-side CSV generation with proper encoding
- UTF-8 BOM for Excel compatibility
- Timestamped filenames
- Export users and payments data

✅ **Equipment Showcase**

- Equipment image upload in admin panel
- Display equipment on landing page with images
- Image validation (JPG/PNG/GIF, max 5MB)
- Automatic old image deletion to save space
- Equipment cards with hover effects
- 6 working equipment items shown on homepage
- Object-fit contain to prevent image cropping

✅ **UI/UX Enhancements**

- Compact tables with fixed column widths
- Single-screen view optimization
- Gender field added to user profiles
- "Pending Activation" status for unpaid memberships
- Gradient buttons and modern styling
- Improved form layouts

✅ **Admin Features**

- Edit attendance within last 15 days
- My Profile page for admin account management
- Slot request notifications on dashboard
- Payment amount validation

---

## 📝 Notes

- This is a simple, clean implementation without unnecessary complexity
- All passwords are securely hashed using bcrypt
- The system uses prepared statements to prevent SQL injection
- Session management ensures secure user authentication
- The design is responsive and works on all devices

---

## 🤝 Support

For any issues or questions:

1. Check the troubleshooting section
2. Verify all installation steps were followed
3. Ensure XAMPP Apache and MySQL are running
4. Check error logs in XAMPP

---

## 📄 License

**⚠️ PROPRIETARY SOFTWARE ⚠️**  
No commercial use, distribution, or modification permitted without written consent. See [LICENSE](LICENSE) for details.

## 👨‍💻 Developer Notes

### Adding New Features

1. Create new PHP file in appropriate folder (admin/ or user/)
2. Include header, sidebar, and functions
3. Implement CRUD operations with prepared statements
4. Add navigation link in sidebar
5. Test thoroughly

### Database Changes

1. Make changes to `gym_management.sql`
2. Document changes in this README
3. Test with fresh database import

### Security Best Practices

- Always use prepared statements
- Sanitize user input
- Validate data on server-side
- Use HTTPS in production
- Keep software updated

---

**Built with ❤️ for fitness enthusiasts**
By Vivek Sharma
Enjoy managing your gym efficiently with FitZone! 💪
