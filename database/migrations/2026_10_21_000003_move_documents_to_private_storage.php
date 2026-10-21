<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

/**
 * File dokumen resmi sebelumnya ada di storage/app/public/documents sehingga bisa diunduh
 * tanpa login lewat symlink public/storage. Pindahkan ke storage/app/private/documents.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->move(storage_path('app/public/documents'), storage_path('app/private/documents'));
    }

    public function down(): void
    {
        $this->move(storage_path('app/private/documents'), storage_path('app/public/documents'));
    }

    private function move(string $from, string $to): void
    {
        if (! File::isDirectory($from)) {
            return;
        }

        foreach (File::allFiles($from, true) as $file) {
            $target = $to.DIRECTORY_SEPARATOR.$file->getRelativePathname();

            if (! File::exists($target)) {
                File::ensureDirectoryExists(dirname($target));
                File::move($file->getPathname(), $target);
            }
        }

        File::deleteDirectory($from);
    }
};
