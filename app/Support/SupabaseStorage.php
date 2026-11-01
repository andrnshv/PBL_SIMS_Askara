<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorage
{
    private static function base(): string
    {
        return rtrim(config('services.supabase.url'), '/') . '/storage/v1/object';
    }

    private static function bucket(): string
    {
        return config('services.supabase.bucket');
    }

    private static function headers(): array
    {
        $key = config('services.supabase.key');

        $headers = ['apikey' => $key];

        // Key lama (service_role) berupa JWT dan perlu dikirim juga sebagai Bearer.
        if (str_starts_with((string) $key, 'eyJ')) {
            $headers['Authorization'] = 'Bearer ' . $key;
        }

        return $headers;
    }

    /** Unggah file, kembalikan path (mis. variants/abc.jpg). Melempar exception jika gagal. */
    public static function upload(UploadedFile $file, string $folder = 'variants'): string
    {
        $path = $folder . '/' . Str::uuid() . '.' . $file->extension();

        Http::withHeaders(self::headers())
            ->withBody($file->get(), $file->getMimeType())
            ->post(self::base() . '/' . self::bucket() . '/' . $path)
            ->throw();

        return $path;
    }

    public static function delete(string $path): void
    {
        Http::withHeaders(self::headers())
            ->delete(self::base() . '/' . self::bucket() . '/' . $path)
            ->throw();
    }

    public static function url(?string $path): ?string
    {
        return $path
            ? self::base() . '/public/' . self::bucket() . '/' . $path
            : null;
    }
}