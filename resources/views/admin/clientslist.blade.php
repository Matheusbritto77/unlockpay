@include('admin.bar_admin') 

<div class="container py-4">
    <h2 class="mb-4">Lista de Usuários</h2>

    {{-- Formulário de busca --}}
    <form method="GET" action="{{ route('admin.listarUsuarios') }}" class="mb-3">
        <div class="input-group" style="max-width:400px">
            <input type="text" name="busca" value="{{ old('busca', $busca ?? '') }}"
                   class="form-control" placeholder="Buscar por nome...">
            <button class="btn btn-primary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Crédito</th>
                    <th>Comissão (%)</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            @forelse($usuarios as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>R$ {{ number_format($user->credito ?? 0, 2, ',', '.') }}</td>
                    <td id="porcent_resseller_{{$user->id}}">
                        {{ $user->porcent_resseller ?? 0 }}
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-warning"
                            onclick="abrirModalComissao({{ $user->id }}, '{{ $user->name }}', {{ $user->porcent_resseller ?? 0 }})">
                            Editar Comissão
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Nenhum usuário encontrado.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de edição de comissão -->
<div class="modal fade" id="modalComissao" tabindex="-1" aria-labelledby="modalComissaoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formAlterarComissao" method="POST" onsubmit="return salvarComissao();">
        @csrf
        <input type="hidden" name="id" id="comissao_user_id">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalComissaoLabel">Editar Comissão do Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label"><b>Usuário:</b></label>
                    <span id="comissao_user_name"></span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Comissão (%)</label>
                    <input type="number" class="form-control" min="0" max="100"
                        name="porcent_resseller" id="comissao_percent" required>
                </div>
                <div id="comissao_alert"></div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Salvar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Pega o CSRF token do meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function abrirModalComissao(id, nome, percent) {
    document.getElementById('comissao_user_id').value = id;
    document.getElementById('comissao_user_name').textContent = nome;
    document.getElementById('comissao_percent').value = percent;
    document.getElementById('comissao_alert').innerHTML = '';
    var modal = new bootstrap.Modal(document.getElementById('modalComissao'));
    modal.show();
}

function salvarComissao() {
    const form = document.getElementById('formAlterarComissao');
    const id = form.id.value;
    const porcent = form.porcent_resseller.value;
    fetch('/admin/alterar-porcentagem-reseller', {
        method: 'POST',
        headers: {
           'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ id: id, porcent_resseller: porcent })
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.message) {
            document.getElementById('porcent_resseller_'+id).textContent = porcent;
            document.getElementById('comissao_alert').innerHTML =
                `<div class="alert alert-success mb-2">${data.message}</div>`;
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalComissao')).hide();
            }, 700);
        } else if (data.error) {
            document.getElementById('comissao_alert').innerHTML =
                `<div class="alert alert-danger mb-2">${data.error}</div>`;
        }
    }).catch(() => {
        document.getElementById('comissao_alert').innerHTML =
            `<div class="alert alert-danger mb-2">Erro ao tentar atualizar comissão.</div>`;
    });
    return false; // evita submit normal
}
</script>
</body>
</html>