<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$panel = \Filament\Facades\Filament::getPanel('admin');
$items = [];
foreach ($panel->getResources() as $r) {
    if ($r::getNavigationGroup() === 'Pengaturan') {
        $items[] = ['type' => 'Resource', 'class' => $r, 'icon' => $r::getNavigationIcon()];
    }
}
foreach ($panel->getPages() as $p) {
    if ($p::getNavigationGroup() === 'Pengaturan') {
        $items[] = ['type' => 'Page', 'class' => $p, 'icon' => $p::getNavigationIcon()];
    }
}
print_r($items);
