<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Plugins;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function index()
    {
        return view('admin.plugins.index', ['plugins' => Plugins::definitions()]);
    }

    public function edit(string $plugin)
    {
        $def = Plugins::definitions()[$plugin] ?? abort(404);

        return view('admin.plugins.edit', ['key' => $plugin, 'def' => $def, 'values' => Plugins::config($plugin), 'enabled' => Plugins::enabled($plugin)]);
    }

    public function update(Request $request, string $plugin)
    {
        $def = Plugins::definitions()[$plugin] ?? abort(404);
        $rules = ['enabled' => 'boolean'];
        foreach (array_keys($def['fields']) as $field) {
            $rules["values.$field"] = 'nullable|string|max:5000';
        }
        $data = $request->validate($rules);
        Plugins::save($plugin, $request->boolean('enabled'), $data['values'] ?? []);

        return redirect()->route('admin.plugins.edit', $plugin)->with('success', "«{$def['name']}» guardado.");
    }

    public function toggle(Request $request, string $plugin)
    {
        $def = Plugins::definitions()[$plugin] ?? abort(404);
        Plugins::save($plugin, ! Plugins::enabled($plugin), Plugins::config($plugin));

        return back()->with('success', "«{$def['name']}» ".(Plugins::enabled($plugin) ? 'activado' : 'desactivado').'.');
    }

    public function reset(string $plugin)
    {
        $def = Plugins::definitions()[$plugin] ?? abort(404);
        Plugins::reset($plugin);

        return redirect()->route('admin.plugins.edit', $plugin)->with('success', "«{$def['name']}» volvió a los valores de marketcity.co.");
    }
}
