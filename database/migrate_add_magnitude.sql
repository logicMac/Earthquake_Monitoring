-- Add magnitude column to seismic_logs table
-- Run this migration to add magnitude estimation support

USE earthquake_monitoring;

-- Add magnitude column after intensity
ALTER TABLE seismic_logs 
ADD COLUMN magnitude DECIMAL(3, 1) DEFAULT NULL AFTER intensity;

-- Add index for magnitude queries
ALTER TABLE seismic_logs 
ADD INDEX idx_magnitude (magnitude);

-- Update existing records with estimated magnitude
-- This will calculate magnitude for all existing logs
UPDATE seismic_logs 
SET magnitude = ROUND((2/3) * (
    CASE 
        WHEN (intensity / 980 * 100) < 0.17 THEN 1
        WHEN (intensity / 980 * 100) < 1.4 THEN 2.5
        WHEN (intensity / 980 * 100) < 3.9 THEN 4
        WHEN (intensity / 980 * 100) < 9.2 THEN 5
        WHEN (intensity / 980 * 100) < 18 THEN 6
        WHEN (intensity / 980 * 100) < 34 THEN 7
        WHEN (intensity / 980 * 100) < 65 THEN 8
        WHEN (intensity / 980 * 100) < 124 THEN 9
        ELSE 10
    END
) + 1, 1)
WHERE magnitude IS NULL;

-- Verify the migration
SELECT 
    COUNT(*) as total_records,
    COUNT(magnitude) as records_with_magnitude,
    MIN(magnitude) as min_magnitude,
    MAX(magnitude) as max_magnitude,
    AVG(magnitude) as avg_magnitude
FROM seismic_logs;
