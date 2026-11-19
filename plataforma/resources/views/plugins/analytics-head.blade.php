{{-- Consent Mode v2: todo negado por defecto. Las etiquetas solo se cargan tras «Aceptar» (resources/js/app.js). --}}
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'denied', wait_for_update: 500});
window.mcAnalytics = @json(\App\Support\Analytics::ids());
window.mcEvents = window.mcEvents || [];
</script>
