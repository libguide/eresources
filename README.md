
# PHP + MySQL Resource Portal (CSV Import/Export)

A minimal portal to display library resources (title, publisher, subject, year, link),
with CSV import and CSV export.

## Features
- Display table with columns: Title, Publisher, Subject, Year, Link
- Optional search (Title/Publisher/Subject)
- Upload a CSV to import (headers required: `title,publisher,subject,year,link`)
- Export all records as CSV
- Uses PDO with prepared statements

## Quick Start

1) Create a MySQL database (e.g., `resource_portal`) and user.
   ```sql
   CREATE DATABASE resource_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'portal_user'@'%' IDENTIFIED BY 'strongpassword';
   GRANT ALL PRIVILEGES ON resource_portal.* TO 'portal_user'@'%';
   FLUSH PRIVILEGES;
   ```

2) Import the schema:
   ```bash
   mysql -u portal_user -p resource_portal < schema.sql
   ```

3) Update `config.php` with your DB credentials.

4) Copy all files to your PHP server (Apache/Nginx with PHP 8+). Make sure `uploads/` is writable:
   ```bash
   mkdir -p uploads
   chmod 775 uploads
   ```

5) Open `index.php` in your browser.

## CSV Format
- Header row is required and must exactly be: `title,publisher,subject,year,link`
- Example:
  ```csv
  title,publisher,subject,year,link
  Data Science 101,O'Reilly,Data,2021,https://example.com/ds101
  Library Automation Basics,Springer,Library Science,2019,https://example.com/lab
  ```

## Files
- `config.php` – database credentials
- `db.php` – PDO connection helper
- `schema.sql` – table DDL
- `index.php` – list view + search + upload form + export link
- `import.php` – CSV processing + import
- `export.php` – CSV export of all rows
- `sample.csv` – example CSV


---

## New: Edit/Delete & Auto-Dedupe

- **Edit/Delete**: On `index.php`, each row has **Edit** and **Delete** (admin login required).
  - `edit.php` updates fields.
  - `delete.php` removes a record.
- **Auto-Remove Duplicates**:
  - Table now has a deterministic `checksum` (MD5 over normalized fields) with a **UNIQUE** index.
  - Imports use `INSERT IGNORE`, so rows that would duplicate an existing record are skipped automatically.
  - To clean up historical duplicates once, run:
    ```bash
    php dedupe.php
    ```

### Schema Migration (if upgrading an existing database)
If you have an existing `resources` table, run these:
```sql
ALTER TABLE resources
  ADD COLUMN checksum CHAR(32) GENERATED ALWAYS AS (
    MD5(CONCAT_WS('|',
      LOWER(TRIM(title)),
      LOWER(TRIM(COALESCE(publisher,''))),
      LOWER(TRIM(COALESCE(subject,''))),
      LPAD(COALESCE(CAST(year AS CHAR),''),4,'0'),
      LOWER(TRIM(COALESCE(link,'')))
    ))
  ) STORED,
  ADD UNIQUE KEY uq_resources_checksum (checksum);
```
Then (optionally) remove old duplicates:
```bash
php dedupe.php
```
