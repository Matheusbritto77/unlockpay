<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();  // Cria o campo id (auto incremento)
            $table->string('nome');  // Campo para o nome do cliente
            $table->string('numero');  // Campo para o número do cliente
            $table->unsignedBigInteger('user_id');  // Campo para associar o cliente ao user_id
            $table->timestamps();  // Campos de timestamps (created_at e updated_at)

            // Definir a chave estrangeira (relacionamento com a tabela 'users')
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Remover a chave estrangeira e a coluna user_id
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // Dropar a tabela 'clientes' 
        Schema::dropIfExists('clientes');
    }
}
