<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Mail\ProductQuestionAnsweredMail;
use App\Models\ProductQuestion;
use App\Support\ContactGuard;
use App\Support\Mailer;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $store = $request->user()->store;
        $answered = $request->input('estado') === 'respondidas';
        $q = ProductQuestion::with('product')->where('store_id', $store->id)->where('hidden', false)
            ->when($answered, fn ($q) => $q->whereNotNull('answered_at')->latest('answered_at'), fn ($q) => $q->whereNull('answered_at')->oldest());

        return view('seller.questions.index', ['questions' => $q->paginate(20)->withQueryString(), 'answered' => $answered]);
    }

    public function answer(Request $request, ProductQuestion $question)
    {
        abort_unless($question->store_id === $request->user()->store->id && ! $question->hidden, 404);
        $data = $request->validate(['answer' => 'required|string|min:2|max:1000'], [], ['answer' => 'respuesta']);
        if (ContactGuard::hasContactData($data['answer'])) {
            return back()->withInput()->withErrors(['answer' => 'La respuesta es pública: no incluyas teléfonos, correos ni enlaces. La venta se hace en MarketCity.']);
        }
        $first = ! $question->answered_at;
        $question->update(['answer' => trim($data['answer']), 'answered_at' => now(), 'answered_by' => $request->user()->id]);
        if ($first) {
            Mailer::send($question->email, new ProductQuestionAnsweredMail($question));
        }

        return back()->with('success', 'Respuesta publicada'.($first ? ' y enviada al cliente.' : '.'));
    }
}
