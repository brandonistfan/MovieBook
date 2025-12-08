# MovieBook

MovieBook is a very small PHP/MySQL app where people can register, log in, and leave one review per movie. Pages include a movie list, movie detail with reviews, search, and a profile page that lists your own reviews.

## Requirements
- PHP 7.4+
- MySQL (or MariaDB)

## How to run
1. Import `moviebook.sql` into your MySQL database (`mysql -u user -p < moviebook.sql`).
2. Leave `config/database.php` as is.
3. Start a dev server from the project root with `php -S localhost:8000` and visit `http://localhost:8000` in your browser.
