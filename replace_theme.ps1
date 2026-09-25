$files = Get-ChildItem -Path "d:\laragon\www\dcms.syifaglobalgroup.com\resources\views\filament\widgets" -Filter "*.blade.php"
foreach ($f in $files) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    $content = $content -replace '#0d9488', '#10b981'
    $content = $content -replace '#2dd4bf', '#34d399'
    $content = $content -replace '#134e4a', '#064e3b'
    $content = $content -replace '13,148,136', '16,185,129'
    $content = $content -replace '13, 148, 136', '16, 185, 129'
    $content = $content -replace '204, 253, 246', '209, 250, 229'
    [System.IO.File]::WriteAllText($f.FullName, $content, [System.Text.Encoding]::UTF8)
}
Write-Output "Replacement done."
