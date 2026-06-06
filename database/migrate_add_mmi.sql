-- Migration Script: Add MMI columns to existing database
-- Run this if you already have the seismic_logs table

USE earthquake_monitoring;

-- Add new columns to seismic_logs table
ALTER TABLE seismic_logs 
ADD COLUMN mmi_level VARCHAR(10) DEFAULT NULL AFTER intensity,
ADD COLUMN mmi_name VARCHAR(50) DEFAULT NULL AFTER mmi_level,
ADD COLUMN percent_g DECIMAL(10, 4) DEFAULT NULL AFTER mmi_name;

-- Add index for MMI level
ALTER TABLE seismic_logs 
ADD INDEX idx_mmi_level (mmi_level);

-- Update existing records with MMI data (if any)
-- This will calculate MMI for existing records based on intensity
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

SELECT 'Migration completed successfully!' as status;
