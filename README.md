# GigLyte Backend API 🌐

## Overview
GigLyte is a robust backend API built with PHP and PDO for seamless interaction with a MySQL database. It powers the core functionalities of a freelance marketplace, including comprehensive user management and a structured database schema.

## Features
- **User Authentication Flow**: Implements a secure multi-step signup process with email verification and strong password hashing.
- **Dynamic Database Schema Generation**: Automatically creates and manages essential tables for users, categories, gigs, orders, messages, reviews, payments, and temporary users upon initialization.
- **Secure Password Handling**: Utilizes `PASSWORD_BCRYPT` for robust password hashing, ensuring user data security.
- **Database Abstraction with PDO**: Leverages PHP Data Objects (PDO) for secure and efficient database interactions, mitigating SQL injection risks.
- **Modular Design**: Separates database connection logic, schema definition, and API endpoint handling for improved maintainability.
- **JSON API Responses**: Provides clear and consistent JSON responses for all API interactions, including detailed success and error messages.

## Getting Started

### Environment Variables
Before running the application, configure your database connection settings. For a production environment, these should be loaded as actual environment variables. For local development, you can modify the `db_connect.php` file directly:

| Variable      | Description                  | Example Value   |
| :------------ | :--------------------------- | :-------------- |
| `DB_HOST`     | The database host address    | `localhost`     |
| `DB_NAME`     | The name of the database     | `giglyte`       |
| `DB_USER`     | The username for database access | `root`          |
| `DB_PASSWORD` | The password for the database user (can be empty) | `""`            |

## Usage
To initialize the database schema, simply execute the `query.php` script once. This script will create all necessary tables within your configured `giglyte` database.

```bash
# Example: Running query.php via command line (if PHP is configured for CLI)
php query.php

# Alternatively, access it via a web browser once your web server is running:
# http://localhost/path/to/giglyte/query.php
```
After successful execution, the `giglyte` database will be populated with tables such as `users`, `categories`, `gigs`, `orders`, `messages`, `reviews`, `payments`, `gig_images`, and `temp_users`.

The `signup.php` file serves as the primary API endpoint for managing the user registration process, which is divided into three distinct steps.

## API Documentation
### Base URL
The base URL for the `signup.php` endpoint is the direct path to the file on your web server.
Example: `http://localhost/giglyte/signup.php` (adjust `/giglyte/` based on your project's directory structure).

### Endpoints

#### POST /signup.php?step=one
**Description**: Initiates the user registration process. It validates the provided email and password, creates a temporary user record, and generates a verification token.
**Request**:
```json
{
  "email": "newuser@example.com",
  "password": "StrongPassword123"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Step 1 complete, verify email",
  "token": "a1b2c3d4e5f67890a1b2c3d4e5f67890"
}
```
**Errors**:
- `405 Method Not Allowed`: If the request method is not POST.
- `400 Bad Request`: If the email format is invalid or the password is too short (minimum 6 characters).
- `409 Conflict`: If the provided email address is already registered in the permanent `users` table.

#### GET /signup.php?step=two
**Description**: Verifies the user's email address using the token received in Step One. If the token is valid, the temporary user's ID is stored in the session for the next step.
**Request**:
No request body. The verification token must be passed as a query parameter.
```
GET /signup.php?step=two&token=a1b2c3d4e5f67890a1b2c3d4e5f67890
```
**Response**:
```json
{
  "status": "success",
  "message": "Email verified"
}
```
**Errors**:
- `400 Bad Request`: If the `token` query parameter is missing.
- `404 Not Found`: If the provided token is invalid or has expired.

#### POST /signup.php?step=three
**Description**: Completes the user registration. This step requires a valid session from Step Two and takes the user's full name, username, and role. The temporary user data is then moved to the permanent `users` table.
**Request**:
```json
{
  "full_name": "Alice Wonderland",
  "username": "alicew",
  "role": "client" 
}
```
**Note**: The `role` can be `'freelancer'` or `'client'`. Any other value provided will default to `'client'`.
**Response**:
```json
{
  "status": "success",
  "message": "Signup complete, you can login now"
}
```
**Errors**:
- `400 Bad Request`: If no valid user session is found, indicating that Step Two was not successfully completed.
- `405 Method Not Allowed`: If the request method is not POST.
- `404 Not Found`: If the temporary user record corresponding to the session ID is not found.

## Technologies Used

| Technology    | Description                                   | Link                                        |
| :------------ | :-------------------------------------------- | :------------------------------------------ |
| **PHP 🐘**    | Server-side scripting language for web development | [php.net](https://www.php.net/)             |
| **MySQL**     | Open-source relational database management system | [mysql.com](https://www.mysql.com/)         |
| **PDO**       | PHP Data Objects, a consistent interface for accessing databases | [php.net/manual/en/book.pdo.php](https://www.php.net/manual/en/book.pdo.php) |
| **JSON**      | Standard data interchange format for web APIs | [json.org](https://www.json.org/)           |
| **BCRYPT**    | Cryptographic hashing function for password security | [en.wikipedia.org/wiki/Bcrypt](https://en.wikipedia.org/wiki/Bcrypt) |

## Author
**[Your Name]**
Connect with me:
- **LinkedIn**: [Your LinkedIn Profile](https://linkedin.com/in/yourusername)
- **Twitter**: [Your Twitter Profile](https://twitter.com/yourusername)
- **Portfolio**: [Your Personal Website](https://yourwebsite.com)

---

[![PHP](https://img.shields.io/badge/PHP-8.x-blue?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Database](https://img.shields.io/badge/Database-PDO-brightgreen?style=flat)](https://www.php.net/manual/en/book.pdo.php)
[![API](https://img.shields.io/badge/API-RESTful%20Principles-informational?style=flat)](https://restfulapi.net/)
[![License: Unlicensed](https://img.shields.io/badge/License-Unlicensed-lightgrey.svg)](https://unlicense.org/)

[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)