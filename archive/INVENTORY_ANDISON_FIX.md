# 📊 Inventory & Andison Manila Data Import - Complete Fix

## Problem
✗ Inventory data wasn't being separated properly
✗ Andison Manila inventory items weren't identified
✗ Both inventory and main data went to default company instead of routing correctly

## Root Causes
1. **No AUTO-ROUTING logic** in main `import-data.php`
2. **No sold_to support** in `import-inventory.php`  
3. **PHP limit exceeded** (max_input_vars = 1000)
4. **Silent failures** with only 20 errors shown

## Fixes Applied

### 1️⃣ PHP Configuration (✅ Already Done)
- `max_input_vars = 10000` (was 1000)
- `max_multipart_body_parts = 15000` (was commented)

### 2️⃣ Import API Error Reporting (✅ Already Done)
- Now shows ALL errors and skipped rows
- Added error counts in response

### 3️⃣ Auto-Routing Logic - Main API
**File:** `api/import-data.php` (Lines 752-772)
- If `sold_to` is **empty** → Routes to `Stock Addition` (Inventory)
- If `sold_to` contains **"andison"** or **"stock in manila"** → Routes to `to Andison Manila`
- Otherwise → Uses provided `company_name`

### 4️⃣ Auto-Routing Logic - Inventory API  
**File:** `api/import-inventory.php` (Lines 119-173)
- Checks for optional `sold_to` column in Excel
- If `sold_to` present and contains **"andison"** → Routes to `to Andison Manila`
- Default → Routes to `Stock Addition` (Inventory)
- Includes `sold_to` value in database for reference

### 5️⃣ Employee API Versions
**Files Updated:**
- `employee/api/import-data.php` (already had logic)
- `employee/api/import-inventory.php` (updated with same logic)

---

## How to Use This Feature

### Option A: Inventory Only (Separate File)
Upload a file with columns:
- `ITEMS` - Item code
- `DESCRIPTION` - Item name
- `UOM` - Unit of measure
- `INVENTORY` - Quantity
- **Optional:** `SOLD TO` - Leave empty for general inventory, or mark "Andison Manila" for Andison Manila items

**Result:**
- Items without Sold To → Go to **Inventory** view
- Items marked "Andison Manila" → Go to **Andison Manila Inventory** view

### Option B: Regular Import with Routing
Upload a file with columns:
- `Invoice No.`
- `Item Code`
- `Quantity`
- **`Sold To`** - THIS COLUMN CONTROLS ROUTING!

**Routing Rules:**
- Empty/blank → Goes to `Stock Addition` (Inventory)
- Contains "andison", "andiso", "stock in manila" → Goes to `to Andison Manila`
- Other values → Goes to that company

---

## Excel Template Example

### For Inventory Import:

| ITEMS | DESCRIPTION | UOM | INVENTORY | SOLD TO |
|-------|------------|-----|-----------|---------|
| MCX-001 | Multi Gas Detector | Unit | 50 | |
| SGL-001 | Single O2 Detector | Unit | 30 | Andison Manila |
| BAT-001 | Battery Pack | Unit | 100 | Stock in Manila |

**Result:**
- MCX-001 → Inventory (50 units)
- SGL-001 → Andison Manila (30 units)
- BAT-001 → Andison Manila (100 units)

---

## Verification

### Check Inventory Counts:
```
SELECT COUNT(*), SUM(quantity) 
FROM delivery_records 
WHERE company_name = 'Stock Addition'
```

### Check Andison Manila Inventory:
```
SELECT COUNT(*), SUM(quantity) 
FROM delivery_records 
WHERE company_name = 'to Andison Manila'
```

### View Imported Items:
- **Inventory:** inventory.php → Shows `company_name='Stock Addition'`
- **Andison Manila:** andison-manila.php → Shows `company_name='to Andison Manila'`

---

## Important Notes

✅ **Sold To field is OPTIONAL** - Old files without this column will still work
✅ **Case-insensitive** - "Andison", "ANDISON", "andison" all work
✅ **Flexible matching** - "Stock in Manila", "to Andison Manila", "Andison Industrial" all recognized
✅ **Backwards compatible** - Existing import flows still work

---

## Files Modified

1. `/api/import-data.php` - Added auto-routing logic
2. `/api/import-inventory.php` - Added sold_to support & auto-routing
3. `/employee/api/import-inventory.php` - Added sold_to support & auto-routing
4. `/php.php` - Increased max_input_vars & max_multipart_body_parts
5. `/api/import-data.php` - Added full error reporting
6. `/employee/api/import-data.php` - Added full error reporting

---

**Status:** ✅ Ready for testing
**Test Date:** 2026-05-07
**Last Updated:** 14:48 UTC
