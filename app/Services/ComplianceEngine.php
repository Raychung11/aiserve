<?php

namespace App\Services;

use App\Models\Property;

class ComplianceEngine
{
    // STR-banned or restricted building types/states
    private const BANNED_STATES = [];

    // Factors that push toward GREEN (STR suitable)
    private const GREEN_KEYWORDS = [
        'serviced residence', 'service residence', 'serviced apartment',
        'soho', 'sofo', 'sovo', 'hotel suite', 'corporate suite',
    ];

    // Factors that push toward RED
    private const RED_KEYWORDS = [
        'residential condominium', 'residential apartment',
        'ppam', 'pr1ma', 'rumah mampu milik', 'affordable housing',
        'flat', 'government housing',
    ];

    /**
     * Evaluate compliance status for a property and return a result array.
     */
    public function evaluate(Property $property): array
    {
        $score = 50; // start neutral (AMBER)
        $flags = [];
        $recommendations = [];

        $building = strtolower($property->strata_building ?? '');
        $type = strtolower($property->property_type ?? '');

        // Check property type
        if (in_array($property->property_type, ['soho', 'sofo'])) {
            $score += 30;
            $flags[] = 'Property type is SOHO/SOFO — typically STR-friendly';
        }

        if ($property->property_type === 'serviced_apartment') {
            $score += 25;
            $flags[] = 'Serviced Apartment — generally STR-permitted';
        }

        if ($property->property_type === 'commercial') {
            $score += 20;
            $flags[] = 'Commercial classification — fewer residential restrictions';
        }

        // Check building name keywords
        foreach (self::GREEN_KEYWORDS as $keyword) {
            if (str_contains($building, $keyword)) {
                $score += 20;
                $flags[] = "Building name suggests STR-friendly: \"{$keyword}\"";
                break;
            }
        }

        foreach (self::RED_KEYWORDS as $keyword) {
            if (str_contains($building, $keyword) || str_contains($type, $keyword)) {
                $score -= 40;
                $flags[] = "Possible restriction detected: \"{$keyword}\"";
                $recommendations[] = 'Verify house rules / strata by-laws before listing on STR platforms.';
                break;
            }
        }

        // Is strata?
        if ($property->is_strata) {
            $flags[] = 'Strata property — check JMB/MC house rules for STR policy';
            $recommendations[] = 'Request written consent or house rules from JMB/Management Corporation.';
        }

        // Determine status
        $status = match (true) {
            $score >= 70 => 'green',
            $score >= 40 => 'amber',
            default      => 'red',
        };

        $recommendation = match ($status) {
            'green' => 'STR listing on Airbnb/Booking.com is recommended. Ensure unit is registered with local authority.',
            'amber' => 'Verify building house rules and obtain written approval before STR listing. Consider MID_TERM as fallback.',
            'red'   => 'STR not recommended. Switch to MID_TERM, SUBLET, or CORPORATE lease strategy.',
        };

        return [
            'status'         => $status,
            'score'          => $score,
            'flags'          => $flags,
            'recommendations'=> $recommendations,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Classify and update the property's compliance status.
     */
    public function classify(Property $property): Property
    {
        $result = $this->evaluate($property);

        $property->compliance_status = $result['status'];
        $property->compliance_notes  = implode("\n", array_merge($result['flags'], $result['recommendations']));
        $property->save();

        return $property;
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'green' => '🟢 GREEN — STR Allowed',
            'amber' => '🟡 AMBER — Verify Required',
            'red'   => '🔴 RED — STR Not Suitable',
            default => 'Unknown',
        };
    }
}
