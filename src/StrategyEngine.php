<?php
class StrategyEngine {
    private const STR_CITIES       = ['kuala lumpur','kl','petaling jaya','penang','george town','kota kinabalu','langkawi','johor bahru','mont kiara'];
    private const CORPORATE_CITIES = ['kuala lumpur','kl','petaling jaya','cyberjaya','putrajaya','iskandar puteri','klcc','bangsar'];

    public static function recommend(array $property): array {
        $scores  = ['STR' => 0, 'MID_TERM' => 0, 'SUBLET' => 0, 'CORPORATE' => 0];
        $reasons = ['STR' => [], 'MID_TERM' => [], 'SUBLET' => [], 'CORPORATE' => []];
        $compliance = $property['compliance_status'] ?? 'amber';
        $type       = $property['property_type']     ?? 'condo';
        $beds       = (int)($property['bedrooms']    ?? 1);
        $city       = strtolower($property['city']   ?? '');

        // Compliance gating
        match ($compliance) {
            'green' => (function() use (&$scores, &$reasons) {
                $scores['STR'] += 50; $reasons['STR'][] = 'Compliance status GREEN — STR allowed'; })(),
            'amber' => (function() use (&$scores, &$reasons) {
                $scores['STR'] += 20; $scores['MID_TERM'] += 30;
                $reasons['STR'][] = 'Compliance AMBER — proceed with caution';
                $reasons['MID_TERM'][] = 'Mid-Term safer while compliance is being verified'; })(),
            default => (function() use (&$scores, &$reasons) {
                $scores['MID_TERM'] += 40; $scores['SUBLET'] += 30; $scores['CORPORATE'] += 30;
                $reasons['MID_TERM'][] = 'Compliance RED — STR not suitable'; })(),
        };

        // City scoring
        foreach (self::STR_CITIES as $c) {
            if (str_contains($city, $c)) { $scores['STR'] += 20; $reasons['STR'][] = "High STR demand city: {$property['city']}"; break; }
        }
        foreach (self::CORPORATE_CITIES as $c) {
            if (str_contains($city, $c)) { $scores['CORPORATE'] += 15; $reasons['CORPORATE'][] = "Corporate demand city: {$property['city']}"; break; }
        }

        // Bedrooms
        if ($beds >= 3) { $scores['SUBLET'] += 25; $reasons['SUBLET'][] = "{$beds} bedrooms — room sublet viable"; }
        if ($beds <= 1) { $scores['STR'] += 15;    $reasons['STR'][] = 'Studio/1BR — optimal for STR'; }

        // Type
        if (in_array($type, ['soho', 'sofo'])) { $scores['CORPORATE'] += 20; $reasons['CORPORATE'][] = 'SOHO/SOFO — strong corporate appeal'; }
        if ($type === 'commercial')            { $scores['CORPORATE'] += 25; $reasons['CORPORATE'][] = 'Commercial classification'; }

        arsort($scores);
        $top = array_key_first($scores);
        $alts = array_slice(array_keys($scores), 1, 2);

        $desc = [
            'STR'       => 'List on Airbnb/Booking.com for nightly stays. Maximises revenue with high occupancy.',
            'MID_TERM'  => '30–90 day stays targeting digital nomads and relocators. Lower turnover costs.',
            'SUBLET'    => 'Rent individual rooms to maximise per-sqft yield. Best for 3+ BR units.',
            'CORPORATE' => 'Long-term company lease for staff housing. Stable income, minimal void.',
        ];

        return [
            'recommended'  => $top,
            'scores'       => $scores,
            'reasons'      => $reasons[$top],
            'description'  => $desc[$top],
            'alternatives' => $alts,
        ];
    }

    public static function badge(string $mode): string {
        $labels = ['STR'=>'🏠 STR','MID_TERM'=>'📅 Mid-Term','SUBLET'=>'🚪 Sublet','CORPORATE'=>'🏢 Corporate'];
        $classes = ['STR'=>'badge-str','MID_TERM'=>'badge-mid','SUBLET'=>'badge-sub','CORPORATE'=>'badge-corp'];
        $label = $labels[$mode] ?? $mode;
        $class = $classes[$mode] ?? 'badge-secondary';
        return "<span class=\"$class\">$label</span>";
    }
}
