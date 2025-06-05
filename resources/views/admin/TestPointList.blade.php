<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lista de Test Points</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans min-h-screen flex flex-col">
    @include('admin.bar_admin') <!-- Navbar -->

    <div class="flex-1 flex justify-center items-center p-8">
        <div class="bg-gray-50 p-8 w-[90%] max-w-2xl rounded-lg shadow-lg text-center">
            <h2 class="mb-6 text-2xl">Lista de Marcas e Modelos de Test Points</h2>

            <!-- Campo de Pesquisa -->
            <div class="mb-6 flex justify-center">
                <input type="text" id="searchInput" class="p-2 border border-gray-300 rounded w-[70%] max-w-xs bg-white text-gray-700"
                    placeholder="Pesquisar por marca ou modelo..." onkeyup="pesquisarTempoReal(this.value)">
            </div>

            <!-- Lista de Marcas e Modelos -->
            <div class="text-left" id="resultadosPesquisa">
                @foreach($marcas as $marca => $modelos)
                    <div class="marca-container mb-2" data-marca="{{ $marca }}">
                        <div class="marca-item cursor-pointer font-bold mb-2 bg-gray-700 text-white border-none p-3 rounded flex justify-between items-center hover:bg-blue-400 transition-colors"
                             onclick="toggleModelos('{{ $marca }}')">
                            <span>{{ $marca }}</span>
                            <div>
                                <button class="edit bg-gray-500 text-white py-1 px-2 ml-2 rounded text-sm hover:bg-green-500"
                                        onclick="abrirModalEditarMarca('{{ $marca }}'); event.stopPropagation();">Editar</button>
                                <button class="delete bg-gray-500 text-white py-1 px-2 ml-2 rounded text-sm hover:bg-red-500"
                                        onclick="deletarMarca('{{ $marca }}'); event.stopPropagation();">Deletar</button>
                            </div>
                        </div>
                        <div id="modelos-{{ $marca }}" class="modelo-list hidden">
                            @foreach($modelos as $modelo)
                                <div class="modelo-item cursor-pointer ml-5 bg-gray-700 text-white border-none p-2 rounded flex justify-between items-center hover:bg-blue-600 transition-colors"
                                     data-modelo="{{ $modelo['modelo'] }}"
                                     onclick="abrirModal('{{ addslashes($modelo['imagem_url']) }}', '{{ addslashes($modelo['modelo']) }}')">
                                    <span>{{ $modelo['modelo'] }}</span>
                                    <div>
                                        <button class="edit bg-gray-500 text-white py-1 px-2 ml-2 rounded text-sm hover:bg-green-500"
                                                onclick="abrirModalEditarModelo('{{ $marca }}', '{{ $modelo['modelo'] }}'); event.stopPropagation();">Editar</button>
                                        <button class="delete bg-gray-500 text-white py-1 px-2 ml-2 rounded text-sm hover:bg-red-500"
                                                onclick="deletarModelo('{{ $marca }}', '{{ $modelo['modelo'] }}'); event.stopPropagation();">Deletar</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- MODAIS CENTRALIZADOS NA TELA -->
    <!-- Modal: IMAGEM -->
    <div id="imagemModal" class="fixed z-50 inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden">
        <div class="w-[90%] max-w-4xl bg-gray-50 rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h2 id="imagemModalLabel" class="text-xl font-medium">Imagem do Test Point</h2>
                <button onclick="fecharModal('imagemModal')" class="text-black hover:text-red-600 text-2xl">&times;</button>
            </div>
            <div class="p-6 flex flex-col items-center">
                <img id="modalImagem" src="" alt="Imagem do Test Point" class="max-w-full max-h-[70vh] mx-auto"
                     onload="console.log('Imagem carregada com sucesso')" onerror="imagemErro()">
                <p id="mensagemErro" class="text-red-500 text-sm mt-1 hidden">Erro ao carregar a imagem. Verifique a URL.</p>
            </div>
            <div class="p-4 border-t border-gray-200 flex justify-end">
                <button onclick="fecharModal('imagemModal')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: EDITAR MARCA -->
    <div id="editarMarcaModal" class="fixed z-50 inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden">
        <div class="bg-gray-50 w-[90%] max-w-md rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h2 class="text-xl font-medium" id="editarMarcaModalLabel">Editar Marca</h2>
                <button onclick="fecharModal('editarMarcaModal')" class="text-black hover:text-red-600 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <form id="formEditarMarca" class="w-full">
                    <div class="mb-4">
                        <label for="marcaAntiga" class="block mb-2">Nome Atual</label>
                        <input type="text" id="marcaAntiga" name="marca_antiga" readonly
                               class="w-full p-2 border border-gray-300 rounded bg-white text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label for="marcaNova" class="block mb-2">Novo Nome</label>
                        <input type="text" id="marcaNova" name="marca_nova" required
                               class="w-full p-2 border border-gray-300 rounded bg-white text-gray-700">
                    </div>
                </form>
                <p id="mensagemErroMarca" class="text-red-500 text-sm hidden"></p>
            </div>
            <div class="p-4 border-t border-gray-200 flex justify-end">
                <button type="button" onclick="fecharModal('editarMarcaModal')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">Cancelar</button>
                <button type="button" onclick="salvarMarcaEditada()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Modal: EDITAR MODELO -->
    <div id="editarModeloModal" class="fixed z-50 inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden">
        <div class="bg-gray-50 w-[90%] max-w-md rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h2 class="text-xl font-medium" id="editarModeloModalLabel">Editar Modelo</h2>
                <button onclick="fecharModal('editarModeloModal')" class="text-black hover:text-red-600 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <form id="formEditarModelo" enctype="multipart/form-data" class="w-full">
                    <div class="mb-4">
                        <label for="marcaModelo" class="block mb-2">Marca</label>
                        <input type="text" id="marcaModelo" name="marca" readonly
                               class="w-full p-2 border border-gray-300 rounded bg-white text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label for="modeloAntigo" class="block mb-2">Nome Atual do Modelo</label>
                        <input type="text" id="modeloAntigo" name="modelo_antigo" readonly
                               class="w-full p-2 border border-gray-300 rounded bg-white text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label for="modeloNovo" class="block mb-2">Novo Nome do Modelo (opcional)</label>
                        <input type="text" id="modeloNovo" name="modelo_novo"
                               class="w-full p-2 border border-gray-300 rounded bg-white text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label for="novaImagem" class="block mb-2">Nova Imagem (opcional)</label>
                        <input type="file" id="novaImagem" name="imagem" accept="image/*"
                               class="w-full border border-gray-300 rounded bg-white text-gray-700">
                    </div>
                </form>
                <p id="mensagemErroModelo" class="text-red-500 text-sm hidden"></p>
            </div>
            <div class="p-4 border-t border-gray-200 flex justify-end">
                <button type="button" onclick="fecharModal('editarModeloModal')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">Cancelar</button>
                <button type="button" onclick="salvarModeloEditado()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS apenas para eventuais dependências, mas NÃO usado mais nos modais -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let timeoutPesquisa;

    // MODAIS CENTRALIZADOS: Função universal
    function abrirModalImagem(urlImagem, nomeModelo) {
        document.getElementById('imagemModal').classList.remove('hidden');
        let modalImagem = document.getElementById('modalImagem');
        let modalTitle = document.getElementById('imagemModalLabel');
        let mensagemErro = document.getElementById('mensagemErro');
        urlImagem = decodeURIComponent(urlImagem);
        modalImagem.src = urlImagem;
        modalTitle.textContent = `Imagem do Test Point - ${nomeModelo}`;
        mensagemErro.classList.add('hidden');
    }
    function abrirModal(urlImagem, nomeModelo) { abrirModalImagem(urlImagem, nomeModelo); }
    function fecharModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Marca - Editar
    function abrirModalEditarMarca(marca) {
        document.getElementById('editarMarcaModal').classList.remove('hidden');
        document.getElementById('marcaAntiga').value = marca;
        document.getElementById('marcaNova').value = marca;
        document.getElementById('mensagemErroMarca').classList.add('hidden');
    }
    function salvarMarcaEditada() {
        const marcaAntiga = document.getElementById('marcaAntiga').value;
        const marcaNova = document.getElementById('marcaNova').value;
        const mensagemErro = document.getElementById('mensagemErroMarca');
        if (!marcaNova) {
            mensagemErro.textContent = 'Por favor, insira um novo nome para a marca.';
            mensagemErro.classList.remove('hidden');
            return;
        }
        fetch('/admin/marca/editar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ marca_antiga: marcaAntiga, marca_nova: marcaNova })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                mensagemErro.textContent = data.error;
                mensagemErro.classList.remove('hidden');
            } else {
                alert(data.message);
                location.reload();
            }
        })
        .catch(() => {
            mensagemErro.textContent = 'Erro ao salvar. Tente novamente.';
            mensagemErro.classList.remove('hidden');
        });
    }

    // Modelo - Editar
    function abrirModalEditarModelo(marca, modelo) {
        document.getElementById('editarModeloModal').classList.remove('hidden');
        document.getElementById('marcaModelo').value = marca;
        document.getElementById('modeloAntigo').value = modelo;
        document.getElementById('modeloNovo').value = modelo;
        document.getElementById('novaImagem').value = '';
        document.getElementById('mensagemErroModelo').classList.add('hidden');
    }
    function salvarModeloEditado() {
        const marca = document.getElementById('marcaModelo').value;
        const modeloAntigo = document.getElementById('modeloAntigo').value;
        const modeloNovo = document.getElementById('modeloNovo').value;
        const novaImagem = document.getElementById('novaImagem');
        const mensagemErro = document.getElementById('mensagemErroModelo');
        const formData = new FormData();
        formData.append('marca', marca);
        formData.append('modelo_antigo', modeloAntigo);
        if (modeloNovo) formData.append('modelo_novo', modeloNovo);
        if (novaImagem.files.length > 0) formData.append('imagem', novaImagem.files[0]);
        formData.append('_token', csrfToken);

        fetch('/admin/testpoint/editar', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                mensagemErro.textContent = data.error;
                mensagemErro.classList.remove('hidden');
            } else {
                alert(data.message);
                location.reload();
            }
        })
        .catch(() => {
            mensagemErro.textContent = 'Erro ao salvar. Tente novamente.';
            mensagemErro.classList.remove('hidden');
        });
    }

    // Modal de imagem: erro ao carregar
    function imagemErro() {
        document.getElementById('mensagemErro').classList.remove('hidden');
        console.error('Erro ao carregar a imagem.');
    }

    // Deletar marca/modelo
    function deletarMarca(marca) {
        if (confirm(`Tem certeza que deseja deletar a marca "${marca}" e todos os seus modelos? Essa ação não pode ser desfeita.`)) {
            fetch('/admin/marca/deletar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ marca })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.error ? data.error : data.message);
                if (!data.error) location.reload();
            })
        }
    }
    function deletarModelo(marca, modelo) {
        if (confirm(`Tem certeza que deseja deletar o modelo "${modelo}" da marca "${marca}"? Essa ação não pode ser desfeita.`)) {
            fetch('/admin/testpoint/deletar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ marca, modelo })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.error ? data.error : data.message);
                if (!data.error) location.reload();
            })
        }
    }

    // Abrir/fechar modelos na lista por marca
    function toggleModelos(marca) {
        const el = document.getElementById(`modelos-${marca}`);
        if (el.classList.contains('hidden')) el.classList.remove('hidden');
        else el.classList.add('hidden');
    }

    // Pesquisa em tempo real
    function pesquisarTempoReal(texto) {
        clearTimeout(timeoutPesquisa);
        timeoutPesquisa = setTimeout(() => {
            if (!texto.trim()) {
                document.querySelectorAll('.marca-container').forEach(container => {
                    container.style.display = 'block';
                    let modelos = container.querySelector('.modelo-list');
                    if (modelos) modelos.classList.add('hidden');
                });
                return;
            }
            fetch(`/admin/pesquisar?termo=${encodeURIComponent(texto)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }
            })
            .then(r => r.ok ? r.json() : Promise.reject(r.status))
            .then(data => {
                if (!data || !data.resultados) return;
                document.querySelectorAll('.marca-container').forEach(c => c.style.display = 'none');
                Object.entries(data.resultados).forEach(([marca, modelos]) => {
                    const marcaContainer = document.querySelector(`.marca-container[data-marca="${marca}"]`);
                    if (marcaContainer) {
                        marcaContainer.style.display = 'block';
                        const modelosList = marcaContainer.querySelector('.modelo-list');
                        if (modelosList) {
                            modelosList.classList.remove('hidden');
                            modelosList.querySelectorAll('.modelo-item').forEach(i => i.style.display = 'none');
                            (modelos || []).forEach(modeloObj => {
                                if (modeloObj && modeloObj.modelo) {
                                    const modeloItem = modelosList.querySelector(`.modelo-item[data-modelo="${modeloObj.modelo}"]`);
                                    if (modeloItem) modeloItem.style.display = 'flex';
                                }
                            });
                        }
                    }
                });
            });
        }, 300);
    }
    </script>
</body>
</html>