# WordPress Plugin Packaging Script
# Creates a clean distribution package for wp-cpt-rest-api plugin

$PluginName = "wp-cpt-rest-api"

# Extract version from readme.txt
$ReadmePath = "src/readme.txt"
if (-not (Test-Path $ReadmePath)) {
    Write-Host "Error: $ReadmePath not found!" -ForegroundColor Red
    exit 1
}

$ReadmeContent = Get-Content $ReadmePath -Raw
if ($ReadmeContent -match 'Stable tag:\s*([0-9]+\.[0-9]+\.[0-9]+)') {
    $Version = $Matches[1]
} else {
    Write-Host "Error: Could not extract version from $ReadmePath" -ForegroundColor Red
    Write-Host "Please ensure 'Stable tag:' line exists with version number (e.g., 'Stable tag: 1.0.1')" -ForegroundColor Yellow
    exit 1
}

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

# Create ZIP package using .NET System.IO.Compression for cross-platform compatibility
Write-Host "Creating ZIP package..." -ForegroundColor Cyan
if (Test-Path "$PackageName.zip") {
    Remove-Item "$PackageName.zip" -Force
}

# Load .NET compression assemblies
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

# Create ZIP with proper forward slashes for WordPress compatibility
$SourcePath = Resolve-Path "$BuildDir/$PluginName"
$DestinationPath = Join-Path -Path (Get-Location) -ChildPath "$PackageName.zip"

# Create ZIP archive manually to control path separators
$zip = [System.IO.Compression.ZipFile]::Open($DestinationPath, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    # Get all files and add them with forward slashes
    Get-ChildItem -Path $SourcePath -Recurse -File | ForEach-Object {
        $relativePath = $_.FullName.Substring($SourcePath.Path.Length + 1)
        # Replace backslashes with forward slashes for cross-platform compatibility
        $zipEntryName = "$PluginName/" + $relativePath.Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $zipEntryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }

    # Add empty directories
    Get-ChildItem -Path $SourcePath -Recurse -Directory | ForEach-Object {
        $relativePath = $_.FullName.Substring($SourcePath.Path.Length + 1)
        # Replace backslashes with forward slashes and add trailing slash for directories
        $zipEntryName = "$PluginName/" + $relativePath.Replace('\', '/') + '/'
        # Only add if directory is empty
        if ((Get-ChildItem -Path $_.FullName -Force | Measure-Object).Count -eq 0) {
            $zip.CreateEntry($zipEntryName) | Out-Null
        }
    }
} finally {
    $zip.Dispose()
}

$FileSize = (Get-Item "$PackageName.zip").Length / 1KB
Write-Host "`nPackage created: $PackageName.zip" -ForegroundColor Green
Write-Host "Package size: $([math]::Round($FileSize, 2)) KB" -ForegroundColor Green

# Show package structure
Write-Host "`nPackage structure:" -ForegroundColor Cyan
Get-ChildItem -Path "$BuildDir/$PluginName" -Recurse -Name | Sort-Object | Select-Object -First 30

Write-Host "`nPackaging complete!" -ForegroundColor Green
Write-Host "Distribution package ready: $PackageName.zip" -ForegroundColor Yellow
