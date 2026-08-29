# 🚀 Quick Setup Guide: Bank Sampah → Supabase PostgreSQL

## Prerequisites:
- Internet connection ✓
- GitHub account ✓  
- Time: ~20 minutes

---

## Step 1: Create Supabase Project (5 min)

1. **Go to**: https://supabase.com
2. **Sign up** menggunakan GitHub account
3. **New Project** → Fill in:
   - Name: `banksampah-indah-lestari`
   - Password: **[SIMPAN DI KEYCHAIN/VAULT!]**
   - Region: Singapore (ap-southeast-1)
   - Database version: PostgreSQL 15+
4. **Wait 2-3 menit** untuk provisioning

---

## Step 2: Get Connection String (2 min)

1. Dashboard → **Settings** → **Database**
2. Scroll ke **Connection string** → **URI**
3. Copy: `postgresql://postgres:[PASSWORD]@db.[PROJECT].supabase.co:5432/postgres`
4. Extract values untuk `.env`

---

## Step 3: Test Connection from Laravel (5 min)

```bash
# Create test env
cp .env .env.pg_test

# Edit .env.pg_test with Supabase credentials
```

Test di Laravel Tinker:
```bash
DB_CONNECTION=pgsql \
DB_HOST=db.[YOUR-PROJECT].supabase.co \
DB_PORT=5432 \
DB_DATABASE=postgres \
DB_USERNAME=postgres \
DB_PASSWORD=[YOUR-PASSWORD] \
php artisan tinker

>> DB::connection()->getPdo();
// Should return PDO object if connected
```

---

## Step 4: Run Migrations (8 min)

```bash
# Fresh migrate (WARNING: drops all tables!)
DB_CONNECTION=pgsql \
DB_HOST=db.[YOUR-PROJECT].supabase.co \
DB_PORT=5432 \
DB_DATABASE=postgres \
DB_USERNAME=postgres \
DB_PASSWORD=[YOUR-PASSWORD] \
php artisan migrate:fresh --force
```

---

## Step 5: Export SQLite Data (3 min)

```bash
mkdir -p /tmp/banksampah-export

# Export each table
sqlite3 database/database.sqlite ".mode csv" \
  ".headers on" \
  ".output /tmp/banksampah-export/users.csv" \
  "SELECT * FROM users ORDER BY id;"

sqlite3 database/database.sqlite ".mode csv" \
  ".headers on" \
  ".output /tmp/banksampah-export/setoran.csv" \
  "SELECT * FROM setoran ORDER BY id;"
```

---

## Step 6: Import to PostgreSQL (7 min)

### Option A: Via Supabase Table Editor (Easiest)
1. Dashboard → Table Editor
2. Select table → Insert rows → Upload CSV
3. Upload exported files
4. Map columns correctly

### Option B: Via psql command
```bash
psql postgresql://postgres:[PASSWORD]@db.[PROJECT].supabase.co:5432/postgres \
-c "COPY users(name,email,password,...) FROM '/path/to/users.csv' WITH CSV HEADER;"
```

---

## Step 7: Verify Data (5 min)

```bash
# Compare counts
sqlite3 database/database.sqlite "SELECT COUNT(*) FROM users;"
# vs PostgreSQL count
```

---

## Common Issues & Fixes:

### 1. TINYINT(1) → BOOLEAN
```sql
ALTER TABLE users ALTER COLUMN aktif TYPE BOOLEAN USING aktif::boolean;
```

### 2. Auto-increment sequences
```sql
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
```

### 3. Timestamp timezones
```sql
SET TIMEZONE='UTC';
```

---

## Production Ready Checklist:

- [ ] Supabase project created
- [ ] Connection successful from Laravel
- [ ] All tables created
- [ ] Data imported successfully  
- [ ] Row counts verified
- [ ] Application can login/register
- [ ] Setoran entries saved correctly
- [ ] Reports generating properly

---

**Total Estimated Time**: ~45 minutes

Next step: **Follow this guide step-by-step!** 🚀