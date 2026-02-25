# 🎓 Student Late Monitor (SLM)

**A Web-Based Student Late Entry Monitoring System**  
_Developed & Deployed at Nandha Engineering College (Autonomous), Erode_

---

## 📌 Project Overview

**Student Late Monitor (SLM)** is a professional, real-time web application built for **Nandha Engineering College** to digitally record, track, and analyze student late entries. The system uses **barcode ID card scanning** to instantly log late entries, display student profile details, and generate comprehensive administrative reports.

The application is successfully deployed on the **college's local IIS server** connected to a centralized **Microsoft SQL Server** database, accessible within the internal campus network under the **SmartNandha** initiative.

---

## 🎯 Objectives

- Eliminate manual late entry recording with barcode-based scanning
- Provide instant student recognition and entry confirmation (with photo)
- Maintain a centralized, persistent database of all late entry records
- Enable administrative filtering, analysis, and data export
- Generate per-student cumulative and monthly late entry summaries
- Support secure deployment within the college campus network

---

## 🚀 Features

| Feature                        | Description                                                                  |
| :----------------------------- | :--------------------------------------------------------------------------- |
| 🔍 **Barcode ID Scanning**     | Real-time student ID card scan via dedicated hardware barcode scanners       |
| 📸 **Student Photo Display**   | Auto-loads student profile photo after every successful scan                 |
| 🕒 **Precise Time Logging**    | Records exact scan date and time into the database instantly                 |
| 📊 **Live Statistics Display** | Shows total late days and current-month late count on each scan              |
| ✅ **Auto-Reset Interface**    | Screen auto-reloads after 6 seconds, ready for the next scan                 |
| 📋 **Advanced Report Page**    | Multi-filter attendance report (date range, department, class, name, reg no) |
| 👤 **Student Summary Report**  | Per-student aggregated view — monthly & cumulative late counts               |
| 📥 **Excel / CSV Export**      | One-click export of filtered data to `.csv` for offline analysis             |
| 🔢 **DataTables Pagination**   | Client-side paginated, sortable tables for all report views                  |
| 🖨️ **Print Support**           | Print-friendly layout (hides action buttons automatically)                   |
| 💻 **Responsive UI**           | Mobile-friendly layouts built with Bootstrap 5                               |

---

## 🛠️ Tech Stack

| Layer            | Technology                                     |
| :--------------- | :--------------------------------------------- |
| **Backend**      | PHP (with PDO for SQL Server)                  |
| **Database**     | Microsoft SQL Server (`SLM` database)          |
| **Frontend**     | HTML5, CSS3, JavaScript (ES6+)                 |
| **UI Framework** | Bootstrap 5.3                                  |
| **Icons**        | Bootstrap Icons                                |
| **Tables**       | DataTables 1.13.7 (jQuery plugin)              |
| **Fonts**        | Google Fonts — Outfit, Poppins                 |
| **Server**       | IIS (Internet Information Services) on Windows |
| **DB Driver**    | PHP `sqlsrv` / PDO SQLSRV extension            |

---

## 🗄️ Database Schema

### Database: `SLM`

#### Table: `Student_Master`

Stores master records of all students (pre-loaded).

| Column       | Type    | Description                           |
| :----------- | :------ | :------------------------------------ |
| `Reg_Number` | VARCHAR | Student register number (Primary Key) |
| `Name`       | VARCHAR | Full student name                     |
| `Department` | VARCHAR | Department name                       |
| `Class`      | VARCHAR | Class / section                       |
| `Gender`     | VARCHAR | Student gender                        |

#### Table: `student_late_entry_monitor`

Stores every individual late entry scan event.

| Column         | Type    | Description                          |
| :------------- | :------ | :----------------------------------- |
| `reg_no`       | VARCHAR | Student register number              |
| `student_name` | VARCHAR | Student full name                    |
| `department`   | VARCHAR | Department                           |
| `class`        | VARCHAR | Class / section                      |
| `scan_date`    | DATE    | Date of the late scan (`YYYY-MM-DD`) |
| `entry_time`   | TIME    | Time of the late scan (`HH:MM:SS`)   |
| `Gender`       | VARCHAR | Student gender                       |
| `Photo`        | VARCHAR | Photo filename (`<reg_no>.jpg`)      |

---

## 📂 Project Structure

```
Late_Monitor/
│
├── index.php               # 🖥️ Main scanner interface (Scan ID → Log & Display)
├── save_scan.php           # ⚙️ Backend API: validates scan, inserts record, returns JSON
├── report.php              # 📊 Detailed late attendance report with filters & export
├── student_summary.php     # 👤 Per-student aggregated summary report
├── db.php                  # 🔌 Shared SQL Server PDO database connection
├── db.php.example          # 📄 Example config (safe to commit publicly)
├── web.config              # 🌐 IIS configuration file
│
├── assets/
│   └── images/
│       ├── nec_logo.png          # College logo displayed in header
│       └── students/
│           ├── default.png       # Fallback photo if student photo is missing
│           └── <reg_no>.jpg      # Individual student photos (named by reg number)
│
└── README.md               # 📖 Project documentation
```

---

## 🔄 Application Workflow

```
1. Student scans ID card barcode on index.php
         ↓
2. Barcode data is sent via Fetch API to save_scan.php (POST)
         ↓
3. save_scan.php:
   a. Looks up student in Student_Master table
   b. Inserts a new row into student_late_entry_monitor
   c. Calculates total late days + current month late count
   d. Returns JSON response with student details & stats
         ↓
4. index.php displays:
   - Student photo, name, reg no, department, class, gender
   - Entry date & time
   - Total late count & current month count badges
   - "Scan Recorded Successfully" confirmation
         ↓
5. Page auto-reloads after 6 seconds for the next student
```

---

## 📊 Pages / Modules

### 1. `index.php` — Scanner Dashboard

- Full-width barcode input field (read-only between scans, activates only via scanner hardware)
- Animated student info card revealed after each successful scan
- Displays student photo with fallback to `default.png`
- Shows **Total Late Days** and **Current Month Late** count in colored stat cards
- Auto-reload after 6 seconds (4 seconds on error)

### 2. `save_scan.php` — Scan Processing API

- Accepts `POST` request with `reg_no`
- Validates student exists in `Student_Master`
- Inserts scan record with current timestamp
- Computes late statistics using SQL aggregation (`COUNT`, `MONTH/YEAR` filter)
- Returns structured `JSON` response (success or error)

### 3. `report.php` — Detailed Attendance Report

- Filter by: **Date Range**, **Department**, **Class**, **Register Number**, **Student Name**
- Displays tabular data with student photo thumbnails
- DataTables pagination (10 / 25 / 50 / 100 per page)
- **Export to CSV/Excel** button — streams filtered records as `.csv`
- Print-friendly layout
- Reset filter button clears all applied filters

### 4. `student_summary.php` — Student Summary Report

- Aggregated view: one row per student
- Shows **Monthly Late Count** (badge: orange) and **Total Late Count** (badge: red)
- Sortable by total late count (highest first by default)
- Filter by: Department, Class, Register Number, Student Name
- Export to CSV/Excel
- DataTables with built-in search within loaded results

---

## ⚙️ Installation & Configuration

### 1️⃣ Clone Repository

```bash
git clone https://github.com/rajkumar-tech-2002/project_late_monitor.git
cd project_late_monitor
```

### 2️⃣ Configure Database Connection

Copy the example config and edit with your actual credentials:

```bash
copy db.php.example db.php
```

Edit `db.php`:

```php
$serverName = 'YOUR_SQL_SERVER_IP';   // e.g., 192.168.1.100
$database   = 'SLM';
$username   = 'YOUR_SQL_USERNAME';
$password   = 'YOUR_SQL_PASSWORD';
```

> ⚠️ **Important**: Never commit `db.php` with real credentials. It is already in `.gitignore`.

### 3️⃣ SQL Server Setup

1. Create a database named `SLM`
2. Create the `Student_Master` table and import student records
3. Create the `student_late_entry_monitor` table (schema above)
4. Ensure the SQL Server user has **SELECT**, **INSERT**, and **READ** permissions

### 4️⃣ PHP Prerequisites

Ensure the following PHP extensions are enabled:

- `php_pdo_sqlsrv.dll`
- `php_sqlsrv.dll`

Install Microsoft ODBC Driver for SQL Server if not already installed.

### 5️⃣ IIS Configuration

1. Install **IIS** with **PHP** handler mapping enabled
2. Place project files in the IIS virtual directory (e.g., `C:\inetpub\wwwroot\SLM\`)
3. Set application pool to **No Managed Code**
4. Ensure `web.config` is present (already included in this project)
5. Grant IIS App Pool user **read/write** access to the `assets/` folder
6. Restart IIS and access the application at the configured URL

---

## 🔐 Security Notes

- `db.php` is excluded from version control via `.gitignore`
- All user inputs are sanitized using `htmlspecialchars()` for output
- SQL queries use **PDO prepared statements** to prevent SQL injection
- Barcode input field is `readonly` by default — only writable during active scanner input (prevents manual keyboard typing)
- Application is accessible only within the **internal campus network**

---

## 🏢 Deployment Details

| Parameter              | Value                                    |
| :--------------------- | :--------------------------------------- |
| **Server**             | IIS on Windows (College Local Server)    |
| **Database Host**      | `10.11.16.250` (Internal Network)        |
| **Database Name**      | `SLM`                                    |
| **PHP Driver**         | PDO SQLSRV                               |
| **Network Access**     | Internal campus LAN only                 |
| **Project Initiative** | SmartNandha — Nandha Engineering College |

---

## 👨‍💻 Developer Information

| Field            | Details                                        |
| :--------------- | :--------------------------------------------- |
| **Name**         | Rajkumar Anbazhagan                            |
| **Role**         | Full Stack Developer                           |
| **Institution**  | Nandha Engineering College (Autonomous), Erode |
| **Project Type** | Institutional Working Environment Project      |
| **Year**         | 2026                                           |
| **Initiative**   | SmartNandha                                    |

---

## 📜 License

This project is developed exclusively for institutional use at **Nandha Engineering College**, Erode. All rights reserved.

---

<p align="center">
  © 2026 Student Late Monitor | <strong>SmartNandha — Nandha Engineering College, Erode</strong>
</p>
