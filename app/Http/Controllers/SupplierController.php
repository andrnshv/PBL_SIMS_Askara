<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,active,inactive'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $search = trim($validated['q'] ?? '');
        $status = $validated['status'] ?? 'all';
        $perPage = (int) ($validated['per_page'] ?? 10);

        // Query daftar supplier.
        $query = DB::table('suppliers')
            ->select([
                'id',
                'name',
                'contact_person',
                'phone',
                'email',
                'address',
                'is_active',
                'created_at',
            ]);

        // Pencarian berdasarkan nama dan informasi kontak.
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $suppliers = $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        // Ringkasan seluruh supplier, tidak terpengaruh filter.
        $stats = [
            'total' => DB::table('suppliers')->count(),
            'active' => DB::table('suppliers')
                ->where('is_active', true)
                ->count(),
            'inactive' => DB::table('suppliers')
                ->where('is_active', false)
                ->count(),
        ];

        return view('pages.suppliers.index', [
            'title' => 'Manajemen Supplier',
            'suppliers' => $suppliers,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {
        return view('pages.suppliers.form', [
            'title' => 'Tambah Supplier',
            'supplier' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama supplier wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        DB::table('suppliers')->insert([
            'name' => $validated['name'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $supplier = DB::table('suppliers')
            ->where('id', $id)
            ->first();

        abort_if(! $supplier, 404);

        return view('pages.suppliers.form', [
            'title' => 'Ubah Supplier',
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $supplier = DB::table('suppliers')
            ->where('id', $id)
            ->first();

        abort_if(! $supplier, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama supplier wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        DB::table('suppliers')
            ->where('id', $id)
            ->update([
                'name' => $validated['name'],
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Data supplier berhasil diperbarui.');
    }

    // Menonaktifkan supplier tanpa menghapus riwayatnya.
    public function destroy(int $id)
    {
        $supplier = DB::table('suppliers')
            ->where('id', $id)
            ->first();

        abort_if(! $supplier, 404);

        if (! $supplier->is_active) {
            return redirect()
                ->route('suppliers.index')
                ->with('error', 'Supplier sudah berstatus nonaktif.');
        }

        DB::table('suppliers')
            ->where('id', $id)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier berhasil dinonaktifkan.');
    }
}
