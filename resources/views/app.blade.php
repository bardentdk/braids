<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="theme-color" content="#1a1a2e" />

        {{-- SEO --}}
        <meta name="description" content="Patricia Braids — Studio de tresses premium. Réservez votre session, découvrez nos créations et offrez-vous une expérience capillaire d'exception." />
        <meta name="robots" content="index, follow" />

        {{-- Open Graph --}}
        <meta property="og:type"        content="website" />
        <meta property="og:site_name"   content="Patricia Braids" />
        <meta property="og:locale"      content="fr_FR" />

        {{-- Favicon --}}
        {{-- <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="icon" type="image/png"     href="/favicon.png" />
        <link rel="apple-touch-icon"          href="/apple-touch-icon.png" /> --}}

        {{-- Preconnect fonts --}}
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

        {{-- Inertia title --}}
        <title inertia>Patricia Braids</title>

        {{-- Routes Ziggy --}}
        {{-- @routes --}}

        {{-- Vite assets --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Inertia Head --}}
        @inertiaHead
    </head>

    <body class="font-poppins antialiased bg-cream-100 text-onyx-800">

        {{-- Page Loader --}}
        <div id="app-loader" aria-hidden="true">
            <div style="text-align:center;">
                <svg width="60" height="60" viewBox="0 0 60 60" fill="none"
                     style="animation: spin-slow 3s linear infinite;">
                    <circle cx="30" cy="30" r="28"
                            stroke="#c4956a" stroke-width="1.5"
                            stroke-dasharray="8 6" />
                    <circle cx="30" cy="30" r="18"
                            stroke="#d4af37" stroke-width="1"
                            stroke-dasharray="5 4"
                            style="animation: spin-slow 2s linear infinite reverse;" />
                    <circle cx="30" cy="30" r="5"
                            fill="#c4956a" />
                </svg>
                <p style="color:#c4956a; font-family:'Poppins',sans-serif;
                          font-size:0.75rem; letter-spacing:0.2em;
                          text-transform:uppercase; margin-top:1rem;">
                    Patricia Braids
                </p>
            </div>
        </div>

        {{-- Application Inertia --}}
        @inertia

        {{-- Masquer le loader quand Vue est monté --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const loader = document.getElementById('app-loader')
                if (loader) {
                    setTimeout(() => loader.classList.add('hidden'), 800)
                }
            })
        </script>

    </body>
</html>