<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstanciasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instancias', function (Blueprint $table) {
            $table->id();
            $table->string('nome');    // Campo 'nome'
            $table->string('caminho'); // Campo 'caminho'
            $table->string('status')->default('ativo'); // Campo 'status' com valor padrão 'ativo'
            $table->foreignId('user_id')->constrained(); // Referência ao usuário (relacionamento)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instancias');
    }
}
