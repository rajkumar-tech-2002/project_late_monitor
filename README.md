# 🎓 Student Late Monitor (SLM)

**A Web-Based Student Late Entry Monitoring System**  
_Developed and Deployed in College Working Environment_

---

## 📌 Project Overview

**Student Late Monitor (SLM)** is a professional web-based application designed to record, track, and analyze student late entries within an institutional environment. The system enables real-time ID scanning, automated time logging, and structured reporting for administrative monitoring.

This project is successfully deployed in our college local server environment using **Internet Information Services (IIS)** and connected to **Microsoft SQL Server**.

---

## 🎯 Objectives

- Digitize student late entry recording process
- Reduce manual errors in attendance tracking
- Provide structured reporting for administration
- Maintain centralized database records
- Deploy within secure campus network

---

## 🚀 Features

- **🔍 Student ID Scan Entry**: Real-time barcode/ID scanning.
- **🕒 Automatic Time Recording**: Precision logging of entry times.
- **📊 Daily & Monthly Reports**: Automated report generation.
- **📄 Student-wise Summary**: Aggregated individual performance tracking.
- **📥 Excel & CSV Export**: Export data for offline analysis.
- **💻 Responsive User Interface**: Modern, mobile-friendly design.
- **🏫 Local Server Deployment**: Fully optimized for IIS.

---

## 🛠 Tech Stack

| Layer         | Technology                    |
| :------------ | :---------------------------- |
| **Backend**   | PHP                           |
| **Database**  | Microsoft SQL Server          |
| **Frontend**  | HTML, CSS, JavaScript, jQuery |
| **UI Tables** | DataTables                    |
| **Server**    | IIS (Windows Server)          |

---

## 🏢 Deployment Environment

- Hosted on **IIS (College Local Server)**
- Connected to centralized **SQL Server database**
- Accessible within internal campus network
- Designed for institutional administrative use

---

## ⚙️ Installation & Configuration

### 1️⃣ Clone Repository

```bash
git clone https://github.com/rajkumar-tech-2002/project_late_monitor.git
cd project_late_monitor
```

### 2️⃣ Database Setup

- **Create Database**: `SLM`
- **Import Tables**: Set up the required schema in SQL Server.
- **Configure `db.php`**:

```php
$serverName = 'YOUR_SERVER_IP';
$database   = 'SLM';
$username   = 'YOUR_USERNAME';
$password   = 'YOUR_PASSWORD';
```

### 2️⃣ IIS Configuration

- Install **IIS** and **PHP**.
- Enable **SQL Server Drivers** (`sqlsrv`).
- Create a website or virtual directory in IIS Manager.
- Restart IIS and access your application.

---

## 📂 Project Structure

```text
project_late_monitor/
│
├── index.php             # Core scanning interface
├── report.php            # Daily/Monthly reports
├── student_summary.php   # Individual statistics
├── db.php                # Database connection
├── assets/               # CSS, JS, and Images
└── README.md             # Project documentation
```

---

## 📊 Modules

| Module               | Description                     |
| :------------------- | :------------------------------ |
| **Entry Module**     | Records student late entries    |
| **Reporting Module** | Generates daily/monthly reports |
| **Summary Module**   | Displays individual statistics  |
| **Export Module**    | Download Excel/CSV reports      |

---

## 🔐 Security Considerations

- Internal network access only
- Database authentication enabled
- Structured query execution (PDO)
- Server-level access control

---

## 👨‍💻 Developer Information

- **Name**: Rajkumar Anbazhagan
- **Role**: Full Stack Developer
- **Project Type**: Working Environment College Project
- **Year**: 2026

---

## 📜 License

This project is developed for institutional use within the college environment.

---

<p align="center">
  © 2026 Student Late Monitor Project
</p>
