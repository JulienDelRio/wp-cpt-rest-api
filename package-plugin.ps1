# WordPress Plugin Packaging Script
# Creates a clean distribution package for wp-cpt-rest-api plugin

$PluginName = "wp-cpt-rest-api"
$Version = "1.0.0-RC1"
$BuildDir = "build"
$PackageName = "$PluginName-$Version"

Write-Host "Packaging $PluginName version $Version..." -ForegroundColor Green

# Clean previous build
if (Test-Path $BuildDir) {
    Remove-Item -Recurse -Force $BuildDir
}
New-Item -ItemType Directory -Path "$BuildDir/$PluginName" -Force | Out-Null

# Copy plugin files
Write-Host "Copying plugin files..." -ForegroundColor Cyan
Copy-Item "src/wp-cpt-rest-api.php" "$BuildDir/$PluginName/"
Copy-Item "src/readme.txt" "$BuildDir/$PluginName/"
Copy-Item "src/uninstall.php" "$BuildDir/$PluginName/"
Copy-Item "LICENSE" "$BuildDir/$PluginName/"
Copy-Item "src/API_ENDPOINTS.md" "$BuildDir/$PluginName/"
Copy-Item "src/OPENAPI.md" "$BuildDir/$PluginName/"

# Copy directories
Write-Host "Copying directories..." -ForegroundColor Cyan
Copy-Item "src/admin" "$BuildDir/$PluginName/" -Recurse
Copy-Item "src/includes" "$BuildDir/$PluginName/" -Recurse
Copy-Item "src/rest-api" "$BuildDir/$PluginName/" -Recurse
Copy-Item "src/swagger" "$BuildDir/$PluginName/" -Recurse
Copy-Item "src/assets" "$BuildDir/$PluginName/" -Recurse
Copy-Item "src/languages" "$BuildDir/$PluginName/" -Recurse

# Remove development files
Write-Host "Removing development files..." -ForegroundColor Yellow
Get-ChildItem -Path "$BuildDir/$PluginName" -Recurse -Force |
    Where-Object { $_.Name -eq '.vscode' -or $_.Name -eq 'tests' -or $_.Name -eq '.DS_Store' } |
    Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

# Create ZIP package
Write-Host "Creating ZIP package..." -ForegroundColor Cyan
if (Test-Path "$PackageName.zip") {
    Remove-Item "$PackageName.zip" -Force
}

# Change to build directory and compress from there to avoid nesting issues
$CurrentDir = Get-Location
Set-Location $BuildDir
Compress-Archive -Path $PluginName -DestinationPath "../$PackageName.zip" -Force
Set-Location $CurrentDir

$FileSize = (Get-Item "$PackageName.zip").Length / 1KB
Write-Host "`nPackage created: $PackageName.zip" -ForegroundColor Green
Write-Host "Package size: $([math]::Round($FileSize, 2)) KB" -ForegroundColor Green

# Show package structure
Write-Host "`nPackage structure:" -ForegroundColor Cyan
Get-ChildItem -Path "$BuildDir/$PluginName" -Recurse -Name | Sort-Object | Select-Object -First 30

Write-Host "`nPackaging complete!" -ForegroundColor Green
Write-Host "Distribution package ready: $PackageName.zip" -ForegroundColor Yellow
