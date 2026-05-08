# Unit Type Filtering Implementation

## Setup Instructions

### 1. Run the Database Migration
Visit this URL in your browser to add the `unit_type` column to the database:
```
http://localhost/BW/add-unit-type-column.php
```

You should see:
```
✓ unit_type column added successfully
✓ Index created on unit_type column
Migration complete!
```

### 2. Prepare Your Excel File
Add a new column called **"UNIT TYPE"** to your Excel file with values:
- `1a`, `1b`, `2a`, `2b`, `3a`, `4a`

Example:
```
Invoice No. | Item Code | Description | Unit Type | Qty | ...
INV-001     | 1234-A    | Gas Sensor  | 1a        | 10  | ...
INV-002     | 5678-B    | Controller  | 2a        | 5   | ...
```

### 3. Upload Data
Upload the Excel file as usual. The system will automatically:
- Read the "UNIT TYPE" column
- Store it in lowercase (1a, 2a, etc.)
- Apply filtering to all dashboards

## What Gets Filtered
When displaying "UNIT TYPE" values of **1a, 2a, or 4a**:
- ✅ Yearly Total
- ✅ Top Products chart
- ✅ Monthly Sales Trend chart
- ✅ Sales Overview reports
- ✅ Unit Sold counts
- ✅ Total Deliveries counts

Items with unit types 1b, 2b, 3a will NOT appear in these displays.

## How It Works
1. **Import**: Excel "UNIT TYPE" column → `unit_type` database field
2. **Storage**: Values stored as lowercase (1a, 1b, 2a, etc.)
3. **Query Filtering**: All dashboard queries use `WHERE unit_type IN ('1a', '2a', '4a')`
4. **Both Roles**: Filtering applies to both admin and employee dashboards

## Benefits
- No more LIKE pattern matching (more efficient queries)
- Cleaner Excel column structure
- Easy to add/remove unit types in the future
- Database-level filtering ensures consistency
