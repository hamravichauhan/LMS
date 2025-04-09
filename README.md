# Library Management System Documentation

## System Overview

A comprehensive library management system with:

- **User Authentication**: Secure login system with role-based access control (admin, librarian, student)
- **Book Inventory**: Complete catalog management with detailed metadata
- **Reservation System**: Track book checkouts, returns, and late fees
- **Student Dashboard**: Personalized interface with recent activity and quick actions
- **Search Functionality**: Full-text search across book titles, authors, and metadata
- **Reporting**: Track reservations, overdue books, and user activity

**Technology Stack**:

- Frontend: HTML5, CSS3, JavaScript, Tailwind CSS
- Backend: PHP 8.2
- Database: MySQL/MariaDB
- Web Server: Apache/Nginx

## Database Schema

### Detailed Table Structure

#### books

```sql
CREATE TABLE `books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `status` enum('available','issued') DEFAULT 'available',
  `reserved_by` int(11) DEFAULT NULL,
  `ISBN` varchar(20) DEFAULT NULL,
  `images` varchar(255) NOT NULL,
  `copies` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `genre` varchar(100) DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` int(11) DEFAULT NULL,
  `edition` varchar(50) DEFAULT NULL,
  `book_summary` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ISBN` (`ISBN`),
  KEY `fk_reserved_by` (`reserved_by`),
  KEY `idx_books_title` (`title`),
  KEY `idx_books_author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Fields**:

- `status`: Tracks availability (available/issued)
- `reserved_by`: References user who reserved the book
- `images`: Path to book cover images
- `copies`: Number of available copies
- Full text search fields: title, author, ISBN, summary

**Indexes**:

- Optimized for title and author searches
- ISBN uniqueness constraint

#### users

```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `role` enum('admin','librarian','student') NOT NULL DEFAULT 'student',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Security Features**:

- Password storage: bcrypt hashing
- Email uniqueness constraint
- Session tokens for authentication
- Role-based permissions system

**User Roles**:

1. Admin: Full system access
2. Librarian: Book/reservation management
3. Student: Basic borrowing privileges

#### reservations

```sql
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `reservation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `book_taken_date` date DEFAULT NULL,
  `book_returned_date` date DEFAULT NULL,
  `expected_return_date` date DEFAULT NULL,
  `late_fee` decimal(10,2) DEFAULT 0.00,
  `days_overdue` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_reservation_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_reservation_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Business Logic**:

- Automatic status transitions:
  - Pending → Completed when book taken
  - Overdue calculation when past expected_return_date
- Late fee formula: ₹10 per day overdue
- Cascading deletes when users/books are removed

#### issued_books

- Records book checkouts with:
  - Issue and return dates
  - Links to users and books

#### search_history

- Logs user search queries

## Key Features

### Student Dashboard

- **Homepage** (`index.php`):

  - Displays recently added books in carousel
  - Quick search functionality
  - Recent searches section
  - Quick access links to main features

- **Reservations Management** (`reservations.php`):
  - View all reservations with status indicators
  - Filter by status (pending/active/overdue/completed)
  - Cancel pending reservations
  - Pay overdue fines
  - Visual indicators for overdue items

### Authentication System

- Role-based access control:
  - Admin: Full system access
  - Librarian: Book management
  - Student: Browse and reserve books

## Technical Architecture

### Frontend Implementation

**Core Components**:

1. **Dashboard (index.php)**:

   - Book carousel using Swiper.js
   - Search form with autocomplete
   - Quick stats widget
   - Responsive grid layout

2. **Reservations (reservations.php)**:
   - Interactive status cards
   - Overdue item highlighting
   - Action buttons (cancel/pay)
   - Pagination controls

**Key Libraries**:

- Tailwind CSS (utility-first styling)
- Font Awesome 6 (icons)
- Swiper 9 (touch-enabled carousels)
- Alpine.js (reactive components)

**Responsive Design**:

- Mobile-first approach
- Breakpoints: 640px, 768px, 1024px
- Flexible grid layouts
- Touch-friendly controls

### Backend Implementation

**Database Layer**:

- MySQL/MariaDB relational database
- Optimized indexes for common queries
- Foreign key constraints
- Transaction support for critical operations

**Business Logic**:

- Reservation workflow:
  ```php
  // Sample reservation code
  function createReservation($userId, $bookId) {
    // Check book availability
    // Validate user privileges
    // Calculate expected return date
    // Create reservation record
    // Update book status
  }
  ```

**Security Measures**:

- Prepared statements for all SQL queries
- Input validation/sanitization
- CSRF protection tokens
- Session regeneration
- Password hashing (bcrypt)

### Security Features

- Password hashing
- Input sanitization
- CSRF protection
- Session validation
- Role-based access control

## User Roles

### Admin

- Full system access
- User management
- System configuration

### Librarian

- Add/edit books
- Manage reservations
- Process checkouts/returns

### Student

- Browse books
- Make reservations
- View personal history
- Pay fines

## Installation & Setup

### System Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)
- 100MB disk space
- Composer (for dependencies)

### Deployment Steps

1. **Database Setup**:

   ```bash
   mysql -u root -p < auth_system.sql
   ```

2. **Configuration**:
   Edit `db/config.php`:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'library_user');
   define('DB_PASS', 'secure_password');
   define('DB_NAME', 'library_db');
   ```

3. **Web Server**:

   ```apache
   <VirtualHost *:80>
       DocumentRoot /path/to/library-system
       ServerName library.example.com
   </VirtualHost>
   ```

4. **Initial Admin Account**:
   ```sql
   INSERT INTO users (name, email, password, role)
   VALUES ('Admin', 'admin@example.com', '$2y$10$...', 'admin');
   ```

### Sample Queries

**Get Overdue Books**:

```sql
SELECT b.title, u.name, r.expected_return_date, r.days_overdue
FROM reservations r
JOIN books b ON r.book_id = b.id
JOIN users u ON r.user_id = u.id
WHERE r.status = 'completed'
AND r.expected_return_date < CURDATE()
ORDER BY r.days_overdue DESC;
```

**Monthly Statistics**:

```sql
SELECT
    DATE_FORMAT(reservation_date, '%Y-%m') AS month,
    COUNT(*) AS total_reservations,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
    SUM(late_fee) AS total_fines
FROM reservations
GROUP BY month
ORDER BY month DESC;
```
