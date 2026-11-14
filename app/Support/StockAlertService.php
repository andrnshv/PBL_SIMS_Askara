<?php

namespace App\Support;

use App\Mail\StockNotificationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class StockAlertService
{
    /**
     * $changes: [['variant_id' => 1, 'before' => 10, 'after' => 2], ...]
     * Kirim email stok habis / perlu reorder hanya saat batas terlewati.
     */
    public static function notify(array $changes): void
    {
        try {
            if (empty($changes)) {
                return;
            }

            $variants = DB::table('product_variants as v')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->whereIn('v.id', collect($changes)->pluck('variant_id'))
                ->get(['v.id', 'v.sku', 'v.name as variant', 'v.unit', 'v.reorder_point', 'p.name as product'])
                ->keyBy('id');

            $empty = [];
            $reorder = [];

            foreach ($changes as $c) {
                $v = $variants->get($c['variant_id']);
                if (! $v) {
                    continue;
                }

                $before = (int) $c['before'];
                $after = (int) $c['after'];
                $point = (int) ($v->reorder_point ?? 0);

                $row = [
                    'name' => $v->product . ' - ' . $v->variant,
                    'sku' => $v->sku,
                    'stock' => $after,
                    'unit' => $v->unit,
                    'note' => '',
                ];

                if ($after <= 0 && $before > 0) {
                    $row['note'] = 'Stok habis';
                    $empty[] = $row;
                } elseif ($after > 0 && $point > 0 && $after <= $point && $before > $point) {
                    $row['note'] = 'Batas reorder: ' . $point;
                    $reorder[] = $row;
                }
            }

            if ($empty) {
                self::send(
                    ['admin', 'staff_pembelian', 'staff_gudang'],
                    new StockNotificationMail('[Stok Habis] ' . count($empty) . ' varian', 'Varian dengan stok habis', $empty,
                        'Segera lakukan pemesanan ulang.')
                );
            }

            if ($reorder) {
                self::send(
                    ['admin', 'staff_pembelian'],
                    new StockNotificationMail('[Perlu Reorder] ' . count($reorder) . ' varian', 'Varian yang perlu dipesan ulang', $reorder,
                        'Stok sudah mencapai batas minimum.')
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Email ringkasan penerimaan barang. */
    public static function notifyReceipt(int $receiptId): void
    {
        try {
            $receipt = DB::table('stock_receipts')->where('id', $receiptId)->first(['receipt_no', 'receipt_date']);
            if (! $receipt) {
                return;
            }

            $rows = DB::table('stock_receipt_items as i')
                ->join('product_variants as v', 'v.id', '=', 'i.variant_id')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->where('i.receipt_id', $receiptId)
                ->get(['p.name as product', 'v.name as variant', 'v.sku', 'v.unit', 'v.current_stock', 'i.qty_received'])
                ->map(fn ($r) => [
                    'name' => $r->product . ' - ' . $r->variant,
                    'sku' => $r->sku,
                    'stock' => (int) $r->current_stock,
                    'unit' => $r->unit,
                    'note' => 'Masuk +' . $r->qty_received,
                ])->all();

            self::send(
                ['admin', 'staff_gudang'],
                new StockNotificationMail('[Stok Masuk] ' . $receipt->receipt_no, 'Penerimaan barang ' . $receipt->receipt_no, $rows,
                    'Kolom Stok menunjukkan stok setelah penerimaan.')
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

        private static function send(array $roles, StockNotificationMail $mail): void
    {
        // Penerima pengganti (untuk uji). Jika kosong, ambil dari tabel users berdasarkan peran.
        $override = collect(explode(',', (string) config('services.stock_alert.to')))
            ->map(fn ($e) => trim($e))
            ->filter()
            ->values()
            ->all();

        $emails = $override ?: DB::table('users')
            ->whereIn('role', $roles)   // sesuaikan jika nama kolomnya berbeda
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();

        if ($emails) {
            Mail::to($emails)->send($mail);
        }
    }
}