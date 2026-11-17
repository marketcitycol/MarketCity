@extends('layouts.admin', ['title' => 'Páginas'])
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Páginas</h1>
    <a href="{{ route('admin.pages.create') }}" class="btn-primary">Nueva página</a>
</div>
<p class="mt-1 text-sm text-slate-500">Políticas, contacto y demás páginas de texto. Se ven en <code>v1.marketcity.co/&lt;dirección&gt;</code>.</p>
<div class="card mt-5 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs text-slate-500 uppercase"><tr><th class="px-4 py-3">Título</th><th class="px-4 py-3">Dirección</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Origen</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse ($pages as $p)
            <tr>
                <td class="px-4 py-3"><a class="font-medium text-brand-700 hover:underline" href="{{ route('admin.pages.edit', $p) }}">{{ $p->title }}</a></td>
                <td class="px-4 py-3"><a class="text-slate-600 hover:underline" href="{{ route('page', $p) }}" target="_blank">/{{ $p->slug }}</a></td>
                <td class="px-4 py-3">{{ $p->isPublished() ? 'Publicada' : 'Borrador' }}</td>
                <td class="px-4 py-3 text-slate-500">{{ $p->legacy_id ? 'marketcity.co #'.$p->legacy_id : 'Creada aquí' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Aún no hay páginas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
