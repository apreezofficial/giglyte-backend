# GigLyte API

## Overview
GigLyte is a robust backend API crafted in **plain PHP** using **PDO** for efficient database management. Designed to power a dynamic freelance marketplace, it provides core functionalities for user authentication, job posting, proposal management, and role-based access control, laying the foundation for a comprehensive gig economy platform.

## Features
- 🛡️ **User Authentication & Authorization**: Secure signup and login mechanisms for clients and freelancers, managing access based on distinct user roles.
- 💼 **Job Listing & Management**: Clients can effortlessly create, view, modify, and delete job postings, specifying budgets and required skills.
- 📝 **Proposal Submission System**: Freelancers gain the ability to submit detailed proposals for open job opportunities, including cover letters and proposed compensation.
- 📊 **Comprehensive Database Schema**: A well-structured relational database design supports users, jobs, proposals, contracts, payments, messages, and reviews.
- ⚙️ **Modular API Endpoints**: Clearly defined endpoints for each major functionality, ensuring maintainability and scalability.

## Getting Started
### Installation
This section is skipped as per your request.
            
### Environment Variables
To ensure the API functions correctly, the following environment variable must be set in your server configuration (e.g., Apache/Nginx environment variables or a `.env` file if using `phpdotenv` beyond the `composer.json` indication):

- `JWT_SECRET`: A critical, cryptographically secure random string used for signing JSON Web Tokens.
  *Example*: `JWT_SECRET=your_highly_confidential_and_complex_jwt_signing_key_789ABCDEF`

## API Documentation
### Base URL
This API does not operate from a single, unified base URL. Each endpoint is directly accessible via its respective file path (e.g., `/login.php`, `/jobs/create.php`).

### Endpoints

#### POST /login.php
Authenticates a user by email and password. Upon successful authentication, a JSON Web Token (JWT) is issued. **Note**: While a JWT is generated, the primary method for authorizing subsequent requests to job-related API endpoints relies on active server-side sessions (`$_SESSION`).

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field    | Type     | Required | Description                        | Example               |
|----------|----------|----------|------------------------------------|-----------------------|
| `email`    | `string` | Yes      | The user's registered email address.| `dev.freelancer@example.com` |
| `password` | `string` | Yes      | The user's password.               | `SecureP@ssw0rd123`   |

**Response**:
```json
{
  "status": "success",
  "message": "Login successful",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJleGFtcGxlLmNvbSIsImF1ZCI6ImV4YW1wbGUuY29tIiwiaWF0IjoxNjc4NjQ5MjAwLCJleHAiOjE2Nzg3MzU2MDAsInVzZXJfaWQiOjUsInJvbGUiOiJjbGllbnQifQ.some_unique_jwt_signature",
  "user": {
    "id": 5,
    "username": "clientpro",
    "email": "dev.freelancer@example.com",
    "role": "client"
  }
}
```

**Errors**:
- `405`: Method not allowed.
- `400`: Invalid email format / Password is required.
- `401`: Invalid email or password.

---

#### POST /signup.php?step=one
The first step of user registration. This endpoint validates the provided email and password, creates a temporary user record, and returns a verification token.

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field    | Type     | Required | Description                           | Example                 |
|----------|----------|----------|---------------------------------------|-------------------------|
| `email`    | `string` | Yes      | The desired email address for the new account.| `new.applicant@example.com` |
| `password` | `string` | Yes      | The user's chosen password (minimum 6 characters).| `MyNewSecurePwd!`       |

**Response**:
```json
{
  "status": "success",
  "message": "Step 1 complete, verify email",
  "token": "a4b5c6d7e8f90123456789abcdef012345"
}
```

**Errors**:
- `405`: Method not allowed.
- `400`: Invalid email format / Password too short (min 6 characters).
- `409`: Email already registered.
- `500`: Database error.

---

#### GET /signup.php?step=two
The second step in the registration process. This endpoint verifies the email using the token obtained from `step=one`, then sets a session to allow progression to the final registration step.

**Request**:
```
Query Parameters
```
| Field   | Type     | Required | Description                            | Example                              |
|---------|----------|----------|----------------------------------------|--------------------------------------|
| `token` | `string` | Yes      | The verification token received from `step=one`.| `a4b5c6d7e8f90123456789abcdef012345` |

**Response**:
```json
{
  "status": "success",
  "message": "Email verified, proceed to next step"
}
```

**Errors**:
- `400`: Token required.
- `404`: Invalid or expired token.
- `500`: Database error.

---

#### POST /signup.php?step=three
The final step of user registration. This endpoint collects the user's personal details and role, then finalizes the account creation by moving temporary user data into the permanent `users` table.

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field         | Type     | Required | Description                                  | Example                 |
|---------------|----------|----------|----------------------------------------------|-------------------------|
| `full_name`     | `string` | Yes      | The user's full name.                        | `Alice Wonderland`      |
| `username`      | `string` | Yes      | A unique username for the account.           | `alice_dev`             |
| `role`          | `string` | Yes      | The user's intended role: `client` or `freelancer`.| `freelancer`            |
| `phone`         | `string` | No       | User's contact phone number.                 | `+1-555-123-4567`       |
| `country`       | `string` | No       | User's country of residence.                 | `Canada`                |
| `city`          | `string` | No       | User's city of residence.                    | `Toronto`               |
| `bio`           | `string` | No       | A short biography or professional summary.   | `Full-stack developer with 5+ years experience.`|

**Response**:
```json
{
  "status": "success",
  "message": "Signup complete, you can login now"
}
```

**Errors**:
- `400`: No user session found, restart signup / [Field] is required.
- `405`: Method not allowed.
- `409`: Username already taken.
- `404`: Temporary user not found.
- `500`: Database error.

---

#### POST /jobs/create.php
Allows an authenticated client to post a new job. The job includes a title, description, budget, and a list of required skills.

**Authentication**:
- Requires an active session with a valid `user_id`.
- The authenticated user must possess the `client` role.

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field         | Type     | Required | Description                                  | Example                             |
|---------------|----------|----------|----------------------------------------------|-------------------------------------|
| `title`         | `string` | Yes      | The title of the job posting.                | `Develop a Custom CRM`              |
| `description`   | `string` | Yes      | A detailed description outlining job requirements and scope.| `Seeking an experienced PHP/MySQL developer to build a custom CRM solution...`|
| `budget`        | `float`  | No       | The allocated budget for the job. Defaults to 0.00.| `1500.00`                           |
| `skills`        | `string` | No       | A comma-separated list of skills required for the job.| `PHP,MySQL,Laravel,Vue.js`          |

**Response**:
```json
{
  "status": "success",
  "message": "Job created successfully",
  "data": {
    "job_id": 201,
    "skills": ["PHP", "MySQL", "Laravel", "Vue.js"]
  }
}
```

**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: Only clients can create jobs.
- `405`: Method not allowed.
- `400`: Title and description are required / Invalid budget.
- `500`: Database error.

---

#### GET /jobs/list.php
Retrieves a list of currently open job postings. This endpoint supports filtering by keywords, budget ranges, and specific skills.

**Authentication**:
- Requires an active session with a valid `user_id`.
- The authenticated user must possess the `freelancer` role.

**Request**:
```
Query Parameters
```
| Field        | Type     | Required | Description                                   | Example             |
|--------------|----------|----------|-----------------------------------------------|---------------------|
| `search`     | `string` | No       | A keyword to filter jobs by title or description. Uses `LIKE` matching.| `API development`   |
| `min_budget` | `float`  | No       | The minimum budget for jobs to be returned. Defaults to 0.| `500.00`            |
| `max_budget` | `float`  | No       | The maximum budget for jobs to be returned. Defaults to a very high value.| `2500.00`           |
| `skill`      | `string` | No       | Filters jobs that require a specific skill.   | `React`             |

**Response**:
```json
{
  "status": "success",
  "message": "Jobs fetched successfully",
  "data": {
    "jobs": [
      {
        "id": 15,
        "title": "React Frontend Developer",
        "description": "Seeking a skilled React developer for a dynamic web application.",
        "budget": "800.00",
        "status": "open",
        "created_at": "2023-10-26 14:30:00",
        "client_name": "Digital Solutions Inc.",
        "skills": ["React", "JavaScript", "HTML", "CSS"]
      },
      {
        "id": 16,
        "title": "E-commerce Backend with PHP",
        "description": "Develop a robust backend for an online store using PHP and MySQL.",
        "budget": "1200.00",
        "status": "open",
        "created_at": "2023-10-25 09:15:00",
        "client_name": "Shopify Pro",
        "skills": ["PHP", "MySQL", "API", "Stripe"]
      }
    ]
  }
}
```

**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: Only freelancers can view jobs.
- `500`: Database error.

---

#### POST /jobs/apply.php
Enables an authenticated freelancer to submit a proposal for an available job.

**Authentication**:
- Requires an active session with a valid `user_id`.
- The authenticated user must possess the `freelancer` role.

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field            | Type     | Required | Description                                 | Example                                |
|------------------|----------|----------|---------------------------------------------|----------------------------------------|
| `job_id`         | `integer`| Yes      | The unique identifier of the job to which the proposal is being submitted.| `15`                                   |
| `cover_letter`   | `string` | Yes      | A detailed cover letter outlining the freelancer's qualifications and approach.| `I have extensive experience with React and modern frontend frameworks...`|
| `proposed_amount`| `float`  | Yes      | The amount (in currency) the freelancer proposes for the job.| `750.00`                               |
| `estimated_days` | `integer`| Yes      | The estimated number of days required to complete the job.| `10`                                     |

**Response**:
```json
{
  "status": "success",
  "message": "Proposal submitted successfully"
}
```

**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: Only freelancers can apply to jobs.
- `405`: Method not allowed.
- `400`: All fields are required.
- `404`: Job not found or not open for proposals.
- `409`: You already applied to this job.
- `500`: Database error.

---

#### POST /jobs/manage.php?action=edit
Allows an authenticated client to modify details of a job posting they own. Clients can update the job's title, description, budget, and associated skills.

**Authentication**:
- Requires an active session with a valid `user_id`.
- The authenticated user must possess the `client` role.

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field         | Type     | Required | Description                                  | Example                             |
|---------------|----------|----------|----------------------------------------------|-------------------------------------|
| `job_id`        | `integer`| Yes      | The unique identifier of the job to be edited.| `15`                                |
| `title`         | `string` | Yes      | The updated title for the job.               | `Updated: React Frontend Specialist`|
| `description`   | `string` | Yes      | The revised detailed description of the job requirements.| `Revised scope: Need a React expert for a scalable SPA.`|
| `budget`        | `float`  | Yes      | The updated budget for the job.              | `850.00`                            |
| `skills`        | `string` | No       | A comma-separated list of updated skills for the job.| `React,Redux,TypeScript,APIs`       |

**Response**:
```json
{
  "status": "success",
  "message": "Job updated successfully"
}
```

**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: Only clients can manage jobs.
- `405`: Method not allowed.
- `400`: Title and description are required.
- `404`: Job not found or not owned by you.
- `500`: Database error.

---

#### POST /jobs/manage.php?action=delete
Enables an authenticated client to permanently delete a job posting they own.

**Authentication**:
- Requires an active session with a valid `user_id`.
- The authenticated user must possess the `client` role.

**Request**:
```
Content-Type: application/x-www-form-urlencoded
```
| Field         | Type     | Required | Description             | Example |
|---------------|----------|----------|-------------------------|---------|
| `job_id`        | `integer`| Yes      | The unique identifier of the job to be deleted.| `16`    |

**Response**:
```json
{
  "status": "success",
  "message": "Job deleted successfully"
}
```

**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: Only clients can manage jobs.
- `405`: Method not allowed.
- `404`: Job not found or not owned by you.
- `500`: Database error.

---

#### GET /jobs/view_proposals.php
Allows an authenticated client to retrieve a list of all proposals submitted for a specific job they own.

**Authentication**:
- Requires an active session with a valid `user_id`.
- The authenticated user must possess the `client` role.

**Request**:
```
Query Parameters
```
| Field   | Type     | Required | Description               | Example |
|---------|----------|----------|---------------------------|---------|
| `job_id`| `integer`| Yes      | The unique identifier of the job to view proposals for.| `15`    |

**Response**:
```json
{
  "status": "success",
  "message": "Proposals fetched successfully",
  "data": {
    "proposals": [
      {
        "proposal_id": 101,
        "cover_letter": "I am highly proficient in React and confident I can deliver this project within your timeline.",
        "proposed_amount": "750.00",
        "estimated_days": 10,
        "status": "pending",
        "freelancer_id": 25,
        "username": "frontend_master",
        "full_name": "Maria Garcia",
        "rating": "4.80",
        "profile_image": "https://example.com/profiles/maria.jpg"
      },
      {
        "proposal_id": 102,
        "cover_letter": "As a seasoned JavaScript developer, I can tackle your React project with efficiency.",
        "proposed_amount": "800.00",
        "estimated_days": 12,
        "status": "pending",
        "freelancer_id": 26,
        "username": "js_wizard",
        "full_name": "David Lee",
        "rating": "4.20",
        "profile_image": null
      }
    ]
  }
}
```

**Errors**:
- `401`: Unauthorized. Please log in.
- `403`: Only clients can view proposals / You are not authorized to view proposals for this job.
- `400`: Job ID required.
- `500`: Database error.

---

## Technologies Used

| Technology | Description                                         |
|------------|-----------------------------------------------------|
| ![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) | The core scripting language for the entire backend API. |
| ![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white) | The relational database system storing all platform data. |
| ![Composer](https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white) | The dependency manager used for PHP libraries like `firebase/php-jwt`. |
| ![JWT](https://img.shields.io/badge/JWT-000000?style=for-the-badge&logo=json-web-tokens&logoColor=white) | Used for generating secure authentication tokens upon user login. |
| ![DotEnv](https://img.shields.io/badge/DotEnv-E74C3C?style=for-the-badge&logo=dotenv&logoColor=white) | A library for loading environment variables from a `.env` file. |

## License
This project is open-source.

## Author Info
**Your Name/Team Name**
- **Email**: [Your Email Here]
- **LinkedIn**: [Your LinkedIn Profile Here]
- **Twitter**: [Your Twitter Handle Here]

---

## Badges
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)
![Database](https://img.shields.io/badge/Database-MySQL-orange)
![Authentication](https://img.shields.io/badge/Auth-Session%20Based-purple)
![License](https://img.shields.io/badge/License-Open%20Source-green)

[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)