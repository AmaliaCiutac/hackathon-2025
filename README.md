# Budget Tracker – Evozon PHP Internship Hackathon 2025

## Starting from the skeleton

Prerequisites:

- PHP >= 8.1 with the usual extension installed, including PDO.
- [Composer](https://getcomposer.org/download)
- Sqlite3 (or another database tool that allows handling SQLite databases)
- Git
- A good PHP editor: PHPStorm or something similar

About the skeleton:

- The skeleton is built on Slim (`slim/slim : ^4.0`)
- The templating engine of choice is Twig (`slim/twig-view`)
- The dependency injection container of choice is `php-di/php-di`
- The database access layer of choice is plain PDO
- The configuration should be provided in a .env file (`vlucas/phpdotenv`)
- There is logging support by using `monolog/monolog`
- Input validation should be simply done using `webmozart/assert` and throwing Slim dedicated HTTP exceptions

## Step-by-step set-up

Install dependencies:

```
composer install
```

Set up the database:

```
cd database
./apply_migrations.sh
```

Note: be aware that, if you are using WSL2 (Windows Subsystem for Linux), you'll have trouble opening SQLite databases
with a DB management app (PHPStorm, for example) in Windows **when they are stored within the virtualized WSL2 drive**.
The solution is to store the `db.sqlite` file on the Windows drive (`/mnt/c`) and configure the path to the file in the
application config (`.env`):

```
cd database
./apply_migrations.sh /mnt/c/Users/<user>/AppData/Local/Temp/db.sqlite
```

Copy `.env.example` to `.env` and configure as necessary:

```
cp .env.example .env
```

Run the built-in server on http://localhost:8000

```
composer start
```

## Features

## Tasks

### Before you start coding

Make sure you inspect the skeleton and identify the important parts:

- `public/index.php` - the web entry point
- `app/Kernel.php` - DI container and application setup
- classes under `app` - this is where most of your code will go
- templates under `templates` are almost complete, at least in terms of static mark-up; all you need is to make use of
  the Twig syntax to make them dynamic.

### Main tasks — for having a functional application

Start coding: search for `// TODO: ...` and fill in the necessary logic. Don't limit yourself to that; you can do
whatever you want, design it the way you see fit. The TODOs are a starting point that you may choose to use.

### Extra tasks — for extra points

Solve extra requirements for extra points. Some of them you can implement from the start, others we prefer you to attack
after you have a fully functional application, should you have time left. More instructions on this in the assignment.

### Deliver well designed quality code

Before delivering your solution, make sure to:

- format every file and make sure there is no commented code left, and code looks spotless

- run static analysis tools to check for code issues:

```
composer analyze
```

- run unit tests (in case you added any):

```
composer test
```

A solution with passing analysis and unit tests will receive extra points.

## Delivery details

Participant:
- Full name: Ciutac Amalia-Andreea
- Email address: ciutacamalia26@gmail.com


Features fully implemented:
- ...
- **Register**
  - Users can sign up through a secure form
  - Form has validation:
    - Username needs to be at least 4 characters
    - Password must have at least 8 characters and one number
    - You also need to confirm your password to avoid typos
  - Passwords are hashed using `password_hash()` before storing
  - After registering, users get redirected to the login page

- **Login**
  - Users log in with their saved credentials
  - Invalid credentials are rejected
  - If login is successful, a session is started
  - Then the user is redirected to the dashboard

- **Logout**
  - Ends the session and redirects back to login

- **Expense Management (CRUD)**

  - **List Expenses**
    - Shows a list of all expenses for the selected month and year
    - Pagination included (20 per page)
    - Sorted from newest to oldest
    - Each expense shows its description, amount, and category
    - Each entry also has edit and delete options

  - **Create Expense**
    - Users can add a new expense with description, date, category, and amount (> 0)
    - Form has validation, and it keeps the input if there are errors
    - After a successful save, it redirects back to the expense list

  - **Edit Expense**
    - Opens a form filled with the selected expense's info
    - Same validation rules as in the creation form
    - Only the person who created the expense can edit it

  - **Delete Expense**
    - Deletes the selected expense and redirects back to the list
    - Only the person who created the expense can delete it

- **Dashboard**
  - Shows total spending for the current month
  - Also breaks down expenses by category
  - If a category goes over budget, a warning is shown
