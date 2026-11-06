<?php
define('LARAVEL_START', microtime(true));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// 1. Widget permissions yang ada di DB
$widgetPerms = Permission::where('name', 'like', 'widget_%')->pluck('name')->toArray();
echo "=== Widget permissions in DB: " . count($widgetPerms) . " ===\n";
foreach ($widgetPerms as $p) {
    echo "  $p\n";
}

// 2. Cek role super_admin punya widget perms apa
$role = Role::where('name', 'super_admin')->first();
if ($role) {
    $roleWidgetPerms = $role->permissions()->where('name', 'like', 'widget_%')->pluck('name')->toArray();
    echo "\n=== super_admin widget permissions: " . count($roleWidgetPerms) . " ===\n";
    foreach ($roleWidgetPerms as $p) {
        echo "  $p\n";
    }
} else {
    echo "\nRole super_admin: NOT FOUND\n";
}

// 3. Cek shield super_admin bypass
$shieldCfg = config('filament-shield.super_admin');
echo "\n=== Shield super_admin bypass: " . ($shieldCfg['enabled'] ? 'ENABLED (bypass all)' : 'DISABLED') . " ===\n";

// 4. Cek widget classes
echo "\n=== Widget class existence ===\n";
$classes = [
    'EmployeeActivityHubWidget' => 'App\Filament\Admin\Widgets\EmployeeActivityHubWidget',
    'ModuleOverviewWidget'      => 'App\Filament\Admin\Widgets\Dashboard\ModuleOverviewWidget',
    'AgendaRoomsWidget'         => 'App\Filament\Admin\Widgets\Dashboard\AgendaRoomsWidget',
    'FollowUpWidget'            => 'App\Filament\Admin\Widgets\Dashboard\FollowUpWidget',
    'NumberingDocumentsWidget'  => 'App\Filament\Admin\Widgets\Dashboard\NumberingDocumentsWidget',
    'MyCalendarWidget'          => 'App\Filament\Admin\Widgets\MyCalendarWidget',
];
foreach ($classes as $name => $class) {
    echo "  " . (class_exists($class) ? '✓' : '✗') . " $name\n";
}

