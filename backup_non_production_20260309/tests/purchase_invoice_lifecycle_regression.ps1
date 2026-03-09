param(
    [string]$BaseUrl = 'http://localhost/Satyam_Clinical',
    [string]$PhpExe = 'C:\xampp\php\php.exe'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Assert-Condition {
    param(
        [bool]$Condition,
        [string]$Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

function Invoke-Seeder {
    param([string]$PhpPath)

    if (-not (Test-Path $PhpPath)) {
        throw "PHP executable not found: $PhpPath"
    }

    Write-Host "[INFO] Running QA seeder..."
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

    return $kv
}

function New-AuthenticatedSession {
    param(
        [string]$RootUrl,
        [string]$Email,
        [string]$Password
    )

    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

    $null = Invoke-WebRequest -Uri "$RootUrl/login.php" -Method Post -Body @{
        email = $Email
        password = $Password
    } -WebSession $session -MaximumRedirection 5

    $probe = Invoke-WebRequest -Uri "$RootUrl/dashboard.php" -WebSession $session -MaximumRedirection 5
    $redirectedToLogin = $false
    if ($probe.BaseResponse -and $probe.BaseResponse.ResponseUri) {
        $redirectedToLogin = $probe.BaseResponse.ResponseUri.AbsoluteUri -like '*login.php*'
    }

    Assert-Condition (-not $redirectedToLogin) 'Login did not establish an authenticated session'

    return $session
}

function Invoke-JsonRequest {
    param(
        [Microsoft.PowerShell.Commands.WebRequestSession]$Session,
        [string]$Url,
        [ValidateSet('POST', 'GET')][string]$Method,
        [object]$Body,
        [string]$ContentType = 'application/x-www-form-urlencoded'
    )

    if ($Method -eq 'GET') {
        $resp = Invoke-WebRequest -Uri $Url -Method Get -WebSession $Session -MaximumRedirection 5
    } elseif ($ContentType -eq 'application/json') {
        $jsonBody = $Body | ConvertTo-Json -Depth 10 -Compress
        $resp = Invoke-WebRequest -Uri $Url -Method Post -Body $jsonBody -ContentType $ContentType -WebSession $Session -MaximumRedirection 5
    } else {
        $resp = Invoke-WebRequest -Uri $Url -Method Post -Body $Body -WebSession $Session -MaximumRedirection 5
    }

    $raw = [string]$resp.Content
    try {
        $json = $raw | ConvertFrom-Json
    }
    catch {
        throw "Expected JSON response from $Url but got:`n$raw"
    }

    return [PSCustomObject]@{
        StatusCode = [int]$resp.StatusCode
        Json = $json
        Raw = $raw
    }
}

function Get-ApiMessage {
    param($Json)

    $props = @($Json.PSObject.Properties.Name)
    if ($props -contains 'error' -and -not [string]::IsNullOrWhiteSpace([string]$Json.error)) {
        return [string]$Json.error
    }
    if ($props -contains 'message' -and -not [string]::IsNullOrWhiteSpace([string]$Json.message)) {
        return [string]$Json.message
    }
    return ''
}

function Assert-ApiSuccess {
    param(
        $Response,
        [string]$Label
    )

    Assert-Condition ($Response.StatusCode -eq 200) "$Label failed with HTTP $($Response.StatusCode)"

    $props = @($Response.Json.PSObject.Properties.Name)
    $hasSuccess = $props -contains 'success'
    Assert-Condition $hasSuccess "$Label response missing success flag: $($Response.Raw)"
    Assert-Condition ([bool]$Response.Json.success) "$Label expected success but got: $($Response.Raw)"
}

function Assert-ApiFailure {
    param(
        $Response,
        [string]$ExpectedMessagePart,
        [string]$Label
    )

    $props = @($Response.Json.PSObject.Properties.Name)
    $hasSuccess = $props -contains 'success'
    Assert-Condition $hasSuccess "$Label response missing success flag: $($Response.Raw)"
    Assert-Condition (-not [bool]$Response.Json.success) "$Label expected failure but got success: $($Response.Raw)"

    if (-not [string]::IsNullOrWhiteSpace($ExpectedMessagePart)) {
        $msg = Get-ApiMessage -Json $Response.Json
        Assert-Condition ($msg -like "*$ExpectedMessagePart*") "$Label expected message containing '$ExpectedMessagePart' but got '$msg'"
    }
}

function New-PurchaseInvoicePayload {
    param(
        [int]$SupplierId,
        [int]$ProductId,
        [string]$Status,
        [string]$Suffix
    )

    $today = Get-Date -Format 'yyyy-MM-dd'
    $dueDate = (Get-Date).AddDays(30).ToString('yyyy-MM-dd')
    $expiryDate = (Get-Date).AddDays(365).ToString('yyyy-MM-dd')
    $mfgDate = (Get-Date).AddDays(-15).ToString('yyyy-MM-dd')

    return @{
        supplier_id = $SupplierId
        invoice_no = "TMP-$Suffix"
        supplier_invoice_no = "LIFE-$Suffix"
        supplier_invoice_date = $today
        invoice_date = $today
        due_date = $dueDate
        gst_type = 'intrastate'
        place_of_supply = 'Gujarat'
        payment_status = 'UNPAID'
        payment_mode = 'Credit'
        paid_amount = 0
        status = $Status
        notes = "Lifecycle regression invoice $Suffix"
        items = @(
            @{
                product_id = $ProductId
                product_name = 'QA Lifecycle Medicine'
                batch_no = "LCY-$Suffix"
                manufacture_date = $mfgDate
                expiry_date = $expiryDate
                qty = 5
                free_qty = 0
                unit_cost = 10
                mrp = 15
                discount_percent = 0
                tax_rate = 12
            }
        )
    }
}

try {
    $seed = Invoke-Seeder -PhpPath $PhpExe

    Assert-Condition ($seed.ContainsKey('QA_LOGIN_EMAIL')) 'Seeder did not return QA_LOGIN_EMAIL'
    Assert-Condition ($seed.ContainsKey('QA_LOGIN_PASSWORD')) 'Seeder did not return QA_LOGIN_PASSWORD'
    Assert-Condition ($seed.ContainsKey('QA_SUPPLIER_ID')) 'Seeder did not return QA_SUPPLIER_ID'
    Assert-Condition ($seed.ContainsKey('QA_PRODUCT_IDS')) 'Seeder did not return QA_PRODUCT_IDS'

    $email = [string]$seed['QA_LOGIN_EMAIL']
    $password = [string]$seed['QA_LOGIN_PASSWORD']
    $supplierId = [int]$seed['QA_SUPPLIER_ID']
    $productIds = ([string]$seed['QA_PRODUCT_IDS']) -split ','
    $productId = [int]$productIds[0]

    Assert-Condition ($supplierId -gt 0) 'Invalid supplier id from seeder'
    Assert-Condition ($productId -gt 0) 'Invalid product id from seeder'

    Write-Host "[INFO] Logging in as $email ..."
    $session = New-AuthenticatedSession -RootUrl $BaseUrl -Email $email -Password $password

    $suffixA = [DateTime]::UtcNow.ToString('yyyyMMddHHmmssfff')
    $payloadA = New-PurchaseInvoicePayload -SupplierId $supplierId -ProductId $productId -Status 'Matched' -Suffix $suffixA

    $createA = Invoke-JsonRequest -Session $session -Url "$BaseUrl/php_action/create_purchase_invoice.php" -Method POST -Body $payloadA -ContentType 'application/json'
    Assert-ApiSuccess -Response $createA -Label 'Create invoice with legacy status'

    $invoiceA = [int]$createA.Json.invoice_id
    Assert-Condition ($invoiceA -gt 0) "Create response missing invoice_id: $($createA.Raw)"
    Write-Host "[PASS] Created invoice A: $invoiceA"

    $approveA = Invoke-JsonRequest -Session $session -Url "$BaseUrl/php_action/po_actions.php" -Method POST -Body @{
        action = 'approve_invoice'
        invoice_id = $invoiceA
    }
    Assert-ApiSuccess -Response $approveA -Label 'Approve draft invoice A'
    Write-Host '[PASS] Draft invoice approved successfully'

    $approveAgainA = Invoke-JsonRequest -Session $session -Url "$BaseUrl/php_action/po_actions.php" -Method POST -Body @{
        action = 'approve_invoice'
        invoice_id = $invoiceA
    }
    Assert-ApiFailure -Response $approveAgainA -ExpectedMessagePart 'Only Draft invoices can be approved' -Label 'Re-approve approved invoice A'
    Write-Host '[PASS] Re-approve guard enforced'

    $deleteApprovedA = Invoke-JsonRequest -Session $session -Url "$BaseUrl/php_action/po_actions.php" -Method POST -Body @{
        action = 'delete_invoice'
        invoice_id = $invoiceA
    }
    Assert-ApiFailure -Response $deleteApprovedA -ExpectedMessagePart 'Only Draft invoices can be deleted' -Label 'Delete approved invoice A'
    Write-Host '[PASS] Delete guard enforced for approved invoice'

    $editApprovedA = Invoke-JsonRequest -Session $session -Url "$BaseUrl/php_action/po_edit_action.php" -Method POST -Body @{
        invoice_id = $invoiceA
        'items[0][product_id]' = $productId
    }
    Assert-ApiFailure -Response $editApprovedA -ExpectedMessagePart 'Only Draft invoices can be edited' -Label 'Edit approved invoice A'
    Write-Host '[PASS] Edit guard enforced for approved invoice'

    $suffixB = [DateTime]::UtcNow.AddMilliseconds(1).ToString('yyyyMMddHHmmssfff')
    $payloadB = New-PurchaseInvoicePayload -SupplierId $supplierId -ProductId $productId -Status 'Draft' -Suffix $suffixB

    $createB = Invoke-JsonRequest -Session $session -Url "$BaseUrl/php_action/create_purchase_invoice.php" -Method POST -Body $payloadB -ContentType 'application/json'
    Assert-ApiSuccess -Response $createB -Label 'Create draft invoice B'

    $invoiceB = [int]$createB.Json.invoice_id
    Assert-Condition ($invoiceB -gt 0) "Create draft response missing invoice_id: $($createB.Raw)"
    Write-Host "[PASS] Created invoice B: $invoiceB"

    $deleteB = Invoke-JsonRequest -Session $session -Url "$BaseUrl/php_action/po_actions.php" -Method POST -Body @{
        action = 'delete_invoice'
        invoice_id = $invoiceB
    }
    Assert-ApiSuccess -Response $deleteB -Label 'Delete draft invoice B'
    Write-Host '[PASS] Draft delete path works'

    Write-Host ''
    Write-Host 'Purchase invoice lifecycle regression passed.' -ForegroundColor Green
    exit 0
}
catch {
    Write-Host "[FAIL] $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
