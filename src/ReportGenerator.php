<?php
/**
 * ESG Report Generator
 * Generates HTML compliance reports aligned to Bursa SEDG or GRI
 */
class ReportGenerator {

    /**
     * Generate full ESG report for a company
     */
    public static function generate(int $companyId, string $framework, string $period,
                                     int $userId): array {
        $company   = Database::fetchOne('SELECT * FROM companies WHERE id = ?', [$companyId]);
        if (!$company) return ['success' => false, 'message' => 'Company not found.'];

        $indicators = Company::getFrameworkIndicators($framework);
        $savedData  = ESGDataManager::getAll($companyId, $period);
        $stats      = ESGDataManager::getCompletionStats($companyId, $framework, $period);
        $score      = ESGDataManager::calcOverallScore($stats);
        $scoreInfo  = ESGDataManager::scoreLabel($score);
        $gaps       = GapAnalyzer::analyze($companyId, $framework, $period);

        $frameworkLabel = $framework === 'BURSA_SEDG' ? 'Bursa Malaysia Sustainability Reporting Guide'
                       : ($framework === 'GRI' ? 'GRI Standards' : 'Bursa SEDG + GRI Standards');

        $html = self::buildReportHTML($company, $framework, $frameworkLabel, $period,
                                      $indicators, $savedData, $stats, $score, $scoreInfo, $gaps);

        $title    = $company['name'] . ' — ESG Report ' . $period . ' (' . $framework . ')';
        $reportId = Database::insert('reports', [
            'company_id'   => $companyId,
            'title'        => $title,
            'framework'    => $framework,
            'period'       => $period,
            'content_html' => $html,
            'score'        => $score,
            'generated_by' => $userId,
        ]);

        Database::insert('activity_log', [
            'user_id'     => $userId,
            'company_id'  => $companyId,
            'action'      => 'REPORT_GENERATED',
            'description' => "Generated {$framework} ESG report for {$period}",
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        return ['success' => true, 'report_id' => $reportId, 'html' => $html, 'score' => $score];
    }

    /**
     * Build report HTML
     */
    private static function buildReportHTML(array $company, string $framework, string $frameworkLabel,
                                             string $period, array $indicators, array $savedData,
                                             array $stats, float $score, array $scoreInfo, array $gaps): string {
        $name       = htmlspecialchars($company['name']);
        $industry   = htmlspecialchars($company['industry']);
        $revenue    = str_replace(['below_10M','10M_to_50M','above_50M'], ['< RM10M','RM10M–50M','> RM50M'], $company['revenue_tier']);
        $employees  = number_format($company['employee_count']);
        $generated  = date('d M Y, h:i A');
        $envScore   = $stats['ENVIRONMENT']['score'] ?? 0;
        $socScore   = $stats['SOCIAL']['score'] ?? 0;
        $govScore   = $stats['GOVERNANCE']['score'] ?? 0;
        $gapCount   = $gaps['gaps_count'] ?? 0;
        $critGaps   = count(array_filter($gaps['gaps'] ?? [], fn($g) => $g['priority'] === 'critical'));

        $ob = '';
        $ob .= '<!DOCTYPE html><html lang="en"><head>';
        $ob .= '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $ob .= '<title>' . $name . ' ESG Report ' . $period . '</title>';
        $ob .= '<style>';
        $ob .= self::reportCSS();
        $ob .= '</style></head><body>';

        // Cover page
        $ob .= '<div class="cover">';
        $ob .= '<div class="cover-logo">Adcellent ESG OS</div>';
        $ob .= '<h1>' . $name . '</h1>';
        $ob .= '<h2>ESG Compliance Report ' . $period . '</h2>';
        $ob .= '<p class="framework-badge">' . $frameworkLabel . '</p>';
        $ob .= '<div class="cover-meta">';
        $ob .= '<div class="meta-item"><span>Industry</span><strong>' . $industry . '</strong></div>';
        $ob .= '<div class="meta-item"><span>Revenue</span><strong>' . $revenue . '</strong></div>';
        $ob .= '<div class="meta-item"><span>Employees</span><strong>' . $employees . '</strong></div>';
        $ob .= '<div class="meta-item"><span>Reporting Year</span><strong>' . $period . '</strong></div>';
        $ob .= '</div>';
        $ob .= '<div class="cover-score">ESG Score: <span style="color:' . $scoreInfo['color'] . '">' . $score . '%</span> — ' . $scoreInfo['label'] . '</div>';
        $ob .= '<p class="generated">Generated: ' . $generated . ' | Confidential</p>';
        $ob .= '</div>';

        // Executive Summary
        $ob .= '<div class="page-break"></div>';
        $ob .= '<div class="section"><h2>1. Executive Summary</h2>';
        $ob .= '<table class="summary-table"><tr>';
        $ob .= '<td><div class="score-box" style="border-color:' . $scoreInfo['color'] . '">';
        $ob .= '<div class="score-num" style="color:' . $scoreInfo['color'] . '">' . $score . '%</div>';
        $ob .= '<div class="score-label">Overall ESG Score</div><div class="score-rating">' . $scoreInfo['label'] . '</div>';
        $ob .= '</div></td>';
        $ob .= '<td><div class="score-box"><div class="score-num" style="color:#16a34a">' . $envScore . '%</div>';
        $ob .= '<div class="score-label">Environment</div></div></td>';
        $ob .= '<td><div class="score-box"><div class="score-num" style="color:#0891b2">' . $socScore . '%</div>';
        $ob .= '<div class="score-label">Social</div></div></td>';
        $ob .= '<td><div class="score-box"><div class="score-num" style="color:#7c3aed">' . $govScore . '%</div>';
        $ob .= '<div class="score-label">Governance</div></div></td>';
        $ob .= '</tr></table>';
        $ob .= '<p><strong>' . $name . '</strong> has completed ESG data collection for ';
        $ob .= '<strong>' . $gaps['completed'] . ' of ' . $gaps['total_indicators'] . ' indicators</strong>';
        $ob .= ' under the <strong>' . $frameworkLabel . '</strong> framework for reporting year <strong>' . $period . '</strong>.</p>';
        $ob .= '<p>There are <strong>' . $gapCount . ' disclosure gaps</strong>, of which ';
        $ob .= '<strong style="color:#dc2626">' . $critGaps . ' are critical</strong> and require immediate attention.</p>';

        if (!empty($gaps['quick_wins'])) {
            $ob .= '<h3>Top Quick Wins</h3><ul>';
            foreach (array_slice($gaps['quick_wins'], 0, 5) as $qw) {
                $ob .= '<li><strong>' . htmlspecialchars($qw['indicator']['code']) . ' — ' . htmlspecialchars($qw['indicator']['name']) . '</strong>: ';
                $ob .= htmlspecialchars($qw['recommendation']) . ' <em>(' . $qw['effort'] . ')</em></li>';
            }
            $ob .= '</ul>';
        }
        $ob .= '</div>';

        // Sections for each category
        $categories = ['ENVIRONMENT' => 'Environmental', 'SOCIAL' => 'Social', 'GOVERNANCE' => 'Governance'];
        $sectionNum = 2;
        foreach ($categories as $cat => $catLabel) {
            $catIndicators = array_filter($indicators, fn($i) => $i['category'] === $cat);
            $ob .= '<div class="page-break"></div>';
            $ob .= '<div class="section"><h2>' . $sectionNum . '. ' . $catLabel . ' Disclosures</h2>';
            $ob .= '<p>Completion: <strong>' . ($stats[$cat]['score'] ?? 0) . '%</strong> ('
                 . ($stats[$cat]['completed'] ?? 0) . '/' . ($stats[$cat]['total'] ?? 0) . ' indicators)</p>';
            $ob .= '<table class="data-table"><thead><tr>';
            $ob .= '<th>Code</th><th>Indicator</th><th>Value</th><th>Unit</th><th>Status</th>';
            $ob .= '</tr></thead><tbody>';
            foreach ($catIndicators as $ind) {
                $id   = $ind['indicator_id'];
                $val  = $savedData[$id]['value'] ?? null;
                $done = $val !== null && $val !== '';
                $statusClass = $done ? 'status-done' : ($ind['required'] ? 'status-missing-req' : 'status-missing');
                $statusText  = $done ? 'Disclosed' : ($ind['required'] ? 'MISSING (Required)' : 'Not Disclosed');
                $displayVal  = $done ? htmlspecialchars($val) : '—';
                $ob .= '<tr>';
                $ob .= '<td>' . htmlspecialchars($ind['code']) . '</td>';
                $ob .= '<td>' . htmlspecialchars($ind['name']) . ($ind['required'] ? ' <span class="req-star">*</span>' : '') . '</td>';
                $ob .= '<td>' . $displayVal . '</td>';
                $ob .= '<td>' . htmlspecialchars($ind['unit'] ?? '—') . '</td>';
                $ob .= '<td class="' . $statusClass . '">' . $statusText . '</td>';
                $ob .= '</tr>';
            }
            $ob .= '</tbody></table>';
            $ob .= '<p class="footnote">* Required disclosure</p>';
            $ob .= '</div>';
            $sectionNum++;
        }

        // Gap Analysis Summary
        $ob .= '<div class="page-break"></div>';
        $ob .= '<div class="section"><h2>' . $sectionNum . '. Gap Analysis &amp; Recommendations</h2>';
        if (empty($gaps['gaps'])) {
            $ob .= '<p style="color:#16a34a">All indicators have been disclosed. Excellent work!</p>';
        } else {
            $ob .= '<table class="data-table"><thead><tr>';
            $ob .= '<th>Priority</th><th>Code</th><th>Indicator</th><th>Category</th><th>Recommendation</th><th>Impact</th>';
            $ob .= '</tr></thead><tbody>';
            foreach (array_slice($gaps['gaps'], 0, 30) as $gap) {
                $ind = $gap['indicator'];
                $pClass = 'priority-' . $gap['priority'];
                $ob .= '<tr>';
                $ob .= '<td><span class="' . $pClass . '">' . strtoupper($gap['priority']) . '</span></td>';
                $ob .= '<td>' . htmlspecialchars($ind['code']) . '</td>';
                $ob .= '<td>' . htmlspecialchars($ind['name']) . '</td>';
                $ob .= '<td>' . htmlspecialchars($ind['category']) . '</td>';
                $ob .= '<td>' . htmlspecialchars($gap['recommendation']) . '</td>';
                $ob .= '<td>' . htmlspecialchars($gap['impact']) . '</td>';
                $ob .= '</tr>';
            }
            $ob .= '</tbody></table>';
        }
        $ob .= '</div>';

        // Financing Opportunities
        if (!empty($gaps['financing_ops'])) {
            $sectionNum++;
            $ob .= '<div class="section"><h2>' . $sectionNum . '. Green Financing Opportunities</h2>';
            $ob .= '<table class="data-table"><thead><tr>';
            $ob .= '<th>Programme</th><th>Benefit</th><th>Status</th><th>Requirement</th>';
            $ob .= '</tr></thead><tbody>';
            foreach ($gaps['financing_ops'] as $op) {
                $statusClass = $op['status'] === 'eligible' ? 'status-done' : 'status-missing-req';
                $statusText  = $op['status'] === 'eligible' ? 'Eligible Now' : 'Action Required';
                $ob .= '<tr>';
                $ob .= '<td><strong>' . htmlspecialchars($op['name']) . '</strong></td>';
                $ob .= '<td>' . htmlspecialchars($op['benefit']) . '</td>';
                $ob .= '<td class="' . $statusClass . '">' . $statusText . '</td>';
                $ob .= '<td>' . htmlspecialchars($op['requirement']) . '</td>';
                $ob .= '</tr>';
            }
            $ob .= '</tbody></table></div>';
        }

        // Footer
        $ob .= '<div class="report-footer">';
        $ob .= '<p>This report was generated by <strong>Adcellent ESG OS</strong> on ' . $generated . '.</p>';
        $ob .= '<p>This report is intended for internal use and ESG disclosure purposes. ';
        $ob .= 'Final reports should be reviewed by a qualified ESG consultant or auditor.</p>';
        $ob .= '<p style="color:#16a34a">Adcellent ESG OS — Automate compliance, secure supply chains, and unlock financing.</p>';
        $ob .= '</div>';
        $ob .= '</body></html>';

        return $ob;
    }

    /**
     * Report CSS styles
     */
    private static function reportCSS(): string {
        return '
            body { font-family: Arial, sans-serif; color: #1e293b; margin: 0; padding: 0; font-size: 13px; }
            .cover { background: linear-gradient(135deg, #16a34a 0%, #0d9488 100%); color: white;
                     padding: 80px 60px; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; }
            .cover-logo { font-size: 14px; font-weight: bold; letter-spacing: 2px; opacity: 0.8; margin-bottom: 40px; }
            .cover h1 { font-size: 36px; margin: 0 0 10px; }
            .cover h2 { font-size: 22px; font-weight: normal; margin: 0 0 20px; opacity: 0.9; }
            .framework-badge { display: inline-block; background: rgba(255,255,255,0.2);
                               padding: 6px 16px; border-radius: 20px; font-size: 12px; margin-bottom: 40px; }
            .cover-meta { display: flex; gap: 40px; margin-bottom: 40px; }
            .meta-item span { display: block; font-size: 11px; opacity: 0.7; margin-bottom: 4px; }
            .meta-item strong { font-size: 15px; }
            .cover-score { font-size: 20px; margin-bottom: 20px; }
            .generated { font-size: 11px; opacity: 0.6; }
            .page-break { page-break-before: always; }
            .section { padding: 40px 60px; border-bottom: 1px solid #e2e8f0; }
            .section h2 { color: #16a34a; border-bottom: 2px solid #16a34a; padding-bottom: 8px; }
            .section h3 { color: #374151; font-size: 15px; }
            .summary-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .summary-table td { text-align: center; padding: 10px; }
            .score-box { border: 2px solid #e2e8f0; border-radius: 8px; padding: 15px; }
            .score-num { font-size: 32px; font-weight: bold; }
            .score-label { font-size: 12px; color: #6b7280; margin-top: 4px; }
            .score-rating { font-size: 11px; font-weight: bold; margin-top: 4px; }
            .data-table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 12px; }
            .data-table th { background: #f1f5f9; padding: 8px 10px; text-align: left; border: 1px solid #e2e8f0; }
            .data-table td { padding: 7px 10px; border: 1px solid #e2e8f0; vertical-align: top; }
            .data-table tr:nth-child(even) { background: #f8fafc; }
            .status-done { color: #16a34a; font-weight: bold; }
            .status-missing-req { color: #dc2626; font-weight: bold; }
            .status-missing { color: #6b7280; }
            .req-star { color: #dc2626; }
            .footnote { font-size: 11px; color: #6b7280; margin-top: 8px; }
            .priority-critical { background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
            .priority-high { background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
            .priority-medium { background: #e0f2fe; color: #075985; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
            .priority-low { background: #f3f4f6; color: #6b7280; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
            .report-footer { background: #f8fafc; padding: 30px 60px; font-size: 11px; color: #6b7280; border-top: 2px solid #16a34a; }
            @media print {
                .page-break { page-break-before: always; }
                body { font-size: 11px; }
            }
        ';
    }
}
