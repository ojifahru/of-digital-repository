<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\TriDharma;
use App\Settings\PublicSiteSettings;
use App\Support\Seo\Seo;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(PublicSiteSettings $siteSettings): View
    {
        $publishedDocumentsQuery = TriDharma::query()
            ->where('status', 'published');

        $stats = [
            'documents' => (clone $publishedDocumentsQuery)->count(),
            'authors' => Author::query()->count(),
            'faculties' => Faculty::query()->count(),
            'study_programs' => StudyProgram::query()->count(),
        ];

        $latestDocuments = (clone $publishedDocumentsQuery)
            ->with([
                'authors' => function ($query) {
                    $query->whereNull('authors.deleted_at');
                },
                'category',
                'faculty',
                'studyProgram',
            ])
            ->orderBy('publish_year', 'desc')
            ->limit(8)
            ->get();

        $canonical = route('public.home');
        $title = Seo::title(['Repository Institusi']);
        $description = Seo::description($siteSettings->site_description);

        $jsonLd = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => Seo::siteName(),
                'url' => $canonical,
                'inLanguage' => 'id',
            ],
        ];

        return view('public.home', [
            'stats' => $stats,
            'latestDocuments' => $latestDocuments,
            'siteSettings' => $siteSettings,
            'seo' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'jsonLd' => $jsonLd,
            ],
        ]);
    }
}
