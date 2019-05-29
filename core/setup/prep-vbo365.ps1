# Download Veeam Backup for Microsoft Office 365 v3
$url = "https://download2.veeam.com/VeeamBackupOffice365_3.0.0.422.zip"
$output = "$PSScriptRoot\VBO365.zip"
Invoke-WebRequest -Uri $url -OutFile $output

# Unpack VBO365.zip
Expand-Archive -Force -LiteralPath $output -DestinationPath C:\VBO365Install

# Install Chocolatey and .NET framework 4.7.2
Set-ExecutionPolicy Bypass -Scope Process -Force; iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
choco install dotnet4.7.2 -y

# Restart server to finalize .NET framework 4.7.2 installation
Write-Host "Rebooting server"
Restart-Computer -Force