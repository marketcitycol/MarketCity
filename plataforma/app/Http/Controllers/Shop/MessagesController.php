<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MessagingPermissions;
use Illuminate\Http\Request;

/** Baja de mensajes con un clic desde el enlace firmado de cada correo (#82). */
class MessagesController extends Controller
{
    public function show(Request $request)
    {
        $data = MessagingPermissions::decodeUnsubscribe((string) $request->query('d'));
        abort_unless($data, 404);

        return view('shop.unsubscribe', ['done' => false, 'payload' => $request->query('d'), 'hasPhone' => (bool) $data['p']]);
    }

    public function unsubscribe(Request $request, MessagingPermissions $permissions)
    {
        $data = MessagingPermissions::decodeUnsubscribe((string) $request->query('d'));
        abort_unless($data, 404);
        $channel = in_array($request->input('canal'), ['email', 'whatsapp'], true) ? $request->input('canal') : 'todo';
        $permissions->revoke($data['e'], $data['p'], $channel, 'baja-enlace');

        return $request->expectsJson() || $request->has('List-Unsubscribe')
            ? response()->noContent()
            : view('shop.unsubscribe', ['done' => true, 'payload' => null, 'hasPhone' => false]);
    }
}
