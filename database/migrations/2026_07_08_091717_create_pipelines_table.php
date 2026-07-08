<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('salesperson_id')->constrained('users');
            $table->foreignId('department_id')->nullable()->constrained();
            $table->enum('stage', [
                'new', 'qualify', 'proposal_submitted', 'shortlisted',
                'verbal', 'contract', 'loss', 'renewal', 'decline',
            ])->default('new');

            // Qualify stage
            $table->string('project_name')->nullable();
            $table->unsignedTinyInteger('chance_percent')->nullable();
            $table->date('expected_start_date')->nullable();
            $table->string('scope_of_service')->nullable();
            $table->string('customer_product')->nullable();
            $table->timestamp('date_funnel')->nullable();

            // Proposal Submitted / Shortlisted / Verbal
            $table->decimal('value_per_annum', 15, 2)->nullable();
            $table->enum('contract_period', ['long_term', 'adhoc'])->nullable();
            $table->unsignedTinyInteger('number_of_months')->nullable();
            $table->string('origin_country')->nullable();
            $table->string('port_of_loading')->nullable();
            $table->string('destination_country')->nullable();
            $table->string('port_of_destination')->nullable();
            $table->decimal('operating_profit_margin', 5, 2)->nullable();
            $table->text('remarks_proposal')->nullable();

            // Contract / Renewal / Decline
            $table->date('date_secured')->nullable();
            $table->date('date_go_live')->nullable();
            $table->decimal('forecast_revenue', 15, 2)->nullable();
            $table->text('remarks_contract')->nullable();
            $table->boolean('is_locked')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipelines');
    }
};
