<x-public-layouts.app :seo="$seo" :title="$author->name">
    <nav class="mb-5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600" aria-label="Breadcrumb">
        <a class="font-medium text-gray-700 hover:text-gray-900 hover:underline"
            href="{{ route('public.documents.index') }}">Dokumen</a>
        <span class="text-gray-300" aria-hidden="true">/</span>
        <span class="min-w-0 truncate text-gray-900">Author</span>
    </nav>

    <section class="rounded-3xl bg-gradient-to-br from-indigo-50/60 via-white to-white p-6 sm:p-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                <div
                    class="flex size-16 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-900/5">
                    @if (!empty($authorImageUrl))
                        <img class="h-full w-full object-cover" src="{{ $authorImageUrl }}"
                            alt="Foto {{ $author->name }}">
                    @else
                        <span class="text-xl font-semibold text-indigo-700">{{ mb_substr($author->name, 0, 1) }}</span>
                    @endif
                </div>

                <div class="min-w-0">
                    <h1 class="text-3xl font-semibold tracking-tight text-gray-900">
                        {{ $author->name }}
                    </h1>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if (!empty($author->identifier))
                            <x-ui.badge class="border-0 bg-white/70 text-gray-700">ID:
                                {{ $author->identifier }}</x-ui.badge>
                        @endif
                        <x-ui.badge variant="primary" class="border-indigo-200 bg-indigo-50 text-indigo-800">
                            {{ $documents->total() }} dokumen
                        </x-ui.badge>
                    </div>
                </div>
            </div>

            <a class="inline-flex items-center justify-center rounded-2xl bg-white/80 px-5 py-3 text-sm font-semibold text-gray-800 shadow-sm ring-1 ring-gray-900/5 transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                href="{{ route('public.documents.index') }}">
                Lihat semua dokumen
            </a>
        </div>

        @php
            $bioText = trim(strip_tags((string) $author->bio));
        @endphp
        @if ($bioText !== '')
            <div
                class="mt-6 max-w-3xl rounded-2xl bg-white/70 p-5 text-sm leading-relaxed text-gray-700 shadow-sm ring-1 ring-gray-900/5">
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-indigo-600"></span>
                    <div class="text-sm font-semibold text-gray-900">Bio</div>
                </div>
                <div class="mt-3 rich-content">
                    {!! str($author->bio)->sanitizeHtml() !!}
                </div>
            </div>
        @endif
    </section>

    <section class="mt-10">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-gray-900">Publikasi</h2>
                <p class="mt-1 text-sm text-gray-600">Daftar dokumen published untuk author ini.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @forelse($documents as $doc)
                <a href="{{ route('public.repository.show', $doc) }}"
                    class="group rounded-2xl bg-white/80 p-6 shadow-sm ring-1 ring-gray-900/5 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md hover:ring-indigo-500/15 focus:outline-none focus:ring-4 focus:ring-indigo-500/15">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-base font-semibold leading-snug text-gray-900 group-hover:underline">
                                {{ $doc->title ?? 'Untitled' }}
                            </div>
                            <div class="mt-1 text-sm text-gray-600">
                                Download: {{ $doc->download_count ?? 0 }}
                            </div>
                        </div>

                        <x-ui.badge variant="primary" class="border-indigo-200 bg-indigo-50 text-indigo-800">
                            {{ $doc->publish_year ?? '-' }}
                        </x-ui.badge>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @if ($doc->category)
                            <x-ui.badge
                                class="border-0 bg-gray-100 text-gray-700">{{ $doc->category->name }}</x-ui.badge>
                        @endif
                        @if ($doc->documentType)
                            <x-ui.badge
                                class="border-0 bg-gray-100 text-gray-700">{{ $doc->documentType->name }}</x-ui.badge>
                        @endif

                        <span class="ml-auto inline-flex items-center gap-1 text-sm font-semibold text-indigo-700">
                            Detail
                            <svg viewBox="0 0 20 20" fill="currentColor" class="size-4" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M7.21 14.77a.75.75 0 0 1 .02-1.06L10.94 10 7.23 6.29a.75.75 0 1 1 1.06-1.06l4.24 4.24a.75.75 0 0 1 0 1.06l-4.24 4.24a.75.75 0 0 1-1.06-.02Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl bg-white/80 p-8 text-sm text-gray-600 shadow-sm ring-1 ring-gray-900/5">
                    Belum ada dokumen published untuk author ini.
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $documents->links('pagination.public') }}
        </div>
    </section>
</x-public-layouts.app>
