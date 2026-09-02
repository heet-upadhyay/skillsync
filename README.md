# Academia-Industry Collaboration Portal

A PHP + MySQL + XAMPP based web portal connecting **students**, **industries**, **academicians**, and **institutions** for internships, skill assessment, courses, and collaboration.

## Tech Stack
- **Backend:** PHP (procedural, mysqli, no framework)
- **Database:** MySQL (via phpMyAdmin)
- **Frontend:** HTML, CSS, Bootstrap 5, vanilla JavaScript

## Directory Structure
```
sih44/
├── index.php                 # Landing page
├── register.php              # Role selection + dynamic registration
├── login.php                 # Login + role-based redirect
├── logout.php                # Session destroy
├── student_dashboard.php     # Student dashboard home
├── skill_assessment.php      # MCQ skill quiz
├── internships.php           # Search/filter/apply internships
├── courses.php               # Recommended courses (by skill gaps)
├── portfolio.php             # Student projects/certificates/skills CRUD
├── application_tracker.php   # Application status table
├── profile.php               # Edit info, change password, consent (all roles)
├── industry_dashboard.php    # Industry placeholder dashboard
├── academician_dashboard.php # Academician placeholder dashboard
├── institution_dashboard.php # Institution placeholder dashboard
├── assets/
│   ├── css/style.css
│   └── js/register.js
├── includes/
│   ├── db_connect.php        # Shared mysqli connection + helpers
│   ├── auth_header.php / auth_footer.php
│   ├── page_header.php / page_footer.php          # Student dashboard layout
│   └── page_header_role.php / page_footer_role.php # Generic role layout
└── database/
    └── schema.sql            # Full DB schema + seed skills
```

## Setup (XAMPP)
1. **Start XAMPP** → start **Apache** and **MySQL**.
2. **Copy the folder** into the htdocs directory:
   `C:\xampp\htdocs\sih44` (the folder contents).
3. **Create the database:**
   - Open phpMyAdmin at `http://localhost/phpmyadmin`
   - Go to **Import** → choose `database/schema.sql` → **Go** (creates tables + seed skills).
   - Then import `database/seed_data.sql` → **Go** (creates demo accounts + sample courses/internships/skills).
4. **Open the portal:** `http://localhost/sih44/index.php`

## Demo Accounts (2-step login: pick role first, then email + password)
| Role      | Email              | Password    |
|-----------|--------------------|-------------|
| Student   | `student@demo.com` | `student123`|
| Teacher   | `teacher@demo.com` | `teacher123`|
| Industry  | `industry@demo.com`| `industry123`|
| College   | `college@demo.com` | `college123`|

Login is two-step: **Step 1** click your role card, **Step 2** enter email + password. The selected role must match the account's role.

## Database Credentials
Defaults in `includes/db_connect.php`:
```php
DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASS = ''        // empty by default in XAMPP
DB_NAME = 'academia_portal'
```

## Deploy on InfinityFree (free hosting)
1. Upload the whole `sih44` folder to InfinityFree via **File Manager** → `htdocs`.
2. In InfinityFree **cPanel**, open **MySQL Databases**:
   - Create a database → note its name (e.g. `if0_1234567_academia_portal`)
   - Note the **username** (e.g. `if0_1234567`)
   - Note the **host** (e.g. `sql104.infinityfree.com`)
3. Open **includes/db_connect.php** and fill in your 4 values:
   ```php
   define('DB_HOST', 'sql104.infinityfree.com');  // your host
   define('DB_USER', 'if0_1234567');             // your username
   define('DB_PASS', 'YOUR_INFINITYFREE_PASSWORD');
   define('DB_NAME', 'if0_1234567_academia_portal');
   ```
4. In cPanel **phpMyAdmin**, import (Paste SQL) the contents of:
   - `database/schema.sql`
   - `database/migration_industry.sql`
   - `database/seed_data.sql`
5. Open your site URL. Done.
> Note: InfinityFree's MySQL may not support JSON / certain ENUM columns on all plans. If `job_tests` import fails on the `questions JSON` column, change that column type to `LONGTEXT` — the PHP code already stores/reads it as text.

## Navigation (top navbar)
Each role has a **top navigation bar**:
- **Student:** Dashboard, Skill Test, Internships, Courses, Portfolio, Applications, Profile, Logout
- **Industry:** Dashboard, Post Internship / Job, Applications, About / Company, Logout
- **Teacher / College:** their role dashboard + Profile + Logout

## Industry Features
- **Post Internship / Post Job** (`industry_post.php`) — set title, description, required skills, salary, age limit, number of posts, duration, and mode.
- **Create a Test** per posting:
  - **Manual**: add your own MCQs and mark the correct answer.
  - **Auto-generate**: pick a skill + question count, and questions are auto-generated from the shared question bank.
- **Applications** (`industry_applications.php`) — see received requests with student info, filter by status/posting, and manage status (Test Pending / Shortlist / Reject). Status-chips show totals.
- **About / Company** (`industry_profile.php`) — update company info + about, and change password.

Opportunities (internships + jobs) share the same table; students see both on `internships.php` with type/salary/age/posts filters.

## Default User Roles
Registration allows four roles, each with role-specific fields:
- **Student** → college, course/branch, year → `student_details`
- **Industry** → company, type, size, website → `industry_details`
- **Academician** → college, department, designation → `academician_details`
- **Institution** → institution name, type, location → `institution_details`

## Security Notes
- All SQL uses **prepared statements** (mysqli).
- Passwords hashed with `password_hash()` / verified with `password_verify()`.
- Every protected page calls `require_role()` to enforce session-based role checks.
- Output escaped with `htmlspecialchars()` (helper `e()`) to prevent XSS.

## Seed / Sample Data
- `database/schema.sql` seeds the base `skills` list.
- `database/seed_data.sql` adds demo accounts for all 4 roles, sample student skill scores, courses, and internships.
