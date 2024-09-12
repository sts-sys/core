<?php
<<<<<<< HEAD
namespace sts\core\config;

interface config_interface
{
    public function get(string $config, string $key = null): array|null;
=======
namespace sts\config;

interface config_interface
{
    public function get(string $config, string $key = null): string|null;
>>>>>>> 09d69e644898e89c53ad41785ecbfdc7aa7daf7f
    public function set(string $config, string $key = null, ?string &$value = ''): void;
    public function delete(string $config, string $key = null): void;
    public function all(): array;
}