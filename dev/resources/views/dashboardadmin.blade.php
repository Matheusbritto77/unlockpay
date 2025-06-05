<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Administrador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Incluindo o Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Incluindo ícones do Font Awesome para possíveis usos futuros -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos personalizados para transições suaves e ajustes finos */
        .transition-all {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans min-h-screen flex flex-col">
    <!-- Barra de navegação fixada no topo -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-md">
        @include('admin.bar_admin')
    </header>
    <br>
    <br>
    <br>

    <!-- Conteúdo principal com margem superior para não ser coberto pela barra fixa -->
    <main class="flex-1 mt-16 p-4 md:p-6">
        <!-- Título -->
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Painel do Administrador</h1>

        <!-- Seção de informações do usuário logado -->
        <div class="bg-white p-4 md:p-6 rounded-lg shadow-md mb-6 transition-all hover:shadow-lg">
            <h2 class="text-lg md:text-xl font-semibold text-gray-700 mb-2">Informações do Usuário</h2>
            <p class="text-gray-600 text-sm md:text-base"><strong>Usuário logado:</strong> {{ $user->name }}</p>
            <p class="text-gray-600 text-sm md:text-base"><strong>Email:</strong> {{ $user->email }}</p>
        </div>

        <!-- Seção de estatísticas -->
        <div class="bg-white p-4 md:p-6 rounded-lg shadow-md mb-6 transition-all hover:shadow-lg">
            <h2 class="text-lg md:text-xl font-semibold text-gray-700 mb-2">Estatísticas de Usuários</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50 p-3 rounded-lg">
                    <p class="text-gray-600 text-sm md:text-base"><strong>Total de usuários cadastrados:</strong> {{ $totalUsuarios }}</p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg">
                    <p class="text-gray-600 text-sm md:text-base"><strong>Total de usuários com planos ativos:</strong> {{ $totalUsuariosPlanos25 }}</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Rodapé (opcional, pode ser ajustado conforme necessário) -->
    <footer class="bg-white p-4 text-center text-gray-600 text-sm shadow-inner">
        &copy; {{ date('Y') }} - Painel do Administrador
    </footer>
</body>
</html>