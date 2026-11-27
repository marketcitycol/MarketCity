<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductQuestion;
use Illuminate\Http\Request;

/** Moderación de las preguntas al vendedor: ver todas y ocultar las que no deben publicarse. */
class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('estado');
        $q = ProductQuestion::with('product', 'store')->latest('id')
            ->when($filter === 'sin-responder', fn ($q) => $q->unanswered())
            ->when($filter === 'ocultas', fn ($q) => $q->where('hidden', true));

        return view('admin.questions.index', [
            'questions' => $q->paginate(30)->withQueryString(),
            'late' => ProductQuestion::unanswered()->where('created_at', '<', now()->subDays(2))->count(),
        ]);
    }

    public function toggle(ProductQuestion $question)
    {
        $question->update(['hidden' => ! $question->hidden]);

        return back()->with('success', $question->hidden ? 'Pregunta oculta.' : 'Pregunta visible de nuevo.');
    }
}
