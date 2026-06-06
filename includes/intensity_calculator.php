<?php
/**
 * Earthquake Intensity Calculator
 * Based on Peak Ground Acceleration (PGA) and MMI Scale
 */

class IntensityCalculator {
    
    /**
     * Convert Gal to %g
     * 1g = 980 Gal
     */
    public static function galToPercentG($gal) {
        return ($gal / 980) * 100;
    }
    
    /**
     * Get MMI (Modified Mercalli Intensity) Scale
     * Based on Peak Acceleration (%g)
     */
    public static function getMMIScale($gal) {
        $percent_g = self::galToPercentG($gal);
        
        if ($percent_g < 0.17) {
            return [
                'level' => 'I',
                'name' => 'Not Felt',
                'description' => 'Not felt except by a very few under especially favorable conditions.',
                'damage' => 'None',
                'color' => 'gray',
                'alarm_level' => 1
            ];
        } elseif ($percent_g >= 0.17 && $percent_g < 1.4) {
            return [
                'level' => 'II-III',
                'name' => 'Weak',
                'description' => 'Felt only by a few persons at rest, especially on upper floors.',
                'damage' => 'None',
                'color' => 'blue',
                'alarm_level' => 1
            ];
        } elseif ($percent_g >= 1.4 && $percent_g < 3.9) {
            return [
                'level' => 'IV',
                'name' => 'Light',
                'description' => 'Felt indoors by many, outdoors by few. Dishes, windows disturbed.',
                'damage' => 'None',
                'color' => 'cyan',
                'alarm_level' => 2
            ];
        } elseif ($percent_g >= 3.9 && $percent_g < 9.2) {
            return [
                'level' => 'V',
                'name' => 'Moderate',
                'description' => 'Felt by nearly everyone. Some dishes, windows broken.',
                'damage' => 'Very Light',
                'color' => 'green',
                'alarm_level' => 2
            ];
        } elseif ($percent_g >= 9.2 && $percent_g < 18) {
            return [
                'level' => 'VI',
                'name' => 'Strong',
                'description' => 'Felt by all. Some heavy furniture moved. Slight damage.',
                'damage' => 'Light',
                'color' => 'yellow',
                'alarm_level' => 2
            ];
        } elseif ($percent_g >= 18 && $percent_g < 34) {
            return [
                'level' => 'VII',
                'name' => 'Very Strong',
                'description' => 'Damage negligible in buildings of good design. Slight to moderate in ordinary structures.',
                'damage' => 'Moderate',
                'color' => 'orange',
                'alarm_level' => 3
            ];
        } elseif ($percent_g >= 34 && $percent_g < 65) {
            return [
                'level' => 'VIII',
                'name' => 'Severe',
                'description' => 'Damage slight in specially designed structures. Considerable damage in ordinary buildings.',
                'damage' => 'Moderate/Heavy',
                'color' => 'dark-orange',
                'alarm_level' => 3
            ];
        } elseif ($percent_g >= 65 && $percent_g < 124) {
            return [
                'level' => 'IX',
                'name' => 'Violent',
                'description' => 'Damage considerable in specially designed structures. Buildings shifted off foundations.',
                'damage' => 'Heavy',
                'color' => 'red',
                'alarm_level' => 3
            ];
        } else {
            return [
                'level' => 'X+',
                'name' => 'Extreme',
                'description' => 'Most masonry and frame structures destroyed. Ground badly cracked.',
                'damage' => 'Very Heavy',
                'color' => 'dark-red',
                'alarm_level' => 3
            ];
        }
    }
    
    /**
     * Get alarm level description
     */
    public static function getAlarmLevel($alarm_level) {
        switch ($alarm_level) {
            case 1:
                return 'Level-1: Monitor Only';
            case 2:
                return 'Level-2: Local Alert (Buzzer + LCD)';
            case 3:
                return 'Level-3: Emergency Alert (SMS + Buzzer + LCD)';
            default:
                return 'Unknown';
        }
    }
    
    /**
     * Should send SMS alert?
     */
    public static function shouldSendSMS($gal) {
        $intensity = self::getMMIScale($gal);
        return $intensity['alarm_level'] >= 3;
    }
    
    /**
     * Should trigger local alert (buzzer)?
     */
    public static function shouldTriggerLocalAlert($gal) {
        $intensity = self::getMMIScale($gal);
        return $intensity['alarm_level'] >= 2;
    }
    
    /**
     * Estimate earthquake magnitude from PGA (Gal)
     * 
     * IMPORTANT: This is an ESTIMATE based on local ground motion.
     * Actual magnitude requires data from multiple seismic stations.
     * 
     * Uses empirical relationship: M ≈ 2/3 * MMI + 1
     * This assumes you're relatively close to the epicenter.
     * 
     * @param float $gal Peak Ground Acceleration in Gal
     * @return float Estimated magnitude (use with caution)
     */
    public static function estimateMagnitude($gal) {
        $percent_g = self::galToPercentG($gal);
        
        // Convert MMI to numeric value
        if ($percent_g < 0.17) {
            $mmi_numeric = 1;
        } elseif ($percent_g < 1.4) {
            $mmi_numeric = 2.5;
        } elseif ($percent_g < 3.9) {
            $mmi_numeric = 4;
        } elseif ($percent_g < 9.2) {
            $mmi_numeric = 5;
        } elseif ($percent_g < 18) {
            $mmi_numeric = 6;
        } elseif ($percent_g < 34) {
            $mmi_numeric = 7;
        } elseif ($percent_g < 65) {
            $mmi_numeric = 8;
        } elseif ($percent_g < 124) {
            $mmi_numeric = 9;
        } else {
            $mmi_numeric = 10;
        }
        
        // Empirical formula
        $magnitude = (2/3) * $mmi_numeric + 1;
        
        return round($magnitude, 1);
    }
}
?>
