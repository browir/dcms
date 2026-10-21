<?php

// app/Models/Unit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ['name', 'prefix', 'keycloak_code', 'whatsapp_group', 'department_id', 'is_active'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function accessibleDocuments(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_unit_access', 'unit_id', 'document_id')->withTimestamps();
    }
}
