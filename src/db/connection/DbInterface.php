<?php
namespace sts\db;

interface DbInterface {
    public function connect(): void;
    public function disconnect(): void;
    public function query(string $sql): mixed;
    public function getLastInsertId(): int;
    public function getErrorInfo(): array;
    public function getDriverName(): string;
    public function getServerVersion(): string;
    public function getAttribute(int $attribute): mixed;
    public function setAttribute(int $attribute, mixed $value): void;
    public function getConnection(): mixed;
    public function setConnection(mixed $connection): void;
}
