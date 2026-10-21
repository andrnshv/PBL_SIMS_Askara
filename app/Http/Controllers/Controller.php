<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected function actor(): object
    {
        $user = Auth::user();

        abort_if(! $user, 401, 'Silakan login terlebih dahulu.');

        return (object) [
            'id'   => $user->id,
            'name' => $user->name,
        ];
    }
}