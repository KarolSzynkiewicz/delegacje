<?php

use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Support\PulseMigration;

return new class extends PulseMigration
{
    /**
     * MySQL 9.6 usunął MD5() z serwera. Wyliczane key_hash Pulse wtedy nie daje się otworzyć
     * i start serwera pada. SHA2() zostaje wbudowane, a Pulse i tak nie zapisuje tego hasha sam.
     */
    public function up(): void
    {
        $this->rewrite('unhex(substr(sha2(`key`, 256), 1, 32))', 'md5');
    }

    public function down(): void
    {
        $this->rewrite('unhex(md5(`key`))', 'sha2');
    }

    private function rewrite(string $to, string $needle): void
    {
        if (! in_array($this->driver(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $connection = DB::connection($this->getConnection());
        $schema = $connection->getDatabaseName();

        foreach (['pulse_values', 'pulse_entries', 'pulse_aggregates'] as $table) {
            $column = $connection->selectOne(
                'SELECT GENERATION_EXPRESSION AS expr FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$schema, $table, 'key_hash']
            );

            $expression = strtolower((string) ($column->expr ?? ''));
            if ($expression === '' || ! str_contains($expression, $needle)) {
                continue;
            }

            $connection->statement(
                "ALTER TABLE `{$table}` MODIFY `key_hash` binary(16) GENERATED ALWAYS AS ({$to}) VIRTUAL"
            );
        }
    }
};
