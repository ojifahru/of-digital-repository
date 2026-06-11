<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TriDharma extends Model
{
    use LogsActivity, SoftDeletes;
    use Searchable;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $table = 'tri_dharmas';

    protected $fillable = [
        'title',
        'slug',
        'abstract',
        'category_id',
        'document_type_id',
        'faculty_id',
        'study_program_id',
        'publish_year',
        'status',
        'file_path',
        'file_size',
        'download_count',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $triDharma): void {
            if (! is_string($triDharma->slug) || trim($triDharma->slug) === '') {
                $base = Str::slug((string) $triDharma->title);
                $base = $base !== '' ? $base : 'dokumen';

                $candidate = $base;
                $suffix = 2;

                while (self::query()->where('slug', $candidate)->exists()) {
                    $candidate = $base.'-'.$suffix;
                    $suffix++;

                    if ($suffix > 50) {
                        $candidate = $base.'-'.Str::lower(Str::random(6));
                        break;
                    }
                }

                $triDharma->slug = $candidate;
            }
        });
    }

    protected $casts = [
        'publish_year' => 'integer',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function documentPath(): string
    {
        return (string) $this->file_path;
    }

    public function documentDisk(): ?string
    {
        $path = $this->documentPath();

        if ($path === '') {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    public function documentDownloadName(): string
    {
        $path = $this->documentPath();
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $baseNameSource = $this->title ?: pathinfo($path, PATHINFO_FILENAME);
        $baseName = Str::slug((string) $baseNameSource, '_');

        if ($baseName === '') {
            $baseName = 'document';
        }

        return $extension !== '' ? $baseName.'.'.$extension : $baseName;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->canManageAllStudyPrograms()) {
            return $query;
        }

        if ($user->study_program_id === null) {
            return $query->whereKey([]);
        }

        return $query->where('study_program_id', $user->study_program_id);
    }

    public function searchableAs(): string
    {
        return 'tri_dharmas';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published' && $this->deleted_at === null;
    }

    public function makeAllSearchableUsing($query)
    {
        return $query->with([
            'authors:id,name,deleted_at',
            'category:id,name',
            'documentType:id,name',
            'faculty:id,name',
            'studyProgram:id,name',
        ]);
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing([
            'authors:id,name,deleted_at',
            'category:id,name',
            'documentType:id,name',
            'faculty:id,name',
            'studyProgram:id,name',
        ]);

        $activeAuthors = $this->authors
            ->filter(fn ($author) => $author->deleted_at === null)
            ->values();

        $normalizedTitle = $this->normalizeText((string) $this->title);
        $normalizedAbstract = $this->normalizeText((string) $this->abstract);

        return [
            'id' => $this->id,
            'title' => $normalizedTitle,
            'abstract' => $normalizedAbstract,
            'authors' => $activeAuthors->pluck('name')->all(),
            'author_ids' => $activeAuthors->pluck('id')->all(),
            'category' => $this->category?->name,
            'category_id' => $this->category_id,
            'document_type' => $this->documentType?->name,
            'document_type_id' => $this->document_type_id,
            'faculty' => $this->faculty?->name,
            'faculty_id' => $this->faculty_id,
            'study_program' => $this->studyProgram?->name,
            'study_program_id' => $this->study_program_id,
            'publish_year' => $this->publish_year,
            'status' => $this->status,
        ];
    }

    protected function normalizeText(string $value): string
    {
        $normalized = preg_replace('/[\r\n]+/', ' ', $value);
        $normalized = preg_replace('/\s+/', ' ', $normalized ?? '');

        return trim($normalized ?? '');
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class)->withTimestamps()->withTrashed();
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class)->withTrashed();
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class)->withTrashed();
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_id')->withTrashed();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tri_dharma')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getTitleAttribute($value)
    {
        return $this->normalizeText($value);
    }
}
