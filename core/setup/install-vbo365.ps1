# Veeam Backup for Microsoft Office 365 auto install
$jsoninstall = '{ 
   "license": { "src":"veeam_backup_microsoft_office.lic" },
   "steps": [
		{  
		  "src":"Veeam.Backup365_3.0.0.422.msi",
		  "install":"__file__",
		  "arguments":[  
			 "/qn",
			 "/log __log__",
			 "ACCEPT_EULA=1",
			 "ACCEPT_THIRDPARTY_LICENSES=1"
		  ]
	   },
	   {  
		  "src":"VeeamExplorerForExchange_3.0.0.422.msi",
		  "install":"__file__",
		  "arguments":[  
			 "/qn",
			 "/log __log__",
			 "ACCEPT_EULA=1",
			 "ACCEPT_THIRDPARTY_LICENSES=1"
		  ]
	   },
	   {  
		  "src":"VeeamExplorerForSharePoint_3.0.0.422.msi",
		  "install":"__file__",
		  "arguments":[  
			 "/qn",
			 "/log __log__",
			 "ACCEPT_EULA=1",
			 "ACCEPT_THIRDPARTY_LICENSES=1"
		  ]
	   }
	]
}'

function log {
    param($logline)
    write-host ("[{0}] - {1}" -f (get-date).ToString("yyyyMMdd - hh:mm:ss"), $logline)
}

function replaceenv {
    param( $line, $file, $log)

    $line = $line -replace "__file__", $file
    $line = $line -replace "__log__", $log

    return $line
}

function VBOinstall {
    param ($hostname)
    $json = @($jsoninstall | ConvertFrom-Json)[0]
    $steps = $json.steps
    $tmp = "C:\VBO365Install"

	foreach ($step in $steps) {
		if ($step.disabled -and $step.disabled -eq 1 ) {
			log(("Disabled step detected {0}" -f $step.src))
		} else {
			$src = ("{0}" -f $step.src)
			$tmpfile = Join-Path -Path $tmp -ChildPath $src
			$tmplog = Join-Path -Path $tmp -ChildPath "$src.log"
			$installline = replaceenv -line $step.install -file $tmpfile -log $tmplog
			$rebuildargs = @()

			foreach($pa in $step.arguments) {
				$rebuildargs += ((replaceenv -line $pa -file $src -log $tmplog))
			}

			log("Installing now:")
			log($installline)
			log($rebuildargs -join ",")
		
			Start-Process -FilePath $installline -ArgumentList $rebuildargs -Wait
		}
	}

	Import-Module "C:\Program Files\Veeam\Backup365\Veeam.Archiver.PowerShell\Veeam.Archiver.PowerShell.psd1"
	
	#if ($json.license -and $json.license.src)  {
	#    $tmpfile = Join-Path -Path $tmp -ChildPath $json.license.src
	#    Install-VBOLicense -Path $tmpfile
	#}

	$cert = New-SelfSignedCertificate -subject $hostname -NotAfter (Get-Date).AddYears(10) -KeyDescription "Veeam Backup for Microsoft Office 365 auto install" -KeyFriendlyName "Veeam Backup for Microsoft Office 365 auto install"
	$certfile = (join-path $tmp "cert.pfx")
	$securepassword = ConvertTo-SecureString "VBOpassword!" -AsPlainText -Force

	Export-PfxCertificate -Cert $cert -FilePath $certfile -Password $securepassword

	Write-Host "Enabling RESTful API service"
	Set-VBORestAPISettings -EnableService -CertificateFilePath $certfile -CertificatePassword $securepassword

	Write-Host "Enabling Tenant Auhtentication Settings"
	Set-VBOTenantAuthenticationSettings -EnableAuthentication -CertificateFilePath $certfile -CertificatePassword $securepassword
}

Write-Host "Starting Veeam Backup for Microsoft Office 365 install"
VBOinstall -hostname ([System.Net.Dns]::GetHostEntry([string]$env:computername).hostname)

Write-Host "Creating Veeam Backup for Microsoft Office 365 firewall rules"
netsh advfirewall firewall add rule name="Veeam Backup for Microsoft Office 365 RESTful API Service" protocol=TCP dir=in localport=4443 action=allow
netsh advfirewall firewall add rule name="Veeam Backup for Microsoft Office 365" protocol=TCP dir=in localport=9191 action=allow