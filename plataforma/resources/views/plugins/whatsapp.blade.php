@php($w = \App\Services\Content\Plugins::config('whatsapp'))
@if ($n = preg_replace('/\D/', '', $w['number']))
<a href="https://wa.me/{{ $n }}?text={{ rawurlencode($w['message']) }}" target="_blank" rel="noopener" data-track="whatsapp_click"
   class="fixed right-4 bottom-20 z-40 grid h-14 w-14 place-items-center rounded-full bg-[#25d366] text-white shadow-lg hover:brightness-95 sm:bottom-6"
   aria-label="Escríbenos por WhatsApp">
    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20zm4.4-6c-.2-.1-1.4-.7-1.7-.8-.2-.1-.4-.1-.5.1l-.8 1c-.1.2-.3.2-.5.1a6.5 6.5 0 0 1-3.2-2.8c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.5-.4h-.5a.9.9 0 0 0-.6.3 2.6 2.6 0 0 0-.8 1.9 4.5 4.5 0 0 0 1 2.4 10.3 10.3 0 0 0 4 3.5c1.5.6 2 .7 2.8.6a2.4 2.4 0 0 0 1.6-1.1 2 2 0 0 0 .1-1.1c0-.1-.2-.2-.4-.3z"/></svg>
</a>
@endif
