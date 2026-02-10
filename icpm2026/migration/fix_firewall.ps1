# Fix Firewall Script
$ErrorActionPreference = "Stop"

$vpsUser = "root"
$vpsHost = "69.62.119.120"
$sshPath = "C:\Windows\System32\OpenSSH\ssh.exe"

Write-Host "Opening Port 8081 on Firewall..." -ForegroundColor Cyan
Write-Host ">>> PLEASE ENTER PASSWORD: AmirAmr300400@ <<<" -ForegroundColor Yellow

$commands = @(
    # 1. Allow port 8081
    "ufw allow 8081/tcp",
    "ufw reload",
    
    # 2. Ensure Apache is actually listening on 8081 (Just in case previous script didn't finish)
    "sed -i 's/Listen 80$/Listen 8081/' /etc/apache2/ports.conf",
    "sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8081>/' /etc/apache2/sites-available/000-default.conf",
    "systemctl restart apache2",

    # 3. Verify
    "echo '--- UFW Status ---'",
    "ufw status | grep 8081",
    "echo '--- Port Status ---'",
    "netstat -tulpn | grep 8081"
)

$remoteCommand = $commands -join " && "

& $sshPath "$($vpsUser)@$($vpsHost)" $remoteCommand

Write-Host "`n---------------------------------------------------"
Write-Host "Firewall rule added." -ForegroundColor Green
Write-Host "Try accessing: http://$vpsHost:8081/icpm2026/" -ForegroundColor Green
