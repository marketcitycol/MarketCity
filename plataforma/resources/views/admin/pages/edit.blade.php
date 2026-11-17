@extends('layouts.admin', ['title' => $page->exists ? $page->title : 'Nueva página'])
@section('content')
<p class="text-sm"><a href="{{ route('admin.pages') }}" class="text-brand-600 hover:underline">← Páginas</a></p>
<h1 class="mt-1 text-2xl font-bold">{{ $page->exists ? $page->title : 'Nueva página' }}</h1>
<form method="post" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="card mt-5 space-y-4 p-5">
    @csrf @if ($page->exists) @method('put') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="title">Título</label><input class="input" id="title" name="title" value="{{ old('title', $page->title) }}" required></div>
        <div><label class="label" for="slug">Dirección (después de v1.marketcity.co/)</label><input class="input" id="slug" name="slug" value="{{ old('slug', $page->slug) }}" required pattern="[a-z0-9]+(-[a-z0-9]+)*"></div>
    </div>
    <div>
        <label class="label" for="body">Contenido (HTML sencillo: títulos, párrafos, listas, enlaces y tablas)</label>
        <textarea class="input font-mono text-xs" id="body" name="body" rows="22">{{ old('body', $page->body) }}</textarea>
    </div>
    <div><label class="label" for="status">Estado</label>
        <select class="input sm:w-60" id="status" name="status">
            <option value="published" @selected(old('status', $page->status) === 'published')>Publicada</option>
            <option value="draft" @selected(old('status', $page->status) === 'draft')>Borrador (oculta)</option>
        </select></div>
    <div class="flex gap-2">
        <button class="btn-primary">Guardar</button>
        @if ($page->exists)<a href="{{ route('page', $page) }}" target="_blank" class="btn-ghost">Ver página</a>@endif
    </div>
</form>
@if ($page->exists && $page->body)
<section class="card mt-5 p-5"><h2 class="font-semibold">Vista previa</h2><div class="content mt-3">{!! $page->body !!}</div></section>
@endif
@endsection
