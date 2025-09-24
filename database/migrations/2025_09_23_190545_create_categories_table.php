<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Добавляет parent_id, _lft, _rgt (индексы тоже поставит)
            NestedSet::columns($table);

            $table->string('name');
            $table->string('slug');               // слаг в рамках одного родителя
            $table->string('img')->nullable();    // путь к картинке
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0); // на случай ручной сортировки среди братьев
            $table->json('meta_json')->nullable();

            $table->timestamps();

            // уникальность в рамках родителя (parent_id NULL разрешает одинаковые slug в разных корнях)
            $table->unique(['parent_id', 'slug']);
            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
