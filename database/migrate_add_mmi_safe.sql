-- Safe Migration Script: Add MMI columns to existing database
-- This version checks if columns exist before adding them
-- Safe to run multiple times without errors

USE earthquake_monitoring;

-- Check and add mmi_level column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'earthquake_monitoring' 
AND TABLE_NAME = 'seismic_logs' 
AND COLUMN_NAME = 'mmi_level';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE seismic_logs ADD COLUMN mmi_level VARCHAR(10) DEFAULT NULL AFTER intensity',
    'SELECT "Column mmi_level already exists - skipping" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add mmi_name column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'earthquake_monitoring' 
AND TABLE_NAME = 'seismic_logs' 
AND COLUMN_NAME = 'mmi_name';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE seismic_logs ADD COLUMN mmi_name VARCHAR(50) DEFAULT NULL AFTER mmi_level',
    'SELECT "Column mmi_name already exists - skipping" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add percent_g column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'earthquake_monitoring' 
AND TABLE_NAME = 'seismic_logs' 
AND COLUMN_NAME = 'percent_g';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE seismic_logs ADD COLUMN percent_g DECIMAL(10, 4) DEFAULT NULL AFTER mmi_name',
    'SELECT "Column percent_g already exists - skipping" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add index for MMI level (if not exists)
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'earthquake_monitoring' 
AND TABLE_NAME = 'seismic_logs' 
AND INDEX_NAME = 'idx_mmi_level';

SET @query = IF(@index_exists = 0, 
    'ALTER TABLE seismic_logs ADD INDEX idx_mmi_level (mmi_level)',
    'SELECT "Index idx_mmi_level already exists - skipping" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records with MMI data (only records without MMI data)
UPDATE seismic_logs 
SET 
    percent_g = (intensity / 980) * 100,
    mmi_level = CASE
        WHEN (intensity / 980) * 100 < 0.17 THEN 'I'
        WHEN (intensity / 980) * 100 >= 0.17 AND (intensity / 980) * 100 < 1.4 THEN 'II-III'
        WHEN (intensity / 980) * 100 >= 1.4 AND (intensity / 980) * 100 < 3.9 THEN 'IV'
        WHEN (intensity / 980) * 100 >= 3.9 AND (intensity / 980) * 100 < 9.2 THEN 'V'
        WHEN (intensity / 980) * 100 >= 9.2 AND (intensity / 980) * 100 < 18 THEN 'VI'
        WHEN (intensity / 980) * 100 >= 18 AND (intensity / 980) * 100 < 34 THEN 'VII'
        WHEN (intensity / 980) * 100 >= 34 AND (intensity / 980) * 100 < 65 THEN 'VIII'
        WHEN (intensity / 980) * 100 >= 65 AND (intensity / 980) * 100 < 124 THEN 'IX'
        ELSE 'X+'
    END,
    mmi_name = CASE
        WHEN (intensity / 980) * 100 < 0.17 THEN 'Not Felt'
        WHEN (intensity / 980) * 100 >= 0.17 AND (intensity / 980) * 100 < 1.4 THEN 'Weak'
        WHEN (intensity / 980) * 100 >= 1.4 AND (intensity / 980) * 100 < 3.9 THEN 'Light'
        WHEN (intensity / 980) * 100 >= 3.9 AND (intensity / 980) * 100 < 9.2 THEN 'Moderate'
        WHEN (intensity / 980) * 100 >= 9.2 AND (intensity / 980) * 100 < 18 THEN 'Strong'
        WHEN (intensity / 980) * 100 >= 18 AND (intensity / 980) * 100 < 34 THEN 'Very Strong'
        WHEN (intensity / 980) * 100 >= 34 AND (intensity / 980) * 100 < 65 THEN 'Severe'
        WHEN (intensity / 980) * 100 >= 65 AND (intensity / 980) * 100 < 124 THEN 'Violent'
        ELSE 'Extreme'
    END
WHERE mmi_level IS NULL;

SELECT 'Migration completed successfully! All columns are ready.' as status;
