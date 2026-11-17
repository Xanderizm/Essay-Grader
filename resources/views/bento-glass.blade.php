{{--
    Glassmorphism untuk seluruh kartu bento Statistik User (.ux-card) dan Statistik Admin (.adm-card).
    Diadaptasi dari auth-glass (.auth .auth-card.card). Dipakai: @include('bento-glass') tepat setelah @include('bento-assets').
--}}
<style>
    /* Latar belakang berwarna di belakang kartu, supaya efek blur kaca benar-benar terlihat */
    .ux {
        background-image:
            radial-gradient(circle at 10% 6%, color-mix(in srgb, var(--ux-green) 24%, transparent), transparent 34%),
            radial-gradient(circle at 92% 38%, color-mix(in srgb, #8b3dff 18%, transparent), transparent 36%),
            radial-gradient(circle at 14% 92%, color-mix(in srgb, #ffb800 16%, transparent), transparent 34%),
            radial-gradient(circle, color-mix(in oklab, var(--ink) 9%, transparent) 0 1px, transparent 1.4px);
        background-size: auto, auto, auto, 20px 13px;
    }
    .adm {
        background-image:
            radial-gradient(circle at 8% 5%, color-mix(in srgb, var(--a-lime) 34%, transparent), transparent 32%),
            radial-gradient(circle at 94% 34%, color-mix(in srgb, var(--a-vio) 30%, transparent), transparent 34%),
            radial-gradient(circle at 12% 70%, color-mix(in srgb, #ff9b7a 22%, transparent), transparent 30%),
            radial-gradient(circle at 90% 95%, color-mix(in srgb, var(--a-lime) 26%, transparent), transparent 32%);
    }

    /* Kartu kaca */
    .ux .ux-card,
    .adm .adm-card {
        position: relative; isolation: isolate;
        background: linear-gradient(135deg, color-mix(in srgb, var(--paper) 72%, transparent), color-mix(in srgb, var(--paper) 42%, transparent));
        -webkit-backdrop-filter: blur(18px) saturate(1.5); backdrop-filter: blur(18px) saturate(1.5);
        border: 1.5px solid color-mix(in srgb, var(--paper) 72%, transparent);
        box-shadow: 0 10px 34px color-mix(in srgb, var(--ink) 12%, transparent), inset 0 1px 0 rgba(255, 255, 255, .65), inset 0 -1px 0 rgba(255, 255, 255, .18);
    }
    /* Kilau diagonal tipis di sudut kiri atas */
    .ux .ux-card::before,
    .adm .adm-card::before {
        content: ""; position: absolute; inset: 0; z-index: -1; border-radius: inherit; pointer-events: none;
        background: linear-gradient(115deg, rgba(255, 255, 255, .5) 0%, rgba(255, 255, 255, .12) 26%, transparent 46%);
    }

    /* Kartu lime (Token AI total) tetap lime, tetapi dibuat tembus pandang seperti kaca berwarna */
    .adm .adm-card.adm-lime {
        background: linear-gradient(135deg, color-mix(in srgb, var(--a-lime) 90%, transparent), color-mix(in srgb, var(--a-lime) 66%, transparent));
        border-color: color-mix(in srgb, var(--a-lime) 70%, #fff);
    }

    /* Mode gelap */
    html[data-theme="dark"] .ux .ux-card,
    html[data-theme="dark"] .adm .adm-card {
        border-color: rgba(236, 236, 232, .2);
        box-shadow: 0 10px 34px rgba(0, 0, 0, .45), inset 0 1px 0 rgba(255, 255, 255, .14), inset 0 -1px 0 rgba(255, 255, 255, .05);
    }
    html[data-theme="dark"] .ux .ux-card::before,
    html[data-theme="dark"] .adm .adm-card::before {
        background: linear-gradient(115deg, rgba(255, 255, 255, .12) 0%, rgba(255, 255, 255, .03) 26%, transparent 46%);
    }
    html[data-theme="dark"] .adm .adm-card.adm-lime { border-color: rgba(200, 249, 102, .5); }
    html[data-theme="dark"] .adm .adm-card.adm-lime::before {
        background: linear-gradient(115deg, rgba(255, 255, 255, .35) 0%, rgba(255, 255, 255, .08) 26%, transparent 46%);
    }

    /* Cadangan bila browser tidak mendukung backdrop-filter */
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .ux .ux-card, .adm .adm-card:not(.adm-lime) {
            background: linear-gradient(135deg, color-mix(in srgb, var(--paper) 94%, transparent), color-mix(in srgb, var(--mist) 94%, transparent));
        }
    }
</style>
