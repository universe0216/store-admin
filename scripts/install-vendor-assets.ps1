$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

npm install --no-save jqwidgets-scripts@16.0.0 bootstrap@5.3.3 jquery@3.7.1 chart.js@4.4.9

$assets = Join-Path $root "public\assets"
New-Item -ItemType Directory -Force -Path (Join-Path $assets "css"), (Join-Path $assets "js") | Out-Null

Copy-Item "node_modules\bootstrap\dist\css\bootstrap.min.css" (Join-Path $assets "css\") -Force
Copy-Item "node_modules\bootstrap\dist\js\bootstrap.bundle.min.js" (Join-Path $assets "js\") -Force
Copy-Item "node_modules\jquery\dist\jquery.min.js" (Join-Path $assets "js\") -Force
Copy-Item "node_modules\chart.js\dist\chart.umd.js" (Join-Path $assets "js\chart.umd.js") -Force

$jqDest = Join-Path $assets "jqwidgets"
if (Test-Path $jqDest) {
    Remove-Item $jqDest -Recurse -Force
}
Copy-Item "node_modules\jqwidgets-scripts\jqwidgets" $jqDest -Recurse -Force

Write-Host "Vendor assets installed to public/assets"
