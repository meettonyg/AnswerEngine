Add-Type -AssemblyName System.IO.Compression.FileSystem

$themes = @('answerenginewp', 'aivisibilityscanner')

foreach ($theme in $themes) {
    $zipPath = Join-Path $PSScriptRoot "$theme.zip"
    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

    $zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')

    $sourceDir = Join-Path $PSScriptRoot $theme
    $files = Get-ChildItem -Path $sourceDir -Recurse -File | Where-Object {
        $_.Name -notmatch '^\.' -and
        $_.FullName -notmatch '\\node_modules\\' -and
        $_.FullName -notmatch '\\\.git\\' -and
        $_.FullName -notmatch '\\docs\\'
    }

    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring($sourceDir.Length - $theme.Length)
        $entryName = $relativePath.Replace('\', '/')
        if ($entryName.StartsWith('/')) { $entryName = $entryName.Substring(1) }
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $entryName) | Out-Null
    }

    $zip.Dispose()
    Write-Host "Created $zipPath"
}
