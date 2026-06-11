@php
    $status = (string) ($status ?? (isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 'Error'));
    $title = $title ?? 'Terjadi kesalahan';
    $message = $message ?? 'Permintaan tidak dapat diproses saat ini.';
    $detail = $detail ?? null;
    $primaryUrl = $primaryUrl ?? url('/');
    $primaryLabel = $primaryLabel ?? 'Ke Beranda';
    $secondaryUrl = $secondaryUrl ?? url('/dokumen');
    $secondaryLabel = $secondaryLabel ?? 'Lihat Dokumen';
    $showReload = $showReload ?? false;
    $appName = config('app.name', 'Digital Repository');
@endphp

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $status }} - {{ $title }} | {{ $appName }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --panel: #ffffff;
            --text: #111827;
            --muted: #4b5563;
            --line: #e5e7eb;
            --accent: #4f46e5;
            --accent-dark: #3730a3;
            --soft: #eef2ff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(79, 70, 229, 0.12), transparent 32rem),
                linear-gradient(180deg, #f8fafc 0%, #ffffff 52%, #f8fafc 100%);
            color: var(--text);
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .page {
            display: grid;
            min-height: 100vh;
            place-items: center;
            padding: 2rem 1rem;
        }

        .shell {
            width: min(100%, 46rem);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            color: var(--muted);
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .brand-mark {
            display: grid;
            width: 2.5rem;
            height: 2.5rem;
            place-items: center;
            border-radius: 0.875rem;
            background: var(--soft);
            color: var(--accent-dark);
            font-weight: 800;
        }

        .panel {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.10);
        }

        .content {
            padding: clamp(1.5rem, 5vw, 3rem);
        }

        .status {
            display: inline-flex;
            align-items: center;
            min-height: 2rem;
            border-radius: 999px;
            background: var(--soft);
            padding: 0.375rem 0.75rem;
            color: var(--accent-dark);
            font-size: 0.8125rem;
            font-weight: 800;
        }

        h1 {
            max-width: 14ch;
            margin: 1.25rem 0 0;
            font-size: clamp(2.25rem, 9vw, 4.75rem);
            line-height: 0.95;
            letter-spacing: 0;
        }

        .message {
            max-width: 42rem;
            margin: 1.25rem 0 0;
            color: var(--muted);
            font-size: clamp(1rem, 2vw, 1.125rem);
            line-height: 1.7;
        }

        .detail {
            margin-top: 1rem;
            border-left: 3px solid var(--line);
            padding-left: 1rem;
            color: #6b7280;
            font-size: 0.9375rem;
            line-height: 1.65;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 2rem;
        }

        .button {
            display: inline-flex;
            min-height: 2.75rem;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 0.875rem;
            padding: 0.75rem 1rem;
            color: var(--text);
            font-size: 0.9375rem;
            font-weight: 800;
            text-decoration: none;
        }

        .button-primary {
            border-color: var(--accent);
            background: var(--accent);
            color: #ffffff;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .meta {
            border-top: 1px solid var(--line);
            background: rgba(248, 250, 252, 0.9);
            padding: 1rem clamp(1.5rem, 5vw, 3rem);
            color: #6b7280;
            font-size: 0.8125rem;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <main class="page">
        <div class="shell">
            <div class="brand" aria-label="{{ $appName }}">
                <span class="brand-mark">DR</span>
                <span>{{ $appName }}</span>
            </div>

            <section class="panel" aria-labelledby="error-title">
                <div class="content">
                    <div class="status">Error {{ $status }}</div>
                    <h1 id="error-title">{{ $title }}</h1>
                    <p class="message">{{ $message }}</p>

                    @if (filled($detail))
                        <p class="detail">{{ $detail }}</p>
                    @endif

                    <div class="actions">
                        <a class="button button-primary" href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>
                        <a class="button" href="{{ $secondaryUrl }}">{{ $secondaryLabel }}</a>

                        @if ($showReload)
                            <a class="button" href="{{ url()->current() }}">Muat Ulang</a>
                        @endif
                    </div>
                </div>

                <div class="meta">
                    Jika masalah terus terjadi, hubungi pengelola repository dan sertakan kode error {{ $status }}.
                </div>
            </section>
        </div>
    </main>
</body>

</html>
