param(
    [string]$BaseUrl = 'http://localhost/Satyam_Clinical',
    [string]$PhpExe = 'C:\xampp\php\php.exe'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Invoke-Seeder {
    param(
        [string]$PhpPath
    )

    if (-not (Test-Path $PhpPath)) {
        throw "PHP executable not found: $PhpPath"
    }

    Write-Host "[INFO] Running idempotent QA seeder..."
    $output = & $PhpPath "tests\sidebar_dummy_data_seeder.php" 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "Seeder failed:`n$output"
    }

    $kv = @{}
    foreach ($line in $output) {
        if ($line -match '^([A-Z_]+)=(.*)$') {
            $kv[$matches[1]] = $matches[2]
        }
    }

    if (-not $kv.ContainsKey('QA_LOGIN_EMAIL') -or -not $kv.ContainsKey('QA_LOGIN_PASSWORD')) {
        throw "Seeder output did not include QA login credentials. Output:`n$output"
    }

    Write-Host "[INFO] Seeder completed."
    return $kv
}

function Test-WebTarget {
    param(
        [Microsoft.PowerShell.Commands.WebRequestSession]$Session,
        [string]$Url,
        [string]$PhpErrorRegex,
        [string]$Label
    )

    try {
        $resp = Invoke-WebRequest -Uri $Url -WebSession $Session -MaximumRedirection 5
        $content = [string]$resp.Content
        $statusCode = [int]$resp.StatusCode
        $hasPhpRuntimeError = $content -match $PhpErrorRegex
        $redirectedToLogin = $false

        if ($resp.BaseResponse -and $resp.BaseResponse.ResponseUri) {
            $redirectedToLogin = $resp.BaseResponse.ResponseUri.AbsoluteUri -like '*login.php*'
        }

        $ok = ($statusCode -eq 200) -and (-not $hasPhpRuntimeError) -and (-not $redirectedToLogin)
        return [PSCustomObject]@{
            Label = $Label
            Url = $Url
            StatusCode = $statusCode
            PhpRuntimeError = $hasPhpRuntimeError
            RedirectedToLogin = $redirectedToLogin
            Ok = $ok
            Message = if ($ok) { 'OK' } else { 'Unexpected status/redirect/runtime error pattern' }
        }
    }
    catch {
        return [PSCustomObject]@{
            Label = $Label
            Url = $Url
            StatusCode = 0
            PhpRuntimeError = $true
            RedirectedToLogin = $true
            Ok = $false
            Message = $_.Exception.Message
        }
    }
}

try {
    $seed = Invoke-Seeder -PhpPath $PhpExe

    $email = $seed['QA_LOGIN_EMAIL']
    $password = $seed['QA_LOGIN_PASSWORD']
    $supplierId = if ($seed.ContainsKey('QA_SUPPLIER_ID')) { $seed['QA_SUPPLIER_ID'] } else { '0' }
    $productId = '0'
    if ($seed.ContainsKey('QA_PRODUCT_IDS')) {
        $productId = ($seed['QA_PRODUCT_IDS'] -split ',')[0]
    }

    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

    Write-Host "[INFO] Logging in as $email ..."
    $null = Invoke-WebRequest -Uri "$BaseUrl/login.php" -Method Post -Body @{ email = $email; password = $password } -WebSession $session -MaximumRedirection 5

    $phpErrorRegex = '(<b>Warning<\/b>:|<b>Notice<\/b>:|Fatal error:|Parse error:|Deprecated:|Uncaught\s+[A-Za-z0-9_\\]+Error)'

    $sidebarPages = @(
        'dashboard.php',
        'clients_form.php',
        'clients_list.php',
        'add-brand.php',
        'brand.php',
        'add-category.php',
        'categories.php',
        'add_medicine.php',
        'manage_medicine.php',
        'create_po.php',
        'po_list.php',
        'purchase_invoice.php',
        'invoice_list.php',
        'sales_invoice_form.php',
        'sales_invoice_list.php',
        'manage_suppliers.php',
        'sales_report.php',
        'inventory_reports.php?type=inventory_summary',
        'inventory_reports.php?type=expiry_tracking',
        'inventory_reports.php?type=stock_movements'
    )

    $activeEndpoints = @(
        'php_action/fetchClients.php',
        'php_action/fetchSalesInvoices.php',
        'php_action/getNextInvoiceNumber.php',
        "php_action/searchMedicines.php?search=QA",
        "php_action/searchProductsInvoice.php?term=QA",
        "php_action/getBatchAllocationPlan.php?product_id=$productId&quantity=2",
        "php_action/get_supplier_details.php?supplier_id=$supplierId",
        "php_action/getSupplier.php?id=$supplierId"
    )

    $results = @()

    Write-Host "[INFO] Testing sidebar pages..."
    foreach ($page in $sidebarPages) {
        $results += Test-WebTarget -Session $session -Url "$BaseUrl/$page" -PhpErrorRegex $phpErrorRegex -Label 'PAGE'
    }

    Write-Host "[INFO] Testing active module endpoints..."
    foreach ($ep in $activeEndpoints) {
        $results += Test-WebTarget -Session $session -Url "$BaseUrl/$ep" -PhpErrorRegex $phpErrorRegex -Label 'ENDPOINT'
    }

    $failures = @($results | Where-Object { -not $_.Ok })

    Write-Host ""
    Write-Host "==================== SIDEBAR FULL SMOKE SUMMARY ===================="
    Write-Host ("Total checks: " + $results.Count)
    Write-Host ("Passed: " + ($results.Count - $failures.Count))
    Write-Host ("Failed: " + $failures.Count)
    Write-Host "===================================================================="

    $results | Select-Object Label, StatusCode, RedirectedToLogin, PhpRuntimeError, Url | Format-Table -AutoSize

    if ($failures.Count -gt 0) {
        Write-Host ""
        Write-Host "Failed checks:" -ForegroundColor Red
        $failures | Select-Object Label, StatusCode, Message, Url | Format-Table -AutoSize
        exit 1
    }

    Write-Host ""
    Write-Host "Sidebar smoke test passed with zero failures." -ForegroundColor Green
    exit 0
}
catch {
    Write-Host "[ERROR] $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
