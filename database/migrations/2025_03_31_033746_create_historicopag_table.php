<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoricoPagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historicopag', function (Blueprint $table) {
            $table->id();  // Cria o campo id (auto incremento)
            $table->string('nome');  // Nome do cliente, se necessário
            $table->string('numero');  // Número do cliente
            $table->unsignedBigInteger('cliente_id');  // Relacionamento com a tabela de clientes
            $table->enum('status', ['pago', 'nao_pago'])->default('nao_pago');  // Status do pagamento
            $table->text('descricao')->nullable();  // Campo de descrição, opcional
            $table->string('reference_id')->nullable();  // Novo campo para o reference_id
            $table->string('instancia')->nullable();  // Novo campo para a instancia, opcional
            $table->decimal('valor', 15, 5);  // Campo para o valor do pagamento, com até 5 casas decimais
            $table->timestamps();  // Campos de timestamps (created_at e updated_at)

            // Definir a chave estrangeira (relacionamento com a tabela 'clientes')
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remover a chave estrangeira e a tabela 'historicopag'
        Schema::table('historicopag', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });

        // Dropar a tabela 'historicopag'
        Schema::dropIfExists('historicopag');
    }
}
