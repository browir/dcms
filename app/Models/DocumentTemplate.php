<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'document_templates';

    protected $fillable = [
        'name',
        'code',
        'description',
        'template_content',
        'template_fields',
        'company_id',
        'category_id',
        'is_active',
        'allow_file_upload',
        'allow_manual_input',
    ];

    protected $casts = [
        'template_fields' => 'array',
        'is_active' => 'boolean',
        'allow_file_upload' => 'boolean',
        'allow_manual_input' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany(Builder $query, ?int $companyId): Builder
    {
        return $query->when($companyId, fn (Builder $q) => $q->where('company_id', $companyId));
    }
}

