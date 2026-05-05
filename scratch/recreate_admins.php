<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

Schema::dropIfExists('admins');
Schema::create('admins', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('password');
    $table->timestamps();
});

echo "Admins table recreated successfully.\n";

// Add default admin
\App\Models\Admin::create([
    'name' => 'admin',
    'password' => 'password',
]);

echo "Default admin created.\n";
