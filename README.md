# SkillShare Local

PHP/MySQL coursework platform with learner/instructor/admin roles, sessions, bookings and ratings.

## Overview

PHP/MySQL skill-session platform with learner/instructor/admin roles, session creation, booking, ratings and suspension management.

## Features

Role authorization, prepared queries, relational schema, session CRUD, bookings, rating workflow.

## Tech Stack

PHP / MySQL

## Getting Started

Requires PHP with PDO MySQL and a local MySQL/MariaDB database. Import `sql/schema.sql`, then the explicitly labeled demonstration seed `sql/seed.sql`. Check `includes/config.php` for the original local database name and root/empty-password development defaults. Serve the `public` directory with PHP, for example `php -S localhost:8000 -t public`, and visit localhost:8000. This is historical local coursework; debug diagnostics and session/CSRF behavior have not been validated for deployment. The seed contains deliberate demo accounts; original account database exports are excluded.

## Validation

Lightweight local checks: JavaScript syntax, PHP syntax. Compilation and syntax checks do not verify application behavior. Source is preserved; interactive application behavior was not executed during archival.

## Notes

Original implementation is preserved. Build outputs, dependencies, machine-specific IDE state, backups, submission documents and private runtime data are excluded. No license has been inferred for the original work.

Academic context: the original footer identifies ACSC476 Project #1.
