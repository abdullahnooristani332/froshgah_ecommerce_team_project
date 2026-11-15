# Froshgah — PHP & MySQL E-Commerce Platform

A web-based e-commerce platform developed using PHP, MySQL, HTML, CSS, and JavaScript. Froshgah is designed to provide an online shopping experience with customer-facing features and administrative functionality.

**Repository:** https://github.com/abdullahnooristani332/froshgah

---

## Table of Contents

- [About the Project](#about-the-project)
- [Technologies](#technologies)
- [Features](#features)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Installation Guide](#installation-guide)
- [Database Configuration](#database-configuration)
- [Running the Application](#running-the-application)
- [How to Use](#how-to-use)
- [Development Setup](#development-setup)
- [Contributing](#contributing)
- [Security](#security)
- [Author](#author)
- [License](#license)

---

## About the Project

Froshgah is an academic software engineering project that demonstrates the development of an e-commerce web application using core web technologies.

The project aims to provide a structured online shopping platform where customers can browse products, manage their shopping activities, and interact with the system.

It also provides an administrative area for managing the application.

This project is suitable for learning, academic purposes, PHP development practice, and further customization.

## Technologies

| Technology | Purpose |
|---|---|
| PHP | Backend development |
| MySQL | Relational database management |
| HTML5 | Web page structure |
| CSS3 | Styling and responsive layouts |
| JavaScript | Client-side interactivity |
| Apache | Web server |
| PDO | PHP database connectivity |
| Git | Version control |
| GitHub | Source code hosting and collaboration |

## Features

### Customer Features

- User registration and authentication
- Product browsing
- Product details
- Shopping cart
- Order management
- Customer profile management
- Order history
- Product reviews
- Notifications

### Administrative Features

- Administrative dashboard
- Product and store management
- Order management
- User management

*Note: The availability of individual features depends on the current implementation in the repository.*

## Project Structure

The main project directories are organized as follows:

```text
froshgah/
│
├── actions/       # Application actions and processing
├── admin/         # Administrative panel
├── assets/        # CSS, JavaScript, images and other assets
├── config/        # Application and database configuration
├── database/      # Database files and SQL scripts
├── includes/      # Shared PHP components
├── pages/         # Application pages
├── uploads/       # Uploaded files and images
│
├── index.php      # Application entry point
├── logout.php     # User logout
├── README.md      # Project documentation
└── .gitignore     # Git ignored files
```

## Requirements

Before installing Froshgah, make sure your computer has:

- PHP 8.0 or later
- MySQL 5.7+ or MariaDB
- Apache web server
- PHP PDO and PDO MySQL extensions
- A modern web browser
- Git (for cloning the repository)

**Recommended local development environment:** Laragon, XAMPP, or another PHP/MySQL environment.

## Installation Guide

Follow these steps to install Froshgah on your local computer.

### Step 1: Clone the Repository

Open your terminal (CMD, PowerShell, Git Bash, or VS Code Terminal).

Navigate to your web server directory.

For Laragon:

```bash
cd C:\laragon\www
```

For XAMPP:

```bash
cd C:\xampp\htdocs
```

Clone the repository:

```bash
git clone https://github.com/abdullahnooristani332/froshgah.git
```

Enter the project directory:

```bash
cd froshgah
```

### Step 2: Start Apache and MySQL

If you are using Laragon:

1. Open Laragon.
2. Start Apache.
3. Start MySQL.

If you are using XAMPP:

1. Open XAMPP Control Panel.
2. Start Apache.
3. Start MySQL.

### Step 3: Create the Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

1. Create a new MySQL database.
2. Choose a database name.
3. Open the `database/` directory in the project.
4. Locate the provided `.sql` database file.
5. Select the database in phpMyAdmin.
6. Open the Import tab.
7. Select the SQL file.
8. Click Import.

If the project database has not yet been exported, ask the project maintainer for the current database schema.

### Step 4: Configure the Database Connection

Open the configuration directory:

```text
config/
```

Locate the PHP file responsible for database connectivity.

Update the database credentials according to your local MySQL configuration.

Example configuration:

```php
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'root';
$password = '';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$pdo = new PDO($dsn, $username, $password);
```

**Important:** This is an illustrative PDO configuration. Preserve the existing connection structure in the actual project and update only the appropriate credentials.

### Step 5: Run the Application

Open your browser and visit:

```text
http://localhost/froshgah/
```

If everything is configured correctly, the Froshgah homepage should appear.

---

## How to Use

### For Customers

1. Open the Froshgah homepage.
2. Browse the available products.
3. Register for an account or log in.
4. Explore product details.
5. Add products to your shopping cart.
6. Continue through the available order process.
7. Manage your profile and review your orders.

### For Administrators

1. Access the administrative section.
2. Log in using an authorized administrator account.
3. Manage available administrative functions.
4. Manage products, customers, and orders according to your permissions.

**Note:** An administrator account must be configured in the database or through the project's supported registration process. Never assume default administrator credentials.

---

## Development Setup

If you want to contribute to the project or modify its source code:

Clone the repository:

```bash
git clone https://github.com/abdullahnooristani332/froshgah.git
```

Enter the project directory:

```bash
cd froshgah
```

Check the current Git status:

```bash
git status
```

Create a new branch for your changes:

```bash
git checkout -b feature/your-feature-name
```

After making changes:

```bash
git add .
```

Commit your changes:

```bash
git commit -m "Add your feature description"
```

Push your branch:

```bash
git push -u origin feature/your-feature-name
```

Then open a Pull Request on GitHub.

## Contributing

Contributions, suggestions, bug reports, and improvements are welcome.

To contribute:

1. Fork this repository.
2. Clone your fork.
3. Create a new feature branch.
4. Implement your improvements.
5. Test your changes.
6. Commit and push your changes.
7. Submit a Pull Request.

Please provide clear descriptions of your changes.

## Security

- Never commit database passwords or API keys.
- Do not upload private user information.
- Keep sensitive configuration files out of version control.
- Use secure password hashing.
- Validate and sanitize user input.
- Use prepared SQL statements.
- Configure appropriate access permissions.

## Author

**Abdullah Nooristani**

Software Engineering Student | PHP & Web Developer

GitHub: [@abdullahnooristani332](https://github.com/abdullahnooristani332)

## License

No license has currently been specified.

Unless a license is added to this repository, the project should not be assumed to be freely reusable, modified, or redistributed.

For reuse and distribution, the project owner should add an appropriate open-source license.

---

**If you find this project useful, consider giving the repository a star on GitHub!**

⭐ Thank you for visiting Froshgah.