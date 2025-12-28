<?php
// app/core/Helpers.php
class Helpers {
    
    public static function escape($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    public static function generateCode($prefix) {
        return $prefix . strtoupper(uniqid());
    }
    
    public static function formatDate($date, $format = 'Y-m-d') {
        if(empty($date)) return '';
        return date($format, strtotime($date));
    }
    
    public static function formatCurrency($amount) {
        // Display amounts in Philippine peso with standard grouping
        return '₱' . number_format((float)$amount, 2);
    }
    
    public static function getFunnelColor($category) {
        $colors = [
            'yellow' => 'warning',
            'pink' => 'danger',
            'green' => 'success',
            'blue' => 'primary'
        ];
        return $colors[$category] ?? 'secondary';
    }
    
    public static function getFunnelName($category) {
        $names = [
            'yellow' => 'Closable this month',
            'pink' => 'Newly quoted',
            'green' => 'Project based',
            'blue' => 'Services offered'
        ];
        return $names[$category] ?? 'Unknown';
    }
    
    public static function calculateLeadScore($lead) {
        $score = 0;
        
        // Budget
        if($lead['budget'] == 'high') $score += 30;
        elseif($lead['budget'] == 'medium') $score += 20;
        elseif($lead['budget'] == 'low') $score += 10;
        
        // Timeline
        if($lead['timeline'] == 'urgent') $score += 25;
        elseif($lead['timeline'] == '1-3_months') $score += 20;
        elseif($lead['timeline'] == '3-6_months') $score += 10;
        
        // Authority
        if($lead['authority'] == 'decision_maker') $score += 25;
        elseif($lead['authority'] == 'influencer') $score += 15;
        
        // Need
        if($lead['need_level'] == 'high') $score += 20;
        elseif($lead['need_level'] == 'medium') $score += 10;
        
        return $score;
    }
}
?>