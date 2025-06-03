<?php

namespace App\Http\Controllers;

use App\Models\Tutorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TutorialController extends Controller
{
    public function index()
    {
        // Trae todos los tutoriales ordenados según el campo "orden"
        $tutoriales = Tutorial::orderBy('orden', 'asc')->get();

        return view('tutoriales.index', compact('tutoriales'));
    }

    public function create()
    {
        // Muestra el formulario para crear un tutorial
        return view('tutoriales.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'tipo'         => 'required|in:video,imagenes',
            'url'          => 'nullable|required_if:tipo,video|url',
            'imagenes.*'   => 'nullable|image|max:2048',
        ]);

        $data = $request->only('titulo', 'descripcion', 'tipo', 'url');

        // Si es de tipo "imagenes", guarda cada archivo en storage/app/public/tutoriales
        if ($request->tipo === 'imagenes' && $request->hasFile('imagenes')) {
            $rutas = [];
            foreach ($request->file('imagenes') as $img) {
                $rutas[] = $img->store('tutoriales', 'public');
            }
            $data['imagenes'] = json_encode($rutas);
        }

        Tutorial::create($data);

        return redirect()
            ->route('tutoriales.index')
            ->with('success', 'Tutorial creado correctamente.');
    }

    public function show(Tutorial $tutorial)
    {
        // Muestra detalles de un tutorial específico
        return view('tutoriales.show', compact('tutorial'));
    }

    public function edit(Tutorial $tutorial)
    {
        // Muestra el formulario de edición
        return view('tutoriales.edit', compact('tutorial'));
    }

    public function update(Request $request, Tutorial $tutorial)
    {
        $request->validate([
            'titulo'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'tipo'         => 'required|in:video,imagenes',
            'url'          => 'nullable|required_if:tipo,video|url',
            'imagenes.*'   => 'nullable|image|max:2048',
        ]);

        $data = $request->only('titulo', 'descripcion', 'tipo', 'url');

        if ($request->tipo === 'imagenes' && $request->hasFile('imagenes')) {
            // Eliminar imágenes previas (si existen)
            if ($tutorial->imagenes) {
                foreach (json_decode($tutorial->imagenes, true) as $viejaRuta) {
                    Storage::disk('public')->delete($viejaRuta);
                }
            }
            // Guardar nuevas imágenes
            $rutas = [];
            foreach ($request->file('imagenes') as $img) {
                $rutas[] = $img->store('tutoriales', 'public');
            }
            $data['imagenes'] = json_encode($rutas);
        }

        $tutorial->update($data);

        return redirect()
            ->route('tutoriales.index')
            ->with('success', 'Tutorial actualizado correctamente.');
    }

    public function destroy(Tutorial $tutorial)
    {
        // Si tenía imágenes, las elimina del disco
        if ($tutorial->imagenes) {
            foreach (json_decode($tutorial->imagenes, true) as $ruta) {
                Storage::disk('public')->delete($ruta);
            }
        }

        $tutorial->delete();

        return redirect()
            ->route('tutoriales.index')
            ->with('success', 'Tutorial eliminado correctamente.');
    }

    public function sort(Request $request)
    {
        foreach ($request->input('orden', []) as $item) {
            Tutorial::where('id', $item['id'])
                    ->update(['orden' => $item['orden']]);
        }

        return response()->json(['success' => true]);
    }
}
