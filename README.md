# Laravel Skill Test

## 1. Overview

Implement RESTful routes for a `Post` model using Laravel 12. Posts support **drafts**, **scheduled publishing**, and **authenticated user operations**.

## 2. Workflow

1. Clone this repository and set up the local environment.
2. Change the remote repository to your own public repository (do **not** delete commit history)
3. Implement the required features described below.
4. Push all changes to your public repository and let us know the repository URL.

## 3. Specifications

### General

- Follow Laravel 12 best practices and official documentation (https://laravel.com/docs/12.x)
- Write clear, meaningful commit messages with reasonable commit sizes
- View files are **not required**

### Post Status

- The `posts` table already exists.
- You are expected to understand how to determine whether a post is a draft, scheduled, or published by carefully examining the table structure, seed data, and business requirements.
- Scheduled posts do not require a cron job. They will be published automatically when the publish date comes.

### Authentication

- Use Laravel’s built-in **session & cookie-based authentication**
- Token-based systems (Sanctum, Passport, etc.) are not required

## 4. Requirements

### 4-1. `posts.index`

- Retrieve a paginated list of active posts (20 per page)
- Include the user (author) data for each post
- Exclude draft and scheduled posts
- Return a JSON response in a format suitable for passing to views

### 4-2. `posts.create`

- Only authenticated users can access this route
- Return the string `posts.create`

### 4-3. `posts.store`

- Only authenticated users can create new posts
- Validate submitted data before creating the post
- Return an appropriate response for a `POST` request

### 4-4. `posts.show`

- Retrieve a single active post
- Return a JSON response in a format suitable for passing to views
- Return **404** if the post is a draft or scheduled

### 4-5. `posts.edit`

- Only the post author can access this route
- Return the string `posts.edit`

### 4-6. `posts.update`

- Only the post author can update the post
- Validate submitted data before updating the post
- Return an appropriate response for a `PUT/PATCH` request

### 4-7. `posts.destroy`

- Only the post author can delete the post
- Return an appropriate response for a `DELETE` request

## Recommended Environment

- PHP 8.4
- Node v22.15.0
- Database: SQLite
- Server: Laravel built-in server

## Database Seeding

Sample users and posts are provided.

```bash
php artisan db:seed
```

## Test Results

```
  PASS  Tests\Unit\ExampleTest
  ✓ that true is true

  PASS  Tests\Feature\Auth\AuthenticationTest
  ✓ login screen can be rendered
  ✓ users can authenticate using the login screen
  ✓ users can not authenticate with invalid password
  ✓ users can logout

  PASS  Tests\Feature\Auth\EmailVerificationTest
  ✓ email verification screen can be rendered
  ✓ email can be verified
  ✓ email is not verified with invalid hash

  PASS  Tests\Feature\Auth\PasswordConfirmationTest
  ✓ confirm password screen can be rendered
  ✓ password can be confirmed
  ✓ password is not confirmed with invalid password

  PASS  Tests\Feature\Auth\PasswordResetTest
  ✓ reset password link screen can be rendered
  ✓ reset password link can be requested
  ✓ reset password screen can be rendered
  ✓ password can be reset with valid token

  PASS  Tests\Feature\Auth\RegistrationTest
  ✓ registration screen can be rendered
  ✓ new users can register

  PASS  Tests\Feature\DashboardTest
  ✓ guests are redirected to the login page
  ✓ authenticated users can visit the dashboard

  PASS  Tests\Feature\Settings\PasswordUpdateTest
  ✓ password can be updated
  ✓ correct password must be provided to update password

  PASS  Tests\Feature\Settings\ProfileUpdateTest
  ✓ profile page is displayed
  ✓ profile information can be updated
  ✓ email verification status is unchanged when the email address is unchanged
  ✓ user can delete their account
  ✓ correct password must be provided to delete account

  Tests:  26 passed (63 assertions)
  Duration: 1.60s
```
