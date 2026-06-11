<x-public-layouts.app :seo="$seo" :title="$document->title ?? 'Dokumen'">
    <nav class="mb-5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600" aria-label="Breadcrumb">
        <a class="font-medium text-gray-700 hover:text-gray-900 hover:underline"
            href="{{ route('public.documents.index') }}">Dokumen</a>
        <span class="text-gray-300" aria-hidden="true">/</span>
        <span class="min-w-0 truncate text-gray-900">Detail</span>
    </nav>

    <div class="grid gap-8 md:grid-cols-[1fr_20rem] md:items-start">
        <div class="space-y-6">
            <section class="rounded-3xl bg-gradient-to-br from-indigo-50/60 via-white to-white p-6 sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl">
                            {{ $document->title ?? 'Untitled' }}
                        </h1>

                        <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-600">
                            <span class="font-medium text-gray-700">Author:</span>
                            <span class="min-w-0 break-words">
                                @forelse ($document->authors as $author)
                                    <a class="rounded-lg font-medium text-indigo-700 hover:underline focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                        href="{{ route('public.documents.index', ['author_id' => $author->id]) }}">
                                        {{ $author->name }}
                                    </a>
                                    @if (!$loop->last)
                                        ,
                                    @endif
                                @empty
                                    -
                                @endforelse
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($document->category)
                                <a class="focus:outline-none"
                                    href="{{ route('public.documents.index', ['category_id' => $document->category->id]) }}">
                                    <x-ui.badge class="border-0 bg-white/70 text-gray-700 hover:bg-white">
                                        {{ $document->category->name }}
                                    </x-ui.badge>
                                </a>
                            @endif
                            @if ($document->documentType)
                                <a class="focus:outline-none"
                                    href="{{ route('public.documents.index', ['document_type_id' => $document->documentType->id]) }}">
                                    <x-ui.badge class="border-0 bg-white/70 text-gray-700 hover:bg-white">
                                        {{ $document->documentType->name }}
                                    </x-ui.badge>
                                </a>
                            @endif
                            @if ($document->publish_year)
                                <a class="focus:outline-none"
                                    href="{{ route('public.documents.index', ['publish_year' => $document->publish_year]) }}">
                                    <x-ui.badge variant="primary"
                                        class="border-indigo-200 bg-indigo-50 text-indigo-800 hover:bg-indigo-100">
                                        Tahun {{ $document->publish_year }}
                                    </x-ui.badge>
                                </a>
                            @else
                                <x-ui.badge variant="primary" class="border-indigo-200 bg-indigo-50 text-indigo-800">
                                    Tahun -
                                </x-ui.badge>
                            @endif
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                        <a class="inline-flex items-center justify-center rounded-2xl bg-white/80 px-6 py-3 text-sm font-semibold text-gray-800 shadow-sm ring-1 ring-gray-900/5 transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                            href="{{ route('public.repository.pdf', $document) }}" target="_blank" rel="noopener">
                            Lihat PDF
                        </a>
                        <a class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/20"
                            href="{{ route('public.repository.download', $document) }}">
                            Download
                        </a>
                        <a class="inline-flex items-center justify-center rounded-2xl bg-white/80 px-6 py-3 text-sm font-semibold text-gray-800 shadow-sm ring-1 ring-gray-900/5 transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                            href="{{ route('public.documents.index') }}">
                            Kembali
                        </a>
                    </div>
                </div>
            </section>

            @php
                $abstractText = trim(strip_tags((string) $document->abstract));
            @endphp
            @if ($abstractText !== '')
                <section class="rounded-3xl bg-white/80 p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                    <div class="flex items-center gap-2">
                        <span class="size-2 rounded-full bg-indigo-600"></span>
                        <h2 class="text-lg font-semibold tracking-tight text-gray-900">Abstrak</h2>
                    </div>
                    <div class="mt-4 rich-content">
                        {!! str($document->abstract)->sanitizeHtml() !!}
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="rounded-3xl bg-white/80 p-6 shadow-sm ring-1 ring-gray-900/5 md:sticky md:top-24">
                <div class="text-sm font-semibold text-gray-900">Informasi</div>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-600">Fakultas</span>
                        @if ($document->faculty)
                            <a class="rounded-lg text-right font-medium text-indigo-700 hover:underline focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                href="{{ route('public.faculties.show', $document->faculty) }}">
                                {{ $document->faculty->name }}
                            </a>
                        @else
                            <span class="text-right font-medium text-gray-900">-</span>
                        @endif
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-600">Program Studi</span>
                        @if ($document->studyProgram)
                            <a class="rounded-lg text-right font-medium text-indigo-700 hover:underline focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                href="{{ route('public.study-programs.show', $document->studyProgram) }}">
                                {{ $document->studyProgram->name }}
                            </a>
                        @else
                            <span class="text-right font-medium text-gray-900">-</span>
                        @endif
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-600">Ukuran File</span>
                        <span
                            class="text-right font-medium text-gray-900">{{ $document->file_size ? number_format($document->file_size / 1024, 0) . ' KB' : '-' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-600">Download</span>
                        <span class="text-right font-medium text-gray-900">{{ $document->download_count ?? 0 }}</span>
                    </div>
                    <div class="pt-2 text-xs text-gray-500">
                        ID Dokumen: {{ $document->id }}
                    </div>
                </div>
            </div>
        </aside>
    </div>
</x-public-layouts.app>
