# Student Late Monitor (SLM)

<p align="center">
  <img src="https://img.shields.io/github/languages/top/rajkumar-tech-2002/project_late_monitor?style=for-the-badge&color=important" alt="Top Language">
  <img src="https://img.shields.io/github/repo-size/rajkumar-tech-2002/project_late_monitor?style=for-the-badge&color=brightgreen" alt="Repo Size">
  <img src="https://img.shields.io/github/last-commit/rajkumar-tech-2002/project_late_monitor?style=for-the-badge&color=blue" alt="Last Commit">
  <img src="https://img.shields.io/github/stars/rajkumar-tech-2002/project_late_monitor?style=for-the-badge&color=yellow" alt="Stars">
</p>

A professional web-based application to monitor and track student late entries. This system allows for scanning student IDs, recording entry times, and generating comprehensive reports.

## 🚀 Features

- **🔍 Scan Entry**: Quickly record student late entries using ID scanning.
- **📊 Reporting**: Generate daily and monthly reports of late entries.
- **📄 Student Summary**: View aggregated data for individual students, including total and monthly counts.
- **📥 Export**: Export reports to Excel and CSV formats for further analysis.
- **💻 Responsive UI**: Built with a clean interface using DataTables for easy data management.

## 🛠 Tech Stack

- ![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white) **PHP**: Server-side logic.
- ![SQL Server](https://img.shields.io/badge/SQL_Server-CC2927?style=flat-square&logo=microsoft-sql-server&logoColor=white) **SQL Server**: Database for storing student and entry records.
- ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black) **JavaScript/jQuery**: Client-side interactions.
- ![CSS](https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3&logoColor=white) **CSS**: Custom styling for a professional look.
- ![IIS](https://img.shields.io/badge/IIS-0078D7?style=flat-square&logo=microsoft&logoColor=white) **Deployment**: Optimized for IIS servers.

## ⚙️ Installation & Setup

1. **Clone the repository**:

   ```bash
   git clone https://github.com/rajkumar-tech-2002/project_late_monitor.git
   cd project_late_monitor
   ```

2. **Database Configuration**:
   - Create a database named `SLM` in SQL Server.
   - Use the structure expected by the PHP files.
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

## 📖 Usage

- Navigate to `index.php` to start scanning student entries.
- Use `report.php` to view and export late entry logs.
- Check `student_summary.php` for a high-level overview of student performance.

## 👤 Developer

Developed with ❤️ by **Rajkumar Anbazhagan**.

---

<p align="center">
  © 2026 Student Late Monitor Project
</p>
