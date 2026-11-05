<?php

namespace App\Support;

use RuntimeException;

class PasswordRecoveryStore
{
    public function all(): array
    {
        return $this->withLock(LOCK_SH, fn () => $this->read());
    }

    public function mutate(callable $callback): mixed
    {
        return $this->withLock(LOCK_EX, function () use ($callback) {
            $requests = $this->read();
            $result = $callback($requests);
            $encoded = json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            if (file_put_contents($this->dataPath(), $encoded, LOCK_EX) === false) {
                throw new RuntimeException('Gagal menyimpan permintaan pemulihan akun.');
            }

            return $result;
        });
    }

    private function withLock(int $mode, callable $callback): mixed
    {
        $directory = storage_path('app/data');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder penyimpanan pemulihan akun tidak dapat dibuat.');
        }

        $lock = fopen($directory.'/.password-recovery.lock', 'c+');
        if ($lock === false) {
            throw new RuntimeException('Penyimpanan pemulihan akun tidak dapat dikunci.');
        }

        if (! flock($lock, $mode)) {
            fclose($lock);
            throw new RuntimeException('Penyimpanan pemulihan akun tidak dapat dikunci.');
        }

        try {
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function read(): array
    {
        if (! is_file($this->dataPath())) {
            return [];
        }

        $contents = file_get_contents($this->dataPath());
        if ($contents === false) {
            throw new RuntimeException('Permintaan pemulihan akun tidak dapat dibaca.');
        }

        $requests = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($requests)) {
            throw new RuntimeException('Format penyimpanan pemulihan akun tidak valid.');
        }

        return $requests;
    }

    private function dataPath(): string
    {
        return storage_path('app/data/password-recovery.json');
    }
}
