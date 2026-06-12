#!/usr/bin/env pwsh

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$sourceDir = $scriptDir
$targetDir = Join-Path $scriptDir 'php/www/html/xai'
$limeImgsDir = Join-Path $targetDir 'lime-imgs'

Copy-Item (Join-Path $sourceDir 'cases.php') -Destination $targetDir -Force
New-Item -ItemType Directory -Path $limeImgsDir -Force | Out-Null

Get-ChildItem (Join-Path $sourceDir 'lime-imgs') -Filter '*.png' | ForEach-Object {
    Copy-Item $_.FullName -Destination $limeImgsDir -Force
}
