param(
  [string]$BaseUrl = "https://reg-sys.com/icpm2026/poster26/index.php",
  [string]$OutDir = "$PSScriptRoot\\screenshots"
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
if (-not (Test-Path $OutDir)) { New-Item -ItemType Directory -Path $OutDir | Out-Null }
function New-RandString([int]$len=8){
  $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  -join (1..$len | ForEach-Object { $chars[(Get-Random -Minimum 0 -Maximum $chars.Length)] })
}
function New-RandEmail(){
  (New-RandString 8).ToLower() + '+' + (Get-Date -Format 'HHmmssfff') + '@example.com'
}
if (-not (Get-Module -ListAvailable -Name Selenium)) { Install-Module Selenium -Scope CurrentUser -Force }
Import-Module Selenium
$driver = Start-SeNewEdge
Enter-SeUrl -Driver $driver -Url $BaseUrl
Start-Sleep -Milliseconds 500
$email = New-RandEmail
(Find-SeElement -Driver $driver -By Name -Value 'fname').SendKeys('Browser User')
$selNat = New-Object OpenQA.Selenium.Support.UI.SelectElement(Find-SeElement -Driver $driver -By Name -Value 'nationality')
$selNat.SelectByText('United States')
(Find-SeElement -Driver $driver -By Name -Value 'email').SendKeys($email)
$selCo = New-Object OpenQA.Selenium.Support.UI.SelectElement(Find-SeElement -Driver $driver -By Name -Value 'coauthors_count')
$selCo.SelectByValue('0')
$selProf = New-Object OpenQA.Selenium.Support.UI.SelectElement(Find-SeElement -Driver $driver -By Name -Value 'profession')
$selProf.SelectByText('Physician')
$selCat = New-Object OpenQA.Selenium.Support.UI.SelectElement(Find-SeElement -Driver $driver -By Name -Value 'category')
$selCat.SelectByText('Poster Competetion')
(Find-SeElement -Driver $driver -By Name -Value 'organization').SendKeys('BrowserOrg')
(Find-SeElement -Driver $driver -By Name -Value 'password').SendKeys('BrowserPass123!')
(Find-SeElement -Driver $driver -By Name -Value 'contact').SendKeys('1234567890')
$filePath = Join-Path (Split-Path $PSScriptRoot -Parent) 'files\\poster.pdf'
try {
  (Find-SeElement -Driver $driver -By Name -Value 'abstract_file').SendKeys($filePath)
} catch {
  (Find-SeElement -Driver $driver -By Id -Value 'abstractFile').SendKeys($filePath)
}
(Find-SeElement -Driver $driver -By CssSelector -Value "input[type='submit'][name='signup']").Click()
Start-Sleep -Milliseconds 500
$ok = $false
for ($i=0; $i -lt 40; $i++){
  if ($driver.Url -match 'welcome\\.php') { $ok = $true; break }
  Start-Sleep -Milliseconds 250
}
$shot = Join-Path $OutDir 'welcome-browser.png'
$driver.GetScreenshot().SaveAsFile($shot, [OpenQA.Selenium.ScreenshotImageFormat]::Png)
$title = $driver.Title
if ($ok) {
  $out = Join-Path $OutDir 'welcome-browser.txt'
  "OK url=$($driver.Url) title=$title email=$email" | Out-File -FilePath $out -Encoding utf8
} else {
  $out = Join-Path $OutDir 'welcome-browser.txt'
  "FAIL url=$($driver.Url) title=$title email=$email" | Out-File -FilePath $out -Encoding utf8
}
$driver.Quit()
