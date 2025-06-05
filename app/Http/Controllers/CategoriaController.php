<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Support\Facades\Auth;

class CategoriaController extends Controller
{
    /**
     * Register a new category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        // Validação dos dados de entrada
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        // Criação de uma nova categoria
        $categoria = new Categoria();
        $categoria->user_id = Auth::id(); // Pegando o ID do usuário logado
        $categoria->nome = $validatedData['nome'];
        $categoria->save();

        return response()->json(['message' => 'Categoria registrada com sucesso', 'categoria' => $categoria], 201);
    }

    /**
     * Display a listing of the user's categories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Pegando o ID do usuário logado
        $userId = Auth::id();

        // Buscando todas as categorias do usuário logado
        $categorias = Categoria::where('user_id', $userId)->get();

        return response()->json(['categorias' => $categorias], 200);
    }

    /**
     * Update the specified category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validação dos dados de entrada
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        // Encontrar a categoria pelo ID
        $categoria = Categoria::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$categoria) {
            return response()->json(['message' => 'Categoria não encontrada'], 404);
        }

        // Atualizar a categoria
        $categoria->nome = $validatedData['nome'];
        $categoria->save();

        return response()->json(['message' => 'Categoria atualizada com sucesso', 'categoria' => $categoria], 200);
    }

    /**
     * Remove the specified category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        // Encontrar a categoria pelo ID
        $categoria = Categoria::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$categoria) {
            return response()->json(['message' => 'Categoria não encontrada'], 404);
        }

        // Deletar a categoria
        $categoria->delete();

        return response()->json(['message' => 'Categoria deletada com sucesso'], 200);
    }
}