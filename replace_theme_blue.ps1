$files = Get-ChildItem -Path "d:\laragon\www\dcms.syifaglobalgroup.com\resources\views\filament\widgets" -Filter "*.blade.php"
foreach ($f in $files) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    
    # Replace Emerald theme colors with Blue theme colors
    $content = $content -replace '#10b981', '#2563eb'
    $content = $content -replace '#34d399', '#3b82f6'
    $content = $content -replace '#064e3b', '#0f172a'
    $content = $content -replace '#065f46', '#1e3a8a'
    $content = $content -replace '16,185,129', '37,99,235'
    $content = $content -replace '16, 185, 129', '37, 99, 235'
    
    # Replace Particle Network Colors
    $content = $content -replace '209, 250, 229', '191, 219, 254'
    $content = $content -replace '167, 243, 208', '147, 197, 253'
    
    [System.IO.File]::WriteAllText($f.FullName, $content, [System.Text.Encoding]::UTF8)
}
Write-Output "Blue theme replacement done."
