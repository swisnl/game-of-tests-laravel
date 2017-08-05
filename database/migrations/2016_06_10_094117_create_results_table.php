<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateResultsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('results', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('remote');
			$table->string('filename')->index('results_file_index');
			$table->string('line');
			$table->string('author')->default('')->index();
			$table->string('author_normalized')->default('')->index('author_normalized');
			$table->string('author_slug')->default('');
			$table->string('email')->default('');
			$table->string('date');
			$table->string('commitHash');
			$table->string('parser');
			$table->timestamps();
			$table->string('file')->default('');
			$table->unique(['remote','filename','line'], 'results_remote_file_line_unique');
			$table->index(['email','filename'], 'results_email_file_index');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('results');
	}

}
