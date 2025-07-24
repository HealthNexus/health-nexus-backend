# Health Nexus Backend

This is the Laravel-based backend API for the Health Nexus project. It serves as a platform for sharing and accessing health-related information, including information about diseases, hospitals, and user-created posts.

## Core Features

-   **User Authentication:** The application provides endpoints for user registration, login, and logout, using Laravel Sanctum for API token-based authentication.
-   **Posts:** Registered users can create, view, update, and delete posts. This seems to be a central feature for users to share information or experiences.
-   **Comments and Replies:** The system supports a nested discussion structure where users can comment on posts and reply to other comments.
-   **Health Information:** The API provides access to a database of diseases, disease categories, and hospitals.
-   **Data Records:** There are endpoints to retrieve records of disease data, aggregated by month and day, which could be used for statistical analysis or reporting.
-   **Notifications:** The application has a notification system. For example, it sends a notification when a new post about a disease is created.

## Technical Details

-   **Framework:** The application is built with the [Laravel](https://laravel.com/) framework, a popular PHP web application framework.
-   **Database:** It uses a SQLite database, and the schema is managed through Laravel's migration system. The `database/migrations` directory contains the table definitions. The `database/factories` and `database/seeders` directories are used to populate the database with test data.
-   **API:** The application exposes a RESTful API, with routes defined in `routes/api.php`.
-   **API Documentation:** The project uses the `dedoc/scramble` package to automatically generate OpenAPI (Swagger) documentation for the API. The configuration for this is in `config/scramble.php`.
-   **Dependencies:** The project's dependencies are managed by [Composer](https://getcomposer.org/), as defined in `composer.json`.

## Models

The application uses the following database models, which represent the core entities of the system:

-   `User`: Represents a user of the application.
-   `Post`: Represents a user-created post.
-   `Comment`: Represents a comment on a post.
-   `Reply`: Represents a reply to a comment.
-   `Disease`: Represents a specific disease.
-   `Category`: Represents a category for diseases.
-   `Hospital`: Represents a hospital.
-   `Drug`: Represents a drug.
-   `Symptom`: Represents a medical symptom.
-   `Role`: Represents user roles (e.g., admin, user).

## How to Run the Project

1.  **Clone the repository.**
2.  **Install dependencies:** Run `composer install`.
3.  **Set up the environment:** Create a `.env` file by copying `.env.example` (if it exists) and configure your database connection.
4.  **Generate application key:** Run `php artisan key:generate`.
5.  **Run database migrations:** Run `php artisan migrate`.
6.  **(Optional) Seed the database:** Run `php artisan db:seed` to populate the database with sample data.
7.  **Start the development server:** Run `php artisan serve`.

The API will then be accessible at the URL provided by the development server (usually `http://127.0.0.1:8000`). You can view the API documentation by navigating to the appropriate route in your browser (typically `/docs/api`).
