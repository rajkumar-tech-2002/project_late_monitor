# Student Late Monitor (SLM)

A web-based application to monitor and track student late entries. This system allows for scanning student IDs, recording entry times, and generating comprehensive reports.

## Features

- **Scan Entry**: Quickly record student late entries using ID scanning.
- **Reporting**: Generate daily and monthly reports of late entries.
- **Student Summary**: View aggregated data for individual students, including total and monthly counts.
- **Export**: Export reports to Excel and CSV formats for further analysis.
- **Responsive UI**: Built with a clean interface using DataTables for easy data management.

## Tech Stack

- **PHP**: Server-side logic.
- **SQL Server**: Database for storing student and entry records.
- **JavaScript/jQuery**: Client-side interactions.
- **DataTables**: For interactive and searchable tables.
- **CSS**: Custom styling for a professional look.

## Installation & Setup

1. **Clone the repository**:

   ```bash
   git clone https://github.com/rajkumar-tech-2002/project_late_monitor.git
   cd project_late_monitor
   ```

2. **Database Configuration**:
   - Create a database named `SLM` in SQL Server.
   - Use the structure expected by the PHP files (see scan and report queries for details).
   - Copy `db.php.example` to `db.php`.
   - Update `db.php` with your SQL Server credentials:
     ```php
     $serverName = 'YOUR_SERVER_IP';
     $database = 'SLM';
     $username = 'YOUR_USERNAME';
     $password = 'YOUR_PASSWORD';
     ```

3. **Web Server**:
   - Host the project on a web server with PHP and SQL Server drivers installed (e.g., IIS, Apache, or Nginx).
   - Ensure `db.php` is correctly configured to connect to your database.

## Usage

- Navigate to `index.php` to start scanning student entries.
- Use `report.php` to view and export late entry logs.
- Check `student_summary.php` for a high-level overview of student performance.

## Developer

Developed by **Rajkumar Anbazhagan**.
