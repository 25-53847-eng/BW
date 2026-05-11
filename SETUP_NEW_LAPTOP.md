# 🚀 BW Database Setup Guide - Para sa Bagong Laptop

> **Pag lilipat ng BW folder sa ibang laptop, sundin ang steps na ito para hindi mag-error**

---

## Step 1: Copy the Folder
- Copy ang buong **BW** folder sa `C:\xampp\htdocs\`

---

## Step 2: Create Database sa phpMyAdmin

1. **Open phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **"New"** (or **"Databases"**)
3. Enter database name: `bw_gas_detector`
4. Charset: **utf8mb4_unicode_ci**
5. Click **"Create"**

---

## Step 3: Import the Database (CHOOSE ONE)

### ✅ OPTION A: Import Main Database (RECOMMENDED)
If gusto mo ng complete database with existing data:

1. Click sa bagong database `bw_gas_detector`
2. Click **"Import"** tab
3. Select file: **bw_gas_detector.sql** ← **USE THIS!**
4. Click **"Import"** button
5. Wait for success message ✓

### ⚠️ OPTION B: Fresh Schema Only
If brand new setup lang at walang existing data:

1. Click sa bagong database `bw_gas_detector`
2. Click **"Import"** tab  
3. Select file: **backups/DATABASE_SCHEMA.sql**
4. Click **"Import"** button
5. Wait for success message ✓

---

**💡 Pro tip:** Most of the time, use **bw_gas_detector.sql** (it has everything)

---

## Step 4: Verify Database Tables

After import, dapat makita mo sa **bw_gas_detector** database:
- ✓ `delivery_records` table (with `unit_type` column)
- ✓ Views: `vw_inventory`, `vw_sales`, `vw_inquiry`, etc.

**To check:** Open a PHP file in the BW folder → should work na walang error

---

## Step 5: Configure db_config.php (if needed)

Open `db_config.php` at verify:

```php
$servername = "localhost";      // DB server
$username = "root";             // MySQL username (usually "root" sa XAMPP)
$password = "";                 // Password (usually empty sa XAMPP)
$dbname = "bw_gas_detector";    // Database name
```

If localhost, username, o password ay different sa ibang laptop, i-update dito.

---

## ⚠️ Common Issues & Fixes

### Error: "Unknown column 'unit_type'"
**Cause:** Old DATABASE_SCHEMA.sql used (before May 11, 2026)  
**Fix:** Re-import ang latest **DATABASE_SCHEMA.sql**

### Error: "Access denied for user 'root'"
**Cause:** MySQL password is different  
**Fix:** Update ang `db_config.php` with correct credentials

### Error: "Database 'bw_gas_detector' doesn't exist"
**Cause:** Database hindi naka-create  
**Fix:** Go back to Step 2 and create it manually

---

## 🎯 Quick Checklist

- [ ] BW folder copied sa `C:\xampp\htdocs\`
- [ ] XAMPP running (Apache + MySQL)
- [ ] `bw_gas_detector` database created in phpMyAdmin
- [ ] DATABASE_SCHEMA.sql imported successfully
- [ ] `delivery_records` table visible with columns
- [ ] db_config.php settings match your laptop setup
- [ ] index.php loads without errors

---

**Kapag ready na, open:** `http://localhost/BW/index.php`

Should work perfect! No errors na! 🎉
