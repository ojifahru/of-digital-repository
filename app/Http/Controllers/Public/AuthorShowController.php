<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\TriDharma;
use App\Support\Seo\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class AuthorShowController extends Controller
{
    public function __invoke(Author $author): View
    {
        $documents = TriDharma::query()
            ->where('status', 'published')
            ->whereHas('authors', function ($authorQuery) use ($author) {
                $authorQuery
                    ->whereNull('authors.deleted_at')
                    ->where('authors.id', $author->getKey());
            })
            ->with([
                'authors' => function ($authorQuery) {
                    $authorQuery->whereNull('authors.deleted_at');
                },
                'category',
                'documentType',
            ])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $authorImageUrl = null;
        if (! empty($author->image_url) && Storage::disk('public')->exists($author->image_url)) {
            $authorImageUrl = Storage::disk('public')->url($author->image_url);
        }

        $canonical = route('public.authors.show', $author);
        $title = Seo::title(['Publikasi '.$author->name]);
        $description = Seo::description('Daftar dokumen terpublikasi oleh '.$author->name.' di repository institusi: judul, abstrak, tahun, dan unduhan PDF.');

        $jsonLd = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => (string) $author->name,
                'url' => $canonical,
                'identifier' => $author->identifier ?: null,
                'image' => $authorImageUrl,
            ],
        ];

        return view('public.authors.show', [
            'author' => $author,
            'authorImageUrl' => $authorImageUrl,
            'documents' => $documents,
            'seo' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'og' => [
                    'type' => 'profile',
                    'title' => $title,
                    'description' => $description,
                    'url' => $canonical,
                ],
                'jsonLd' => $jsonLd,
            ],
        ]);
    }
}
