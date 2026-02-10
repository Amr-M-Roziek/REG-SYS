param(
  [string]$BaseUrl = "http://localhost/reg-sys.com/icpm2026/poster26/index.php",
  [string]$OutDir = "$PSScriptRoot\\screenshots"
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
if (-not (Test-Path $OutDir)) { New-Item -ItemType Directory -Path $OutDir | Out-Null }
function New-RandString([int]$len=12){
  $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'
  -join (1..$len | ForEach-Object { $chars[(Get-Random -Minimum 0 -Maximum $chars.Length)] })
}
function New-RandEmail(){
  $u = (New-RandString 8).ToLower()
  $d = ('example','testmail','mailhost','icpm','localdomain')[(Get-Random -Minimum 0 -Maximum 5)]
  "$u+$([DateTime]::UtcNow.ToString('HHmmssfff'))@$d.com"
}
function New-RandPhone(){ ('+1',(Get-Random -Minimum 100 -Maximum 999),(Get-Random -Minimum 100 -Maximum 999),(Get-Random -Minimum 1000 -Maximum 9999) -join '-') }
function Save-Text([string]$name, [string]$text){
  $p = Join-Path $OutDir ($name + '.txt')
  Set-Content -Path $p -Value $text -Encoding UTF8
}
$fileOk = Join-Path (Split-Path $PSScriptRoot -Parent) 'files\\poster.pdf'
$nat = 'United States'
$prof = 'Physician'
$org = 'TestOrg'
$baseEmail = New-RandEmail
$cases = @(
  @{ name='T1-valid-0co'; coCount=0; email=$baseEmail; fname='Alice'; nationality=$nat; profession=$prof; organization=$org; password=(New-RandString 12); contact='1234567890'; filePath=$fileOk },
  @{ name='T2-valid-1co'; coCount=1; email=(New-RandEmail); coemail1=(New-RandEmail); fname='Bob'; nationality=$nat; profession=$prof; organization=$org; password=(New-RandString 14); contact='2345678901'; filePath=$fileOk },
  @{ name='T3-valid-5co'; coCount=5; email=(New-RandEmail); coemail1=(New-RandEmail); coemail2=(New-RandEmail); coemail3=(New-RandEmail); coemail4=(New-RandEmail); coemail5=(New-RandEmail); fname='Cara'; nationality=$nat; profession=$prof; organization=$org; password=(New-RandString 16); contact='3456789012'; filePath=$fileOk },
  @{ name='T4-coemail-equals-main'; coCount=1; email=(New-RandEmail); coemail1=$baseEmail; fname='Dan'; nationality=$nat; profession=$prof; organization=$org; password=(New-RandString 12); contact='4567890123'; filePath=$fileOk },
  @{ name='T5-duplicate-coemails'; coCount=2; email=(New-RandEmail); coemail1='dup@example.com'; coemail2='dup@example.com'; fname='Eve'; nationality=$nat; profession=$prof; organization=$org; password=(New-RandString 12); contact='5678901234'; filePath=$fileOk },
  @{ name='T6-invalid-coemail-format'; coCount=1; email=(New-RandEmail); coemail1='bademail'; fname='Finn'; nationality=$nat; profession=$prof; organization=$org; password=(New-RandString 12); contact='6789012345'; filePath=$fileOk },
  @{ name='T7-missing-abstract'; coCount=0; email=(New-RandEmail); fname='Gail'; nationality=$nat; profession=$prof; organization=$org; password=(New-RandString 12); contact='7890123456'; filePath='' },
  @{ name='T8-invalid-file-type'; coCount=0; email=(New-RandEmail); fname='Hank'; nationality=$nat; profession=$prof; organization=$org; password=(New-RandString 12); contact='8901234567'; filePath=(Join-Path (Split-Path $PSScriptRoot -Parent) 'index.php') },
  @{ name='T9-weak-password'; coCount=0; email=(New-RandEmail); fname='Ivy'; nationality=$nat; profession=$prof; organization=$org; password='12345'; contact='9012345678'; filePath=$fileOk },
  @{ name='T10-duplicate-email'; coCount=0; email=$baseEmail; fname='Jake'; nationality=$nat; profession=$prof; organization=$org; password=(New-RandString 12); contact='0123456789'; filePath=$fileOk }
)
$results = @()
foreach ($tc in $cases){
  $cookieJar = Join-Path $OutDir ($tc.name + '.cookies.txt')
  $hdr = Join-Path $OutDir ($tc.name + '.headers.txt')
  $body = Join-Path $OutDir ($tc.name + '.html')
  $args = @()
  $args += '-s'
  $args += '-D'; $args += $hdr
  $args += '-c'; $args += $cookieJar
  $args += '-b'; $args += $cookieJar
  $args += '-o'; $args += $body
  $args += '-F'; $args += "fname=$($tc.fname)"
  $args += '-F'; $args += "nationality=$($tc.nationality)"
  $args += '-F'; $args += "email=$($tc.email)"
  $args += '-F'; $args += "coauthors_count=$($tc.coCount)"
  if ($tc.coCount -ge 1) { $args += '-F'; $args += "coauth1name=CoAuthor1 $($tc.fname)"; $args += '-F'; $args += "coauth1nationality=$($tc.nationality)"; $args += '-F'; $args += "coauth1email=$($tc.coemail1)" }
  if ($tc.coCount -ge 2) { $args += '-F'; $args += "coauth2name=CoAuthor2 $($tc.fname)"; $args += '-F'; $args += "coauth2nationality=$($tc.nationality)"; $args += '-F'; $args += "coauth2email=$($tc.coemail2)" }
  if ($tc.coCount -ge 3) { $args += '-F'; $args += "coauth3name=CoAuthor3 $($tc.fname)"; $args += '-F'; $args += "coauth3nationality=$($tc.nationality)"; $args += '-F'; $args += "coauth3email=$($tc.coemail3)" }
  if ($tc.coCount -ge 4) { $args += '-F'; $args += "coauth4name=CoAuthor4 $($tc.fname)"; $args += '-F'; $args += "coauth4nationality=$($tc.nationality)"; $args += '-F'; $args += "coauth4email=$($tc.coemail4)" }
  if ($tc.coCount -ge 5) { $args += '-F'; $args += "coauth5name=CoAuthor5 $($tc.fname)"; $args += '-F'; $args += "coauth5nationality=$($tc.nationality)"; $args += '-F'; $args += "coauth5email=$($tc.coemail5)" }
  $args += '-F'; $args += "profession=$($tc.profession)"
  $args += '-F'; $args += "category=Poster Competetion"
  $args += '-F'; $args += "organization=$($tc.organization)"
  $args += '-F'; $args += "password=$($tc.password)"
  $args += '-F'; $args += "contact=$($tc.contact)"
  $args += '-F'; $args += "userip=127.0.0.1"
  $args += '-F'; $args += "companyref=University X"
  $args += '-F'; $args += "paypalref="
  if ([string]::IsNullOrEmpty($tc.filePath)) {
    # intentionally missing file
  } else {
    $args += '-F'; $args += "abstract_file=@$($tc.filePath)"
  }
  $args += '-F'; $args += "signup=Sign Up"
  $args += $BaseUrl
  & curl.exe @args | Out-Null
  $codeLine = Get-Content -Path $hdr -ErrorAction SilentlyContinue | Where-Object { $_ -match '^HTTP/' } | Select-Object -Last 1
  $code = if ($codeLine) { ($codeLine -split ' ')[1] } else { '' }
  $loc = (Get-Content -Path $hdr | Where-Object { $_ -match '^Location:' }) -replace '^Location:\s*',''
  $ok = ($loc -match 'welcome\.php')
  $note = if ($ok) { 'redirect to welcome' } elseif ($code -eq '200') { 'no redirect' } else { 'HTTP ' + $code }
  $results += [pscustomobject]@{ name=$tc.name; email=$tc.email; ok=$ok; note=$note; code=$code; location=$loc }
}
$json = $results | ConvertTo-Json -Depth 4
[System.IO.File]::WriteAllText((Join-Path $OutDir 'results.json'), $json)
