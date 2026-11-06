{{--
    Glassmorphism untuk kotak form Masuk / Daftar (.auth-card), sama dengan kotak penawaran (.band) di landing.
    Dipakai: @include('auth-glass') di awal @section('content'), setelah @include('shape-grid').
    Sedikit lebih pekat daripada .band (72% / 42%) supaya label dan teks kecil di form tetap mudah dibaca.
--}}
<style>
    .auth .auth-card.card { position: relative; isolation: isolate; border-radius: 20px;
        background: linear-gradient(135deg, color-mix(in srgb, var(--paper) 72%, transparent), color-mix(in srgb, var(--paper) 42%, transparent));
        -webkit-backdrop-filter: blur(18px) saturate(1.5); backdrop-filter: blur(18px) saturate(1.5);
        border: 1.5px solid color-mix(in srgb, var(--paper) 72%, transparent);
        box-shadow: 0 10px 34px color-mix(in srgb, var(--ink) 12%, transparent), inset 0 1px 0 rgba(255, 255, 255, .65), inset 0 -1px 0 rgba(255, 255, 255, .18); }
    /* kilau diagonal tipis di sudut kiri atas */
    .auth .auth-card.card::before { content: ""; position: absolute; inset: 0; z-index: -1; border-radius: inherit; pointer-events: none;
        background: linear-gradient(115deg, rgba(255, 255, 255, .5) 0%, rgba(255, 255, 255, .12) 26%, transparent 46%); }
    html[data-theme="dark"] .auth .auth-card.card { border-color: rgba(236, 236, 232, .2);
        box-shadow: 0 10px 34px rgba(0, 0, 0, .45), inset 0 1px 0 rgba(255, 255, 255, .14), inset 0 -1px 0 rgba(255, 255, 255, .05); }
    html[data-theme="dark"] .auth .auth-card.card::before { background: linear-gradient(115deg, rgba(255, 255, 255, .12) 0%, rgba(255, 255, 255, .03) 26%, transparent 46%); }
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .auth .auth-card.card { background: linear-gradient(135deg, color-mix(in srgb, var(--paper) 94%, transparent), color-mix(in srgb, var(--mist) 94%, transparent)); }
    }
</style>
