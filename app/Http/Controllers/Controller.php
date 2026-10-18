<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

abstract class Controller
{
    /**
     * SEMENTARA (login belum dibuat): jika belum ada user yang login,
     * pakai user admin pertama dari tabel users sebagai pelaku transaksi.
     * Hapus fallback ini setelah login selesai.
     */
    protected function actor(): object
    {
        static $actor = null;

        if ($actor) {
            return $actor;
        }

        if (Auth::check()) {
            return $actor = (object) [
                'id' => Auth::id(),
                'name' => Auth::user()->name,
            ];
        }

        $admin = DB::table('users')
            ->where('role', 'admin')
            ->where('is_active', true)
            ->orderBy('id')
            ->first(['id', 'name']);

        abort_if(
            ! $admin,
            500,
            'Belum ada user admin. Buat lewat tinker (lihat instruksi) atau login terlebih dahulu.'
        );

        return $actor = $admin;
    }
}
