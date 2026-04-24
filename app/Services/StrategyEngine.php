<?php

namespace App\Services;

use App\Models\Property;

class StrategyEngine
{
    /**
     * Recommend the optimal rental strategy for a property.
     */
    public function recommend(Property $property): array
    {
        $compliance = $property->compliance_status;
        $type       = $property->property_type;
        $beds       = $property->bedrooms;
        $city       = strtolower($property->city ?? '');

        $scores = [
            'STR'       => 0,
            'MID_TERM'  => 0,
            'SUBLET'    => 0,
            'CORPORATE' => 0,
        ];

        $reasons = [];

        // Compliance gating
        if ($compliance === 'green') {
            $scores['STR'] += 50;
            $reasons['STR'][] = 'Compliance status GREEN — STR allowed';
        } elseif ($compliance === 'amber') {
            $scores['STR'] += 20;
            $scores['MID_TERM'] += 30;
            $reasons['STR'][] = 'Compliance AMBER — proceed with caution';
            $reasons['MID_TERM'][] = 'Mid-term safer while compliance is being verified';
        } else {
            $scores['MID_TERM'] += 40;
            $scores['SUBLET'] += 30;
            $scores['CORPORATE'] += 30;
            $reasons['MID_TERM'][] = 'Compliance RED — STR not suitable';
        }

        // Location scoring
        $strCities = ['kuala lumpur', 'kl', 'petaling jaya', 'penang', 'george town', 'kota kinabalu', 'langkawi', 'johor bahru'];
        foreach ($strCities as $strCity) {
            if (str_contains($city, $strCity)) {
                $scores['STR'] += 20;
                $reasons['STR'][] = "High-demand STR city: {$property->city}";
                break;
            }
        }

        $corporateCities = ['kuala lumpur', 'kl', 'petaling jaya', 'cyberjaya', 'putrajaya', 'iskandar puteri'];
        foreach ($corporateCities as $c) {
            if (str_contains($city, $c)) {
                $scores['CORPORATE'] += 15;
                $reasons['CORPORATE'][] = "Corporate demand city: {$property->city}";
                break;
            }
        }

        // Bedrooms
        if ($beds >= 3) {
            $scores['SUBLET'] += 25;
            $scores['CORPORATE'] += 15;
            $reasons['SUBLET'][] = "{$beds} bedrooms — room sublet viable";
        }

        if ($beds === 1 || $beds === 0) {
            $scores['STR'] += 15;
            $reasons['STR'][] = 'Studio/1BR — optimal for STR guests';
        }

        // Property type
        if (in_array($type, ['soho', 'sofo'])) {
            $scores['CORPORATE'] += 20;
            $reasons['CORPORATE'][] = 'SOHO/SOFO — strong corporate appeal';
        }

        if ($type === 'commercial') {
            $scores['CORPORATE'] += 25;
            $scores['STR'] += 10;
            $reasons['CORPORATE'][] = 'Commercial property — corporate lease preferred';
        }

        // Pick winner
        arsort($scores);
        $recommended = array_key_first($scores);

        $descriptions = [
            'STR'       => 'List on Airbnb / Booking.com for nightly/weekly stays. Maximises revenue with high occupancy.',
            'MID_TERM'  => '30–90 day stays targeting digital nomads, relocators, and project workers. Lower turnover cost.',
            'SUBLET'    => 'Rent individual rooms to maximise per-sqft yield. Best for 3+ bedroom units.',
            'CORPORATE' => 'Long-term lease to companies for staff housing or office space. Stable income with minimal void.',
        ];

        return [
            'recommended'  => $recommended,
            'scores'       => $scores,
            'reasons'      => $reasons[$recommended] ?? [],
            'all_reasons'  => $reasons,
            'description'  => $descriptions[$recommended],
            'alternatives' => array_slice(array_keys($scores), 1, 2),
        ];
    }

    public function applyRecommendation(Property $property): Property
    {
        $result = $this->recommend($property);
        $property->strategy_mode = $result['recommended'];
        $property->save();
        return $property;
    }

    public function strategyLabel(string $mode): string
    {
        return match ($mode) {
            'STR'       => '🏠 Short-Term Rental (STR)',
            'MID_TERM'  => '📅 Mid-Term (30–90 days)',
            'SUBLET'    => '🚪 Room Sublet',
            'CORPORATE' => '🏢 Corporate Lease',
            default     => $mode,
        };
    }
}
