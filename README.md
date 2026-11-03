# RPMEIS — Philippine Army Recruitment Physical & Medical Examination Information System (PME v2.1)

Production-hardened web application for managing the Philippine Army Physical & Medical Examination workflow across Recruiter, Registrar, Station Clerk, Specialist Doctor, Chief Doctor, Executive, and Administrator portals.

---

## 1. Hostinger Deployment Guide

This project is pre-configured for direct deployment to **Hostinger** (Linux / Apache / LiteSpeed):

1. **Upload Files:**
   * Upload the contents of `philarmylab` to your Hostinger account (either into `public_html/` directly—protected by the root `.htaccess`—or one directory above `public_html` with `public/` mapped to `public_html`).
2. **Import MySQL Database:**
   * In Hostinger **hPanel → Databases → MySQL Databases**, create your database and user, then import your SQL dump via **phpMyAdmin**.
3. **Update `.env` with Hostinger Credentials:**
   * Edit `.env` and set your Hostinger domain and MySQL credentials:
     ```env
     APP_URL=https://yourdomain.com
     DB_HOST=127.0.0.1
     DB_DATABASE=u123456789_pme
     DB_USERNAME=u123456789_user
     DB_PASSWORD=your_hostinger_db_password
     SESSION_SECURE_COOKIE=true
     ```
4. **No Symlinks Required:**
   * `config/filesystems.php` is configured to use `public/storage` directly (`public_path('storage')`), and applicant photos are saved directly to `public/profile_pic` and `public/uploads`, so you do **not** need SSH or `php artisan storage:link` on Hostinger shared hosting.
