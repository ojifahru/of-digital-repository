<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudyProgram extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'study_programs';

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'name',
        'faculty_id',
        'kode',
        'degree_id',
        'program_type_id',
        'slug',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function degree(): BelongsTo
    {
        return $this->belongsTo(Degree::class);
    }

    public function programType(): BelongsTo
    {
        return $this->belongsTo(ProgramType::class);
    }

    public function triDharmas(): HasMany
    {
        return $this->hasMany(TriDharma::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $studyProgram): void {
            if ($studyProgram->isForceDeleting()) {
                $studyProgram->triDharmas()->forceDelete();
            } else {
                $studyProgram->triDharmas()->delete();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('study_program')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
