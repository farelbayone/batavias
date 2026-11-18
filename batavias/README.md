# BATAVIAS — Event Management Platform

A web-based Event Management Platform for event discovery, ticket booking, payment simulation, and event administration.

## Overview

Batavias is a full-stack web application with three primary interfaces: User, Admin, and Developer, plus an Organizer role for event management.

## Features

### User
- Browse and explore events.
- View event details.
- Book event tickets.
- View orders and transaction history.
- Access ticket information.

### Admin
- Manage events and categories.
- Manage users and organizers.
- Monitor orders and payments.
- View platform statistics.

### Developer
- Monitor API activity.
- Review application and system logs.
- Monitor technical activity and errors.

### Organizer
- Manage organization information.
- Create and manage events.
- Manage event ticket types.

## Demo Data

The SQL file includes fictional sample records for local development. Seeded account passwords are not provided; create or reset local account passwords before testing.

## Technology Stack

- PHP (Native PHP)
- MySQL
- HTML5
- CSS3
- JavaScript
- Laragon
- phpMyAdmin

## Installation

### Requirements
- PHP 8.x
- MySQL
- Laragon or another PHP development environment
- Web browser

### Steps

1. Clone the repository:

   ```bash
   git clone https://github.com/YOUR-USERNAME/batavias.git
   ```

2. Place the project inside Laragon's `www` directory.

3. Start Apache and MySQL.

4. Create the demo database:

   ```sql
   CREATE DATABASE batavias_demo
   CHARACTER SET utf8mb4
   COLLATE utf8mb4_unicode_ci;
   ```

5. Import `database/batavias_demo.sql` using phpMyAdmin.

6. Copy `.env.example` to `.env` in the project root. Update `DB_USER` and `DB_PASS` with your local MySQL credentials if needed. The `.env` file is ignored by Git. The default database name is `batavias_demo`.

7. Open the application:

   ```text
   http://localhost/batavias
   ```

## Project Structure

```text
batavias/
├── assets/
├── config/
├── database/
│   └── batavias_demo.sql
├── includes/
├── views/
├── .gitignore
├── README.md
└── index.php
```

The actual project structure may contain additional directories and files.

## Security Notes

- Keep your local `.env` file private; commit only `.env.example`.
- The bundled SQL contains fictional sample records for demonstration. Seeded account password hashes are placeholders and do not provide usable demo passwords.
- Set unique, strong credentials in your local environment. Never reuse production credentials in this project.
- Payment functionality in this project is a simulation only.

## Development Status

Batavias is an ongoing personal portfolio project. Features and improvements are implemented progressively.

## Author

Developed as a personal portfolio project.

**Batavias — Manage Events. Connect People. Create Experiences.**