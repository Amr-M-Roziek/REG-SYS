# Deploy Script for regsys.cloud
$ErrorActionPreference = "Stop"

# --- Configuration ---
$vpsUser = "root"
$vpsHost = "69.62.119.120"
$scpPath = "scp" 
$sshPath = "ssh"

# Ensure we are in the project root (icpm2026)
$ScriptPath = Split-Path -Parent $MyInvocation.MyCommand.Definition
$ProjectRoot = Resolve-Path "$ScriptPath\.."
Set-Location $ProjectRoot
Write-Host "Working Directory: $ProjectRoot" -ForegroundColor Gray

# --- Check Prerequisites ---
Write-Host "Checking prerequisites..." -ForegroundColor Cyan

# Check if SCP is available
try { & $scpPath -V *>$null } catch { 
    if (Test-Path "C:\Windows\System32\OpenSSH\scp.exe") {
        $scpPath = "C:\Windows\System32\OpenSSH\scp.exe"
        $sshPath = "C:\Windows\System32\OpenSSH\ssh.exe"
    } else {
        Write-Error "SCP not found. Please install OpenSSH Client."
        exit
    }
}

# --- Step 1: Locate SQL Files ---
Write-Host "Step 1: Locating Database SQL Files..." -ForegroundColor Cyan
$SqlSourcePath = "temp\db"

if (-not (Test-Path $SqlSourcePath)) {
    Write-Error "SQL source path '$SqlSourcePath' not found! Please check the folder exists."
    exit
}

$SqlFiles = Get-ChildItem -Path $SqlSourcePath -Filter "*.sql"
if ($SqlFiles.Count -eq 0) {
    Write-Warning "No .sql files found in $SqlSourcePath."
    $confirm = Read-Host "Do you want to continue without databases? (y/n)"
    if ($confirm -ne 'y') { exit }
} else {
    Write-Host "Found $($SqlFiles.Count) SQL files to upload:" -ForegroundColor Green
    $SqlFiles | ForEach-Object { Write-Host " - $($_.Name)" -ForegroundColor Gray }
}

# --- Step 2: Zip Project ---
Write-Host "Step 2: Zipping Project Files..." -ForegroundColor Cyan
if (Test-Path "migration\project.zip") { Remove-Item "migration\project.zip" }

# Compress everything, excluding node_modules and existing zip/sql/migration folder
$exclude = @("migration", "node_modules", ".git", ".vscode", "temp", "uploads", "*.zip", "*.sql")
Write-Host "Compressing files (excluding node_modules)... this may take a moment."
Get-ChildItem -Path "." -Exclude $exclude | Compress-Archive -DestinationPath "migration\project.zip" -Force

# --- Step 3: Upload to VPS ---
Write-Host "Step 3: Uploading to VPS ($vpsHost)..." -ForegroundColor Cyan
Write-Host ">>> PLEASE ENTER PASSWORD: AmirAmr300400@ <<<" -ForegroundColor Yellow

$filesToUpload = @("migration\project.zip", "migration\prepare_vps.sh")
# Add the SQL files found in temp\db
foreach ($sql in $SqlFiles) {
    $filesToUpload += $sql.FullName
}

foreach ($file in $filesToUpload) {
    if (Test-Path $file) {
        $fileName = Split-Path $file -Leaf
        Write-Host "Uploading $fileName..." -NoNewline
        & $scpPath $file "$($vpsUser)@$($vpsHost):~/"
        Write-Host " Done."
    }
}

# --- Step 4: Run Remote Setup ---
Write-Host "Step 4: Running Remote Setup..." -ForegroundColor Cyan
Write-Host ">>> PLEASE ENTER PASSWORD AGAIN IF PROMPTED <<<" -ForegroundColor Yellow

& $sshPath "$($vpsUser)@$($vpsHost)" "chmod +x ~/prepare_vps.sh && ~/prepare_vps.sh"

Write-Host "---------------------------------------------------" -ForegroundColor Green
Write-Host "Migration Complete!" -ForegroundColor Green
Write-Host "Verify at http://$vpsHost/icpm2026/" -ForegroundColor Green
