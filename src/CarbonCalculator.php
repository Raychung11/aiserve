<?php
/**
 * Carbon Calculator
 * Scope 1, 2, 3 GHG emission calculations using Malaysia MyGHG emission factors
 * Source: Malaysia Greenhouse Gas Reporting Programme (MyGHGRP) 2nd Edition, DOE 2023
 */
class CarbonCalculator {

    // All factors in tCO2e per unit
    const FACTORS = [
        // Scope 2 — Purchased electricity (MyGHG 2023)
        'electricity_kwh'       => 0.000694,   // Peninsular Malaysia TNB grid
        'electricity_sabah_kwh' => 0.000829,   // Sabah grid
        'electricity_swk_kwh'   => 0.000512,   // Sarawak grid

        // Scope 1 — Stationary combustion
        'diesel_litre'          => 0.0026914,
        'petrol_litre'          => 0.0023098,
        'natural_gas_m3'        => 0.0020160,
        'lpg_kg'                => 0.0015100,
        'cng_m3'                => 0.0021570,

        // Scope 1 — Mobile combustion (fleet, average light vehicle)
        'fleet_diesel_km'       => 0.00016844,
        'fleet_petrol_km'       => 0.00015302,

        // Scope 3 — Business travel (DEFRA 2023, economy class per passenger-km)
        'flight_domestic_km'    => 0.00025500,  // <3 h
        'flight_intl_km'        => 0.00019085,  // long haul

        // Scope 3 — Waste (DEFRA 2023)
        'waste_landfill_kg'     => 0.00058685,
        'waste_recycled_kg'     => 0.00001467,

        // Scope 3 — Water supply & treatment
        'water_m3'              => 0.00014900,
    ];

    // Malaysia industry intensity benchmarks (tCO2e per employee, annual)
    const INTENSITY_BENCHMARKS = [
        'Manufacturing' => ['scope1' => 12.5, 'scope2' => 8.3, 'scope3' => 15.2],
        'Services'      => ['scope1' =>  1.2, 'scope2' => 2.8, 'scope3' =>  4.1],
        'Trading'       => ['scope1' =>  2.4, 'scope2' => 1.9, 'scope3' =>  8.7],
        'Construction'  => ['scope1' => 18.3, 'scope2' => 4.2, 'scope3' => 22.1],
        'Other'         => ['scope1' =>  3.5, 'scope2' => 3.0, 'scope3' =>  6.0],
    ];

    public static function calculate(array $inputs): array {
        $f = self::FACTORS;

        // Scope 2 — electricity
        $elec = (float)($inputs['electricity_kwh'] ?? 0) * $f['electricity_kwh'];

        // Scope 1 — stationary combustion
        $stationary =
            (float)($inputs['diesel_litre']    ?? 0) * $f['diesel_litre']    +
            (float)($inputs['petrol_litre']    ?? 0) * $f['petrol_litre']    +
            (float)($inputs['natural_gas_m3']  ?? 0) * $f['natural_gas_m3']  +
            (float)($inputs['lpg_kg']          ?? 0) * $f['lpg_kg']          +
            (float)($inputs['cng_m3']          ?? 0) * $f['cng_m3'];

        // Scope 1 — fleet
        $fleet =
            (float)($inputs['fleet_diesel_km'] ?? 0) * $f['fleet_diesel_km'] +
            (float)($inputs['fleet_petrol_km'] ?? 0) * $f['fleet_petrol_km'];

        $scope1 = $stationary + $fleet;
        $scope2 = $elec;

        // Scope 3 — travel
        $travel =
            (float)($inputs['flight_domestic_km'] ?? 0) * $f['flight_domestic_km'] +
            (float)($inputs['flight_intl_km']     ?? 0) * $f['flight_intl_km'];

        // Scope 3 — waste
        $waste =
            (float)($inputs['waste_landfill_kg'] ?? 0) * $f['waste_landfill_kg'] +
            (float)($inputs['waste_recycled_kg'] ?? 0) * $f['waste_recycled_kg'];

        // Scope 3 — water
        $water = (float)($inputs['water_m3'] ?? 0) * $f['water_m3'];

        $scope3 = $travel + $waste + $water;
        $total  = $scope1 + $scope2 + $scope3;

        return [
            'scope1'     => round($scope1, 4),
            'scope2'     => round($scope2, 4),
            'scope3'     => round($scope3, 4),
            'total'      => round($total, 4),
            'breakdown'  => [
                'electricity' => round($elec, 4),
                'stationary'  => round($stationary, 4),
                'fleet'       => round($fleet, 4),
                'travel'      => round($travel, 4),
                'waste'       => round($waste, 4),
                'water'       => round($water, 4),
            ],
        ];
    }

    public static function saveToESG(int $companyId, array $result, string $framework, string $period, int $userId): void {
        // Map framework GHG indicator IDs to calculated scope values
        $mappings = [
            'BURSA_SEDG' => ['BURSA-E-04' => $result['scope1'], 'BURSA-E-05' => $result['scope2']],
            'GRI'        => ['GRI-305-1' => $result['scope1'], 'GRI-305-2' => $result['scope2'], 'GRI-305-3' => $result['scope3']],
            'ISSB'       => ['ISSB-E-4'  => $result['scope1'], 'ISSB-E-5'  => $result['scope2'], 'ISSB-E-6'  => $result['scope3']],
            'CDP'        => ['CDP-C6-1'  => $result['scope1'], 'CDP-C6-3'  => $result['scope2'], 'CDP-C6-5'  => $result['scope3']],
            'ESRS'       => ['ESRS-E1-6' => $result['total']],
        ];

        foreach ($mappings[$framework] ?? [] as $indicatorId => $value) {
            if ($value > 0) {
                ESGDataManager::save($companyId, $indicatorId, $framework, 'ENVIRONMENT', (string)$value, [
                    'unit'    => 'tCO2e',
                    'period'  => $period,
                    'user_id' => $userId,
                    'source'  => 'Carbon Calculator (MyGHG 2023 factors)',
                ]);
            }
        }
    }

    public static function getIntensityBenchmark(string $industry): array {
        return self::INTENSITY_BENCHMARKS[$industry] ?? self::INTENSITY_BENCHMARKS['Other'];
    }
}
