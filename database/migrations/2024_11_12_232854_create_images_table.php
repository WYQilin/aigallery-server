<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->string('file_path')->comment('图片路径');
            $table->text('prompt')->nullable()->comment('提示词');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedInteger('size')->nullable()->comment('文件体积');
            $table->unsignedTinyInteger('score')->default(0)->comment('评分');
            $table->dateTime('modify_time')->comment('图片生成时间');
            $table->date('modify_date')->comment('图片生成日期');
            $table->string('prompt_hash')->comment('图片生成参数');
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
        Schema::dropIfExists('images');
    }
}
