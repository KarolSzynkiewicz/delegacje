<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_contacts')) {
            Schema::create('client_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $table->string('name');
                $table->string('role')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['client_id', 'is_primary']);
            });
        }

        if (! Schema::hasTable('client_contact_attempts')) {
            Schema::create('client_contact_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->foreignId('client_contact_id')->nullable()->constrained('client_contacts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('channel', 32);
                $table->string('purpose', 32)->default('general');
                $table->string('outcome', 32);
                $table->timestamp('contacted_at');
                $table->timestamp('next_action_at')->nullable();
                $table->text('summary')->nullable();
                $table->timestamps();

                $table->index(['client_id', 'contacted_at']);
                $table->index(['project_id', 'contacted_at']);
            });
        }

        if (! Schema::hasTable('clients') || ! Schema::hasTable('client_contacts')) {
            return;
        }

        $clients = DB::table('clients')
            ->select('id', 'contact_person', 'email', 'phone')
            ->orderBy('id')
            ->get();

        foreach ($clients as $client) {
            $name = trim((string) ($client->contact_person ?? ''));
            $email = trim((string) ($client->email ?? ''));
            $phone = trim((string) ($client->phone ?? ''));

            if ($name === '' && $email === '' && $phone === '') {
                continue;
            }

            $exists = DB::table('client_contacts')
                ->where('client_id', $client->id)
                ->where('is_primary', true)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('client_contacts')->insert([
                'client_id' => $client->id,
                'name' => $name !== '' ? $name : 'Kontakt główny',
                'role' => null,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'is_primary' => true,
                'is_active' => true,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_contact_attempts');
        Schema::dropIfExists('client_contacts');
    }
};
