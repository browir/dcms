<?php

// app/Models/Company.php test123

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Company extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = ['name', 'code', 'is_active'];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(DocumentCategory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Get document counter untuk bulan ini
    public function getCurrentMonthDocumentCount($departmentId = null): int
    {
        $query = $this->documents()->whereYear('created_at', date('Y'))->whereMonth('created_at', date('m'));

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->count();
    }
}
