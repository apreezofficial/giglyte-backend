# GigLyte Freelance Platform API 🛠️

## Overview
GigLyte is a robust backend API for a freelance platform, enabling seamless interaction between clients and freelancers. Built with PHP, it leverages native PDO for MySQL database interactions, Firebase JWT for secure authentication, and a modular architecture for managing jobs, proposals, orders, messaging, and user wallets.

## Features
-   **User Management**: Secure multi-step user registration (client/freelancer) and authentication with JWT.
-   **Job Management**: Clients can create, list, view, edit, and delete job postings with specific requirements and budgets.
-   **Proposal System**: Freelancers can browse open jobs, submit detailed proposals, and clients can review and accept proposals.
-   **Order Fulfillment**: Facilitates job progression from proposal acceptance to work submission, delivery review, and order completion.
-   **Real-time Messaging**: Integrated messaging system for direct communication between clients and freelancers on specific job orders.
-   **Wallet System**: Manages user balances and tracks transaction history.
-   **Data Persistence**: Structured MySQL database schema for all platform entities.

## Technologies Used

| Category        | Technology                                                                                                                                              | Purpose                                        |
| :-------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------ | :--------------------------------------------- |
| **Backend**     | [PHP 8.3+](https://www.php.net/)                                                                                                                        | Core scripting language                        |
| **Database**    | [MySQL](https://www.mysql.com/)                                                                                                                         | Relational database for all platform data      |
| **DB Access**   | [PDO (PHP Data Objects)](https://www.php.net/manual/en/book.pdo.php)                                                                                    | Database abstraction layer                     |
| **Auth**        | [Firebase/PHP-JWT](https://github.com/firebase/php-jwt)                                                                                                 | JSON Web Token implementation for authentication |
| **Environment** | [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)                                                                                                 | Loading environment variables from `.env` file |

## Getting Started

### Environment Variables
To run this project, you will need to configure the following environment variables:

| Variable      | Example Value                                  | Description                                                                                                                                                                         |
| :------------ | :--------------------------------------------- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `JWT_SECRET`  | `your_super_secret_jwt_key_here_12345`         | A strong, random key used for signing and verifying JSON Web Tokens. **CRITICAL for security; change from default.**                                                                |
| `DB_HOST`     | `localhost` or `127.0.0.1`                     | Database host. (Currently hardcoded in `db_connect.php`, but recommended for .env)                                                                                                 |
| `DB_NAME`     | `giglyte`                                      | Database name. (Currently hardcoded in `db_connect.php`, but recommended for .env)                                                                                                 |
| `DB_USER`     | `root`                                         | Database username. (Currently hardcoded in `db_connect.php`, but recommended for .env)                                                                                             |
| `DB_PASSWORD` | `your_db_password` or `root` (for local dev)   | Database password. (Currently hardcoded in `db_connect.php`, but recommended for .env)                                                                                             |

Create a `.env` file in the project root directory and populate it with these variables.

```env
JWT_SECRET="your_super_secret_jwt_key_here_12345"
DB_HOST="localhost"
DB_NAME="giglyte"
DB_USER="root"
DB_PASSWORD="your_db_password"
```

### Database Setup
The `query.php` script is designed to initialize the entire database schema.
**Note:** Ensure your MySQL server is running and the database user has sufficient privileges to create tables.

1.  **Create Database:** Manually create an empty database named `giglyte` (or whatever you configure in `db_connect.php`).
    ```sql
    CREATE DATABASE giglyte;
    USE giglyte;
    ```
2.  **Run Schema Initialization:** Access `query.php` via your web server (e.g., `http://localhost/path/to/query.php`). This will create all necessary tables.

## API Documentation

### Base URL
The API endpoints are directly accessible PHP files within the project's web root. For example, if your project is served from `http://localhost/giglyte/`, then the login endpoint would be `http://localhost/giglyte/login.php`.

### Endpoints

#### `POST /login.php`
Authenticates a user and returns a JWT token for subsequent API calls.
**Request**:
```json
{
  "email": "user@example.com",
  "password": "securepassword123"
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
      "username": "johndoe",
      "email": "john.doe@example.com",
      "role": "client"
    }
  }
}
```
**Errors**:
-   `405 Method Not Allowed`: If not a POST request.
-   `400 Bad Request`: Invalid email format or missing password.
-   `401 Unauthorized`: Invalid email or password.

#### `POST /signup.php?step=one`
Initiates user registration by validating email and password, creating a temporary user, and returning a verification token.
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
  "data": {
    "token": "123456"
  }
}
```
**Errors**:
-   `405 Method Not Allowed`: If not a POST request.
-   `400 Bad Request`: Invalid email format or password too short (min 6 characters).
-   `409 Conflict`: Email already registered.

#### `POST /signup.php?step=two`
Verifies the email using the token received in step one.
**Request**:
```json
{
  "code": "123456"
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
-   `400 Bad Request`: Missing verification code.
-   `404 Not Found`: Invalid or expired token.

#### `POST /signup.php?step=three`
Completes user registration with personal details and assigns a role. Requires `temp_user_id` in session from `step=two`.
**Request**:
```json
{
  "full_name": "Jane Doe",
  "username": "janedoe",
  "role": "freelancer",
  "phone": "08012345678",
  "country": "Nigeria",
  "city": "Lagos",
  "bio": "Experienced PHP developer."
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
-   `405 Method Not Allowed`: If not a POST request.
-   `400 Bad Request`: Missing required fields (`full_name`, `username`, `role`).
-   `409 Conflict`: Username already taken.
-   `400 Bad Request`: No user session found (if `step=two` wasn't completed).
-   `404 Not Found`: Temporary user not found.
-   `500 Server Error`: Database error.

#### `POST /jobs/create.php`
Allows authenticated clients to create a new job posting.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
```json
{
  "title": "Build a Custom E-commerce Website",
  "description": "I need a full-stack developer to create a scalable e-commerce platform with payment gateway integration.",
  "budget": 5000.00,
  "skills": "PHP,Laravel,MySQL,Vue.js"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Job created successfully",
  "data": {
    "job_id": 101,
    "skills": ["PHP", "Laravel", "MySQL", "Vue.js"]
  }
}
```
**Errors**:
-   `401 Unauthorized`: Missing or invalid token.
-   `403 Forbidden`: User is not a client.
-   `405 Method Not Allowed`: If not a POST request.
-   `400 Bad Request`: Missing title or description, or invalid budget.

#### `GET /jobs/list.php`
Retrieves a list of open job postings. Can be filtered by keyword, budget range, and specific skills.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
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
        "title": "Backend API Developer",
        "description": "Develop a RESTful API for a new mobile application.",
        "budget": "800.00",
        "status": "open",
        "created_at": "2023-10-26 10:00:00",
        "client_name": "Alice Smith",
        "skills": ["PHP", "API", "MySQL"]
      }
    ]
  }
}
```
**Errors**:
-   `401 Unauthorized`: Missing or invalid token.
-   `403 Forbidden`: User is not authorized to view jobs (currently restricted to freelancers).

#### `POST /jobs/manage.php?action=edit`
Allows authenticated clients to edit their existing job postings.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
```json
{
  "job_id": 101,
  "title": "Updated E-commerce Project",
  "description": "Revised description for the e-commerce platform.",
  "budget": 5500.00,
  "skills": "PHP,MySQL,Vue.js,Stripe"
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
-   `401 Unauthorized`: Missing or invalid token.
-   `403 Forbidden`: User is not a client.
-   `405 Method Not Allowed`: If not a POST request.
-   `400 Bad Request`: Missing title or description.
-   `404 Not Found`: Job not found or not owned by the client.

#### `POST /jobs/manage.php?action=delete`
Allows authenticated clients to delete their job postings.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
```json
{
  "job_id": 101
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
-   `401 Unauthorized`: Missing or invalid token.
-   `403 Forbidden`: User is not a client.
-   `405 Method Not Allowed`: If not a POST request.
-   `404 Not Found`: Job not found or not owned by the client.

#### `POST /jobs/apply.php`
Allows authenticated freelancers to submit a proposal for an open job.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
```json
{
  "job_id": 1,
  "cover_letter": "I have extensive experience in this area...",
  "proposed_amount": 750.00,
  "estimated_days": 7
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
-   `401 Unauthorized`: Missing or invalid token.
-   `403 Forbidden`: User is not a freelancer.
-   `405 Method Not Allowed`: If not a POST request.
-   `400 Bad Request`: Missing or invalid `job_id`, `cover_letter`, `proposed_amount`, or `estimated_days`.
-   `404 Not Found`: Job not found or not open for proposals.
-   `409 Conflict`: Freelancer has already applied to this job.

#### `GET /jobs/view_proposals.php`
Allows authenticated clients to view all proposals submitted for a specific job they posted.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
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
        "cover_letter": "I can deliver this project in 5 days...",
        "proposed_amount": "900.00",
        "estimated_days": 5,
        "status": "pending",
        "freelancer_id": 2,
        "username": "janedoe",
        "full_name": "Jane Doe",
        "rating": "4.8",
        "profile_image": null
      }
    ]
  }
}
```
**Errors**:
-   `401 Unauthorized`: Missing or invalid token.
-   `403 Forbidden`: User is not a client or does not own the job.
-   `400 Bad Request`: Missing `job_id`.

#### `POST /jobs/accept_proposal.php`
Allows authenticated clients to accept a specific proposal for their job. This action also creates an order and marks the job as `in_progress`.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
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
-   `401 Unauthorized`: Missing or invalid token.
-   `400 Bad Request`: Missing or invalid `proposal_id`, or proposal already accepted.
-   `404 Not Found`: Proposal not found.
-   `403 Forbidden`: Not authorized to accept this proposal (not the job's client).
-   `500 Server Error`: Database error during order creation.

#### `GET /jobs/get_orders.php`
Retrieves a list of orders associated with the authenticated user (either as a client or a freelancer).
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
```
GET /jobs/get_orders.php
```
**Response (as Client)**:
```json
{
  "status": "success",
  "message": "Orders fetched successfully",
  "data": {
    "orders": [
      {
        "order_id": 1,
        "status": "in_progress",
        "created_at": "2023-10-26 11:00:00",
        "updated_at": "2023-10-26 11:00:00",
        "job_title": "Backend API Developer",
        "freelancer_username": "janedoe",
        "freelancer_name": "Jane Doe"
      }
    ]
  }
}
```
**Response (as Freelancer)**:
```json
{
  "status": "success",
  "message": "Orders fetched successfully",
  "data": {
    "orders": [
      {
        "order_id": 1,
        "status": "in_progress",
        "created_at": "2023-10-26 11:00:00",
        "updated_at": "2023-10-26 11:00:00",
        "job_title": "Backend API Developer",
        "client_username": "johndoe",
        "client_name": "John Doe"
      }
    ]
  }
}
```
**Errors**:
-   `401 Unauthorized`: Missing or invalid token.
-   `403 Forbidden`: User not found.

#### `POST /jobs/submit_work.php`
Allows authenticated freelancers to submit completed work for an order.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
```json
{
  "order_id": 1,
  "delivery_message": "Final deliverables attached. Let me know if you need any revisions.",
  "delivery_file": "<file_upload>"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Work submitted successfully",
  "data": {
    "order_id": 1,
    "message": "Final deliverables attached. Let me know if you need any revisions.",
    "file": "uploads/deliveries/delivery_653a9b1c.pdf"
  }
}
```
**Errors**:
-   `401 Unauthorized`: Missing or invalid token.
-   `400 Bad Request`: Missing or invalid `order_id` or `delivery_message`. Order not in `in_progress` status.
-   `404 Not Found`: Order not found.
-   `403 Forbidden`: Not authorized to submit work for this order (not the freelancer).

#### `POST /jobs/review_delivery.php`
Allows authenticated clients to review delivered work and either accept it or request changes.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
```json
{
  "order_id": 1,
  "action": "accept",
  "feedback": "Excellent work! Highly satisfied."
}
```
OR for requesting changes:
```json
{
  "order_id": 1,
  "action": "request_changes",
  "feedback": "Please adjust the styling on the homepage."
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
    "feedback": "Excellent work! Highly satisfied."
  }
}
```
**Errors**:
-   `401 Unauthorized`: Missing or invalid token.
-   `400 Bad Request`: Missing or invalid `order_id` or `action`. Order not in `delivered` state.
-   `404 Not Found`: Order not found.
-   `403 Forbidden`: Not authorized to review this order (not the client).

#### `POST /jobs/update_order_status.php`
Allows participants (client or freelancer) to update the status of an order. Specific role-based rules apply.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
```json
{
  "order_id": 1,
  "status": "completed"
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
-   `401 Unauthorized`: Missing or invalid token.
-   `400 Bad Request`: Invalid `order_id` or `status`.
-   `404 Not Found`: Order not found.
-   `403 Forbidden`: Not authorized to update this order (not a participant) or specific role-based restrictions (e.g., only client can cancel, only freelancer can mark completed).

#### `POST /jobs/send_message.php`
Sends a message between job participants (client and freelancer) for a specific job.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
```json
{
  "job_id": 1,
  "receiver_id": 2,
  "message": "Can we discuss the project details tomorrow?"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Message sent successfully",
  "data": {
    "message_id": 1
  }
}
```
**Errors**:
-   `401 Unauthorized`: Missing or invalid token.
-   `400 Bad Request`: Missing `job_id`, `receiver_id`, or `message`.
-   `404 Not Found`: Job not found.
-   `403 Forbidden`: Sender or receiver are not participants in the specified job.

#### `GET /jobs/get_messages.php`
Retrieves all messages for a specific job, marking messages sent to the authenticated user as read.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
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
        "message": "Hello, when can we start?",
        "is_read": 1,
        "created_at": "2023-10-26 12:00:00",
        "sender_name": "John Doe",
        "receiver_name": "Jane Doe"
      }
    ]
  }
}
```
**Errors**:
-   `401 Unauthorized`: Missing or invalid token.
-   `400 Bad Request`: Missing `job_id`.
-   `404 Not Found`: Job not found.
-   `403 Forbidden`: Not authorized to view messages (not a job participant).

#### `GET /wallets/wallet_balance.php`
Retrieves the authenticated user's wallet balance and transaction history.
**Request**:
**Headers**: `Authorization: Bearer <JWT_TOKEN>`
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
        "created_at": "2023-10-25 09:30:00"
      },
      {
        "type": "debit",
        "amount": "50.00",
        "description": "Withdrawal",
        "created_at": "2023-10-24 14:15:00"
      }
    ]
  }
}
```
**Errors**:
-   `401 Unauthorized`: Missing or invalid token.

## License
This project is not currently covered by an open-source license. Please contact the author for licensing inquiries.

## Author
**[Your Name]** 👨‍💻
- **LinkedIn**: [Your LinkedIn Profile]
- **Portfolio**: [Your Personal Portfolio/Website]
- **Email**: [Your Email Address]

---
[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)