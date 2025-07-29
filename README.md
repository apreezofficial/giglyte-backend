# 💡 GigLyte API: The Freelance Connection Backend

## Overview
GigLyte API is a robust backend system developed in **PHP** leveraging **PDO** for database interactions with **MySQL**. It powers a dynamic freelance platform, connecting clients with skilled freelancers for various job engagements.

## Features
- ✨ **User Management**: Secure user registration (multi-step verification) and authentication.
- 💼 **Job Board**: Clients can create, manage, and delete job listings with detailed descriptions and skill requirements.
- ✍️ **Proposal System**: Freelancers can submit proposals to open jobs, detailing their offers and estimated delivery times.
- 🤝 **Order & Contract Management**: Clients can accept proposals, initiating orders and tracking project progress.
- 💬 **Real-time Messaging**: Integrated communication system for clients and freelancers to discuss project details.
- 📤 **Work Submission**: Freelancers can submit completed work, with options for file uploads.
- 🔄 **Order Status Updates**: Clients and freelancers can update order statuses throughout the project lifecycle.
- 🔐 **Secure Authentication**: Utilizes PHP Sessions for managing user state after login, with JWT generation for potential future stateless API usage.
- 📊 **Database Schema Management**: Automated database table creation and updates on startup via `query.php`.

## Getting Started

### Environment Variables
The following environment variables are required for the application to run correctly. It is recommended to create a `.env` file in the project root and load these variables.

| Variable Name   | Example Value                       | Description                               |
| :-------------- | :---------------------------------- | :---------------------------------------- |
| `JWT_SECRET`    | `your_super_secret_jwt_key_here`    | Secret key for JWT encoding/decoding.     |
| `DB_HOST`       | `localhost` or `127.0.0.1`          | Database host address.                    |
| `DB_NAME`       | `giglyte`                           | Name of the MySQL database.               |
| `DB_USER`       | `root`                              | Username for database access.             |
| `DB_PASS`       | `password`                          | Password for database access.             |

**Note:** The current `db_connect.php` hardcodes database credentials. For production, these should be loaded from environment variables using `getenv()` similar to `JWT_SECRET` for enhanced security and flexibility.

## Usage
This backend provides a comprehensive set of API endpoints to manage a freelance marketplace.

### Authentication Flow
1.  **Register as a User (Step 1)**: Send a POST request to `/signup.php?step=one` with your email and password. This creates a temporary user and provides a token for the next step.
2.  **Verify Email (Step 2)**: Use the token received in Step 1 to send a GET request to `/signup.php?step=two`. This verifies the email and prepares for the final registration step.
3.  **Complete Registration (Step 3)**: With the verified session, send a POST request to `/signup.php?step=three` to provide full name, username, and role, finalizing the account creation.
4.  **Login**: Once registered, send a POST request to `/login.php` with your email and password. A successful login will return user details and establish a PHP session, which is used for subsequent authenticated requests.

### Job Management (Clients)
As a client, you can:
-   **Create Jobs**: Post new job opportunities, specifying title, description, budget, and required skills.
-   **Manage Jobs**: Edit details of your existing job listings or delete them if no longer needed.
-   **View Proposals**: Browse all proposals submitted by freelancers for your open jobs.
-   **Accept Proposals**: Accept a suitable proposal, which initiates an order for the job.

### Freelancer Operations
As a freelancer, you can:
-   **List Jobs**: View all open job opportunities posted by clients.
-   **Apply to Jobs**: Submit a proposal with a cover letter, proposed amount, and estimated days for open jobs.
-   **View Orders**: See all ongoing and completed orders where you are the assigned freelancer.
-   **Submit Work**: Deliver completed work for an accepted order, including a message and optional file upload.

### Communication & Order Tracking
-   **Send Messages**: Communicate with the client or freelancer involved in a specific job/order.
-   **Get Messages**: Retrieve the conversation history for a particular job.
-   **Update Order Status**: Both clients and freelancers can update the status of an order (e.g., 'in_progress', 'completed', 'cancelled') following specific role-based rules.

## API Documentation
### Base URL
The API endpoints are located at the root of your server deployment (e.g., `http://localhost/` or `https://api.yourdomain.com/`).

All authenticated endpoints require an active PHP session. After a successful login, the session is managed automatically by your browser. For API clients, ensure your HTTP client is configured to handle cookies for session management.

### Endpoints

#### POST /login.php
Authenticates a user and establishes a session.
**Request**:
```json
{
  "email": "string",
  "password": "string"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Login successful",
  "token": "jwt_token_string",
  "user": {
    "id": 1,
    "username": "johndoe",
    "email": "john.doe@example.com",
    "role": "client"
  }
}
```
**Errors**:
- `405`: Method not allowed
- `400`: Invalid email format or password is required
- `401`: Invalid email or password

#### POST /signup.php?step=one
Initiates user registration by validating email and password. Creates a temporary user record.
**Request**:
```json
{
  "email": "string",
  "password": "string"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Step 1 complete, verify email",
  "token": "verification_token_string"
}
```
**Errors**:
- `405`: Method not allowed
- `400`: Invalid email format or password too short (min 6 characters)
- `409`: Email already registered
- `500`: Database error

#### GET /signup.php?step=two
Verifies the email using the provided token from step one.
**Request**:
Query Parameter: `token` (e.g., `/signup.php?step=two&token=your_verification_token`)
**Response**:
```json
{
  "status": "success",
  "message": "Email verified, proceed to next step"
}
```
**Errors**:
- `400`: Token required
- `404`: Invalid or expired token
- `500`: Database error

#### POST /signup.php?step=three
Completes the user registration with personal details.
**Request**:
```json
{
  "full_name": "string",
  "username": "string",
  "role": "client" | "freelancer",
  "phone": "string" (optional),
  "country": "string" (optional),
  "city": "string" (optional),
  "bio": "string" (optional)
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
- `405`: Method not allowed
- `400`: No user session found, or required fields (full_name, username, role) are missing
- `409`: Username already taken
- `404`: Temporary user not found
- `500`: Database error

#### POST /jobs/accept_proposal.php
**Requires Client role.** Accepts a proposal for a job, creating an order.
**Request**:
```json
{
  "proposal_id": "integer"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Proposal accepted and order created",
  "data": {
    "order_id": 123
  }
}
```
**Errors**:
- `401`: Unauthorized. Please log in.
- `400`: Proposal ID required or Proposal already accepted
- `404`: Proposal not found
- `403`: Not authorized to accept this proposal
- `500`: Could not create order (Database error)

#### POST /jobs/apply.php
**Requires Freelancer role.** Submits a proposal for an open job.
*Note: `jobs/create_proposals.php` is functionally identical to this endpoint.*
**Request**:
```json
{
  "job_id": "integer",
  "cover_letter": "string",
  "proposed_amount": "float",
  "estimated_days": "integer"
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
- `401`: Unauthorized. Please log in.
- `403`: Only freelancers can apply to jobs
- `405`: Method not allowed
- `400`: All fields are required
- `404`: Job not found or not open for proposals
- `409`: You already applied to this job

#### POST /jobs/create.php
**Requires Client role.** Creates a new job listing.
**Request**:
```json
{
  "title": "string",
  "description": "string",
  "budget": "float",
  "skills": "string" // comma-separated, e.g., "PHP,MySQL,Laravel"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Job created successfully",
  "data": {
    "job_id": 101,
    "skills": ["PHP", "MySQL", "Laravel"]
  }
}
```
**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: Only clients can create jobs
- `405`: Method not allowed
- `400`: Title and description are required or Invalid budget
- `500`: Database error

#### GET /jobs/get_messages.php
**Requires participating in the job (client or accepted freelancer).** Retrieves all messages for a specific job.
**Request**:
Query Parameter: `job_id` (e.g., `/jobs/get_messages.php?job_id=101`)
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
        "message": "Hello, how are you?",
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
- `401`: Unauthorized. Please log in.
- `400`: Job ID required
- `404`: Job not found
- `403`: Not authorized to view messages

#### GET /jobs/get_orders.php
**Requires Client or Freelancer role.** Retrieves a list of orders relevant to the authenticated user.
**Request**: None
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
        "created_at": "2023-10-26 09:00:00",
        "updated_at": "2023-10-26 10:00:00",
        "job_title": "Build a PHP API",
        "freelancer_username": "dev_pro", // For client role
        "freelancer_name": "Developer Pro" // For client role
      },
      {
        "order_id": 2,
        "status": "completed",
        "created_at": "2023-10-20 09:00:00",
        "updated_at": "2023-10-25 10:00:00",
        "job_title": "Design a new logo",
        "client_username": "design_buyer", // For freelancer role
        "client_name": "Design Buyer Inc." // For freelancer role
      }
    ]
  }
}
```
**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: User not found
- `500`: Database error

#### GET /jobs/list.php
**Requires Freelancer role.** Lists all open job opportunities, with optional filtering.
**Request**:
Query Parameters:
- `search`: Keyword to search in job title or description.
- `min_budget`: Minimum budget for jobs.
- `max_budget`: Maximum budget for jobs.
- `skill`: Filter jobs by a specific required skill.

Example: `/jobs/list.php?search=web&min_budget=100&skill=PHP`
**Response**:
```json
{
  "status": "success",
  "message": "Jobs fetched successfully",
  "data": {
    "jobs": [
      {
        "id": 1,
        "title": "Build a Laravel E-commerce site",
        "description": "...",
        "budget": "500.00",
        "status": "open",
        "created_at": "2023-10-26 08:00:00",
        "client_name": "Client A",
        "skills": ["Laravel", "Vue.js", "MySQL"]
      }
    ]
  }
}
```
**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: Only freelancers can view jobs
- `500`: Database error

#### POST /jobs/manage.php?action=edit
**Requires Client role and job ownership.** Edits an existing job listing.
**Request**:
```json
{
  "job_id": "integer",
  "title": "string",
  "description": "string",
  "budget": "float",
  "skills": "string" // comma-separated, will overwrite existing skills
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
- `401`: Unauthorized. Please log in.
- `403`: Only clients can manage jobs
- `405`: Method not allowed
- `400`: Title and description are required
- `404`: Job not found or not owned by you
- `500`: Database error

#### POST /jobs/manage.php?action=delete
**Requires Client role and job ownership.** Deletes a job listing.
**Request**:
```json
{
  "job_id": "integer"
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
- `401`: Unauthorized. Please log in.
- `403`: Only clients can manage jobs
- `405`: Method not allowed
- `404`: Job not found or not owned by you
- `500`: Database error

#### POST /jobs/send_message.php
**Requires participating in the job (client or accepted freelancer).** Sends a new message within a job's chat.
**Request**:
```json
{
  "job_id": "integer",
  "receiver_id": "integer",
  "message": "string"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Message sent successfully",
  "data": {
    "message_id": 456
  }
}
```
**Errors**:
- `401`: Unauthorized. Please log in.
- `400`: All fields are required
- `404`: Job not found
- `403`: You are not authorized to send message on this job

#### POST /jobs/submit_work.php
**Requires Freelancer role and order ownership.** Submits completed work for an order.
**Request**:
```json
{
  "order_id": "integer",
  "delivery_message": "string",
  "delivery_file": "file" (optional, multipart/form-data)
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Work submitted successfully",
  "data": {
    "order_id": 123,
    "message": "Here is the completed work!",
    "file": "uploads/deliveries/delivery_xxxxxxxx.pdf" // Null if no file
  }
}
```
**Errors**:
- `401`: Unauthorized. Please log in.
- `400`: Order ID and delivery message are required or Order not in progress
- `404`: Order not found
- `403`: Not your order

#### POST /jobs/update_order_status.php
**Requires participating in the order (client or freelancer).** Updates the status of an order.
**Request**:
```json
{
  "order_id": "integer",
  "status": "in_progress" | "completed" | "cancelled"
}
```
**Response**:
```json
{
  "status": "success",
  "message": "Order status updated",
  "data": {
    "order_id": 123,
    "status": "completed"
  }
}
```
**Errors**:
- `401`: Unauthorized. Please log in.
- `400`: Invalid order ID or status
- `404`: Order not found
- `403`: Not authorized to update this order (due to role or non-participation), or specific role rules violated (e.g., only client can cancel)

#### GET /jobs/view_proposals.php
**Requires Client role and job ownership.** Retrieves all proposals for a specific job.
**Request**:
Query Parameter: `job_id` (e.g., `/jobs/view_proposals.php?job_id=101`)
**Response**:
```json
{
  "status": "success",
  "message": "Proposals fetched successfully",
  "data": {
    "proposals": [
      {
        "proposal_id": 1,
        "cover_letter": "I can complete this job in...",
        "proposed_amount": "450.00",
        "estimated_days": 5,
        "status": "pending",
        "freelancer_id": 2,
        "username": "freelancer_dev",
        "full_name": "Freelancer Developer",
        "rating": "4.80",
        "profile_image": null
      }
    ]
  }
}
```
**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: Only clients can view proposals or You are not authorized to view proposals for this job
- `400`: Job ID required

## Technologies Used

| Technology    | Category                 | Description                                                 |
| :------------ | :----------------------- | :---------------------------------------------------------- |
| **PHP**       | Backend Language         | Core programming language for the API logic.                |
| **MySQL**     | Database                 | Relational database for persistent storage.                 |
| **PDO**       | Database Abstraction     | PHP Data Objects for secure and efficient database access.  |
| **Composer**  | Dependency Manager       | Manages PHP libraries and dependencies.                     |
| **Firebase JWT**| Authentication Library   | For generating and validating JSON Web Tokens.              |
| **phpdotenv** | Environment Management   | Loads environment variables from a `.env` file.             |

## License
This project is licensed under the MIT License.

## Author Info
- **Developer**: [Your Name/Alias Here]
- **LinkedIn**: [Your LinkedIn Profile URL]
- **Portfolio**: [Your Personal Website/Portfolio URL]
- **Email**: [Your Email Address]

---
[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)