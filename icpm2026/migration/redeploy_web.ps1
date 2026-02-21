# Re-Deploy Web Files Script
$ErrorActionPreference = "Stop"

$vpsUser = "root"
$vpsHost = "69.62.119.120"
$scpPath = "C:\Windows\System32\OpenSSH\scp.exe"
$sshPath = "C:\Windows\System32\OpenSSH\ssh.exe"

# Ensure we are in the project root
$ScriptPath = Split-Path -Parent $MyInvocation.MyCommand.Definition
$ProjectRoot = Resolve-Path "$ScriptPath\.."
Set-Location $ProjectRoot
Write-Host "Project Root: $ProjectRoot"

# 1. Zip Project
Write-Host "Zipping project files..." -ForegroundColor Cyan
if (Test-Path "migration\project.zip") { Remove-Item "migration\project.zip" }

# Exclude list
$exclude = @("migration", "node_modules", ".git", ".vscode", "temp", "uploads", "*.zip", "*.sql", "icpm_backend", "icpm_webbackend")
Get-ChildItem -Path "." -Exclude $exclude | Compress-Archive -DestinationPath "migration\project.zip" -Force

# 2. Upload
Write-Host "Uploading project.zip to VPS..." -ForegroundColor Cyan
Write-Host ">>> PLEASE ENTER PASSWORD: AmirAmr300400@ <<<" -ForegroundColor Yellow
& $scpPath "migration\project.zip" "$($vpsUser)@$($vpsHost):/root/project.zip"

# 3. Unzip on Server
Write-Host "Unzipping on VPS..." -ForegroundColor Cyan
$remoteCommands = @(
    "mkdir -p /var/www/html/icpm2026",
    "apt-get install -y unzip",
    "unzip -o /root/project.zip -d /var/www/html/icpm2026",
    "chown -R www-data:www-data /var/www/html/icpm2026",
    "chmod -R 755 /var/www/html/icpm2026",
    "ls -la /var/www/html/icpm2026 | head -n 10"
)
$cmdString = $remoteCommands -join " && "
& $sshPath "$($vpsUser)@$($vpsHost)" $cmdString

Write-Host "---------------------------------------------------"
Write-Host "Web files re-deployed." -ForegroundColor Green
