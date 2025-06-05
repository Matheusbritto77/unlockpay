<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Lista de Usuários</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-50">

  <!-- NAVBAR -->
  <nav class="bg-indigo-900 shadow px-4 py-3">
    <div class="flex justify-between items-center">
      <div class="text-xl font-bold text-white">Painel do Administrador</div>
      <!-- Botão hamburger -->
      <button id="menuToggle" class="text-white sm:hidden focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>

    <!-- Itens do menu -->
    <div id="mobileMenu" class="hidden sm:flex flex-col sm:flex-row sm:items-center sm:justify-start sm:space-x-4 mt-3 sm:mt-0">
      <a href="{{ route('admin.dashboard') }}"
         class="text-white font-semibold px-3 py-2 hover:bg-indigo-700 transition rounded block">
         Dashboard
      </a>

      <a href="{{ route('peliculas.index') }}"
         class="text-white font-semibold px-3 py-2 hover:bg-indigo-700 transition rounded block">
         Peliculas
      </a>

      <!-- Dropdown Test Point -->
      <div class="relative w-full sm:w-auto">
        <button
          type="button"
          onclick="toggleDropdown('tpDrop')"
          class="dropdown-toggle-btn w-full text-left text-white font-semibold px-3 py-2 hover:bg-indigo-700 transition rounded inline-flex items-center sm:w-auto"
        >
          Test Point
          <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div id="tpDrop" class="absolute left-0 z-20 mt-1 w-44 bg-indigo-900 rounded shadow-lg text-white invisible opacity-0 transition-opacity duration-150">
          <a href="{{ route('listMarcasModelos') }}" class="block px-4 py-2 hover:bg-indigo-800">Listar</a>
          <a href="{{ route('marcas.lista') }}" class="block px-4 py-2 hover:bg-indigo-800">Criar Test Point</a>
          <a href="{{ route('admin.criar_marca') }}" class="block px-4 py-2 hover:bg-indigo-800">Criar Marca</a>
        </div>
      </div>

      <!-- Dropdown Usuários -->
      <div class="relative w-full sm:w-auto">
        <button
          type="button"
          onclick="toggleDropdown('userDrop')"
          class="dropdown-toggle-btn w-full text-left text-white font-semibold px-3 py-2 hover:bg-indigo-700 transition rounded inline-flex items-center sm:w-auto"
        >
          Usuários
          <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div id="userDrop" class="absolute left-0 z-20 mt-1 w-32 bg-indigo-900 rounded shadow-lg text-white invisible opacity-0 transition-opacity duration-150">
          <a href="{{ route('admin.listarUsuarios') }}" class="block px-4 py-2 hover:bg-indigo-800">Listar</a>
        </div>
      </div>

      <a href="javascript:window.history.back();" class="text-cyan-400 font-bold hover:text-cyan-200 px-2 block mt-2 sm:mt-0">
        ← Voltar
      </a>
    </div>
  </nav>

  <script>
    // Alternar visibilidade do menu mobile
    document.getElementById('menuToggle').addEventListener('click', function () {
      const menu = document.getElementById('mobileMenu');
      menu.classList.toggle('hidden');
    });

    // Fecha todos os dropdowns
    function closeAllDropdowns() {
      document.querySelectorAll('[id$="Drop"]').forEach(function (el) {
        el.classList.add('invisible', 'opacity-0');
        el.classList.remove('visible', 'opacity-100');
      });
    }

    // Alterna exibição do dropdown
    function toggleDropdown(id) {
      let el = document.getElementById(id);
      let isVisible = !el.classList.contains('invisible');
      closeAllDropdowns();
      if (!isVisible) {
        el.classList.remove('invisible', 'opacity-0');
        el.classList.add('visible', 'opacity-100');
      }
    }

    // Fecha dropdowns ao clicar fora
    document.addEventListener('click', function (event) {
      if (!event.target.closest('.dropdown-toggle-btn') && !event.target.closest('[id$="Drop"]')) {
        closeAllDropdowns();
      }
    });
  </script>

</body>
</html>
