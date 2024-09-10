<?php
namespace sts\config;

interface config_interface
{
    public function get(string $config, string $key = null): string|null;
    public function set(string $config, string $key = null, ?string &$value = ''): void;
    public function delete(string $config, string $key = null): void;
    public function all(): array;
}