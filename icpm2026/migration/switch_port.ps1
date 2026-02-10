# Switch Apache to Port 8081 Script
$ErrorActionPreference = "Stop"

$vpsUser = "root"
$vpsHost = "69.62.119.120"
$sshPath = "C:\Windows\System32\OpenSSH\ssh.exe"

Write-Host "Switching Apache to Port 8081 on $vpsHost..." -ForegroundColor Cyan
Write-Host ">>> PLEASE ENTER PASSWORD: AmirAmr300400@ <<<" -ForegroundColor Yellow

$commands = @(
    # 1. Change ports.conf
    "sed -i 's/Listen 80/Listen 8081/' /etc/apache2/ports.conf",
    
    # 2. Change default vhost port
    "sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8081>/' /etc/apache2/sites-available/000-default.conf",
    
    # 3. Restart Apache
    "systemctl restart apache2",
    
    # 4. Check status
    "systemctl status apache2 --no-pager",
    
    # 5. Check port
    "netstat -tulpn | grep :8081"
)

$remoteCommand = $commands -join " && "

& $sshPath "$($vpsUser)@$($vpsHost)" $remoteCommand

Write-Host "---------------------------------------------------"
Write-Host "Apache should now be running on port 8081." -ForegroundColor Green
Write-Host "Try accessing: http://$vpsHost:8081/icpm2026/" -ForegroundColor Green
