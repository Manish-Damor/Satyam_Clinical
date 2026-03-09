param(
    [string]$BaseUrl = 'http://localhost/Satyam_Clinical',
    [string]$PhpExe = 'C:\xampp\php\php.exe'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$script:PhpErrorRegex = '(<b>Warning<\/b>:|<b>Notice<\/b>:|<b>Deprecated<\/b>:|Fatal error:|Parse error:|Deprecated:|Uncaught\s+[A-Za-z0-9_\\]+Error)'

function Invoke-Seeder {
    param([string]$PhpPath)

    if (-not (Test-Path $PhpPath)) {
        throw "PHP executable not found: $PhpPath"
    }

    Write-Host "[INFO] Running QA seeder before extended smoke..."
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
        throw "Seeder output missing login credentials. Output:`n$output"
    }

    return $kv
}

function New-AuthenticatedSession {
    param(
        [string]$RootUrl,
        [string]$Email,
        [string]$Password
    )

    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $null = Invoke-WebRequest -Uri "$RootUrl/login.php" -Method Post -Body @{ email = $Email; password = $Password } -WebSession $session -MaximumRedirection 5

    # Validate the session by checking a protected page instead of relying on login redirect behavior.
    $probe = Invoke-WebRequest -Uri "$RootUrl/dashboard.php" -WebSession $session -MaximumRedirection 5
    if ($probe.BaseResponse -and $probe.BaseResponse.ResponseUri) {
        if ($probe.BaseResponse.ResponseUri.AbsoluteUri -like '*login.php*') {
            throw "Login did not establish an authenticated session."
        }
    }

    return $session
}

function Test-JsonPayload {
    param([string]$Content)

    $trimmed = $Content.Trim()
    if (-not ($trimmed.StartsWith('{') -or $trimmed.StartsWith('['))) {
        return [PSCustomObject]@{ Ok = $true; Message = 'Not JSON payload' }
    }

    try {
        $parsed = $trimmed | ConvertFrom-Json

        if ($parsed -is [System.Array]) {
            return [PSCustomObject]@{ Ok = $true; Message = 'JSON array payload' }
        }

        # Honor explicit success flags where present.
        $props = @($parsed.PSObject.Properties.Name)
        if ($props -contains 'success' -and $parsed.success -eq $false) {
            return [PSCustomObject]@{ Ok = $false; Message = "JSON payload returned success=false" }
        }

        if ($props -contains 'error') {
            $err = [string]$parsed.error
            if (-not [string]::IsNullOrWhiteSpace($err)) {
                return [PSCustomObject]@{ Ok = $false; Message = "JSON payload returned error: $err" }
            }
        }

        return [PSCustomObject]@{ Ok = $true; Message = 'JSON object payload' }
    }
    catch {
        return [PSCustomObject]@{ Ok = $false; Message = "JSON parse failed: $($_.Exception.Message)" }
    }
}

function Test-WebGet {
    param(
        [Microsoft.PowerShell.Commands.WebRequestSession]$Session,
        [string]$Url,
        [string]$Label
    )

    try {
        $resp = Invoke-WebRequest -Uri $Url -WebSession $Session -MaximumRedirection 5
        $content = [string]$resp.Content
        $statusCode = [int]$resp.StatusCode
        $redirectedToLogin = $false

        if ($resp.BaseResponse -and $resp.BaseResponse.ResponseUri) {
            $redirectedToLogin = $resp.BaseResponse.ResponseUri.AbsoluteUri -like '*login.php*'
        }

        $hasPhpRuntimeError = $content -match $script:PhpErrorRegex
        $jsonCheck = Test-JsonPayload -Content $content

        $ok = ($statusCode -eq 200) -and (-not $redirectedToLogin) -and (-not $hasPhpRuntimeError) -and $jsonCheck.Ok

        return [PSCustomObject]@{
            Label = $Label
            Method = 'GET'
            Url = $Url
            StatusCode = $statusCode
            RedirectedToLogin = $redirectedToLogin
            PhpRuntimeError = $hasPhpRuntimeError
            Ok = $ok
            Message = if ($ok) { 'OK' } else { $jsonCheck.Message }
        }
    }
    catch {
        return [PSCustomObject]@{
            Label = $Label
            Method = 'GET'
            Url = $Url
            StatusCode = 0
            RedirectedToLogin = $true
            PhpRuntimeError = $true
            Ok = $false
            Message = $_.Exception.Message
        }
    }
}

function Test-WebPost {
    param(
        [Microsoft.PowerShell.Commands.WebRequestSession]$Session,
        [string]$Url,
        [hashtable]$Body,
        [string]$Label
    )

    try {
        $resp = Invoke-WebRequest -Uri $Url -Method Post -Body $Body -WebSession $Session -MaximumRedirection 5
        $content = [string]$resp.Content
        $statusCode = [int]$resp.StatusCode
        $redirectedToLogin = $false

        if ($resp.BaseResponse -and $resp.BaseResponse.ResponseUri) {
            $redirectedToLogin = $resp.BaseResponse.ResponseUri.AbsoluteUri -like '*login.php*'
        }

        $hasPhpRuntimeError = $content -match $script:PhpErrorRegex
        $jsonCheck = Test-JsonPayload -Content $content

        $ok = ($statusCode -eq 200) -and (-not $redirectedToLogin) -and (-not $hasPhpRuntimeError) -and $jsonCheck.Ok

        return [PSCustomObject]@{
            Label = $Label
            Method = 'POST'
            Url = $Url
            StatusCode = $statusCode
            RedirectedToLogin = $redirectedToLogin
            PhpRuntimeError = $hasPhpRuntimeError
            Ok = $ok
            Message = if ($ok) { 'OK' } else { $jsonCheck.Message }
        }
    }
    catch {
        return [PSCustomObject]@{
            Label = $Label
            Method = 'POST'
            Url = $Url
            StatusCode = 0
            RedirectedToLogin = $true
            PhpRuntimeError = $true
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
    $purchaseInvoiceId = if ($seed.ContainsKey('QA_PI_ID')) { $seed['QA_PI_ID'] } else { '0' }
    $poId = if ($seed.ContainsKey('QA_PO_ID')) { $seed['QA_PO_ID'] } else { '0' }

    $salesInvoiceId = '0'
    if ($seed.ContainsKey('QA_SI_IDS')) {
        $parts = $seed['QA_SI_IDS'] -split ','
        if ($parts.Length -gt 0) {
            $salesInvoiceId = $parts[0]
        }
    }

    $productId = '0'
    if ($seed.ContainsKey('QA_PRODUCT_IDS')) {
        $parts = $seed['QA_PRODUCT_IDS'] -split ','
        if ($parts.Length -gt 0) {
            $productId = $parts[0]
        }
    }

    Write-Host "[INFO] Logging in as $email ..."
    $session = New-AuthenticatedSession -RootUrl $BaseUrl -Email $email -Password $password

    $today = Get-Date -Format 'yyyy-MM-dd'

    $extendedPages = @(
        "po_view.php?id=$poId",
        "print_po.php?id=$poId",
        "invoice_view.php?id=$purchaseInvoiceId",
        "purchase_invoice_print.php?id=$purchaseInvoiceId",
        "sales_invoice_edit.php?id=$salesInvoiceId",
        "print_invoice.php?id=$salesInvoiceId",
        "view_supplier_po.php?id=$supplierId"
    )

    $extendedGetEndpoints = @(
        "php_action/get_next_entry_reference.php?date=$today",
        "php_action/getSupplierInfo.php?id=$supplierId",
        "php_action/getSupplier.php?id=$supplierId",
        "php_action/getNextInvoiceNumber.php",
        "php_action/fetchSalesInvoices.php",
        "php_action/fetchClients.php",
        "php_action/searchMedicines.php?search=QA",
        "php_action/searchProductsInvoice.php?q=QA"
    )

    $extendedPostEndpoints = @(
        @{ Path = 'php_action/getBatchAllocationPlan.php'; Body = @{ product_id = $productId; quantity = 2 } },
        @{ Path = 'php_action/get_supplier_details.php'; Body = @{ supplier_id = $supplierId } },
        @{ Path = 'php_action/fetchProductInvoice.php'; Body = @{ product_id = $productId } },
        @{ Path = 'php_action/fetchSelectedProduct.php'; Body = @{ productId = $productId } }
    )

    $results = @()

    Write-Host "[INFO] Testing extended non-sidebar pages..."
    foreach ($path in $extendedPages) {
        $results += Test-WebGet -Session $session -Url "$BaseUrl/$path" -Label 'EXT_PAGE'
    }

    Write-Host "[INFO] Testing extended GET endpoints..."
    foreach ($path in $extendedGetEndpoints) {
        $results += Test-WebGet -Session $session -Url "$BaseUrl/$path" -Label 'EXT_ENDPOINT_GET'
    }

    Write-Host "[INFO] Testing extended POST endpoints..."
    foreach ($ep in $extendedPostEndpoints) {
        $results += Test-WebPost -Session $session -Url "$BaseUrl/$($ep.Path)" -Body $ep.Body -Label 'EXT_ENDPOINT_POST'
    }

    $failures = @($results | Where-Object { -not $_.Ok })

    Write-Host ""
    Write-Host "==================== EXTENDED PROJECT SMOKE SUMMARY =================="
    Write-Host ("Total checks: " + $results.Count)
    Write-Host ("Passed: " + ($results.Count - $failures.Count))
    Write-Host ("Failed: " + $failures.Count)
    Write-Host "======================================================================"

    $results | Select-Object Label, Method, StatusCode, RedirectedToLogin, PhpRuntimeError, Url | Format-Table -AutoSize

    if ($failures.Count -gt 0) {
        Write-Host ""
        Write-Host "Failed checks:" -ForegroundColor Red
        $failures | Select-Object Label, Method, StatusCode, Message, Url | Format-Table -AutoSize
        exit 1
    }

    Write-Host ""
    Write-Host "Extended project smoke test passed with zero failures." -ForegroundColor Green
    exit 0
}
catch {
    Write-Host "[ERROR] $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
