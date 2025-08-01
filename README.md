# GigLyte Backend API

Empower your freelance marketplace with a robust and scalable backend system. The GigLyte API provides comprehensive functionalities for user management, job postings, proposal submissions, order fulfillment, and secure financial transactions, all built with a strong focus on performance and data integrity.

## Features

*   **User Authentication & Management**: Secure multi-step signup, login with JWT, and session management for both clients and freelancers.
*   **Job Listing & Creation**: Clients can create detailed job postings, while freelancers can efficiently browse and filter open opportunities.
*   **Proposal System**: Freelancers can submit proposals with cover letters, proposed amounts, and estimated days for job completion.
*   **Order & Contract Workflow**: Streamlined process for accepting proposals, creating orders, submitting work, and reviewing deliveries.
*   **Real-time Messaging**: Facilitates direct communication between clients and freelancers regarding specific jobs.
*   **Wallet & Transactions**: Core financial module for managing user balances and tracking transaction history.
*   **Data Persistence**: Robust MySQL database schema designed for reliability and scalability.

## Technologies Used

| Technology         | Purpose                                     | Link                                                                |
| :----------------- | :------------------------------------------ | :------------------------------------------------------------------ |
| PHP 8.3            | Core backend scripting language             | [PHP Official](https://www.php.net/)                                |
| MySQL              | Relational database management system       | [MySQL Official](https://www.mysql.com/)                            |
| PDO                | PHP Data Objects for secure DB interactions | [PHP PDO](https://www.php.net/manual/en/book.pdo.php)               |
| Firebase PHP-JWT   | JSON Web Token for API authentication       | [firebase/php-jwt](https://github.com/firebase/php-jwt)             |
| vlucas/phpdotenv   | Environment variable management             | [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)             |
| Session Management | User state persistence across requests      | [PHP Sessions](https://www.php.net/manual/en/book.session.php)      |

---

# GigLyte Backend API

## Overview
This is the backend API for the GigLyte platform, developed using PHP and PDO for MySQL database interactions. It provides a full suite of functionalities for a freelance marketplace, including user authentication, job management, proposals, orders, messaging, and wallet services, leveraging JWT for secure API communication.

## Features
- **Authentication**: User registration and login using JWT for API token management.
- **Job Management**: Clients can create, list, edit, and delete job postings. Freelancers can view available jobs.
- **Proposal System**: Freelancers can submit proposals for open jobs, and clients can view and accept these proposals.
- **Order Processing**: Manages the lifecycle of a job once a proposal is accepted, including work submission and delivery review.
- **Messaging**: Enables direct communication between clients and freelancers for specific jobs.
- **Wallet & Transactions**: Core services for managing user balances and tracking financial movements.

## Getting Started

### Environment Variables
To configure the application, create a `.env` file in the project root with the following variables:

- `JWT_SECRET`: A strong, random string used for signing and verifying JSON Web Tokens.
    - Example: `JWT_SECRET="your_very_secure_and_long_jwt_secret_key_here_12345"`

### Database Setup
The database schema is initialized using the `query.php` script. Ensure your MySQL database is running and accessible with the credentials specified in `db_connect.php`.

To set up the database tables, navigate to the `query.php` file in your browser or execute it via a PHP CLI:
```bash
php query.php
```
This will create all necessary tables for users, jobs, proposals, orders, wallets, etc.

## API Documentation

### Base URL
The base URL for all API endpoints depends on your server configuration. Assuming the project is served from `http://localhost:8080/` and the PHP files are directly accessible:
`http://localhost:8080/`

For example, the login endpoint would be `http://localhost:8080/login.php`.

### Endpoints

#### POST /login.php
**Overview**: Authenticates a user and returns a JWT token for subsequent API requests.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
```
email: string (required)
password: string (required)
```
**Payload example**:
```
email=user@example.com&password=yourpassword
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
- `400 Bad Request`: Invalid email format or password missing.
- `401 Unauthorized`: Invalid email or password.
- `405 Method Not Allowed`: Request method is not POST.

#### POST /signup.php?step=one
**Overview**: Initiates user registration by validating email and password, then storing temporary user data and generating a verification token.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
```
email: string (required, valid email format)
password: string (required, minimum 6 characters)
```
**Payload example**:
```
email=newuser@example.com&password=SecurePassword123
```
**Response**:
```json
{
  "status": "success",
  "message": "Step 1 complete, verify email",
  "data": {
    "token": 123456
  }
}
```
**Errors**:
- `400 Bad Request`: Invalid email format or password too short.
- `405 Method Not Allowed`: Request method is not POST.
- `409 Conflict`: Email already registered.

#### POST /signup.php?step=two
**Overview**: Verifies the email by confirming the token sent in step one.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
```
code: string (required, the token received from step one)
```
**Payload example**:
```
code=123456
```
**Response**:
```json
{
  "status": "success",
  "message": "Email verified, proceed to next step"
}
```
**Errors**:
- `400 Bad Request`: Verification code is missing.
- `404 Not Found`: Invalid or expired token.

#### POST /signup.php?step=three
**Overview**: Completes user registration by creating the final user profile after email verification.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
```
full_name: string (required)
username: string (required, unique)
role: string (required, 'client' or 'freelancer')
phone: string (optional)
country: string (optional)
city: string (optional)
bio: string (optional)
```
**Payload example**:
```
full_name=Jane Doe&username=janedoe&role=freelancer&phone=1234567890&country=USA&city=New York&bio=Experienced developer
```
**Response**:
```json
{
  "status": "success",
  "message": "Signup complete, you can login now"
}
```
**Errors**:
- `400 Bad Request`: Required fields missing or invalid.
- `404 Not Found`: Temporary user session not found (restart signup).
- `405 Method Not Allowed`: Request method is not POST.
- `409 Conflict`: Username already taken.
- `500 Internal Server Error`: Database error during finalization.

#### POST /jobs/accept_proposal.php
**Overview**: Allows a client to accept a freelancer's proposal, creating an order and setting the job status to 'in_progress'. Requires client authentication.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer <JWT_TOKEN>
```
```
proposal_id: integer (required)
```
**Payload example**:
```
proposal_id=1
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
- `400 Bad Request`: Proposal ID required or proposal already accepted.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Not authorized to accept this proposal (not the job client).
- `404 Not Found`: Proposal not found.
- `500 Internal Server Error`: Database error during order creation.

#### POST /jobs/apply.php
**Overview**: Allows a freelancer to apply to an open job by submitting a proposal. Requires freelancer authentication.
**Request**:
```
Content-Type: application/x-www-form-urlencoded OR application/json
Authorization: Bearer <JWT_TOKEN>
```
```
job_id: integer (required)
cover_letter: string (required)
proposed_amount: float (required, > 0)
estimated_days: integer (required, > 0)
```
**Payload example (form-urlencoded)**:
```
job_id=1&cover_letter=I can complete this task.&proposed_amount=500.00&estimated_days=7
```
**Payload example (JSON)**:
```json
{
  "job_id": 1,
  "cover_letter": "I can complete this task efficiently.",
  "proposed_amount": 500.00,
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
- `400 Bad Request`: All fields are required or invalid.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Only freelancers can apply, or user not found.
- `404 Not Found`: Job not found or not open for proposals.
- `405 Method Not Allowed`: Request method is not POST.
- `409 Conflict`: Already applied to this job.

#### POST /jobs/create.php
**Overview**: Allows a client to create a new job posting. Requires client authentication.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer <JWT_TOKEN>
```
```
title: string (required)
description: string (required)
budget: float (required, >= 0)
skills: string (optional, comma-separated list of skills, e.g., "PHP, MySQL, REST API")
```
**Payload example**:
```
title=Build REST API&description=Need a RESTful API for a web application.&budget=1000.00&skills=PHP,MySQL,API Development
```
**Response**:
```json
{
  "status": "success",
  "message": "Job created successfully",
  "data": {
    "job_id": 1,
    "skills": ["PHP", "MySQL", "API Development"]
  }
}
```
**Errors**:
- `400 Bad Request`: Title or description missing, or invalid budget.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Only clients can create jobs.
- `405 Method Not Allowed`: Request method is not POST.
- `500 Internal Server Error`: Database error during job creation.

#### POST /jobs/create_proposals.php
**Overview**: Allows a freelancer to submit a proposal for an open job. (Functionally similar to `/jobs/apply.php`, but this endpoint explicitly handles proposal creation). Requires freelancer authentication.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer <JWT_TOKEN>
```
```
job_id: integer (required)
cover_letter: string (required)
proposed_amount: float (required, > 0)
estimated_days: integer (required, > 0)
```
**Payload example**:
```
job_id=1&cover_letter=I am highly skilled for this.&proposed_amount=750.00&estimated_days=5
```
**Response**:
```json
{
  "status": "success",
  "message": "Proposal submitted successfully"
}
```
**Errors**:
- `400 Bad Request`: All fields are required and must be valid, or job not open for proposals.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Only freelancers can create proposals.
- `404 Not Found`: Job not found.
- `409 Conflict`: Already submitted a proposal for this job.

#### GET /jobs/get_messages.php
**Overview**: Retrieves messages for a specific job, marking messages sent to the requesting user as read. Requires authentication as a participant of the job.
**Request**:
```
Authorization: Bearer <JWT_TOKEN>
```
```
job_id: integer (required, as query parameter)
```
**Query parameter example**:
`/jobs/get_messages.php?job_id=1`
**Response**:
```json
{
  "status": "success",
  "message": "Messages fetched & marked as read",
  "data": {
    "messages": [
      {
        "id": 1,
        "sender_id": 2,
        "receiver_id": 1,
        "message": "Hi, I'm interested in this project.",
        "is_read": 1,
        "created_at": "2023-10-27 10:00:00",
        "sender_name": "freelancer_user",
        "receiver_name": "client_user"
      }
    ]
  }
}
```
**Errors**:
- `400 Bad Request`: Job ID required.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Not authorized to view messages for this job.
- `404 Not Found`: Job not found.

#### GET /jobs/get_orders.php
**Overview**: Retrieves a list of orders relevant to the authenticated user (either as client or freelancer). Requires authentication.
**Request**:
```
Authorization: Bearer <JWT_TOKEN>
```
```
No request body or parameters.
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
        "created_at": "2023-10-27 10:30:00",
        "updated_at": "2023-10-27 10:30:00",
        "job_title": "Build REST API",
        "freelancer_username": "freelancer_user",
        "freelancer_name": "Freelancer Name"
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
        "created_at": "2023-10-27 10:30:00",
        "updated_at": "2023-10-27 10:30:00",
        "job_title": "Build REST API",
        "client_username": "client_user",
        "client_name": "Client Name"
      }
    ]
  }
}
```
**Errors**:
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: User not found.
- `500 Internal Server Error`: Database error.

#### GET /jobs/list.php
**Overview**: Retrieves a list of open job postings. Can be filtered by keyword, budget range, and specific skills. Requires freelancer authentication.
**Request**:
```
Authorization: Bearer <JWT_TOKEN>
```
```
search: string (optional, keyword to search in title/description)
min_budget: float (optional, minimum budget)
max_budget: float (optional, maximum budget)
skill: string (optional, exact skill match)
```
**Query parameter examples**:
`/jobs/list.php`
`/jobs/list.php?search=API&min_budget=500`
`/jobs/list.php?skill=PHP`
**Response**:
```json
{
  "status": "success",
  "message": "Jobs fetched successfully",
  "data": {
    "jobs": [
      {
        "id": 1,
        "title": "Build REST API",
        "description": "Need a RESTful API for a web application.",
        "budget": "1000.00",
        "status": "open",
        "created_at": "2023-10-27 09:00:00",
        "client_name": "Client Name",
        "skills": ["PHP", "MySQL", "API Development"]
      }
    ]
  }
}
```
**Errors**:
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Only freelancers can view jobs.
- `500 Internal Server Error`: Database error.

#### POST /jobs/manage.php?action=edit
**Overview**: Allows a client to edit an existing job posting. Requires client authentication and job ownership.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer <JWT_TOKEN>
```
```
job_id: integer (required)
title: string (required)
description: string (required)
budget: float (required)
skills: string (optional, comma-separated list of skills)
```
**Payload example**:
```
job_id=1&title=Updated Job Title&description=Revised job description.&budget=1200.00&skills=PHP,Laravel,Vue.js
```
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
- `403 Forbidden`: Only clients can manage jobs, or not authorized to edit this job.
- `404 Not Found`: Job not found or not owned by the client.
- `405 Method Not Allowed`: Request method is not POST.
- `500 Internal Server Error`: Database error.

#### POST /jobs/manage.php?action=delete
**Overview**: Allows a client to delete an existing job posting. Requires client authentication and job ownership.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer <JWT_TOKEN>
```
```
job_id: integer (required)
```
**Payload example**:
```
job_id=1
```
**Response**:
```json
{
  "status": "success",
  "message": "Job deleted successfully"
}
```
**Errors**:
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Only clients can manage jobs, or not authorized to delete this job.
- `404 Not Found`: Job not found or not owned by the client.
- `405 Method Not Allowed`: Request method is not POST.
- `500 Internal Server Error`: Database error.

#### POST /jobs/review_delivery.php
**Overview**: Allows a client to review a freelancer's work delivery, either accepting it or requesting changes. Requires client authentication and order ownership.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer <JWT_TOKEN>
```
```
order_id: integer (required)
action: string (required, 'accept' or 'request_changes')
feedback: string (optional, client's comments on the delivery)
```
**Payload example (Accept)**:
```
order_id=1&action=accept&feedback=Great work, looks perfect!
```
**Payload example (Request Changes)**:
```
order_id=1&action=request_changes&feedback=Please adjust the header font.
```
**Response**:
```json
{
  "status": "success",
  "message": "Delivery review updated successfully",
  "data": {
    "order_id": 1,
    "new_status": "completed",
    "feedback": "Great work, looks perfect!"
  }
}
```
**Errors**:
- `400 Bad Request`: Invalid order ID, action, or order not in 'delivered' state.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Not authorized to review this order (not the client).
- `404 Not Found`: Order not found.

#### POST /jobs/send_message.php
**Overview**: Sends a message related to a specific job between participants (client or freelancer). Requires authentication.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer <JWT_TOKEN>
```
```
job_id: integer (required)
receiver_id: integer (required, ID of the recipient user)
message: string (required)
```
**Payload example**:
```
job_id=1&receiver_id=2&message=Hello, I have a question about the project scope.
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
- `400 Bad Request`: All fields are required.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Sender or receiver not authorized participants of this job.
- `404 Not Found`: Job not found.

#### POST /jobs/submit_work.php
**Overview**: Allows a freelancer to submit completed work for an order. Can include a delivery message and an attached file. Requires freelancer authentication and order ownership.
**Request**:
```
Content-Type: multipart/form-data
Authorization: Bearer <JWT_TOKEN>
```
```
order_id: integer (required)
delivery_message: string (required, description of the submitted work)
delivery_file: file (optional, the work file to be uploaded)
```
**Payload example (with file upload)**:
```
--BOUNDARY
Content-Disposition: form-data; name="order_id"

1
--BOUNDARY
Content-Disposition: form-data; name="delivery_message"

Here is the completed API.
--BOUNDARY
Content-Disposition: form-data; name="delivery_file"; filename="api_v1.zip"
Content-Type: application/zip

<file content>
--BOUNDARY--
```
**Response**:
```json
{
  "status": "success",
  "message": "Work submitted successfully",
  "data": {
    "order_id": 1,
    "message": "Here is the completed API.",
    "file": "uploads/deliveries/delivery_653b6f0e4a7a8.zip"
  }
}
```
**Errors**:
- `400 Bad Request`: Order ID or delivery message missing, or order not in 'in_progress' state.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Not authorized to submit work for this order (not the freelancer).
- `404 Not Found`: Order not found.

#### POST /jobs/update_order_status.php
**Overview**: Updates the status of an order. Specific role-based rules apply for status changes. Requires authentication as a participant of the order.
**Request**:
```
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer <JWT_TOKEN>
```
```
order_id: integer (required)
status: string (required, 'in_progress', 'completed', or 'cancelled')
```
**Payload example**:
```
order_id=1&status=completed
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
- `400 Bad Request`: Invalid order ID or status.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Not authorized to update this order, or role-specific rule violation (e.g., freelancer cannot cancel).
- `404 Not Found`: Order not found.

#### GET /jobs/view_proposals.php
**Overview**: Allows a client to view all proposals submitted for a specific job. Requires client authentication and job ownership.
**Request**:
```
Authorization: Bearer <JWT_TOKEN>
```
```
job_id: integer (required, as query parameter)
```
**Query parameter example**:
`/jobs/view_proposals.php?job_id=1`
**Response**:
```json
{
  "status": "success",
  "message": "Proposals fetched successfully",
  "data": {
    "proposals": [
      {
        "proposal_id": 1,
        "cover_letter": "I have extensive experience...",
        "proposed_amount": "500.00",
        "estimated_days": 7,
        "status": "pending",
        "freelancer_id": 2,
        "username": "freelancer_john",
        "full_name": "John Doe",
        "rating": "4.50",
        "profile_image": null
      }
    ]
  }
}
```
**Errors**:
- `400 Bad Request`: Job ID required.
- `401 Unauthorized`: User not logged in.
- `403 Forbidden`: Only clients can view proposals, or not authorized to view proposals for this job.

#### GET /wallets/wallet_balance.php
**Overview**: Retrieves the authenticated user's wallet balance and a list of recent transactions.
**Request**:
```
Authorization: Bearer <JWT_TOKEN>
```
```
No request body or parameters.
```
**Response**:
```json
{
  "status": "success",
  "message": "Wallet fetched successfully",
  "data": {
    "balance": "1250.75",
    "transactions": [
      {
        "type": "credit",
        "amount": "1000.00",
        "description": "Job payment for project A",
        "created_at": "2023-10-26 15:30:00"
      },
      {
        "type": "debit",
        "amount": "50.00",
        "description": "Withdrawal to bank",
        "created_at": "2023-10-25 10:00:00"
      }
    ]
  }
}
```
**Errors**:
- `401 Unauthorized`: User not logged in.

## Usage

Interact with the GigLyte API using standard HTTP requests. Below are examples using `curl` to demonstrate typical interactions. Replace `http://localhost:8080` with your actual API base URL.

**1. User Registration (Step 1)**

```bash
curl -X POST \
  http://localhost:8080/signup.php?step=one \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'email=testuser@example.com' \
  --data-urlencode 'password=Password123'
```

**2. User Registration (Step 2 - Email Verification)**

```bash
curl -X POST \
  http://localhost:8080/signup.php?step=two \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'code=123456' # Use the token received from Step 1
```

**3. User Registration (Step 3 - Finalize Profile)**

```bash
curl -X POST \
  http://localhost:8080/signup.php?step=three \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'full_name=Test User' \
  --data-urlencode 'username=testuser123' \
  --data-urlencode 'role=client' \
  --data-urlencode 'phone=5551234567' \
  --data-urlencode 'country=USA' \
  --data-urlencode 'city=Anytown' \
  --data-urlencode 'bio=Client for GigLyte platform.'
```

**4. User Login**

```bash
curl -X POST \
  http://localhost:8080/login.php \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'email=testuser@example.com' \
  --data-urlencode 'password=Password123'
```
*Save the `token` from the response for authenticated requests.*

**5. Create a Job (as a Client)**

```bash
ACCESS_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." # Replace with your actual token

curl -X POST \
  http://localhost:8080/jobs/create.php \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'title=Website Redesign Project' \
  --data-urlencode 'description=Redesign our company website for a modern look and feel.' \
  --data-urlencode 'budget=2500.00' \
  --data-urlencode 'skills=Web Design,UI/UX,Frontend Development'
```

**6. List Open Jobs (as a Freelancer)**

```bash
ACCESS_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." # Replace with your actual token

curl -X GET \
  "http://localhost:8080/jobs/list.php?search=design&min_budget=1000" \
  -H "Authorization: Bearer $ACCESS_TOKEN"
```

**7. Apply to a Job (as a Freelancer)**

```bash
ACCESS_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." # Replace with your actual token

curl -X POST \
  http://localhost:8080/jobs/apply.php \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "job_id": 1,
    "cover_letter": "I have strong experience in web redesign and can deliver a stunning result.",
    "proposed_amount": 2200.00,
    "estimated_days": 14
  }'
```

**8. View Proposals for Your Job (as a Client)**

```bash
ACCESS_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." # Replace with your actual token

curl -X GET \
  "http://localhost:8080/jobs/view_proposals.php?job_id=1" \
  -H "Authorization: Bearer $ACCESS_TOKEN"
```

**9. Accept a Proposal (as a Client)**

```bash
ACCESS_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." # Replace with your actual token

curl -X POST \
  http://localhost:8080/jobs/accept_proposal.php \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'proposal_id=1' # The ID of the proposal to accept
```

**10. Get Wallet Balance and Transactions**

```bash
ACCESS_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." # Replace with your actual token

curl -X GET \
  http://localhost:8080/wallets/wallet_balance.php \
  -H "Authorization: Bearer $ACCESS_TOKEN"
```

## Author

*   **Your Name**
    *   [LinkedIn](https://linkedin.com/in/yourusername)
    *   [Twitter](https://twitter.com/yourusername)
    *   [Portfolio](https://yourportfolio.com)

---

[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)