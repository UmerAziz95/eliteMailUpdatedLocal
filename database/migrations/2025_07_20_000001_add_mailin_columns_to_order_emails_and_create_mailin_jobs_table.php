<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_emails', function (Blueprint $table) {
            if (!Schema::hasColumn('order_emails', 'mailin_domain_id')) {
                $table->string('mailin_domain_id')->nullable()->after('profile_picture');
            }

            if (!Schema::hasColumn('order_emails', 'mailin_mailbox_id')) {
                $table->string('mailin_mailbox_id')->nullable()->after('mailin_domain_id');
            }

            if (!Schema::hasColumn('order_emails', 'mailin_status')) {
                $table->string('mailin_status')->nullable()->after('mailin_mailbox_id');
            }

            if (!Schema::hasColumn('order_emails', 'provisioned_at')) {
                $table->timestamp('provisioned_at')->nullable()->after('mailin_status');
            }
        });

        // Clean up duplicate order/email combinations before adding unique index
        $duplicates = DB::table('order_emails')
            ->select('order_id', 'email', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('order_id', 'email')
            ->having('aggregate', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $idsToKeep = DB::table('order_emails')
                ->where('order_id', $duplicate->order_id)
                ->where('email', $duplicate->email)
                ->orderBy('id')
                ->limit(1)
                ->pluck('id');

            DB::table('order_emails')
                ->where('order_id', $duplicate->order_id)
                ->where('email', $duplicate->email)
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }

        Schema::table('order_emails', function (Blueprint $table) {
            $table->unique(['order_id', 'email']);
        });

        Schema::create('mailin_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('job_id');
            $table->string('status')->nullable();
            $table->json('request_payload_json')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'type']);
            $table->unique(['order_id', 'type', 'job_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_emails', function (Blueprint $table) {
            $table->dropUnique(['order_id', 'email']);

            $table->dropColumn([
                'mailin_domain_id',
                'mailin_mailbox_id',
                'mailin_status',
                'provisioned_at',
            ]);
        });

        Schema::dropIfExists('mailin_jobs');
    }
};
