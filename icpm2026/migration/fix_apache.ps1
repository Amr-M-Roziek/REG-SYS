# Fix Apache Script
$ErrorActionPreference = "Stop"

$vpsUser = "root"
$vpsHost = "69.62.119.120"
$scpPath = "scp"
$sshPath = "ssh"

# Check for OpenSSH
try { & $scpPath -V *>$null } catch { 
    if (Test-Path "C:\Windows\System32\OpenSSH\scp.exe") {
        $scpPath = "C:\Windows\System32\OpenSSH\scp.exe"
        $sshPath = "C:\Windows\System32\OpenSSH\ssh.exe"
    } else {
        Write-Error "SCP not found."
        exit
    }
}

Write-Host "Fixing Apache Configuration on $vpsHost..." -ForegroundColor Cyan
Write-Host ">>> PLEASE ENTER PASSWORD: AmirAmr300400@ <<<" -ForegroundColor Yellow

# 1. Upload new config
$localConfig = "icpm2026\migration\apache_default.conf"
$remoteTemp = "/root/apache_default.conf"

Write-Host "Uploading correct Apache configuration..."
& $scpPath $localConfig "$($vpsUser)@$($vpsHost):$remoteTemp"

# 2. Apply config and restart
Write-Host "Applying configuration and restarting Apache..."
$command = "mv $remoteTemp /etc/apache2/sites-available/000-default.conf && systemctl restart apache2 && systemctl status apache2"
& $sshPath "$($vpsUser)@$($vpsHost)" $command

Write-Host "Done. Try accessing http://$vpsHost/icpm2026/" -ForegroundColor Green
