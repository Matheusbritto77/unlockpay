<?php

namespace App\Http\Controllers;

use App\Models\Pelicula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PeliculaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $peliculas = Pelicula::all();
        return view('admin.peliculaslist', compact('peliculas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('peliculas.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validação dos dados
        $validator = Validator::make($request->all(), [
            'modelo' => 'required|string|max:255',
            'modelos_compativeis' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Criação do registro
        Pelicula::create([
            'modelo' => $request->modelo,
            'modelos_compativeis' => $request->modelos_compativeis,
        ]);

        return redirect()->route('peliculas.index')->with('success', 'Película criada com sucesso!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pelicula = Pelicula::findOrFail($id);
        return view('peliculas.show', compact('pelicula'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pelicula = Pelicula::findOrFail($id);
        return view('peliculas.edit', compact('pelicula'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validação dos dados
        $validator = Validator::make($request->all(), [
            'modelo' => 'required|string|max:255',
            'modelos_compativeis' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Atualização do registro
        $pelicula = Pelicula::findOrFail($id);
        $pelicula->update([
            'modelo' => $request->modelo,
            'modelos_compativeis' => $request->modelos_compativeis,
        ]);

        return redirect()->route('peliculas.index')->with('success', 'Película atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pelicula = Pelicula::findOrFail($id);
        $pelicula->delete();

        return redirect()->route('peliculas.index')->with('success', 'Película deletada com sucesso!');
    }

    /**
     * Search for peliculas by modelo in real-time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query');

        // Busca películas cujo modelo contenha o termo digitado (case insensitive)
        $peliculas = Pelicula::where('modelo', 'LIKE', "%{$query}%")->get();

        // Retorna os resultados em formato JSON
        return response()->json([
            'peliculas' => $peliculas
        ]);
    }
    
    
    /**
 * Retrieve all peliculas as a JSON response for API usage.
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function getAllPeliculas()
{
    $peliculas = Pelicula::all();
    return response()->json([
        'success' => true,
        'data' => $peliculas,
        'message' => 'Lista de películas recuperada com sucesso.'
    ], 200);
}
    
    
}