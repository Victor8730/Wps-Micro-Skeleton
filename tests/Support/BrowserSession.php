<?php

declare(strict_types=1);

namespace Tests\Support;

use WpsMicro\Core\Session;

final class BrowserSession extends Session
{
    public array $values = [];
    public array $old = [];
    public array $new = [];
    public int $regenerations = 0;

    public function nextRequest(): void
    {
        $this->old = $this->new;
        $this->new = [];
    }

    public function start(): void
    {
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($this->values[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->new[$key] = $value;
    }

    public function peekFlash(string $key, mixed $default = null): mixed
    {
        return $this->old[$key] ?? $default;
    }

    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->peekFlash($key, $default);
        unset($this->old[$key]);
        return $value;
    }

    public function regenerate(bool $deleteOldSession = true): void
    {
        ++$this->regenerations;
    }

    public function invalidate(): void
    {
        $this->values = $this->old = $this->new = [];
        $this->regenerate();
    }
}
