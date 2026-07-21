
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_done', function (Blueprint $table) {
            $table->date('technical_review_date')->nullable()->after('total_amount');
            $table->text('technical_review_notes')->nullable()->after('technical_review_date');
            $table->boolean('technical_review_approved')->default(false)->after('technical_review_notes');
            $table->date('book_of_technical_reviews_date')->nullable()->after('technical_review_approved');
            $table->string('book_of_technical_reviews_reference')->nullable()->after('book_of_technical_reviews_date');
            $table->string('technical_reviewer')->nullable()->after('book_of_technical_reviews_reference');
        });
    }

    public function down(): void
    {
        Schema::table('work_done', function (Blueprint $table) {
            $table->dropColumn([
                'technical_review_date',
                'technical_review_notes',
                'technical_review_approved',
                'book_of_technical_reviews_date',
                'book_of_technical_reviews_reference',
                'technical_reviewer',
            ]);
        });
    }
};
