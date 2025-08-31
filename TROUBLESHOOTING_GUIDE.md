# 🔧 Troubleshooting Guide - Timeslot Cleanup

## 🚨 Common Issues and Solutions

### 1. "An error occurred while deleting old timeslots"

**Possible Causes:**
- Database connection issues
- Missing tables
- Permission problems
- Empty timeslot IDs array
- SQL syntax errors

**Solutions:**

#### Step 1: Run Diagnostic Tool
1. Go to admin dashboard
2. Click "Diagnose Cleanup" button
3. Check all test results
4. Look for any ❌ failures

#### Step 2: Check Database Tables
Make sure these tables exist:
```sql
SHOW TABLES LIKE 'Timeslot';
SHOW TABLES LIKE 'Student_Choice';
SHOW TABLES LIKE 'Student_Join';
SHOW TABLES LIKE 'Appointment';
SHOW TABLES LIKE 'Tutor_Creates';
SHOW TABLES LIKE 'Admin_Log';
```

#### Step 3: Check Permissions
Ensure your database user has DELETE permissions:
```sql
GRANT DELETE ON cae_database.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Step 4: Check for Old Timeslots
```sql
SELECT COUNT(*) FROM Timeslot WHERE Date < DATE_SUB(CURDATE(), INTERVAL 7 DAY);
```

### 2. "No old timeslots found to delete"

**Cause:** No timeslots older than 7 days exist in the database.

**Solution:**
1. Add test data: `http://localhost/CAE/backend/admin/setup/add_old_timeslots.php`
2. Or manually add old timeslots for testing

### 3. "Method not allowed" Error

**Cause:** Using GET instead of POST request.

**Solution:**
- Ensure the frontend is making a POST request
- Check that the fetch call uses `method: 'POST'`

### 4. "Unauthorized" Error

**Cause:** Not logged in as admin or session expired.

**Solution:**
1. Log out and log back in as admin
2. Check that you're using the correct admin credentials
3. Verify session is active

### 5. Database Connection Errors

**Symptoms:**
- "Database connection failed"
- "Connection refused"

**Solutions:**
1. Check if MySQL service is running
2. Verify database credentials in `backend/admin/db.php`
3. Check if database `cae_database` exists

### 6. Foreign Key Constraint Errors

**Symptoms:**
- "Cannot delete or update a parent row"

**Solutions:**
1. Check the order of deletion in the code
2. Ensure all related records are deleted first
3. Verify foreign key relationships

## 🔍 Diagnostic Steps

### 1. Run the Diagnostic Tool
```
http://localhost/CAE/backend/admin/test_timeslot_cleanup.php
```

This tool will check:
- ✅ Database connection
- ✅ Table existence
- ✅ Permissions
- ✅ Query structure
- ✅ Admin authentication
- ✅ Sample data

### 2. Check Error Logs
Look for PHP error logs:
- XAMPP: `xampp/php/logs/php_error_log`
- Apache: `xampp/apache/logs/error.log`

### 3. Test Database Queries Manually
```sql
-- Check old timeslots
SELECT * FROM Timeslot WHERE Date < DATE_SUB(CURDATE(), INTERVAL 7 DAY);

-- Check related data
SELECT COUNT(*) FROM Student_Choice sc 
JOIN Timeslot t ON sc.Timeslot_ID = t.Timeslot_ID 
WHERE t.Date < DATE_SUB(CURDATE(), INTERVAL 7 DAY);
```

## 🛠️ Manual Testing

### 1. Test with Sample Data
```bash
# Add test data
http://localhost/CAE/backend/admin/setup/add_old_timeslots.php

# Test cleanup
http://localhost/CAE/backend/admin/test_timeslot_cleanup.php
```

### 2. Test API Directly
```bash
curl -X POST http://localhost/CAE/backend/admin/delete_old_timeslots.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id"
```

## 📋 Checklist

Before reporting an issue, check:

- [ ] Database is running
- [ ] All tables exist
- [ ] Admin is logged in
- [ ] Old timeslots exist (>7 days)
- [ ] Database user has DELETE permissions
- [ ] No foreign key constraint violations
- [ ] PHP error logs are clean
- [ ] Session is active

## 🆘 Getting Help

If the issue persists:

1. **Run diagnostic tool** and note all results
2. **Check error logs** for specific error messages
3. **Test with sample data** to isolate the issue
4. **Provide error details** including:
   - Error message
   - Diagnostic tool results
   - Database structure
   - PHP version
   - MySQL version

## 🔄 Reset and Test

To completely reset and test:

1. **Clear old data:**
```sql
DELETE FROM Admin_Log WHERE Action = 'Delete Old Timeslots';
```

2. **Add fresh test data:**
```
http://localhost/CAE/backend/admin/setup/add_old_timeslots.php
```

3. **Test cleanup:**
```
http://localhost/CAE/backend/admin/test_timeslot_cleanup.php
```

4. **Try cleanup from dashboard**
