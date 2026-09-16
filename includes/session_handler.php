<?php
declare(strict_types=1);

/**
 * Stores PHP sessions in the application's database so serverless instances
 * can share authentication state.
 */
final class DatabaseSessionHandler implements SessionHandlerInterface
{
    private static bool $schemaReady = false;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function open(string $path, string $name): bool
    {
        if (!filter_var(getenv('COFFEE_SESSION_AUTO_MIGRATE') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return $this->ensureSchema();
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $statement = $this->pdo->prepare(
            'SELECT payload FROM app_sessions WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $payload = $statement->fetchColumn();

        return $payload === false ? '' : (string) $payload;
    }

    public function write(string $id, string $data): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO app_sessions (id, payload, last_activity)
             VALUES (:id, :payload, :last_activity)
             ON DUPLICATE KEY UPDATE
                 payload = :updated_payload,
                 last_activity = :updated_activity'
        );

        return $statement->execute([
            'id' => $id,
            'payload' => $data,
            'last_activity' => time(),
            'updated_payload' => $data,
            'updated_activity' => time(),
        ]);
    }

    public function destroy(string $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM app_sessions WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $threshold = time() - max(1, $max_lifetime);
        $statement = $this->pdo->prepare(
            'DELETE FROM app_sessions WHERE last_activity < :threshold'
        );
        $statement->execute(['threshold' => $threshold]);

        return $statement->rowCount();
    }

    private function ensureSchema(): bool
    {
        if (self::$schemaReady) {
            return true;
        }

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_sessions (
                id VARCHAR(128) NOT NULL,
                payload MEDIUMBLOB NOT NULL,
                last_activity INT UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_app_sessions_last_activity (last_activity)
            )'
        );
        self::$schemaReady = true;

        return true;
    }
}
