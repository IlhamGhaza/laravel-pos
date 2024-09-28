
# Laravel POS (Point of Sale) System

This is a Point of Sale (POS) system built with Laravel and Filament.

## Features

- User authentication and authorization
- Product management
- Inventory management
- Sales and transactions
- Reporting and analytics
- Role-based access control using Filament Shield

## Requirements

- PHP 8.1+
- Composer
- MySQL or compatible database
- Node.js and NPM

## Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/IlhamGhaza/laravel-pos.git
   ```

2. Navigate to the project directory:

   ```bash
   cd laravel-pos
   ```

3. Install PHP dependencies:

   ```bash
   composer install
   ```

4. Create a copy of the `.env.example` file and rename it to `.env`:

   ```bash
   cp .env.example .env
   ```

5. Generate an application key:

   ```bash
   php artisan key:generate
   ```

6. Configure your database settings in the `.env` file.

7. Run database migrations:

   ```bash
   php artisan migrate
   ```

   or
   
   ```bash
   php artisan migrate:fresh --seed 
   ```

9. Seed the database (optional):

   ```bash
   php artisan db:seed
   ```

10. Compile assets:

    ```bash
    npm run dev
    ```

11. Start the development server:

    ```bash
    php artisan serve
    ```

## Setting up Filament Shield

Filament Shield is used for role-based access control in this project. To set it up:

1. Run the installation command:

    ```bash
    php artisan shield:install
    ```

2. Generate permissions for your resources:

    ```bash
    php artisan shield:generate
    ```

3. You can now use the `@role` and `@permission` directives in your Blade templates to control access to different parts of your application.

For more detailed information on using Filament Shield, please refer to the [official documentation](https://github.com/bezhanSalleh/filament-shield).

## Usage

After installation, you can access the POS system by navigating to `http://localhost:8000/admin` in your web browser. Log in with the credentials you created during the setup process.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
