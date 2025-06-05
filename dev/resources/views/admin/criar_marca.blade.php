<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Criar Nova Marca</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    {{-- Barra de navegação/admin no topo --}}
    @include('admin.bar_admin')
    
    <div class="flex flex-col flex-1 justify-center items-center w-full">
        <div class="w-full max-w-md mt-12 p-6 bg-indigo-900 text-white rounded shadow-lg mx-auto">
            <h2 class="text-2xl font-bold mb-6 text-center">Criar Nova Marca</h2>
            <form id="formCriarMarca" onsubmit="enviarFormularioMarca(event)">
                @csrf
                <div class="mb-4">
                    <label for="marca" class="block text-sm mb-1 font-semibold">Nome da Marca:</label>
                    <input
                        type="text"
                        id="marca"
                        name="marca"
                        required
                        placeholder="Digite o nome da marca"
                        class="form-control bg-indigo-800 focus:bg-indigo-700 border-0 text-grey placeholder-gray-300"
                    >
                </div>
                <button
                    type="submit"
                    class="w-full py-2 px-4 rounded bg-cyan-400 hover:bg-cyan-600 transition font-bold text-white"
                >
                    Criar Marca
                </button>
            </form>
            <div id="mensagemResposta" class="mt-4 text-center text-sm"></div>

            <div class="mt-6 flex justify-center">
                <a href="{{ url()->previous() }}"
                   class="inline-block text-cyan-200 hover:text-cyan-400 font-semibold">
                   ← Voltar
                </a>
            </div>
        </div>
    </div>

<script>
    async function enviarFormularioMarca(event) {
        event.preventDefault();

        const form = document.getElementById('formCriarMarca');
        const mensagemDiv = document.getElementById('mensagemResposta');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        const token = document.querySelector('input[name="_token"]').value;

        mensagemDiv.innerHTML = '';
        mensagemDiv.className = 'mt-4 text-center text-sm';

        try {
            const response = await fetch('/marca/criar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                mensagemDiv.className += ' text-green-300 font-bold';
                mensagemDiv.innerHTML = result.message;
                setTimeout(() => {
                    window.location.href = '{{ url("admin/dashboard") }}';
                }, 2000); // Redireciona após 2 segundos
            } else {
                mensagemDiv.className += ' text-red-400 font-bold';
                mensagemDiv.innerHTML = result.error || 'Erro ao criar marca.';
            }
        } catch (error) {
            mensagemDiv.className += ' text-red-400 font-bold';
            mensagemDiv.innerHTML = 'Erro de conexão. Tente novamente.';
            console.error('Erro:', error);
        }
    }
</script>
</body>
</html>