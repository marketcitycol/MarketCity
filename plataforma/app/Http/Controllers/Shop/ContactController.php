<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\User;
use App\Services\Content\Plugins;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Página de contacto con formulario y copia por correo a quien escribe (#66). */
class ContactController extends Controller
{
    public function show()
    {
        $page = Page::where('slug', 'contacto')->first();

        return view('shop.page', ['page' => $page && $page->isPublished() ? $page : new Page(['slug' => 'contacto', 'title' => 'Contacto', 'body' => ''])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'topic' => ['required', Rule::in(array_keys(ContactMessage::TOPICS))],
            'order_number' => 'nullable|string|max:20',
            'source' => ['nullable', Rule::in(ContactMessage::SOURCES)],
            'message' => 'required|string|min:10|max:3000',
            'copy' => 'nullable|boolean',
            'website' => 'prohibited', // trampa para bots
        ], [], ['name' => 'nombre', 'email' => 'correo', 'phone' => 'celular', 'topic' => 'tema', 'message' => 'mensaje', 'order_number' => 'número de pedido']);

        $contact = ContactMessage::create([
            'user_id' => $request->user()?->id,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'topic' => $data['topic'],
            'order_number' => isset($data['order_number']) ? strtoupper(trim($data['order_number'])) : null,
            'source' => $data['source'] ?? null,
            'message' => trim($data['message']),
        ]);

        $team = array_filter([Plugins::config('footer')['email'] ?? null]) ?: User::where('role', User::ROLE_ADMIN)->pluck('email')->all();
        foreach (array_unique($team) as $to) {
            Mailer::send($to, new ContactMessageMail($contact));
        }
        if ($request->boolean('copy', true)) {
            Mailer::send($contact->email, new ContactMessageMail($contact, copy: true));
        }

        return redirect()->to(route('contact').'#formulario')->with('success', 'Recibimos tu mensaje. Te respondemos al correo '.$contact->email.'.');
    }
}
