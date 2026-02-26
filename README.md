# 🎓 Student Late Monitor (SLM) 2.0

**A Premium Role-Based Student Late Entry Monitoring System**  
_Developed for Nandha Engineering College (Autonomous), Erode — Part of the **SmartNandha** Initiative._

---

## 📌 Project Overview

**Student Late Monitor (SLM)** is a state-of-the-art web application designed to digitize and streamline the monitoring of student late entries. Version 2.0 introduces **Role-Based Access Control (RBAC)**, advanced data management tools, and a premium administrative portal.

Transitioning from manual logs to high-speed hardware barcode scanning, SLM ensures 100% accuracy in attendance tracking, providing real-time statistics and automated reporting for various institutional stakeholders (Admins & HODs).

---

## 🚀 Key Features

### 🏢 Institutional Governance & RBAC

- **Multi-Role Access**: Dedicated portals for **Super Admins** (full control) and **Department HODs** (department-specific data).
- **Secure Authentication**: An ultra-premium login portal with role validation, session management, and encrypted data handling.
- **Isolated Kiosk Mode**: The student scanner interface is completely isolated from the administrative backend for enhanced security.

### 🔍 Advanced Monitoring & Reporting

- **Hardware Integration**: Optimized for plug-and-play barcode scanners for instant student recognition.
- **Live Stat Cards**: Displays real-time late entry counts (Total & Monthly) during the scanning process.
- **Smart Filtering**: Robust reporting with multi-dimensional filters (Date Range, Department, Class, Student Name, Reg No).
- **Dynamic Class Filtering**: In administrative views, the "Class" filter automatically adapts based on the selected department.
- **Aggregated Summaries**: High-level student performance reports tracking late-attendance trends.

### 🛠️ Data Management Tools

- **Advanced Deletion**:
  - **Single Delete**: Remove individual erroneous records.
  - **Bulk Delete**: Select multiple records using an interactive UI for mass removal.
  - **Range Delete (Admin Only)**: Securely clear data within a specific date and department range.
- **Data Export**: Seamlessly export any filtered report to **Excel / CSV** format for offline analysis or archival.

---

## 🛠️ Technology Stack

| Layer              | Technology                                              |
| :----------------- | :------------------------------------------------------ |
| **Backend**        | PHP 8.x (PDO with SQLSRV extension)                     |
| **Database**       | Microsoft SQL Server (10.11.16.250)                     |
| **UI Framework**   | Bootstrap 5.3 + Custom Premium CSS                      |
| **Interactions**   | jQuery, SweetAlert2 (Interactive Dialogs)               |
| **Data Tables**    | DataTables.net (Advanced Sorting & Pagination)          |
| **Visuals**        | CSS Mesh Gradients & Glassmorphism Design               |
| **Infrastructure** | Windows Server with IIS (Internet Information Services) |

---

## 🗄️ Database Architecture

### Table: `Student_Master`

_Registers of all eligible students._

| Column       | Type        |
| :----------- | :---------- |
| `Reg_Number` | PK, VARCHAR |
| `Name`       | VARCHAR     |
| `Department` | VARCHAR     |
| `Class`      | VARCHAR     |

### Table: `student_late_entry_monitor`

_Historical log of every late entry event._

| Column         | Type    |
| :------------- | :------ |
| `reg_no`       | VARCHAR |
| `student_name` | VARCHAR |
| `department`   | VARCHAR |
| `class`        | VARCHAR |
| `scan_date`    | DATE    |
| `entry_time`   | TIME    |

### Table: `users`

_System users with role-based permissions._

| Column     | Type                                               |
| :--------- | :------------------------------------------------- |
| `id`       | PK, INT                                            |
| `username` | VARCHAR (Unique, used as Department Name for HODs) |
| `password` | VARCHAR                                            |
| `role`     | VARCHAR ('admin', 'hod')                           |

---

## 📂 Project Structure

```bash
Late_Monitor/
├── index.php               # 🖥️ Student Kiosk (Isolated Scanner Interface)
├── login.php               # 🔐 Premium Staff Login Portal (Role Selection)
├── logout.php              # 🚪 Secure Session Termination
├── report.php              # 📊 Detailed Analytics & Data Management
├── footer.php              # 📄 Footer for all pages
├── student_summary.php     # 👤 Student Aggregated Performance Report
├── delete_handler.php      # ⚙️ Secure AJAX Handler for RBAC Deletions
├── db.php                  # 🔌 Centralized PDO Connection & Auth Helpers
├── assets/
│   └── images/
│       ├── nec_logo.png    # Official Institutional Logo
│       └── students/       # Student Directory (<reg_no>.jpg)
└── web.config              # IIS Server Configuration
```

---

## ⚙️ Installation & Deployment

1. **Environment Setup**: Ensure PHP 8.x and Microsoft ODBC Driver for SQL Server are installed.
2. **Database Configuration**: Import the schema into your SQL Server instance.
3. **App Secrets**: Rename `db.php.example` to `db.php` and update the connection string.
4. **IIS Hosting**:
   - Create a new site in IIS Manager.
   - Map the root directory to `Late_Monitor/`.
   - Grant the `IIS_IUSRS` group read/write permissions to the `assets/` folder.

---

## 👨‍💻 Developer Information

| Field           | Details                                        |
| :-------------- | :--------------------------------------------- |
| **Name**        | Rajkumar Anbazhagan                            |
| **Role**        | Lead Full Stack Developer                      |
| **Institution** | Nandha Engineering College (Autonomous), Erode |
| **Initiative**  | SmartNandha                                    |
| **Active Year** | 2026                                           |

---

<p align="center">
  <strong>SmartNandha — Empowering Institutional Excellence through Digital Innovation</strong><br>
  © 2026 Nandha Engineering College, Erode. All Rights Reserved.
</p>
