<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $groups = ['general', 'social', 'shop', 'booking', 'invoice'];

        $settings = [];
        foreach ($groups as $group) {
            $settings[$group] = Setting::where('group', $group)
                ->orderBy('key')
                ->get()
                ->keyBy('key')
                ->map(fn($s) => [
                    'key'         => $s->key,
                    'value'       => $s->value,
                    'type'        => $s->type,
                    'label'       => $s->label,
                    'description' => $s->description,
                ]);
        }

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        foreach ($request->validated()['settings'] as $key => $value) {
            Setting::set($key, $value);
        }

        // Vider le cache
        Cache::flush();

        return back()->with('success', 'Paramètres enregistrés.');
    }

    public function updateGroup(Request $request, string $group): RedirectResponse
    {
        $settings = Setting::where('group', $group)->get();

        foreach ($settings as $setting) {
            $key   = $setting->key;
            $value = $request->input($key);

            if ($value === null) continue;

            // Gestion des fichiers (logo, etc.)
            if ($setting->type === 'file' && $request->hasFile($key)) {
                $old = Setting::get($key);
                if ($old) Storage::disk('public')->delete($old);
                $value = $request->file($key)->store("settings/{$group}", 'public');
            }

            Setting::set($key, $value);
        }

        Cache::forget("settings.group.{$group}");

        return back()->with('success', 'Paramètres mis à jour.');
    }
}