# IELTS AI Pro v2.0 — Complete Local XAMPP & Online HTTPS Package

An end-to-end **IELTS Academic & General Training** preparation and AI assessment platform built with **PHP 8+, MySQL (PDO), Vanilla JavaScript, and HTML5/CSS3**. Pre-configured for **1-click local XAMPP setup** and ready for online HTTPS deployment.

---

## 1. Quick Start on Local XAMPP Server (Windows / macOS / Linux)

### Step 1: Extract the Project Folder
1. Extract this ZIP archive so the `ielts-ai-pro` folder is placed inside your XAMPP `htdocs` directory:
   - **Windows:** `C:\xampp\htdocs\ielts-ai-pro\`
   - **macOS:** `/Applications/XAMPP/htdocs/ielts-ai-pro/`
   - **Linux:** `/opt/lampp/htdocs/ielts-ai-pro/`

### Step 2: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache** and **MySQL** (ensure both turn green).

### Step 3: Run the 1-Click Browser Installer
1. Open your browser and visit:
   ```text
   http://localhost/ielts-ai-pro/install.php
   ```
2. Click **"Initialize Database & Demo Data Now"**.
   - This automatically creates the `ielts_ai_pro` UTF-8 database (`utf8mb4_unicode_ci`), imports `database/schema.sql` and `database/seed.sql`, and generates the demo Admin and Student accounts with fresh bcrypt password hashes.
   - *(Alternative Manual Import)*: You can also open `http://localhost/phpmyadmin`, create a database named `ielts_ai_pro`, and import `database/schema.sql` followed by `database/seed.sql`.

### Step 4: Sign In with Demo Credentials
Visit `http://localhost/ielts-ai-pro/login.php` and sign in:

| Role | Email | Password | Purpose |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@ieltspro.local` | `Admin@12345` | Manage users, create tests, view all student attempts, configure AI API keys |
| **Student 1** | `student@ieltspro.local` | `Student@12345` | Take Reading, Listening, Writing, and Speaking exams; view private results |
| **Student 2** | `sara@ieltspro.local` | `Student@12345` | Secondary student account to verify strict student data isolation (`403 Forbidden`) |

---

## 2. Deploying to an Online PHP/MySQL Host with HTTPS

1. **Create Database & User:** In your hosting control panel (cPanel / Hostinger / Plesk), create a MySQL database and user with full privileges on that database.
2. **Import SQL Schema:** Open phpMyAdmin on your host and import `database/schema.sql` and `database/seed.sql`.
3. **Upload Files:** Upload all project files to `public_html/` (or a subdomain root).
4. **Configure Credentials:** Copy `config/config.example.php` to `config/config.php` and update:
   - `APP_ENV` -> `'production'`
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` -> Your live hosting database credentials.
5. **Enable HTTPS Redirect:** Open `.htaccess` and uncomment the two production HTTPS rewrite lines:
   ```apache
   RewriteCond %{HTTPS} !=on
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

---

## 3. Completed & Audited Features

* **Authentication & Security (`includes/auth.php`, `includes/csrf.php`):**
  - Bcrypt password hashing (`password_hash` / `password_verify`) and `session_regenerate_id(true)` on login.
  - Timing-safe CSRF token verification (`hash_equals`) across all forms and AJAX endpoints.
  - Strict student data isolation (`WHERE user_id = :uid` and `fetch_authorized_attempt()`) preventing students from viewing other users' attempts (`403 Forbidden`).
  - Admin-only guard (`require_admin()`) on every page inside `/admin/`.
* **Official IELTS Band Scoring Engine (`includes/scoring.php`):**
  - Official half-band rounding (`round_ielts_band`: `.25` rounds to `.5`, `.75` rounds to next whole band).
  - Official 40-item Academic and General Training Reading & Listening raw-to-band conversion tables with proportional scaling for shorter practice sets.
  - Official Writing weighted formula (`(Task1 + 2 * Task2) / 3`) and 4-criteria rubric calculation.
* **All 4 IELTS Exam Modules & Interactive Timers:**
  - **Reading (`reading.php`):** Split-screen passage reader, text highlighter, MCQ / True-False-Not Given / Fill-in-the-Blank questions, and auto-submitting countdown timer.
  - **Listening (`listening.php`):** Built-in offline Web Speech API audio synthesizer + transcript toggle and timed question submission.
  - **Writing (`writing.php`):** Task 1 (150+ words) & Task 2 (250+ words) with live word counters and AI band evaluation.
  - **Speaking (`speaking.php`):** Parts 1, 2 (Cue Card 1-min prep + 2-min speech timer), and 3 with browser microphone Speech-to-Text recording.
* **Hybrid AI Evaluation (`includes/ai_helper.php`):**
  - Works **100% offline out-of-the-box on XAMPP** via the built-in Linguistic IELTS Rubric Analyzer.
  - Supports live **Google Gemini API (`gemini-2.5-flash`)** and **OpenAI API (`gpt-4o-mini`)** keys configured in `admin/settings.php` or `config/config.php`.
