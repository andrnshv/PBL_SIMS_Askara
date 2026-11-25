<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseOrderCloseTest extends TestCase
{
    use RefreshDatabase;

    private function buyer(): User
    {
        $user = new User();

        $user->forceFill([
            'name' => 'Staff Pembelian',
            'email' => 'buyer_' . bin2hex(random_bytes(5)) . '@test.local',
            'password' => 'secret-pass',
            'role' => 'staff_pembelian',
            'is_active' => true,
        ])->save();

        return $user;
    }

    /**
     * Membuat PO simulasi beserta supplier, produk, varian,
     * dan satu dokumen penerimaan jika qtyReceived lebih dari nol.
     */
    private function makePurchaseOrder(
        User $buyer,
        string $status = 'partial',
        int $qtyOrdered = 5,
        int $qtyReceived = 2,
        int $stock = 12
    ): array {
        $now = now();
        $unique = bin2hex(random_bytes(5));

        $supplierId = DB::table('suppliers')->insertGetId([
            'name' => 'Supplier Test ' . $unique,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Produk Test ' . $unique,
            'status' => 'active',
            'created_by' => $buyer->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'SKU-' . $unique,
            'name' => 'Varian Test',
            'unit' => 'pcs',
            'selling_price' => 10000,
            'current_stock' => $stock,
            'reorder_point' => 0,
            'status' => 'active',
            'merged_into_variant_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $poId = DB::table('purchase_orders')->insertGetId([
            'po_number' => 'PO-TEST-' . $unique,
            'supplier_id' => $supplierId,
            'order_date' => $now->toDateString(),
            'expected_date' => null,
            'notes' => 'Purchase Order untuk pengujian',
            'status' => $status,
            'created_by' => $buyer->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('purchase_order_items')->insert([
            'purchase_order_id' => $poId,
            'variant_id' => $variantId,
            'qty_ordered' => $qtyOrdered,
            'qty_received' => $qtyReceived,
            'unit_cost' => 5000,
        ]);

        // Simulasikan riwayat penerimaan sebelumnya jika barang sudah diterima.
        $receiptId = null;

        if ($qtyReceived > 0) {
            $receiptId = DB::table('stock_receipts')->insertGetId([
                'receipt_no' => 'IN-TEST-' . $unique,
                'receipt_date' => $now->toDateString(),
                'supplier_id' => $supplierId,
                'purchase_order_id' => $poId,
                'delivery_ref' => 'DO-TEST-' . $unique,
                'notes' => 'Penerimaan untuk pengujian',
                'received_by' => $buyer->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('stock_receipt_items')->insert([
                'receipt_id' => $receiptId,
                'variant_id' => $variantId,
                'qty_received' => $qtyReceived,
            ]);
        }

        return [
            'po_id' => $poId,
            'variant_id' => $variantId,
            'supplier_id' => $supplierId,
            'receipt_id' => $receiptId,
        ];
    }

    public function test_partial_po_can_be_closed_without_changing_stock_or_receipt_history(): void
    {
        $buyer = $this->buyer();

        $data = $this->makePurchaseOrder(
            buyer: $buyer,
            status: 'partial',
            qtyOrdered: 5,
            qtyReceived: 2,
            stock: 12
        );

        $response = $this->actingAs($buyer)
            ->post(route('purchase-orders.close', $data['po_id']));

        $response->assertRedirect(
            route('purchase-orders.show', $data['po_id'])
        );

        $response->assertSessionHas('success');

        // Status PO berubah menjadi closed_partial.
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $data['po_id'],
            'status' => 'closed_partial',
        ]);

        // Jumlah pesanan dan penerimaan tidak berubah.
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $data['po_id'],
            'variant_id' => $data['variant_id'],
            'qty_ordered' => 5,
            'qty_received' => 2,
        ]);

        // Stok tetap sama.
        $this->assertDatabaseHas('product_variants', [
            'id' => $data['variant_id'],
            'current_stock' => 12,
        ]);

        // Riwayat penerimaan tetap tersedia.
        $this->assertDatabaseCount('stock_receipts', 1);
        $this->assertDatabaseCount('stock_receipt_items', 1);
    }

    public function test_only_partial_po_can_be_closed(): void
    {
        $buyer = $this->buyer();

        $cases = [
            ['status' => 'draft', 'received' => 0, 'stock' => 10],
            ['status' => 'ordered', 'received' => 0, 'stock' => 10],
            ['status' => 'received', 'received' => 5, 'stock' => 15],
            ['status' => 'closed_partial', 'received' => 2, 'stock' => 12],
        ];

        foreach ($cases as $case) {
            $data = $this->makePurchaseOrder(
                buyer: $buyer,
                status: $case['status'],
                qtyOrdered: 5,
                qtyReceived: $case['received'],
                stock: $case['stock']
            );

            $response = $this->actingAs($buyer)
                ->post(route('purchase-orders.close', $data['po_id']));

            $response->assertRedirect(
                route('purchase-orders.show', $data['po_id'])
            );

            $response->assertSessionHas('error');

            // Status tidak boleh berubah.
            $this->assertDatabaseHas('purchase_orders', [
                'id' => $data['po_id'],
                'status' => $case['status'],
            ]);
        }
    }

    public function test_partial_po_without_received_items_or_remaining_items_cannot_be_closed(): void
    {
        $buyer = $this->buyer();

        $cases = [
            // Belum ada barang diterima.
            ['received' => 0, 'stock' => 10],

            // Seluruh barang sudah diterima.
            ['received' => 5, 'stock' => 15],
        ];

        foreach ($cases as $case) {
            $data = $this->makePurchaseOrder(
                buyer: $buyer,
                status: 'partial',
                qtyOrdered: 5,
                qtyReceived: $case['received'],
                stock: $case['stock']
            );

            $response = $this->actingAs($buyer)
                ->post(route('purchase-orders.close', $data['po_id']));

            $response->assertSessionHas('error');

            $this->assertDatabaseHas('purchase_orders', [
                'id' => $data['po_id'],
                'status' => 'partial',
            ]);
        }
    }

    public function test_closed_partial_po_cannot_receive_more_goods(): void
    {
        $buyer = $this->buyer();

        $data = $this->makePurchaseOrder(
            buyer: $buyer,
            status: 'closed_partial',
            qtyOrdered: 5,
            qtyReceived: 2,
            stock: 12
        );

        // Form penerimaan untuk PO tertutup harus ditolak.
        $formResponse = $this->actingAs($buyer)
            ->get(route('stock.incoming.create', [
                'po' => $data['po_id'],
            ]));

        $formResponse->assertRedirect(route('purchase-orders.index'));
        $formResponse->assertSessionHas('error');

        // Coba kirim request langsung, tanpa melalui form.
        $storeResponse = $this->actingAs($buyer)
            ->from(route('purchase-orders.show', $data['po_id']))
            ->post(route('stock.store'), [
                'received_at' => now()->toDateString(),
                'purchase_order_id' => $data['po_id'],
                'delivery_ref' => 'DO-ADDITIONAL-TEST',
                'items' => [
                    [
                        'variant_id' => $data['variant_id'],
                        'qty' => 1,
                    ],
                ],
            ]);

        $storeResponse->assertSessionHasErrors('items');

        // Tidak ada penerimaan tambahan yang tersimpan.
        $this->assertDatabaseCount('stock_receipts', 1);
        $this->assertDatabaseCount('stock_receipt_items', 1);

        // Jumlah penerimaan dan stok tidak berubah.
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $data['po_id'],
            'variant_id' => $data['variant_id'],
            'qty_received' => 2,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $data['variant_id'],
            'current_stock' => 12,
        ]);
    }
}
