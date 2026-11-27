<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $all = $request->input('estado') === 'todos';

        return view('admin.contact.index', [
            'messages' => ContactMessage::latest('id')->when(! $all, fn ($q) => $q->whereNull('handled_at'))->paginate(30)->withQueryString(),
            'all' => $all,
        ]);
    }

    public function handle(Request $request, ContactMessage $message)
    {
        $message->update($message->handled_at ? ['handled_at' => null, 'handled_by' => null] : ['handled_at' => now(), 'handled_by' => $request->user()->id]);

        return back()->with('success', $message->handled_at ? 'Marcado como atendido.' : 'Marcado como pendiente.');
    }
}
