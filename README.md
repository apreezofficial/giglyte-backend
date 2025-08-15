# Giglyte API

## Overview
Giglyte is a robust backend API service designed to power a freelance marketplace, connecting clients with freelancers for various job opportunities. Built with PHP and leveraging modern design patterns inspired by frameworks like Laravel, it features a comprehensive suite of functionalities including user authentication, job and proposal management, order processing, real-time messaging, and an integrated wallet system.

## Features
- **User Authentication**: Secure multi-step registration, login, and JWT-based session management.
- **Job Management**: Clients can create, list, edit, and delete job postings. Jobs are approved by administrators.
- **Proposal System**: Freelancers can submit proposals for open jobs, including cover letters and proposed amounts.
- **Order Management**: Clients can accept proposals, initiating orders. Freelancers can submit work, and clients can review and approve deliveries.
- **Real-time Messaging**: Facilitates direct communication between clients and freelancers within job contexts.
- **Wallet System**: Provides user-specific financial dashboards, displaying balances and transaction history.
- **Admin Panel**: A dedicated administrative interface for managing users, jobs, orders, and disputes.
- **Database Management**: Automated table creation and initial admin user seeding.

## Getting Started

### Installation
**Note:** Per user request, detailed installation instructions have been omitted. Please refer to project-specific setup guides or contact the project maintainers for assistance.

### Environment Variables
This project utilizes environment variables for sensitive configurations. Please create a `.env` file in the project root and populate it with the following:

| Variable      | Example Value           | Description                              |
| :------------ | :---------------------- | :--------------------------------------- |
| `JWT_SECRET`  | `your_super_secret_key` | Secret key for JWT token generation.     |
| `DB_HOST`     | `localhost`             | Database host.                           |
| `DB_NAME`     | `giglytec_main`         | Database name.                           |
| `DB_USERNAME` | `giglytec_main`         | Database username.                       |
| `DB_PASSWORD` | `Database@giglyte.co`   | Database password.                       |

**Note**: The database credentials are currently hardcoded in `db_connect.php`. For production environments, it is highly recommended to externalize these into the `.env` file and load them using `getenv()` for enhanced security.

## API Documentation

### Base URL
All API requests should be prefixed with `/api/v1/`. For example, `http://localhost:8080/api/v1/auth/login`.

### Endpoints

#### POST /auth/login
**Description**: Authenticates a user and returns a JWT token for subsequent requests.

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field      | Type   | Required | Description                               |
| :--------- | :----- | :------- | :---------------------------------------- |
| `email`    | string | true     | User's email address.                     |
| `password` | string | true     | User's password.                          |

**Response**:
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 123,
      "username": "johndoe",
      "email": "john.doe@example.com",
      "role": "client"
    }
  }
}
```

**Errors**:
- `400 Bad Request`: Invalid email format or password missing.
- `401 Unauthorized`: Invalid email or password.
- `405 Method Not Allowed`: Only POST requests are accepted.

#### POST /auth/register?step=one
**Description**: Initiates the user registration process by validating email and password.

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field      | Type   | Required | Description                     |
| :--------- | :----- | :------- | :------------------------------ |
| `email`    | string | true     | User's email address.           |
| `password` | string | true     | User's password (min 6 chars).  |

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
- `400 Bad Request`: Invalid email format or password too short.
- `405 Method Not Allowed`: Only POST requests are accepted.
- `409 Conflict`: Email already registered.

#### POST /auth/register?step=two
**Description**: Verifies the email using a provided token (from step one).

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field  | Type   | Required | Description                 |
| :----- | :----- | :------- | :-------------------------- |
| `code` | string | true     | Verification code from email. |

**Response**:
```json
{
  "status": "success",
  "message": "Email verified, proceed to next step"
}
```

**Errors**:
- `400 Bad Request`: Code required.
- `404 Not Found`: Invalid or expired token.
- `405 Method Not Allowed`: Only POST requests are accepted.

#### POST /auth/register?step=three
**Description**: Completes the user registration by collecting personal details and assigning a role.

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field             | Type   | Required | Description                                                    |
| :---------------- | :----- | :------- | :------------------------------------------------------------- |
| `code`            | string | true     | Verification code from email.                                  |
| `email`           | string | true     | User's email address (must match step one).                    |
| `full_name`       | string | true     | User's full name.                                              |
| `username`        | string | true     | Unique username.                                               |
| `role`            | string | true     | User role (`client` or `freelancer`).                          |
| `phone`           | string | false    | User's phone number.                                           |
| `country`         | string | false    | User's country.                                                |
| `city`            | string | false    | User's city.                                                   |
| `bio`             | string | false    | User's biography.                                              |

**Response**:
```json
{
  "status": "success",
  "message": "Signup complete, you can login now"
}
```

**Errors**:
- `400 Bad Request`: Missing required fields, invalid or expired verification code.
- `405 Method Not Allowed`: Only POST requests are accepted.
- `409 Conflict`: Username already taken.
- `500 Internal Server Error`: Server or database error during transaction.

#### POST /jobs/accept_proposal
**Description**: Allows a client to accept a freelancer's proposal for a job, initiating an order.

**Authentication**: JWT Token required.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field          | Type    | Required | Description                      |
| :------------- | :------ | :------- | :------------------------------- |
| `proposal_id`  | integer | true     | The ID of the proposal to accept. |

**Response**:
```json
{
  "status": "success",
  "message": "Proposal accepted and order created",
  "data": {
    "order_id": 456
  }
}
```

**Errors**:
- `400 Bad Request`: Proposal ID required, or proposal already accepted.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not the client who posted the job.
- `404 Not Found`: Proposal not found.
- `500 Internal Server Error`: Database error during order creation.

#### POST /jobs/apply
**Description**: Allows a freelancer to submit a proposal for an open job.

**Authentication**: JWT Token required. User must have `freelancer` role.
**Request**:
```
Content-Type: application/x-www-form-urlencoded` or `application/json
```
| Field            | Type    | Required | Description                                  |
| :--------------- | :------ | :------- | :------------------------------------------- |
| `job_id`         | integer | true     | The ID of the job to apply for.              |
| `cover_letter`   | string  | true     | A detailed letter explaining the freelancer's approach. |
| `proposed_amount`| float   | true     | The amount the freelancer proposes for the job. |
| `estimated_days` | integer | true     | Estimated days to complete the job.          |

**Response**:
```json
{
  "status": "success",
  "message": "Proposal submitted successfully"
}
```

**Errors**:
- `400 Bad Request`: All fields are required and must be valid.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not a freelancer.
- `404 Not Found`: Job not found or not open for proposals.
- `405 Method Not Allowed`: Only POST requests are accepted.
- `409 Conflict`: Freelancer has already applied to this job.

#### POST /jobs/create
**Description**: Allows a client to create a new job posting.

**Authentication**: JWT Token required. User must have `client` role.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field       | Type   | Required | Description                                    |
| :---------- | :----- | :------- | :--------------------------------------------- |
| `title`     | string | true     | Title of the job.                              |
| `description`| string | true     | Detailed description of the job requirements. |
| `budget`    | float  | true     | The budget allocated for the job.              |
| `skills`    | string | false    | Comma-separated list of required skills (e.g., "PHP, MySQL"). |

**Response**:
```json
{
  "status": "success",
  "message": "Job created successfully",
  "data": {
    "job_id": 789,
    "skills": ["PHP", "MySQL"]
  }
}
```

**Errors**:
- `400 Bad Request`: Title or description missing, or invalid budget.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not a client.
- `405 Method Not Allowed`: Only POST requests are accepted.
- `500 Internal Server Error`: Database error.

#### POST /jobs/create_proposals
**Description**: Allows a freelancer to create and submit a proposal for an open job.

**Authentication**: JWT Token required. User must have `freelancer` role.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field            | Type    | Required | Description                                  |
| :--------------- | :------ | :------- | :------------------------------------------- |
| `job_id`         | integer | true     | The ID of the job to create a proposal for.  |
| `cover_letter`   | string  | true     | A detailed letter explaining the freelancer's approach. |
| `proposed_amount`| float   | true     | The amount the freelancer proposes for the job. |
| `estimated_days` | integer | true     | Estimated days to complete the job.          |

**Response**:
```json
{
  "status": "success",
  "message": "Proposal submitted successfully"
}
```

**Errors**:
- `400 Bad Request`: All fields are required and must be valid, or job is not open for proposals.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not a freelancer.
- `404 Not Found`: Job not found.
- `405 Method Not Allowed`: Only POST requests are accepted.
- `409 Conflict`: Freelancer has already submitted a proposal for this job.

#### GET /jobs/get_messages
**Description**: Retrieves messages exchanged for a specific job and marks them as read for the current user.

**Authentication**: JWT Token required. User must be a participant (client or accepted freelancer) of the job.
**Request**:
```
GET /api/v1/jobs/get_messages?job_id={job_id}
```
| Parameter | Type    | Required | Description                                  |
| :-------- | :------ | :------- | :------------------------------------------- |
| `job_id`  | integer | true     | The ID of the job to retrieve messages for. |

**Response**:
```json
{
  "status": "success",
  "message": "Messages fetched & marked as read",
  "data": {
    "messages": [
      {
        "id": 1,
        "sender_id": 101,
        "receiver_id": 202,
        "message": "Hello, I'm interested in this job.",
        "is_read": 1,
        "created_at": "2023-10-26 10:00:00",
        "sender_name": "client_user",
        "receiver_name": "freelancer_user"
      }
    ]
  }
}
```

**Errors**:
- `400 Bad Request`: Job ID required.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not a participant of the job.
- `404 Not Found`: Job not found.

#### GET /jobs/get_orders
**Description**: Retrieves a list of orders associated with the logged-in user, whether as a client or a freelancer.

**Authentication**: JWT Token required.
**Request**:
```
GET /api/v1/jobs/get_orders
```

**Response**:
```json
{
  "status": "success",
  "message": "Orders fetched successfully",
  "data": {
    "orders": [
      {
        "order_id": 456,
        "status": "in_progress",
        "created_at": "2023-10-26 11:00:00",
        "updated_at": "2023-10-26 11:00:00",
        "job_title": "Build E-commerce Website",
        "freelancer_username": "dev_pro",
        "freelancer_name": "Jane Doe"
      }
    ]
  }
}
```

**Errors**:
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User not found.
- `500 Internal Server Error`: Database error.

#### GET /jobs/list
**Description**: Retrieves a list of open job postings. Currently accessible only by freelancers.

**Authentication**: JWT Token required. User must have `freelancer` role.
**Request**:
```
GET /api/v1/jobs/list?search={keyword}&min_budget={min}&max_budget={max}&skill={skill}
```
| Parameter    | Type    | Required | Description                                  |
| :----------- | :------ | :------- | :------------------------------------------- |
| `search`     | string  | false    | Keyword to search in job title or description. |
| `min_budget` | float   | false    | Minimum budget for jobs.                     |
| `max_budget` | float   | false    | Maximum budget for jobs.                     |
| `skill`      | string  | false    | Filter jobs by a specific skill.             |

**Response**:
```json
{
  "status": "success",
  "message": "Jobs fetched successfully",
  "data": {
    "jobs": [
      {
        "id": 1,
        "title": "Develop Mobile App",
        "description": "Need a mobile app developer for iOS and Android.",
        "budget": "500.00",
        "status": "open",
        "created_at": "2023-10-25 09:00:00",
        "client_name": "ClientXYZ",
        "skills": ["Mobile App Development", "iOS", "Android"]
      }
    ]
  }
}
```

**Errors**:
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not a freelancer.
- `500 Internal Server Error`: Database error.

#### POST /jobs/manage?action=edit
**Description**: Allows a client to edit details of their job posting.

**Authentication**: JWT Token required. User must have `client` role and own the job.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field       | Type    | Required | Description                                    |
| :---------- | :------ | :------- | :--------------------------------------------- |
| `job_id`    | integer | true     | The ID of the job to edit.                     |
| `title`     | string  | true     | New title for the job.                         |
| `description`| string  | true     | New detailed description for the job.         |
| `budget`    | float   | true     | New budget for the job.                        |
| `skills`    | string  | false    | Comma-separated list of updated skills.       |

**Response**:
```json
{
  "status": "success",
  "message": "Job updated successfully"
}
```

**Errors**:
- `400 Bad Request`: Title or description missing.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not a client.
- `404 Not Found`: Job not found or not owned by the user.
- `405 Method Not Allowed`: Only POST requests are accepted.
- `500 Internal Server Error`: Database error.

#### POST /jobs/manage?action=delete
**Description**: Allows a client to delete their job posting.

**Authentication**: JWT Token required. User must have `client` role and own the job.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field    | Type    | Required | Description               |
| :------- | :------ | :------- | :------------------------ |
| `job_id` | integer | true     | The ID of the job to delete. |

**Response**:
```json
{
  "status": "success",
  "message": "Job deleted successfully"
}
```

**Errors**:
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not a client.
- `404 Not Found`: Job not found or not owned by the user.
- `405 Method Not Allowed`: Only POST requests are accepted.
- `500 Internal Server Error`: Database error.

#### POST /jobs/review_delivery
**Description**: Allows a client to review submitted work, either by accepting it or requesting changes.

**Authentication**: JWT Token required. User must be the client of the order.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field     | Type    | Required | Description                                                    |
| :-------- | :------ | :------- | :------------------------------------------------------------- |
| `order_id`| integer | true     | The ID of the order to review.                                 |
| `action`  | string  | true     | Action to perform: `accept` or `request_changes`.              |
| `feedback`| string  | false    | Optional feedback message from the client.                     |

**Response**:
```json
{
  "status": "success",
  "message": "Delivery review updated successfully",
  "data": {
    "order_id": 456,
    "new_status": "completed",
    "feedback": "Great job, perfectly done!"
  }
}
```

**Errors**:
- `400 Bad Request`: Invalid order ID or action, or order is not in 'delivered' state.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not the client of the order.
- `404 Not Found`: Order not found.

#### POST /jobs/send_message
**Description**: Sends a message between job participants (client and freelancer).

**Authentication**: JWT Token required. User must be a participant of the job.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field       | Type    | Required | Description                                  |
| :---------- | :------ | :------- | :------------------------------------------- |
| `job_id`    | integer | true     | The ID of the job the message is related to. |
| `receiver_id`| integer | true     | The ID of the user to send the message to.   |
| `message`   | string  | true     | The content of the message.                  |

**Response**:
```json
{
  "status": "success",
  "message": "Message sent successfully",
  "data": {
    "message_id": 123
  }
}
```

**Errors**:
- `400 Bad Request`: All fields are required.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Sender or receiver are not authorized participants of the job.
- `404 Not Found`: Job not found.

#### POST /jobs/submit_work
**Description**: Allows a freelancer to submit completed work for an order.

**Authentication**: JWT Token required. User must be the freelancer of the order.
**Request**:
```
Content-Type: multipart/form-data` (for file upload) or `application/x-www-form-urlencoded
```
| Field           | Type          | Required | Description                                        |
| :-------------- | :------------ | :------- | :------------------------------------------------- |
| `order_id`      | integer       | true     | The ID of the order for which work is submitted.   |
| `delivery_message`| string        | true     | A message accompanying the delivery.                 |
| `delivery_file` | file (binary) | false    | The delivered file (e.g., zip, PDF, image).        |

**Response**:
```json
{
  "status": "success",
  "message": "Work submitted successfully",
  "data": {
    "order_id": 456,
    "message": "Final project delivery.",
    "file": "uploads/deliveries/delivery_12345.zip"
  }
}
```

**Errors**:
- `400 Bad Request`: Order ID or delivery message missing, or order is not in 'in_progress' status.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not the freelancer of the order.
- `404 Not Found`: Order not found.

#### POST /jobs/update_order_status
**Description**: Updates the status of an order (e.g., `in_progress`, `completed`, `cancelled`). Specific roles can perform specific status changes.

**Authentication**: JWT Token required. User must be a participant (client or freelancer) of the order.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field       | Type    | Required | Description                                       |
| :---------- | :------ | :------- | :------------------------------------------------ |
| `order_id`  | integer | true     | The ID of the order to update.                    |
| `status`    | string  | true     | New status: `in_progress`, `completed`, `cancelled`. |

**Response**:
```json
{
  "status": "success",
  "message": "Order status updated",
  "data": {
    "order_id": 456,
    "status": "completed"
  }
}
```

**Errors**:
- `400 Bad Request`: Invalid order ID or status.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not a participant of the order, or user role is not permitted to make the status change (e.g., only client can cancel).
- `404 Not Found`: Order not found.

#### GET /jobs/view_proposals
**Description**: Retrieves all proposals submitted for a specific job.

**Authentication**: JWT Token required. User must be the client who posted the job.
**Request**:
```
GET /api/v1/jobs/view_proposals?job_id={job_id}
```
| Parameter | Type    | Required | Description                            |
| :-------- | :------ | :------- | :------------------------------------- |
| `job_id`  | integer | true     | The ID of the job to view proposals for. |

**Response**:
```json
{
  "status": "success",
  "message": "Proposals fetched successfully",
  "data": {
    "proposals": [
      {
        "proposal_id": 10,
        "cover_letter": "I have extensive experience...",
        "proposed_amount": "250.00",
        "estimated_days": 7,
        "status": "pending",
        "freelancer_id": 202,
        "username": "freelancer_dev",
        "full_name": "Alice Wonderland",
        "rating": "4.80",
        "profile_image": null
      }
    ]
  }
}
```

**Errors**:
- `400 Bad Request`: Job ID required.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User is not the client who posted the job.

#### GET /wallets/wallet_balance
**Description**: Retrieves the current balance and recent transactions for the logged-in user's wallet.

**Authentication**: JWT Token required.
**Request**:
```
GET /api/v1/wallets/wallet_balance
```

**Response**:
```json
{
  "status": "success",
  "message": "Wallet fetched successfully",
  "data": {
    "balance": "1500.75",
    "transactions": [
      {
        "type": "credit",
        "amount": "100.00",
        "description": "Payment for Job #123",
        "created_at": "2023-10-20 14:30:00"
      },
      {
        "type": "debit",
        "amount": "50.00",
        "description": "Withdrawal to Bank",
        "created_at": "2023-10-18 10:00:00"
      }
    ]
  }
}
```

**Errors**:
- `401 Unauthorized`: User not logged in.

## Usage
The Giglyte API serves as the backbone for the freelance marketplace. To interact with it, you will send HTTP requests to the documented endpoints.

### Authentication
Most endpoints require authentication. After successful login via `POST /auth/login`, you will receive a JWT (`token`). Include this token in the `Authorization` header of subsequent requests:

```
Authorization: Bearer <your_jwt_token>
```

### Example Workflow: Posting a Job (Client Role)
1.  **Register as a Client**:
    *   `POST /auth/register?step=one` with `email` and `password`.
    *   `POST /auth/register?step=two` with `code` (from email).
    *   `POST /auth/register?step=three` with `code`, `email`, `full_name`, `username`, `role='client'`.
2.  **Log In**:
    *   `POST /auth/login` with your client `email` and `password` to get your JWT.
3.  **Create a Job**:
    *   Make a `POST` request to `/jobs/create` with your JWT in the `Authorization` header.
    *   Include form data for `title`, `description`, `budget`, and optional `skills`.

### Example Workflow: Applying for a Job (Freelancer Role)
1.  **Register as a Freelancer**:
    *   Follow the registration steps similar to a client, but specify `role='freelancer'` in `step=three`.
2.  **Log In**:
    *   `POST /auth/login` with your freelancer `email` and `password` to get your JWT.
3.  **Browse Jobs**:
    *   Make a `GET` request to `/jobs/list` with your JWT.
    *   You can add query parameters like `search` or `skill` to filter results.
4.  **Apply to a Job**:
    *   Once you find a suitable job (e.g., job ID 123), make a `POST` request to `/jobs/apply` with your JWT.
    *   Include form data for `job_id`, `cover_letter`, `proposed_amount`, and `estimated_days`.

## Technologies Used

| Technology         | Category           | Link                                           |
| :----------------- | :----------------- | :--------------------------------------------- |
| PHP                | Programming Language | [php.net](https://www.php.net/)                |
| MySQL              | Database           | [mysql.com](https://www.mysql.com/)            |
| PDO                | Database Abstraction | [php.net/manual/en/book.pdo.php](https://www.php.net/manual/en/book.pdo.php) |
| Composer           | Package Manager    | [getcomposer.org](https://getcomposer.org/)    |
| Firebase JWT       | Authentication     | [firebase.com/docs/php/setup](https://firebase.com/docs/php/setup) |
| phpdotenv          | Env Management     | [github.com/vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) |
| Tailwind CSS (Admin UI) | CSS Framework      | [tailwindcss.com](https://tailwindcss.com/)    |

This project utilizes modern PHP practices and design patterns, drawing inspiration from the architectural principles found in frameworks like Laravel, despite not being built directly on the full Laravel framework.

## Author Info
**Your Name**: [Your Social Media (e.g., LinkedIn, Twitter)]

## Badges
![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)
![Database](https://img.shields.io/badge/Database-MySQL-orange.svg)
![Authentication](https://img.shields.io/badge/Auth-JWT-red.svg)
![Composer](https://img.shields.io/badge/Dependency%20Manager-Composer-yellow.svg)
[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)