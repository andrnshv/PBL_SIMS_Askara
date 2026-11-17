<?php

namespace App\Support;

use RuntimeException;

/**
 * Impor gagal total (file tidak bisa dibaca, header salah, tidak ada baris valid, dsb.).
 * Jika gagal karena baris-baris tidak valid, rinciannya ada di $errors.
 */
class AccurateImportException extends RuntimeException
{
    /** @var array<int, array{row:int, code:string, message:string}> */
    public array $errors = [];

    public static function withErrors(string $message, array $errors): self
    {
        $e = new self($message);
        $e->errors = $errors;

        return $e;
    }
}