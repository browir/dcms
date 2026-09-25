$f = 'd:\laragon\www\dcms.syifaglobalgroup.com\resources\views\filament\widgets\employee-activity-hub.blade.php'
$c = [System.IO.File]::ReadAllText($f)
$c = $c -replace '110,231,183', '96,165,250'
[System.IO.File]::WriteAllText($f, $c, [System.Text.Encoding]::UTF8)
