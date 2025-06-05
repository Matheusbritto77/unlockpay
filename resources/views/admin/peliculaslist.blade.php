<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Películas - Admin</title>
    <!-- Incluindo Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Estilo personalizado para ajustar o modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 16px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 0.5rem;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        /* Estilo para tabela em mobile */
        @media (max-width: 640px) {
            .table-mobile-stack tr {
                display: block;
                margin-bottom: 1rem;
                border-bottom: 2px solid #e2e8f0;
            }
            .table-mobile-stack td {
                display: block;
                text-align: left;
                padding: 0.5rem 1.5rem;
            }
            .table-mobile-stack td::before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 0.5rem;
                color: #4a5568;
            }
            .table-mobile-stack th {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">
    <!-- Incluindo a barra de navegação fixada no topo -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-md">
        @include('admin.bar_admin')
    </header>
<br>
<br><br><br>
    <!-- Conteúdo principal com margem superior -->
    <div class="container mx-auto p-4 md:p-6 flex-1 mt-16">
        <h1 class="text-2xl md:text-3xl font-bold mb-6 text-center text-gray-800">Lista de Películas</h1>

        <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-3">
            <button id="openCreateModal" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full md:w-auto">
                Adicionar Nova Película
            </button>
            <input type="text" id="searchInput" placeholder="Buscar por modelo..." 
                   class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-800">
        </div>

        <div id="searchResults" class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table id="peliculasTable" class="min-w-full divide-y divide-gray-200 table-mobile-stack">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modelo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modelos Compatíveis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="peliculasBody" class="bg-white divide-y divide-gray-200">
                        @foreach($peliculas as $pelicula)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800" data-label="ID">{{ $pelicula->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800" data-label="Modelo">{{ $pelicula->modelo }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800" data-label="Modelos Compatíveis">{{ $pelicula->modelos_compativeis ?? 'Nenhum' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" data-label="Ações">
                                    <button onclick="openEditModal({{ $pelicula->id }}, '{{ $pelicula->modelo }}', '{{ $pelicula->modelos_compativeis ?? '' }}')" class="text-green-600 hover:text-green-900 mr-2">Editar</button>
                                    <form action="{{ route('peliculas.destroy', $pelicula->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Tem certeza que deseja deletar?')" class="text-red-600 hover:text-red-900">Deletar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para Criar Película -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCreateModal()">&times;</span>
            <h2 class="text-xl md:text-2xl font-bold mb-4 text-gray-800">Adicionar Nova Película</h2>
            <form action="{{ route('peliculas.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="modelo" class="block text-gray-800 font-bold mb-2">Modelo:</label>
                    <input type="text" name="modelo" id="modelo" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-800" required>
                </div>
                <div class="mb-4">
                    <label for="modelos_compativeis" class="block text-gray-800 font-bold mb-2">Modelos Compatíveis:</label>
                    <textarea name="modelos_compativeis" id="modelos_compativeis" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-800 h-24"></textarea>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full md:w-auto">Salvar</button>
            </form>
        </div>
    </div>

    <!-- Modal para Editar Película -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2 class="text-xl md:text-2xl font-bold mb-4 text-gray-800">Editar Película</h2>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-4">
                    <label for="edit_modelo" class="block text-gray-800 font-bold mb-2">Modelo:</label>
                    <input type="text" name="modelo" id="edit_modelo" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-800" required>
                </div>
                <div class="mb-4">
                    <label for="edit_modelos_compativeis" class="block text-gray-800 font-bold mb-2">Modelos Compatíveis:</label>
                    <textarea name="modelos_compativeis" id="edit_modelos_compativeis" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-800 h-24"></textarea>
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded w-full md:w-auto">Atualizar</button>
            </form>
        </div>
    </div>

    <script>
        // Funções para o Modal de Criar
        function openCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }
        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        // Funções para o Modal de Editar
        function openEditModal(id, modelo, modelos_compativeis) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_modelo').value = modelo;
            document.getElementById('edit_modelos_compativeis').value = modelos_compativeis || '';
            // Define a ação do formulário dinamicamente
            document.getElementById('editForm').action = `peliculas/${id}`;
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Abre o modal de criar ao clicar no botão
        document.getElementById('openCreateModal').addEventListener('click', openCreateModal);

        // Fecha os modais ao clicar fora deles
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeCreateModal();
                closeEditModal();
            }
        }

        // Variável global para armazenar a lista original de películas
        let peliculasOriginais = [];

        // Função para capturar e armazenar os dados originais ao carregar a página
        window.addEventListener('load', function() {
            const tbody = document.getElementById('peliculasBody');
            const rows = tbody.getElementsByTagName('tr');
            
            for (let row of rows) {
                const id = row.cells[0].textContent;
                const modelo = row.cells[1].textContent;
                const modelosCompativeis = row.cells[2].textContent === 'Nenhum' ? '' : row.cells[2].textContent;
                peliculasOriginais.push({
                    id: id,
                    modelo: modelo,
                    modelos_compativeis: modelosCompativeis
                });
            }
        });

        // Função de busca em tempo real
        document.getElementById('searchInput').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const tbody = document.getElementById('peliculasBody');
            tbody.innerHTML = ''; // Limpa resultados anteriores

            if (query.length >= 3) { // Só faz busca no servidor se tiver pelo menos 3 caracteres
                fetch(`/peliculas/search?query=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.peliculas.length > 0) {
                        data.peliculas.forEach(pelicula => {
                            const row = document.createElement('tr');
                            row.classList.add('hover:bg-gray-50');
                            row.innerHTML = `
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800" data-label="ID">${pelicula.id}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800" data-label="Modelo">${pelicula.modelo}</td>
                                <td class="px-6 py-4 text-sm text-gray-800" data-label="Modelos Compatíveis">${pelicula.modelos_compativeis || 'Nenhum'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" data-label="Ações">
                                    <button onclick="openEditModal(${pelicula.id}, '${pelicula.modelo}', '${pelicula.modelos_compativeis || ''}')" class="text-green-600 hover:text-green-900 mr-2">Editar</button>
                                    <form action="peliculas/${pelicula.id}" method="POST" style="display:inline;">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                                        <button type="submit" onclick="return confirm('Tem certeza que deseja deletar?')" class="text-red-600 hover:text-red-900">Deletar</button>
                                    </form>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="4" class="p-4 text-center text-gray-500">Nenhum resultado encontrado.</td>`;
                        tbody.appendChild(row);
                    }
                })
                .catch(error => {
                    console.error('Erro na busca:', error);
                    tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-red-500">Erro ao buscar. Tente novamente.</td></tr>`;
                });
            } else {
                // Se tiver menos de 3 caracteres, restaura a lista original sem reload
                if (peliculasOriginais.length > 0) {
                    peliculasOriginais.forEach(pelicula => {
                        const row = document.createElement('tr');
                        row.classList.add('hover:bg-gray-50');
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800" data-label="ID">${pelicula.id}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800" data-label="Modelo">${pelicula.modelo}</td>
                            <td class="px-6 py-4 text-sm text-gray-800" data-label="Modelos Compatíveis">${pelicula.modelos_compativeis || 'Nenhum'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm" data-label="Ações">
                                <button onclick="openEditModal(${pelicula.id}, '${pelicula.modelo}', '${pelicula.modelos_compativeis || ''}')" class="text-green-600 hover:text-green-900 mr-2">Editar</button>
                                <form action="peliculas/${pelicula.id}" method="POST" style="display:inline;">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                                    <button type="submit" onclick="return confirm('Tem certeza que deseja deletar?')" class="text-red-600 hover:text-red-900">Deletar</button>
                                </form>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td colspan="4" class="p-4 text-center text-gray-500">Nenhuma película encontrada.</td>`;
                    tbody.appendChild(row);
                }
            }
        });
    </script>
</body>
</html>