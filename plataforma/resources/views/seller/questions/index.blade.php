@extends('layouts.seller', ['title' => 'Preguntas'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Preguntas de clientes</h1>
    <div class="flex gap-1 text-sm">
        <a href="{{ route('seller.questions') }}" class="rounded-full px-3 py-1 {{ $answered ? 'hover:bg-white' : 'bg-white font-semibold' }}">Sin responder</a>
        <a href="{{ route('seller.questions', ['estado' => 'respondidas']) }}" class="rounded-full px-3 py-1 {{ $answered ? 'bg-white font-semibold' : 'hover:bg-white' }}">Respondidas</a>
    </div>
</div>
<p class="mt-1 text-sm text-slate-500">La respuesta se publica en la página del producto y le llega al cliente por correo. No incluyas teléfonos, correos ni enlaces: la venta se hace en MarketCity.</p>

@forelse ($questions as $q)
    <div class="card mt-4 p-4 text-sm">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <a href="{{ route('product', $q->product) }}#preguntas" class="font-semibold text-brand-600 hover:underline" target="_blank" rel="noopener">{{ $q->product->name }}</a>
            <span class="text-xs text-slate-500">{{ $q->publicName() }} · {{ fecha($q->created_at, 'd/m/Y H:i') }}</span>
        </div>
        <p class="mt-2 text-slate-900">«{{ $q->question }}»</p>
        <form method="post" action="{{ route('seller.questions.answer', $q) }}" class="mt-3 space-y-2">@csrf
            <textarea class="input min-h-20" name="answer" required minlength="2" maxlength="1000" aria-label="Respuesta">{{ old('answer', $q->answer) }}</textarea>
            <button class="btn-primary btn-sm">{{ $q->answered_at ? 'Corregir respuesta' : 'Responder' }}</button>
            @if ($q->answered_at)<span class="text-xs text-slate-500">Respondida el {{ fecha($q->answered_at, 'd/m/Y') }}</span>@endif
        </form>
    </div>
@empty
    <p class="card mt-4 p-8 text-center text-sm text-slate-500">{{ $answered ? 'Aún no has respondido preguntas.' : 'No tienes preguntas pendientes.' }}</p>
@endforelse
<div class="mt-4">{{ $questions->links() }}</div>
@endsection
