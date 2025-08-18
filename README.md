**Giglyte Backend API: A Modular Freelance Platform**

Welcome to the **Giglyte Backend API**! 🚀 This project powers a dynamic freelance marketplace, connecting clients with talented freelancers for various services. Built with a focus on modularity and a clear API interface, it provides the core functionalities for user management, job postings, proposals, orders, and secure transactions.

✨ **Key Highlights:**

*   **Robust User Management:** Secure registration, login, and role-based access for clients, freelancers, and administrators.
*   **Comprehensive Job Lifecycle:** From posting jobs to accepting proposals, submitting work, and reviewing deliveries.
*   **Integrated Messaging:** Facilitate communication between clients and freelancers on active jobs.
*   **Wallet & Transactions:** Manage user balances and track financial transactions within the platform.
*   **Admin Dashboard:** Tools for overseeing users, jobs, disputes, and system settings.
*   **API-Driven:** Designed for seamless integration with frontend applications.

---

## Technologies Used

| Technology         | Category           | Description                                                                    | Link                                                                        |
| :----------------- | :----------------- | :----------------------------------------------------------------------------- | :-------------------------------------------------------------------------- |
| PHP 8.3+           | Programming Language | Core backend logic and server-side scripting.                                  | [PHP.net](https://www.php.net/)                                             |
| PDO                | Database Interface | PHP Data Objects for secure and efficient database interactions.               | [PHP PDO](https://www.php.net/manual/en/book.pdo.php)                       |
| MySQL              | Database           | Relational database for storing all application data.                          | [MySQL.com](https://www.mysql.com/)                                         |
| Composer           | Package Manager    | Manages PHP dependencies, including JWT and Dotenv libraries.                  | [Composer](https://getcomposer.org/)                                        |
| Firebase/PHP-JWT   | Authentication     | Securely handles JSON Web Token (JWT) creation and verification for APIs.      | [PHP-JWT](https://github.com/firebase/php-jwt)                              |
| vlucas/phpdotenv   | Environment Mgmt.  | Loads environment variables from a `.env` file for secure configuration.       | [phpdotenv](https://github.com/vlucas/phpdotenv)                            |
| Tailwind CSS       | Frontend Framework | Utilized for the responsive and modern design of the administrative dashboard. | [Tailwind CSS](https://tailwindcss.com/)                                    |

---

## Getting Started

To get the Giglyte API backend up and running locally, follow these steps.

### Prerequisites

*   PHP 8.3 or higher installed.
*   Composer installed globally.
*   MySQL server running (or a compatible database).
*   A web server (e.g., Apache, Nginx, or PHP's built-in server) configured to serve the project root.

### Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd giglyte-backend # Or your project root directory
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Database Setup:**
    *   Create a MySQL database named `giglyte`.
    *   Configure your `db_connect.php` file with your database credentials. For development, you can use the provided example:
        ```php
        // db_connect.php (local development settings)
        $host = '0.0.0.0'; // Or 'localhost'
        $dbname = 'giglyte';
        $username = 'root';
        $password = 'root';
        ```
    *   Run the database schema creation and seeding scripts:
        ```bash
        php db_connect.php
        php query.php
        php admin/seed.php
        ```
        These scripts will create all necessary tables and populate them with sample data, including an admin user (`username: admin`, `password: Admin123!`).

### Environment Variables

The project uses environment variables for sensitive data like database credentials and JWT secret. Create a `.env` file in the project root with the following variables:

```dotenv
# .env Example
DB_HOST=0.0.0.0
DB_NAME=giglyte
DB_USER=root
DB_PASS=root
JWT_SECRET=your_super_secret_jwt_key_here_please_change_this_in_production
```
*Make sure to replace `your_super_secret_jwt_key_here_please_change_this_in_production` with a strong, unique key.*

### Running the API

You can use PHP's built-in web server for local development:

```bash
php -S 0.0.0.0:8000
```
This will start the server on `http://0.0.0.0:8000`. You can then access the API endpoints relative to this base URL.

---

# Giglyte Platform API

## Overview
The Giglyte Platform API serves as the central communication hub for the freelance marketplace, handling all data operations and user interactions. It's built on a plain PHP backend, leveraging PDO for database management and Firebase JWT for secure, stateless authentication, ensuring a high degree of modularity and scalability.

## Features
- `PHP`: Core scripting language for backend logic and API endpoint handling.
- `PDO`: Provides a robust and secure interface for interacting with the MySQL database.
- `MySQL`: The relational database used for storing all application data, including users, jobs, proposals, and transactions.
- `Firebase/PHP-JWT`: Implements JSON Web Tokens for authentication, enabling secure and scalable API access.
- `vlucas/phpdotenv`: Manages environment variables, ensuring sensitive configuration data is kept out of the codebase.
- `Modular Design`: API endpoints are logically organized into modules (auth, jobs, wallets) for clear separation of concerns and easier maintenance.

## Getting Started
### Installation
<!-- User opted out of Installation Instructions -->

### Environment Variables
All required environment variables must be defined in a `.env` file in the project root.

| Variable        | Description                       | Example Value                                  |
| :-------------- | :-------------------------------- | :--------------------------------------------- |
| `DB_HOST`       | Database host address             | `0.0.0.0` or `localhost`                       |
| `DB_NAME`       | Database name                     | `giglyte`                                      |
| `DB_USER`       | Database username                 | `root`                                         |
| `DB_PASS`       | Database password                 | `root`                                         |
| `JWT_SECRET`    | Secret key for JWT signing        | `your_super_secret_jwt_key_here`               |

## API Documentation
### Base URL
The root of your deployed application (e.g., `http://localhost:8000/`)

### Endpoints

#### `POST /signup.php?step=one`
Registers a new user (step 1: email and password).
**Request**:
```json
{
  "email": "user@example.com",
  "password": "StrongPassword123"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Step 1 complete, verify email",
  "data": {
    "token": 123456 // Verification token (for demo purposes)
  }
}
```
**Errors**:
- `400`: Invalid email format, Password too short.
- `409`: Email already registered.
- `405`: Method not allowed (if not POST).
- `500`: Server error, Database error.

#### `POST /signup.php?step=two`
Verifies user's email with a code.
**Request**:
```json
{
  "code": "123456" // Verification code from step one
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Email verified, proceed to next step"
}
```
**Errors**:
- `400`: Code required.
- `404`: Invalid or expired token.
- `405`: Method not allowed (if not POST).
- `500`: Server error, Database error.

#### `POST /signup.php?step=three`
Completes user registration with profile details.
**Request**:
```json
{
  "code": "123456", // Verification code
  "email": "user@example.com", // Email from step one
  "full_name": "Jane Doe",
  "username": "janedoe",
  "role": "client", // or "freelancer"
  "phone": "08012345678",
  "country": "Nigeria",
  "city": "Lagos",
  "bio": "I am a new client."
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Signup complete, you can login now"
}
```
**Errors**:
- `400`: Verification code required, Email required, Missing required fields (full_name, username, role), Invalid or expired verification code.
- `409`: Username already taken.
- `405`: Method not allowed (if not POST).
- `500`: Server error, Database error.

#### `POST /login.php`
Authenticates a user and returns a JWT.
**Request**:
```json
{
  "email": "user@example.com",
  "password": "StrongPassword123"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "username": "janedoe",
      "email": "user@example.com",
      "role": "client"
    }
  }
}
```
**Errors**:
- `400`: Invalid email format, Password is required.
- `401`: Invalid email or password.
- `405`: Method not allowed (if not POST).

#### `POST /jobs/create.php`
Creates a new job posting (Client only). Requires `Authorization: Bearer <token>`.
**Request**:
```json
{
  "title": "Need a React Native Developer",
  "description": "Develop a cross-platform mobile app for e-commerce.",
  "budget": 500.00,
  "skills": "React Native, Node.js, API Integration"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Job created successfully",
  "data": {
    "job_id": 123,
    "skills": ["React Native", "Node.js", "API Integration"]
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Only Users (Clients) can create jobs.
- `400`: Title and description are required, Invalid budget.
- `405`: Method not allowed (if not POST).
- `500`: Database error.

#### `GET /jobs/list.php`
Lists available open jobs (Freelancer only). Requires `Authorization: Bearer <token>`.
**Request**:
```
GET /jobs/list.php?search=developer&min_budget=100&max_budget=1000&skill=PHP
```
**Response**:
```json
{
  "status": "success",
  "message": "Jobs fetched successfully",
  "data": {
    "jobs": [
      {
        "id": 1,
        "title": "Build a simple PHP API",
        "description": "...",
        "budget": "200.00",
        "status": "open",
        "created_at": "2023-10-27 10:00:00",
        "client_name": "John Doe",
        "skills": ["PHP", "MySQL", "REST API"]
      }
    ]
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Only freelancers can view jobs.
- `500`: Database error.

#### `POST /jobs/apply.php`
Submits a proposal for a job (Freelancer only). Requires `Authorization: Bearer <token>`.
**Request**:
```json
{
  "job_id": 1,
  "cover_letter": "I am an experienced PHP developer...",
  "proposed_amount": 180.00,
  "estimated_days": 5
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Proposal submitted successfully"
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Only freelancers can apply to jobs.
- `400`: All fields are required.
- `404`: Job not found or not open for proposals.
- `409`: You already applied to this job.
- `405`: Method not allowed (if not POST).

#### `GET /jobs/view_proposals.php`
Views proposals for a specific job (Client only). Requires `Authorization: Bearer <token>`.
**Request**:
```
GET /jobs/view_proposals.php?job_id=1
```
**Response**:
```json
{
  "status": "success",
  "message": "Proposals fetched successfully",
  "data": {
    "proposals": [
      {
        "proposal_id": 1,
        "cover_letter": "...",
        "proposed_amount": "180.00",
        "estimated_days": 5,
        "status": "pending",
        "freelancer_id": 2,
        "username": "dev_expert",
        "full_name": "Dev Expert",
        "rating": "4.50",
        "profile_image": null
      }
    ]
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Only clients can view proposals / Not authorized to view proposals for this job.
- `400`: Job ID required.

#### `POST /jobs/accept_proposal.php`
Accepts a proposal and creates an order (Client only). Requires `Authorization: Bearer <token>`.
**Request**:
```json
{
  "proposal_id": 1
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Proposal accepted and order created",
  "data": {
    "order_id": 1
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Not authorized to accept this proposal.
- `400`: Proposal ID required, Proposal already accepted.
- `404`: Proposal not found.
- `500`: Could not create order.

#### `POST /jobs/manage.php?action=edit`
Edits a job posting (Client only). Requires `Authorization: Bearer <token>`.
**Request**:
```json
{
  "job_id": 1,
  "title": "Updated Job Title",
  "description": "New description for the job.",
  "budget": 550.00,
  "skills": "PHP, MySQL, React"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Job updated successfully"
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Only clients can manage jobs.
- `400`: Title and description are required, Invalid action.
- `404`: Job not found or not owned by you.
- `405`: Method not allowed (if not POST).
- `500`: Database error.

#### `POST /jobs/manage.php?action=delete`
Deletes a job posting (Client only). Requires `Authorization: Bearer <token>`.
**Request**:
```json
{
  "job_id": 1
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Job deleted successfully"
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Only clients can manage jobs.
- `400`: Invalid action.
- `404`: Job not found or not owned by you.
- `405`: Method not allowed (if not POST).
- `500`: Database error.

#### `GET /jobs/get_orders.php`
Retrieves a list of orders for the logged-in user (Client or Freelancer). Requires `Authorization: Bearer <token>`.
**Request**:
```
GET /jobs/get_orders.php
```
**Response**:
```json
{
  "status": "success",
  "message": "Orders fetched successfully",
  "data": {
    "orders": [
      {
        "order_id": 1,
        "status": "in_progress",
        "created_at": "2023-10-28 09:00:00",
        "updated_at": "2023-10-28 09:00:00",
        "job_title": "Build simple API",
        "freelancer_username": "dev_expert" // or "client_username" for freelancer
      }
    ]
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: User not found.
- `500`: Database error.

#### `POST /jobs/submit_work.php`
Freelancer submits completed work for an order. Requires `Authorization: Bearer <token>`.
**Request**:
(Multipart/form-data for file upload)
```
order_id: 1
delivery_message: "Here is the final API documentation and code."
delivery_file: (file upload field)
```
**Response**:
```json
{
  "status": "success",
  "message": "Work submitted successfully",
  "data": {
    "order_id": 1,
    "message": "Here is the final API documentation and code.",
    "file": "uploads/deliveries/delivery_12345.zip"
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Not your order.
- `400`: Order ID and delivery message are required, Order not in progress.
- `404`: Order not found.

#### `POST /jobs/review_delivery.php`
Client reviews a submitted delivery. Requires `Authorization: Bearer <token>`.
**Request**:
```json
{
  "order_id": 1,
  "action": "accept", // or "request_changes"
  "feedback": "Great work! Looks perfect." // Optional for accept, required for request_changes
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Delivery review updated successfully",
  "data": {
    "order_id": 1,
    "new_status": "completed",
    "feedback": "Great work! Looks perfect."
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Not your order.
- `400`: Order ID and valid action are required, Order is not in delivered state.
- `404`: Order not found.

#### `POST /jobs/update_order_status.php`
Updates the status of an order. Requires `Authorization: Bearer <token>`.
**Request**:
```json
{
  "order_id": 1,
  "status": "completed" // Can be "in_progress", "completed", "cancelled"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Order status updated",
  "data": {
    "order_id": 1,
    "status": "completed"
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Not authorized to update this order, Only client can cancel order, Only freelancer can mark completed.
- `400`: Invalid order ID or status.
- `404`: Order not found.

#### `GET /jobs/get_messages.php`
Retrieves messages for a specific job. Requires `Authorization: Bearer <token>`.
**Request**:
```
GET /jobs/get_messages.php?job_id=1
```
**Response**:
```json
{
  "status": "success",
  "message": "Messages fetched & marked as read",
  "data": {
    "messages": [
      {
        "id": 1,
        "sender_id": 1,
        "receiver_id": 2,
        "message": "Hello, how is the project going?",
        "is_read": 1,
        "created_at": "2023-10-28 10:00:00",
        "sender_name": "John Doe",
        "receiver_name": "dev_expert"
      }
    ]
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Not authorized to view messages.
- `400`: Job ID required.
- `404`: Job not found.

#### `POST /jobs/send_message.php`
Sends a message related to a job. Requires `Authorization: Bearer <token>`.
**Request**:
```json
{
  "job_id": 1,
  "receiver_id": 2,
  "message": "Could you please provide an update?"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Message sent successfully",
  "data": {
    "message_id": 2
  }
}
```
**Errors**:
- `401`: Unauthorized.
- `403`: Not authorized to send message on this job.
- `400`: All fields are required.
- `404`: Job not found.

#### `GET /wallets/wallet_balance.php`
Retrieves the logged-in user's wallet balance and transaction history. Requires `Authorization: Bearer <token>`.
**Request**:
```
GET /wallets/wallet_balance.php
```
**Response**:
```json
{
  "status": "success",
  "message": "Wallet fetched successfully",
  "data": {
    "balance": "150.75",
    "transactions": [
      {
        "type": "credit",
        "amount": "200.00",
        "description": "Payment for Job #1",
        "created_at": "2023-10-28 11:00:00"
      },
      {
        "type": "debit",
        "amount": "50.00",
        "description": "Withdrawal to bank",
        "created_at": "2023-10-28 11:30:00"
      }
    ]
  }
}
```
**Errors**:
- `401`: Unauthorized.

---

## Usage Examples

Here are some `curl` examples demonstrating how to interact with the Giglyte API. Replace `http://localhost:8000` with your actual base URL.

### 1. User Registration (Step 1)

```bash
curl -X POST \
  http://localhost:8000/signup.php?step=one \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'email=newuser@example.com&password=SecurePassword123'
```

### 2. User Registration (Step 3 - assuming email is verified from step 2)

```bash
curl -X POST \
  http://localhost:8000/signup.php?step=three \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'code=123456&email=newuser@example.com&full_name=New User&username=newuser&role=client&phone=09012345678&country=USA&city=New York&bio=A new client looking for talent.'
```

### 3. User Login

```bash
curl -X POST \
  http://localhost:8000/login.php \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'email=admin@jobpost.com&password=Admin123!'
# Extract the 'token' from the response for subsequent authenticated requests
```

### 4. Create a New Job (as a Client)

First, login as a client to get a JWT token. Then use that token in the `Authorization` header.

```bash
CLIENT_TOKEN="YOUR_CLIENT_JWT_TOKEN_HERE" # Replace with actual token

curl -X POST \
  http://localhost:8000/jobs/create.php \
  -H "Authorization: Bearer ${CLIENT_TOKEN}" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'title=Frontend Developer for E-commerce&description=Looking for an experienced frontend developer to build a modern e-commerce interface.&budget=1200.00&skills=React,JavaScript,CSS'
```

### 5. List All Open Jobs (as a Freelancer)

First, login as a freelancer to get a JWT token.

```bash
FREELANCER_TOKEN="YOUR_FREELANCER_JWT_TOKEN_HERE" # Replace with actual token

curl -X GET \
  'http://localhost:8000/jobs/list.php?search=developer&min_budget=500' \
  -H "Authorization: Bearer ${FREELANCER_TOKEN}"
```

### 6. Get Wallet Balance

```bash
ANY_USER_TOKEN="YOUR_USER_JWT_TOKEN_HERE" # Replace with actual token

curl -X GET \
  http://localhost:8000/wallets/wallet_balance.php \
  -H "Authorization: Bearer ${ANY_USER_TOKEN}"
```

---

*No screenshots available for this project.*

---

## Author

**Your Name**

*   Twitter: [@your_twitter_handle](https://twitter.com/your_twitter_handle) (replace with your handle)
*   LinkedIn: [your-linkedin-profile](https://linkedin.com/in/your-linkedin-profile) (replace with your profile URL)

---

[![PHP](https://img.shields.io/badge/PHP-8.3+-8892BF.svg?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1.svg?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Composer](https://img.shields.io/badge/Composer-88563A.svg?logo=composer&logoColor=white)](https://getcomposer.org/)
[![JWT](https://img.shields.io/badge/JWT-000000.svg?logo=json-web-tokens&logoColor=white)](https://jwt.io/)

[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)