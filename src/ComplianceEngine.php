<?php
class ComplianceEngine {
    private const GREEN_TYPES    = ['serviced_apartment', 'soho', 'sofo', 'commercial'];
    private const GREEN_KEYWORDS = ['serviced residence','service residence','serviced apartment','soho','sofo','sovo','hotel suite','corporate suite','suite'];
    private const RED_KEYWORDS   = ['ppam','pr1ma','rumah mampu milik','affordable housing','flat','government housing','residential condominium'];

    public static function evaluate(array $property): array {
        $score    = 50;
        $flags    = [];
        $recs     = [];
        $building = strtolower($property['strata_building'] ?? '');
        $type     = $property['property_type'] ?? '';

        if (in_array($type, self::GREEN_TYPES)) {
            $score += 30;
            $flags[] = ucfirst(str_replace('_', ' ', $type)) . ' — typically STR-friendly';
        }

        foreach (self::GREEN_KEYWORDS as $kw) {
            if (str_contains($building, $kw)) {
                $score += 20;
                $flags[] = 'Building name suggests STR-friendly: "' . $kw . '"';
                break;
            }
        }

        foreach (self::RED_KEYWORDS as $kw) {
            if (str_contains($building, $kw)) {
                $score -= 40;
                $flags[] = 'Possible restriction detected: "' . $kw . '"';
                $recs[]  = 'Verify strata by-laws and JMB house rules before STR listing.';
                break;
            }
        }

        if (!empty($property['is_strata'])) {
            $flags[] = 'Strata property — check JMB/MC house rules for STR policy.';
            $recs[]  = 'Obtain written consent from JMB/Management Corporation.';
        }

        $status = $score >= 70 ? 'green' : ($score >= 40 ? 'amber' : 'red');

        $recommendation = match ($status) {
            'green' => 'STR listing is recommended. Register unit with local authority.',
            'amber' => 'Verify house rules before STR. Consider Mid-Term as backup strategy.',
            'red'   => 'STR not suitable. Switch to MID_TERM, SUBLET, or CORPORATE strategy.',
        };

        return compact('status', 'score', 'flags', 'recs', 'recommendation');
    }

    public static function badge(string $status): string {
        return match ($status) {
            'green' => '<span class="badge-green">🟢 GREEN</span>',
            'amber' => '<span class="badge-amber">🟡 AMBER</span>',
            'red'   => '<span class="badge-red">🔴 RED</span>',
            default => '<span class="badge-secondary">—</span>',
        };
    }

    public static function label(string $status): string {
        return match ($status) {
            'green' => '🟢 GREEN — STR Allowed',
            'amber' => '🟡 AMBER — Verify Required',
            'red'   => '🔴 RED — STR Not Suitable',
            default => '—',
        };
    }
}
