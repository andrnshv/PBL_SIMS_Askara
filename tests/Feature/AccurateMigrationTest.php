<?php

namespace Tests\Feature;

use App\Models\AccurateItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\AccurateImporter;
use App\Support\AccurateImportException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccurateMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "kode_barang,nama_barang,satuan,stok,harga_jual,kategori\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Namespace komponen ini berhuruf kecil (App\View\Components\header) sehingga di Linux yang
        // peka huruf besar/kecil <x-header.notification-dropdown> jatuh ke komponen anonim dan error
        // "Undefined variable $visible". Didaftarkan eksplisit agar layout bisa dirender di test.
        Blade::component('header.notification-dropdown', \App\View\Components\header\NotificationDropdown::class);
    }

    // ---------- helpers ----------

    private function user(string $role): User
    {
        // UserFactory memakai kolom email_verified_at yang tidak ada di skema users proyek ini.
        $u = new User();
        $u->forceFill([
            'name'      => ucfirst($role),
            'email'     => uniqid($role . '_') . '@test.local',
            'password'  => 'secret-pass',
            'role'      => $role,
            'is_active' => true,
        ])->save();

        return $u;
    }

    private function variant(array $override = []): ProductVariant
    {
        $owner = User::query()->first() ?? $this->user(User::ROLE_ADMIN);
        $product = Product::create(['name' => 'Keripik', 'status' => 'active', 'created_by' => $owner->id]);

        return ProductVariant::create($override + [
            'product_id' => $product->id, 'sku' => 'SKU-' . uniqid(), 'name' => 'Original',
            'unit' => 'pcs', 'selling_price' => 10000, 'current_stock' => 0, 'reorder_point' => 0, 'status' => 'active',
        ]);
    }

    private function csv(string $rows, string $name = 'accurate.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, self::HEADER . $rows);
    }

    private function importAs(User $user, string $rows, string $name = 'accurate.csv')
    {
        return $this->actingAs($user)->post(route('accurate.import'), ['file' => $this->csv($rows, $name)]);
    }

    private function item(string $status = 'unmapped', array $override = []): AccurateItem
    {
        $batchId = DB::table('accurate_import_batches')->insertGetId([
            'file_name' => 'x.csv', 'file_hash' => bin2hex(random_bytes(32)), 'uploaded_by' => User::query()->first()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return AccurateItem::create($override + [
            'batch_id' => $batchId, 'row_number' => 2, 'accurate_code' => 'ACC-' . uniqid(), 'accurate_name' => 'Barang',
            'accurate_unit' => 'pcs', 'accurate_stock' => 5, 'mapping_status' => $status,
        ]);
    }

    // ---------- parsing & validasi ----------

    public function test_parse_menerima_bom_titik_koma_dan_alias_header(): void
    {
        $content = "\xEF\xBB\xBFKode Barang;Nama Barang;Satuan;Kuantitas\nA-1;Keripik;pcs;10\nA-2;Tempe;box;1.250\n";

        $r = AccurateImporter::parse($content);

        $this->assertSame(2, $r['total']);
        $this->assertSame([], $r['errors']);
        $this->assertSame(10, $r['rows'][0]['accurate_stock']);
        $this->assertSame(1250, $r['rows'][1]['accurate_stock']);
    }

    public function test_parse_gagal_untuk_file_kosong_hanya_header_dan_kolom_wajib_hilang(): void
    {
        foreach (['', "  \n\n", self::HEADER] as $content) {
            try {
                AccurateImporter::parse($content);
                $this->fail('Seharusnya gagal untuk: ' . json_encode($content));
            } catch (AccurateImportException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }

        $this->expectException(AccurateImportException::class);
        $this->expectExceptionMessage('stok');
        AccurateImporter::parse("kode_barang,nama_barang\nA,B\n");
    }

    public function test_parse_melaporkan_data_kosong_dan_format_tidak_valid_per_baris(): void
    {
        $r = AccurateImporter::parse(self::HEADER . implode("\n", [
            'OK-1,Barang ok,pcs,5,1000,Cat',      // 2 valid
            ',Tanpa kode,pcs,5,,',                // 3 kode kosong
            'X-2,,pcs,5,,',                       // 4 nama kosong
            'X-3,Stok kosong,pcs,,,',             // 5 stok kosong
            'X-4,Stok huruf,pcs,abc,,',           // 6 bukan angka
            'X-5,Stok negatif,pcs,-3,,',          // 7 negatif
            'X-6,Stok pecahan,pcs,"10,5",,',      // 8 bukan bilangan bulat
            'X-7,Harga huruf,pcs,1,murah,',       // 9 harga tidak valid
            'X-8,Stok kebesaran,pcs,99999999999,,', // 10
        ]));

        $this->assertSame(9, $r['total']);
        $this->assertCount(1, $r['rows']);
        $this->assertSame('OK-1', $r['rows'][0]['accurate_code']);
        $this->assertSame('1000.00', $r['rows'][0]['accurate_price']);
        $this->assertSame([3, 4, 5, 6, 7, 8, 9, 10], array_column($r['errors'], 'row'));
        $this->assertStringContainsString('Kode barang kosong', $r['errors'][0]['message']);
        $this->assertStringContainsString('bilangan bulat', $r['errors'][5]['message']);
    }

    public function test_parse_menandai_kode_duplikat_dalam_file_tanpa_peka_huruf_besar_kecil(): void
    {
        $r = AccurateImporter::parse(self::HEADER . "AB-1,Satu,pcs,1,,\nab-1,Dua,pcs,2,,\nAB-2,Tiga,pcs,3,,\n");

        $this->assertCount(2, $r['rows']);
        $this->assertCount(1, $r['errors']);
        $this->assertSame(3, $r['errors'][0]['row']);
        $this->assertStringContainsString('duplikat dengan baris 2', $r['errors'][0]['message']);
    }

    // ---------- impor ----------

    public function test_impor_menyimpan_baris_valid_sebagai_belum_dipetakan_dan_melaporkan_yang_gagal(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $res = $this->importAs($admin, "A-1,Satu,pcs,5,,\nA-2,Dua,pcs,abc,,\n");

        $batch = DB::table('accurate_import_batches')->first();
        $res->assertRedirect(route('accurate.batches.show', $batch->id));

        $this->assertSame(2, (int) $batch->total_rows);
        $this->assertSame(1, (int) $batch->imported_rows);
        $this->assertSame(1, (int) $batch->failed_rows);
        $this->assertCount(1, json_decode($batch->error_report, true));

        $this->assertSame(1, AccurateItem::count());
        $this->assertSame('unmapped', AccurateItem::first()->mapping_status);
        $this->assertSame(0, AccurateItem::query()->verified()->count());
    }

    public function test_impor_menolak_kode_yang_sudah_ada_di_batch_lain_tetapi_boleh_jika_sebelumnya_ditolak(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $this->importAs($admin, "A-1,Satu,pcs,5,,\n", 'a.csv');

        $res = $this->importAs($admin, "a-1,Satu lagi,pcs,7,,\n", 'b.csv');
        $res->assertSessionHas('error');
        $this->assertSame(1, DB::table('accurate_import_batches')->count());

        AccurateItem::query()->update(['mapping_status' => 'rejected', 'rejected_reason' => 'salah']);
        $this->importAs($admin, "A-1,Satu benar,pcs,7,,\n", 'c.csv');
        $this->assertSame(2, DB::table('accurate_import_batches')->count());
    }

    public function test_file_identik_tidak_bisa_diimpor_dua_kali(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $this->importAs($admin, "A-1,Satu,pcs,5,,\n");

        $res = $this->importAs($admin, "A-1,Satu,pcs,5,,\n", 'salinan.csv');

        $res->assertSessionHas('error');
        $this->assertSame(1, DB::table('accurate_import_batches')->count());
        $this->assertSame(1, AccurateItem::count());
    }

    public function test_impor_gagal_total_tidak_menyimpan_apa_pun(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        // Semua baris tidak valid
        $this->importAs($admin, ",,pcs,x,,\nB-1,,pcs,1,,\n")->assertSessionHas('import_errors');
        // Header salah
        $bad = UploadedFile::fake()->createWithContent('x.csv', "foo,bar\n1,2\n");
        $this->actingAs($admin)->post(route('accurate.import'), ['file' => $bad])->assertSessionHas('error');
        // Bukan CSV
        $pdf = UploadedFile::fake()->create('x.pdf', 10, 'application/pdf');
        $this->actingAs($admin)->post(route('accurate.import'), ['file' => $pdf])->assertSessionHasErrors('file');

        $this->assertSame(0, DB::table('accurate_import_batches')->count());
        $this->assertSame(0, AccurateItem::count());
    }

    // ---------- hak akses ----------

    public function test_hak_akses_modul(): void
    {
        $this->get(route('accurate.index'))->assertRedirect();

        $this->actingAs($this->user(User::ROLE_PENJUALAN))->get(route('accurate.index'))->assertForbidden();
        $this->actingAs($this->user(User::ROLE_PEMBELIAN))->get(route('accurate.index'))->assertForbidden();
        $this->actingAs($this->user(User::ROLE_GUDANG))->get(route('accurate.index'))->assertOk();
        $this->actingAs($this->user(User::ROLE_ADMIN))->get(route('accurate.index'))->assertOk();
    }

    public function test_halaman_batch_dan_template_dapat_dibuka(): void
    {
        $gudang = $this->user(User::ROLE_GUDANG);
        $this->importAs($gudang, "A-1,Satu,pcs,5,,\nA-2,,pcs,5,,\n");
        $batchId = DB::table('accurate_import_batches')->value('id');

        $this->actingAs($gudang)->get(route('accurate.batches.show', $batchId))
            ->assertOk()->assertSee('A-1')->assertSee('Belum dipetakan');
        $this->actingAs($gudang)->get(route('accurate.batches.show', $batchId) . '?status=verified&q=zzz')->assertOk();
        $this->actingAs($gudang)->get(route('accurate.template'))->assertOk()->assertHeader('Content-Disposition');
    }

    public function test_halaman_batch_menyarankan_varian_dengan_sku_sama_dan_tab_buat_varian_hanya_untuk_admin(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $gudang = $this->user(User::ROLE_GUDANG);
        $variant = $this->variant(['sku' => 'A-1']);
        $this->importAs($admin, "A-1,Satu,pcs,5,,\n");
        $batchId = DB::table('accurate_import_batches')->value('id');

        $this->actingAs($gudang)->get(route('accurate.batches.show', $batchId))
            ->assertOk()
            ->assertSee('\u0022a-1\u0022:\u0022' . $variant->id . '\u0022', false) // keluaran @js
            ->assertDontSee('Buat varian baru');

        $this->actingAs($admin)->get(route('accurate.batches.show', $batchId))
            ->assertOk()
            ->assertSee('Buat varian baru');
    }

    // ---------- pemetaan & verifikasi ----------

    public function test_alur_petakan_lalu_verifikasi(): void
    {
        $gudang = $this->user(User::ROLE_GUDANG);
        $variant = $this->variant();
        $item = $this->item();

        $this->actingAs($gudang)->post(route('accurate.items.map', $item->id), ['variant_id' => $variant->id])
            ->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame('mapped', $item->mapping_status);
        $this->assertSame($variant->id, $item->mapped_variant_id);
        $this->assertSame($gudang->id, $item->mapped_by);
        $this->assertSame(0, AccurateItem::query()->verified()->count(), 'Belum diverifikasi = belum final');

        $this->actingAs($gudang)->post(route('accurate.items.verify', $item->id))->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame('verified', $item->mapping_status);
        $this->assertSame($gudang->id, $item->verified_by);
        $this->assertSame(1, AccurateItem::query()->verified()->count());
        $this->assertSame(0, (int) $variant->fresh()->current_stock, 'Modul tidak mengubah stok');
    }

    public function test_item_belum_dipetakan_tidak_bisa_diverifikasi_dan_item_final_terkunci(): void
    {
        $gudang = $this->user(User::ROLE_GUDANG);
        $variant = $this->variant();

        $unmapped = $this->item();
        $this->actingAs($gudang)->post(route('accurate.items.verify', $unmapped->id))->assertSessionHasErrors('status');
        $this->assertSame('unmapped', $unmapped->fresh()->mapping_status);

        $verified = $this->item('verified', ['mapped_variant_id' => $variant->id]);
        $this->actingAs($gudang)->post(route('accurate.items.map', $verified->id), ['variant_id' => $variant->id])
            ->assertSessionHasErrors('status');
        $this->actingAs($gudang)->post(route('accurate.items.unmap', $verified->id))->assertSessionHasErrors('status');
        $this->actingAs($gudang)->post(route('accurate.items.reject', $verified->id), ['reason' => 'x'])
            ->assertSessionHasErrors('status');
        $this->assertSame('verified', $verified->fresh()->mapping_status);
    }

    public function test_pemetaan_ke_varian_tidak_aktif_atau_sudah_digabung_ditolak(): void
    {
        $gudang = $this->user(User::ROLE_GUDANG);
        $inactive = $this->variant(['status' => 'inactive']);
        $target = $this->variant();
        $merged = $this->variant(['status' => 'merged', 'merged_into_variant_id' => $target->id]);
        $item = $this->item();

        foreach ([$inactive->id, $merged->id, 999999] as $id) {
            $this->actingAs($gudang)->post(route('accurate.items.map', $item->id), ['variant_id' => $id])
                ->assertSessionHasErrors('variant_id');
        }
        $this->assertSame('unmapped', $item->fresh()->mapping_status);
    }

    public function test_verifikasi_gagal_jika_varian_menjadi_tidak_aktif_setelah_dipetakan(): void
    {
        $gudang = $this->user(User::ROLE_GUDANG);
        $variant = $this->variant();
        $item = $this->item('mapped', ['mapped_variant_id' => $variant->id]);

        $variant->update(['status' => 'inactive']);

        $this->actingAs($gudang)->post(route('accurate.items.verify', $item->id))->assertSessionHasErrors('status');
        $this->assertSame('mapped', $item->fresh()->mapping_status);
    }

    public function test_tolak_wajib_alasan_dan_batalkan_pemetaan(): void
    {
        $gudang = $this->user(User::ROLE_GUDANG);
        $variant = $this->variant();
        $item = $this->item('mapped', ['mapped_variant_id' => $variant->id]);

        $this->actingAs($gudang)->post(route('accurate.items.reject', $item->id), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($gudang)->post(route('accurate.items.unmap', $item->id));
        $item->refresh();
        $this->assertSame('unmapped', $item->mapping_status);
        $this->assertNull($item->mapped_variant_id);

        $this->actingAs($gudang)->post(route('accurate.items.reject', $item->id), ['reason' => 'tidak dijual lagi']);
        $this->assertSame('rejected', $item->fresh()->mapping_status);
    }

    public function test_verifikasi_massal_hanya_memproses_item_menunggu_verifikasi_di_batch_itu(): void
    {
        $gudang = $this->user(User::ROLE_GUDANG);
        $variant = $this->variant();

        $a = $this->item('mapped', ['mapped_variant_id' => $variant->id]);
        $b = $this->item('unmapped', ['batch_id' => $a->batch_id]);
        $other = $this->item('mapped', ['mapped_variant_id' => $variant->id]); // batch lain

        $this->actingAs($gudang)->post(route('accurate.batches.verify', $a->batch_id), ['ids' => [$a->id, $b->id, $other->id]]);

        $this->assertSame('verified', $a->fresh()->mapping_status);
        $this->assertSame('unmapped', $b->fresh()->mapping_status);
        $this->assertSame('mapped', $other->fresh()->mapping_status);
    }

    public function test_buat_varian_baru_dan_buka_kembali_hanya_admin(): void
    {
        $gudang = $this->user(User::ROLE_GUDANG);
        $admin = $this->user(User::ROLE_ADMIN);
        $item = $this->item('unmapped', ['accurate_code' => 'NEW-1', 'accurate_category' => 'Keripik']);
        $payload = ['product_name' => 'Keripik Baru', 'variant_name' => 'Pedas', 'sku' => 'NEW-1', 'unit' => 'pcs', 'selling_price' => 12000];

        $this->actingAs($gudang)->post(route('accurate.items.create-variant', $item->id), $payload)->assertForbidden();
        $this->assertSame(0, Product::where('name', 'Keripik Baru')->count());

        $this->actingAs($admin)->post(route('accurate.items.create-variant', $item->id), $payload)->assertSessionHasNoErrors();
        $variant = ProductVariant::where('sku', 'NEW-1')->firstOrFail();
        $this->assertSame(0, (int) $variant->current_stock);
        $this->assertSame('Keripik', Product::find($variant->product_id)->category);
        $this->assertSame('mapped', $item->fresh()->mapping_status);

        // SKU yang sama tidak bisa dibuat dua kali
        $dup = $this->item();
        $this->actingAs($admin)->post(route('accurate.items.create-variant', $dup->id), $payload)->assertSessionHasErrors('sku');

        // Buka kembali: gudang dilarang, admin boleh
        $this->actingAs($admin)->post(route('accurate.items.verify', $item->id));
        $this->actingAs($gudang)->post(route('accurate.items.reopen', $item->id))->assertForbidden();
        $this->actingAs($admin)->post(route('accurate.items.reopen', $item->id));
        $this->assertSame('mapped', $item->fresh()->mapping_status);
    }

    // ---------- konsistensi dengan modul produk ----------

    public function test_varian_yang_sudah_dipetakan_tidak_boleh_dihapus(): void
    {
        $this->user(User::ROLE_ADMIN);
        $variant = $this->variant();
        $this->assertFalse($variant->hasTransactionHistory());

        $this->item('mapped', ['mapped_variant_id' => $variant->id]);

        $this->assertTrue($variant->hasTransactionHistory());
        $this->assertTrue($variant->product->hasTransactionHistory());
    }

    public function test_penggabungan_varian_mengalihkan_pemetaan_accurate_ke_varian_tujuan(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $target = $this->variant(['sku' => 'T-1']);
        $source = $this->variant(['sku' => 'S-1', 'product_id' => $target->product_id]);
        $item = $this->item('verified', ['mapped_variant_id' => $source->id]);

        $this->actingAs($admin)->post(route('products.variants.merge.store', $target->product_id), [
            'target_variant_id' => $target->id, 'source_ids' => [$source->id], 'reason' => 'duplikat',
        ])->assertSessionHasNoErrors();

        $this->assertSame('merged', $source->fresh()->status);
        $this->assertSame($target->id, $item->fresh()->mapped_variant_id);
        $this->assertSame('verified', $item->fresh()->mapping_status);
    }
}