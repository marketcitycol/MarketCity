@extends('layouts.shop')
@section('title', $page->title)
@section('content')
<article class="mx-auto max-w-3xl">
    <h1 class="text-3xl font-extrabold text-slate-900">{{ $page->title }}</h1>
    <div class="content mt-6">{!! $page->body !!}</div>
    @if ($page->slug === 'contacto')
        @php($f = \App\Support\Plugins::config('footer'))
        <div class="mt-8 flex flex-wrap gap-3">
            @if ($f['whatsapp'])<a class="btn-primary" href="https://wa.me/{{ preg_replace('/\D/', '', $f['whatsapp']) }}" target="_blank" rel="noopener">Escríbenos por WhatsApp</a>@endif
            @if ($f['email'])<a class="btn-ghost" href="mailto:{{ $f['email'] }}">{{ $f['email'] }}</a>@endif
        </div>
    @endif
</article>
@endsection
