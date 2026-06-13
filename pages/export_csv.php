<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding'); exit;
}

$companyId = $activeCompanyId;
$format    = $_GET['format'] ?? 'esg';   // esg | action_plans | kpi_trends
$period    = $_GET['period'] ?? $activeCompany['reporting_year'];
$framework = $activeCompany['framework'];

// ── Build and stream the CSV ──────────────────────────────────────────
$safeName  = preg_replace('/[^a-z0-9_-]/i', '_', $activeCompany['name']);
$filename  = 'adcellent_' . strtolower($format) . '_' . $safeName . '_' . $period . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
// UTF-8 BOM so Excel opens correctly
fwrite($out, "\xEF\xBB\xBF");

if ($format === 'esg') {
    // ── ESG indicator data ────────────────────────────────────────────
    fputcsv($out, [
        'Indicator ID', 'Code', 'Name', 'Category', 'Framework',
        'Required', 'Unit', 'Data Type',
        'Value', 'Data Source', 'Notes', 'Verified', 'Period', 'Updated At',
    ]);

    $allIndicators = Company::getFrameworkIndicators($framework);
    $savedData     = [];
    $rows = Database::fetchAll(
        'SELECT * FROM esg_data WHERE company_id = ? AND period = ?',
        [$companyId, $period]
    );
    foreach ($rows as $r) {
        $savedData[$r['indicator_id']] = $r;
    }

    foreach ($allIndicators as $ind) {
        $saved = $savedData[$ind['indicator_id']] ?? null;
        fputcsv($out, [
            $ind['indicator_id'],
            $ind['code'],
            $ind['name'],
            $ind['category'],
            $ind['framework'] ?? $framework,
            $ind['required'] ? 'Yes' : 'No',
            $ind['unit'] ?? '',
            $ind['data_type'] ?? 'number',
            $saved['value']       ?? '',
            $saved['data_source'] ?? '',
            $saved['notes']       ?? '',
            !empty($saved['verified']) ? 'Yes' : 'No',
            $period,
            $saved['updated_at']  ?? '',
        ]);
    }

} elseif ($format === 'action_plans') {
    // ── Action plans ─────────────────────────────────────────────────
    fputcsv($out, [
        'ID', 'Title', 'Priority', 'Status',
        'Assigned To', 'Department', 'Indicator ID',
        'Description', 'Recommendation',
        'Due Date', 'Completed At', 'Created By', 'Created At',
    ]);

    $plans = ActionPlanManager::getForCompany($companyId);
    foreach ($plans as $p) {
        fputcsv($out, [
            $p['id'],
            $p['title'],
            $p['priority'],
            $p['status'],
            $p['assigned_to_name'] ?? '',
            $p['department_name']  ?? '',
            $p['indicator_id']     ?? '',
            $p['description']      ?? '',
            $p['recommendation']   ?? '',
            $p['due_date']         ?? '',
            $p['completed_at']     ?? '',
            $p['created_by_name']  ?? '',
            $p['created_at'],
        ]);
    }

} elseif ($format === 'kpi_trends') {
    // ── Monthly KPI snapshots ─────────────────────────────────────────
    fputcsv($out, [
        'Year', 'Month', 'Overall Score', 'E Score', 'S Score', 'G Score',
        'Carbon Scope 1', 'Carbon Scope 2', 'Carbon Scope 3',
        'Data Completion %', 'Indicators Filled', 'Indicators Total', 'Snapshot At',
    ]);

    $snapshots = KPITracker::getTrendOldestFirst($companyId, 36);
    foreach ($snapshots as $s) {
        fputcsv($out, [
            $s['year'],
            $s['month'],
            $s['overall_score'],
            $s['e_score'],
            $s['s_score'],
            $s['g_score'],
            $s['carbon_scope1'],
            $s['carbon_scope2'],
            $s['carbon_scope3'],
            $s['data_completion'],
            $s['indicators_filled'],
            $s['indicators_total'],
            $s['snapshot_at'],
        ]);
    }
}

fclose($out);
exit;
