# Coffee Management System

A complete PHP-based coffee management system for connecting coffee sellers with customers. Built with pure PHP, SQLite, HTML, CSS, and vanilla JavaScript.

## Features

### Admin Panel
- Secure login system
- Dashboard with statistics
- Create, edit, and delete coffee types
- View all registered sellers
- View all coffee stocks
- Prevent duplicate coffee types

### Seller Panel
- Registration system
- Login/Logout functionality
- Add coffee stock
- Select coffee type from dropdown
- Enter available kilos
- Update stock
- Delete stock
- View only their stocks

### Customer Interface (Public)
- No login required
- View all available coffee
- See coffee type, seller name, location, and available kilos
- Search and filtering functionality
- Contact seller information

## Technical Requirements

- PHP 7.4+ or PHP 8.0+
- SQLite3 extension
- Web server (Apache, Nginx, or PHP built-in server)

## Security Features

- `password_hash()` and `password_verify()` for secure password storage
- Session authentication with role-based access control
- CSRF protection on all forms
- Input validation and sanitization
- Output escaping to prevent XSS
- Session regeneration on login
- Proper logout functionality
- Prepared statements to prevent SQL injection

## Installation

### 1. Setup the Project

1. Download or clone the project to your web server directory
2. Ensure the `database/` directory is writable by the web server

### 2. Configure the System

The system uses SQLite, so no database configuration is needed. The database file will be created automatically on first run.

### 3. Set Permissions

Make sure the following directories are writable:
- `database/` (for SQLite database file)
- `logs/` (for error logs - create if it doesn't exist)

```bash
chmod 755 database/
chmod 755 logs/
```

### 4. Access the System

1. Open your web browser and navigate to the project URL
2. Default admin login:
   - Phone: `0000000000`
   - Password: `admin123`

**Important:** Change the default admin password immediately after first login!

## Project Structure

```
coffee-management-system/
├── admin/                  # Admin panel files
│   ├── index.php          # Admin dashboard
│   ├── coffee-types.php   # Manage coffee types
│   ├── sellers.php        # View all sellers
│   ├── stocks.php         # View all stocks
│   └── seller-details.php # Individual seller details
├── config/
│   └── config.php         # Configuration settings
├── database/
│   └── init.sql           # Database initialization script
├── includes/
│   ├── database.php       # Database connection class
│   ├── functions.php      # Common functions
│   ├── header.php         # Header component
│   └── footer.php         # Footer component
├── public/
│   └── coffee.php         # Public coffee browsing
├── seller/                # Seller panel files
│   ├── index.php          # Seller dashboard
│   ├── register.php       # Seller registration
│   ├── add-stock.php      # Add new stock
│   ├── stocks.php         # Manage stocks
│   └── update-stock.php   # Update stock handler
├── assets/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── script.js      # JavaScript functionality
├── index.php              # Home page
├── login.php              # Login page
├── logout.php             # Logout handler
└── README.md              # This file
```

## Database Schema

### Users Table
- `id` - Primary key
- `name` - User's full name
- `phone` - Phone number (used for login)
- `location` - User's location
- `password` - Hashed password
- `role` - User role (admin/seller)
- `created_at` - Registration timestamp

### Coffee Types Table
- `id` - Primary key
- `name` - Coffee type name (unique)
- `created_at` - Creation timestamp

### Stocks Table
- `id` - Primary key
- `seller_id` - Foreign key to users table
- `coffee_type_id` - Foreign key to coffee_types table
- `kilos` - Available kilos
- `updated_at` - Last update timestamp

## Usage Instructions

### For Admins

1. Login with admin credentials
2. Access the admin dashboard
3. Manage coffee types from the "Coffee Types" menu
4. View all sellers and their stocks
5. Monitor system statistics

### For Sellers

1. Register as a seller from the main page
2. Login with your phone number and password
3. Add coffee stock by selecting coffee type and entering kilos
4. Update or delete existing stock records
5. View your stock statistics

### For Customers

1. Browse available coffee without registration
2. Use search and filters to find specific coffee
3. View seller information and contact details
4. Contact sellers directly to make purchases

## Security Notes

- All passwords are hashed using PHP's `password_hash()`
- CSRF tokens are used on all forms
- Input is validated and sanitized
- SQL injection is prevented using prepared statements
- XSS is prevented through output escaping
- Session security is implemented with regeneration

## Customization

### Adding New Coffee Types

Admins can add new coffee types through the admin panel:
1. Go to Admin Panel → Coffee Types
2. Click "Add Coffee Type"
3. Enter the coffee type name
4. Save

### Modifying the UI

The CSS is located in `assets/css/style.css`. The system uses a clean, modern design with coffee-themed colors.

### Adding New Features

The system is built with a modular structure. New features can be added by:
1. Creating new PHP files in appropriate directories
2. Adding new database tables or columns
3. Updating the navigation and routing

## Troubleshooting

### Database Issues

If the database doesn't initialize:
1. Check if the `database/` directory is writable
2. Ensure SQLite3 extension is enabled in PHP
3. Check error logs in `logs/error.log`

### Login Issues

If login fails:
1. Verify the phone number format (10-15 digits)
2. Check if the user exists in the database
3. Ensure password is correct

### Permission Issues

If you get permission errors:
1. Set proper file permissions for `database/` directory
2. Ensure web server can write to the database file
3. Check PHP error logs for specific issues

## Support

This is a demonstration system. For production use, consider:
- Adding more robust error handling
- Implementing email notifications
- Adding payment processing
- Enhancing security measures
- Adding more comprehensive logging


