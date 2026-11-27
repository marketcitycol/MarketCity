<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\ProductQuestionMail;
use App\Models\Product;
use App\Support\ContactGuard;
use App\Support\Mailer;
use Illuminate\Http\Request;

/** «Pregúntale al vendedor» en la página del producto: la pregunta le llega por correo a la tienda. */
class ProductQuestionController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($product->status === 'active' && $product->store->isActive(), 404);
        $user = $request->user();
        $data = $request->validate([
            'question' => 'required|string|min:8|max:500',
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:80'],
            'email' => [$user ? 'nullable' : 'required', 'email', 'max:150'],
            'website' => 'prohibited', // trampa para bots
        ], [], ['question' => 'pregunta', 'name' => 'nombre', 'email' => 'correo']);

        if (ContactGuard::hasContactData($data['question'])) {
            return back()->withInput()->withErrors(['question' => 'No incluyas teléfonos, correos ni enlaces: la respuesta te llega a tu correo y queda publicada aquí.']);
        }

        $question = $product->questions()->create([
            'store_id' => $product->store_id,
            'user_id' => $user?->id,
            'name' => $user?->name ?? $data['name'],
            'email' => $user?->email ?? strtolower($data['email']),
            'question' => trim($data['question']),
        ]);

        Mailer::send($product->store->notificationEmail(), new ProductQuestionMail($question));

        return redirect()->to(route('product', $product).'#preguntas')
            ->with('success', 'Enviamos tu pregunta a '.$product->store->name.'. Te avisamos por correo cuando responda.');
    }
}
