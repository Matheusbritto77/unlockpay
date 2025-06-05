<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Test Point</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans min-h-screen flex flex-col">
    <!-- Incluindo a barra de navegação -->
    @include('admin.bar_admin')

    <div class="flex-1 flex justify-center items-center p-8">
        <div class="bg-gray-50 p-8 rounded-lg w-[90%] max-w-md text-center shadow-lg">
            <h2 class="mt-0 mb-6 text-2xl">Criar Novo Test Point</h2>
            <form id="formCriarTestPoint" action="{{ route('test-point.criar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-6 text-left">
                    <label for="marca" class="block mb-2 font-bold">Marca:</label>
                    <select id="marca" name="marca" required class="w-full p-2 border border-gray-300 rounded bg-white text-gray-900 text-base">
                        <option value="">Selecione uma Marca</option>
                        @foreach($marcas ?? [] as $marca)
                            <option value="{{ $marca }}">{{ ucfirst($marca) }}</option>
                        @endforeach
                    </select>
                    @error('marca')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-6 text-left">
                    <label for="modelo" class="block mb-2 font-bold">Nome do Test Point (Modelo):</label>
                    <input type="text" id="modelo" name="modelo" required placeholder="Digite o nome do modelo" value="{{ old('modelo') }}" class="w-full p-2 border border-gray-300 rounded bg-white text-gray-900 text-base">
                    @error('modelo')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-6 text-left">
                    <label for="imagem" class="block mb-2 font-bold">Imagem do Test Point:</label>
                    <input type="file" id="imagem" name="imagem" accept="image/*" required class="w-full p-2 text-gray-900">
                    @error('imagem')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <button type="submit" class="bg-blue-400 text-white border-none px-6 py-3 rounded cursor-pointer text-base font-bold transition-colors duration-300 hover:bg-blue-600 disabled:bg-gray-500 disabled:cursor-not-allowed">Criar Test Point</button>
                </div>
            </form>
            @if (session('success'))
                <div id="mensagemRespostaTestPoint" class="mt-4 text-sm p-2 rounded text-center bg-green-100 text-green-600">
                    {{ session('success') }}
                </div>
            @elseif (session('error'))
                <div id="mensagemRespostaTestPoint" class="mt-4 text-sm p-2 rounded text-center bg-red-100 text-red-600">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>