<?php

namespace App\Support;

use App\Models\AccurateImportBatch;
use Illuminate\Support\Facades\DB;

/**
 * Impor data produk lama dari ekspor Accurate (CSV).
 *
 * Aturan:
 *  - Kegagalan tingkat file (kosong, header wajib tidak ada, > MAX_ROWS, file identik
 *    sudah pernah diimpor, tidak ada satu pun baris valid) -> tidak ada data tersimpan.
 *  - Kegagalan tingkat baris (kosong, format salah, duplikat) -> baris dilewati dan
 *    dilaporkan; baris valid lain tetap tersimpan sebagai satu batch.
 *  - Data hasil impor hanya masuk tabel staging accurate_items (status "unmapped").
 */
class AccurateImporter
{
    public const MAX_ROWS = 5000;
    public const MAX_ERRORS_STORED = 500;

    private const MAX_INT = 2147483647;

    /** field => alias header (sudah dinormalisasi: huruf kecil, tanpa spasi/simbol). */
    private const HEADER_ALIASES = [
        'code'     => ['kodebarang', 'kode', 'kodeitem', 'kodeproduk', 'itemno', 'itemcode', 'sku', 'code'],
        'name'     => ['namabarang', 'nama', 'namaitem', 'namaproduk', 'itemname', 'name'],
        'unit'     => ['satuan', 'satuanutama', 'unit', 'uom'],
        'stock'    => ['stok', 'stock', 'kuantitas', 'qty', 'jumlah', 'saldo'],
        'price'    => ['hargajual', 'harga', 'sellingprice', 'price'],
        'category' => ['kategori', 'kategoribarang', 'category'],
    ];

    private const REQUIRED_FIELDS = ['code', 'name', 'stock'];

    private const FIELD_LABELS = [
        'code'  => 'kode_barang',
        'name'  => 'nama_barang',
        'stock' => 'stok',
    ];

    /** Isi file template yang bisa diunduh (pemisah titik koma agar rapi di Excel lokal Indonesia). */
    public static function templateCsv(): string
    {
        return "\xEF\xBB\xBF"
            . "kode_barang;nama_barang;satuan;stok;harga_jual;kategori\r\n"
            . "BRG-0001;Keripik Singkong Original 250g;pcs;120;15000;Keripik\r\n"
            . "BRG-0002;Keripik Tempe Pedas 200g;pcs;85;13500;Keripik\r\n";
    }

    /**
     * Baca file, validasi, lalu simpan sebagai satu batch.
     *
     * @return array{batch: AccurateImportBatch, imported: int, failed: int, errors: array}
     *
     * @throws AccurateImportException
     */
    public static function import(string $path, string $fileName, int $userId): array
    {
        $content = @file_get_contents($path);

        if ($content === false) {
            throw new AccurateImportException('File tidak dapat dibaca. Unggah ulang file CSV.');
        }

        $hash = hash('sha256', $content);

        $same = AccurateImportBatch::query()->where('file_hash', $hash)->first();
        if ($same) {
            throw new AccurateImportException(
                'File ini identik dengan batch #' . $same->id . ' (' . $same->file_name . ') yang sudah diimpor.'
            );
        }

        $parsed = self::parse($content);
        $errors = $parsed['errors'];

        // Duplikat terhadap data Accurate yang sudah ada di database (selain yang ditolak).
        $existing = self::existingCodes(array_map(fn ($r) => self::key($r['accurate_code']), $parsed['rows']));
        $valid = [];

        foreach ($parsed['rows'] as $row) {
            $key = self::key($row['accurate_code']);

            if (isset($existing[$key])) {
                $errors[] = [
                    'row'     => $row['row_number'],
                    'code'    => $row['accurate_code'],
                    'message' => 'Kode sudah pernah diimpor (batch #' . $existing[$key] . ').',
                ];
                continue;
            }

            $valid[] = $row;
        }

        usort($errors, fn ($a, $b) => $a['row'] <=> $b['row']);

        if ($valid === []) {
            throw AccurateImportException::withErrors(
                'Tidak ada baris valid yang dapat diimpor (' . count($errors) . ' baris ditolak).',
                $errors
            );
        }

        $batch = DB::transaction(function () use ($valid, $errors, $parsed, $fileName, $hash, $userId) {
            $batch = AccurateImportBatch::create([
                'file_name'     => mb_substr($fileName, 0, 255),
                'file_hash'     => $hash,
                'total_rows'    => $parsed['total'],
                'imported_rows' => count($valid),
                'failed_rows'   => count($errors),
                'error_report'  => $errors === [] ? null : array_slice($errors, 0, self::MAX_ERRORS_STORED),
                'uploaded_by'   => $userId,
            ]);

            $now = now();

            foreach (array_chunk($valid, 500) as $chunk) {
                DB::table('accurate_items')->insert(array_map(fn ($r) => $r + [
                    'batch_id'       => $batch->id,
                    'mapping_status' => 'unmapped',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ], $chunk));
            }

            return $batch;
        });

        return [
            'batch'    => $batch,
            'imported' => count($valid),
            'failed'   => count($errors),
            'errors'   => $errors,
        ];
    }

    /**
     * Parse + validasi isi CSV (tanpa menyentuh database).
     *
     * @return array{total:int, rows:array<int,array>, errors:array<int,array{row:int,code:string,message:string}>}
     *
     * @throws AccurateImportException
     */
    public static function parse(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        if (trim($content) === '') {
            throw new AccurateImportException('File kosong. Isi minimal satu baris data di bawah header.');
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            // Ekspor Excel/Accurate lama sering memakai Windows-1252.
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $delimiter = self::detectDelimiter($content);
        $header = null;
        $recordNo = 0;

        while (($record = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $recordNo++;
            if (! self::isBlank($record)) {
                $header = $record;
                break;
            }
        }

        if ($header === null) {
            fclose($stream);
            throw new AccurateImportException('File kosong. Isi minimal satu baris data di bawah header.');
        }

        $columns = self::mapColumns($header);
        $missing = array_values(array_diff(self::REQUIRED_FIELDS, array_keys($columns)));

        if ($missing !== []) {
            fclose($stream);
            $names = implode(', ', array_map(fn ($f) => self::FIELD_LABELS[$f], $missing));

            throw new AccurateImportException(
                'Kolom wajib tidak ditemukan: ' . $names . '. Gunakan template yang disediakan '
                . '(kolom: kode_barang, nama_barang, satuan, stok, harga_jual, kategori).'
            );
        }

        $rows = [];
        $errors = [];
        $seen = [];
        $total = 0;

        while (($record = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $recordNo++;

            if (self::isBlank($record)) {
                continue;
            }

            $total++;

            if ($total > self::MAX_ROWS) {
                fclose($stream);
                throw new AccurateImportException(
                    'Jumlah baris melebihi batas ' . number_format(self::MAX_ROWS, 0, ',', '.') . ' per file. Pecah file menjadi beberapa bagian.'
                );
            }

            $cell = fn (string $field): string => isset($columns[$field])
                ? self::clean($record[$columns[$field]] ?? '')
                : '';

            $code     = $cell('code');
            $name     = $cell('name');
            $unit     = $cell('unit');
            $stockRaw = $cell('stock');
            $priceRaw = $cell('price');
            $category = $cell('category');

            $messages = [];

            if ($code === '') {
                $messages[] = 'Kode barang kosong.';
            } elseif (mb_strlen($code) > 100) {
                $messages[] = 'Kode barang maksimal 100 karakter.';
            }

            if ($name === '') {
                $messages[] = 'Nama barang kosong.';
            } elseif (mb_strlen($name) > 255) {
                $messages[] = 'Nama barang maksimal 255 karakter.';
            }

            if (mb_strlen($unit) > 50) {
                $messages[] = 'Satuan maksimal 50 karakter.';
            }

            if (mb_strlen($category) > 255) {
                $messages[] = 'Kategori maksimal 255 karakter.';
            }

            $stock = null;
            if ($stockRaw === '') {
                $messages[] = 'Stok kosong.';
            } else {
                [$stock, $stockError] = self::parseStock($stockRaw);
                if ($stockError !== null) {
                    $messages[] = $stockError;
                }
            }

            $price = null;
            if ($priceRaw !== '') {
                [$price, $priceError] = self::parsePrice($priceRaw);
                if ($priceError !== null) {
                    $messages[] = $priceError;
                }
            }

            if ($messages === [] && isset($seen[self::key($code)])) {
                $messages[] = 'Kode "' . $code . '" duplikat dengan baris ' . $seen[self::key($code)] . ' di file yang sama.';
            }

            if ($messages !== []) {
                $errors[] = [
                    'row'     => $recordNo,
                    'code'    => mb_substr($code, 0, 100),
                    'message' => implode(' ', $messages),
                ];
                continue;
            }

            $seen[self::key($code)] = $recordNo;

            $rows[] = [
                'row_number'        => $recordNo,
                'accurate_code'     => $code,
                'accurate_name'     => $name,
                'accurate_unit'     => $unit === '' ? null : $unit,
                'accurate_stock'    => $stock,
                'accurate_price'    => $price,
                'accurate_category' => $category === '' ? null : $category,
            ];
        }

        fclose($stream);

        if ($total === 0) {
            throw new AccurateImportException('File hanya berisi header. Isi minimal satu baris data.');
        }

        return ['total' => $total, 'rows' => $rows, 'errors' => $errors];
    }

    /** Kunci pembanding kode: tidak peka huruf besar/kecil. */
    public static function key(string $code): string
    {
        return mb_strtolower($code);
    }

    /** @return array<string,int> kode (huruf kecil) => id batch pertama yang memuatnya */
    private static function existingCodes(array $keys): array
    {
        $found = [];

        foreach (array_chunk(array_values(array_unique($keys)), 500) as $chunk) {
            $rows = DB::table('accurate_items')
                ->whereIn(DB::raw('lower(accurate_code)'), $chunk)
                ->where('mapping_status', '!=', 'rejected')
                ->orderBy('id')
                ->get(['accurate_code', 'batch_id']);

            foreach ($rows as $r) {
                $found[self::key($r->accurate_code)] ??= (int) $r->batch_id;
            }
        }

        return $found;
    }

    private static function detectDelimiter(string $content): string
    {
        $firstLine = strtok($content, "\r\n") ?: '';
        $best = ',';
        $bestCount = 0;

        foreach ([',', ';', "\t"] as $d) {
            $count = substr_count($firstLine, $d);
            if ($count > $bestCount) {
                $best = $d;
                $bestCount = $count;
            }
        }

        return $best;
    }

    /** @return array<string,int> field => indeks kolom */
    private static function mapColumns(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $label) {
            $normalized = preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) $label));

            foreach (self::HEADER_ALIASES as $field => $aliases) {
                if (! isset($columns[$field]) && in_array($normalized, $aliases, true)) {
                    $columns[$field] = $index;
                }
            }
        }

        return $columns;
    }

    private static function isBlank(array $record): bool
    {
        foreach ($record as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function clean(?string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /** @return array{0:?int,1:?string} */
    private static function parseStock(string $raw): array
    {
        if (str_starts_with($raw, '-')) {
            return [null, 'Stok tidak boleh negatif.'];
        }

        $number = self::normalizeNumber($raw);

        if ($number === null) {
            return [null, 'Stok "' . mb_substr($raw, 0, 30) . '" bukan angka yang valid.'];
        }

        if (str_contains($number, '.') && rtrim(explode('.', $number)[1], '0') !== '') {
            return [null, 'Stok harus bilangan bulat (ditemukan "' . mb_substr($raw, 0, 30) . '").'];
        }

        $int = explode('.', $number)[0];

        if (strlen(ltrim($int, '0')) > 10 || (int) $int > self::MAX_INT) {
            return [null, 'Stok terlalu besar.'];
        }

        return [(int) $int, null];
    }

    /** @return array{0:?string,1:?string} */
    private static function parsePrice(string $raw): array
    {
        if (str_starts_with($raw, '-')) {
            return [null, 'Harga tidak boleh negatif.'];
        }

        $number = self::normalizeNumber($raw);

        if ($number === null) {
            return [null, 'Harga "' . mb_substr($raw, 0, 30) . '" bukan angka yang valid.'];
        }

        if (strlen(explode('.', $number)[0]) > 13) {
            return [null, 'Harga terlalu besar.'];
        }

        return [number_format((float) $number, 2, '.', ''), null];
    }

    /**
     * Ubah teks angka menjadi string desimal standar, atau null jika tidak valid.
     * Mendukung: 1250 | 1.250 | 1.250,50 | 1,250 | 1,250.50 | 10,5 | 10.5
     * Catatan: "1.250" dan "1,250" dibaca sebagai seribu dua ratus lima puluh.
     */
    private static function normalizeNumber(string $value): ?string
    {
        $value = preg_replace('/\s+/', '', $value) ?? '';

        if (preg_match('/^\d+$/', $value)) {
            return $value;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $value)) {
            return str_replace(['.', ','], ['', '.'], $value);
        }

        if (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $value)) {
            return str_replace(',', '', $value);
        }

        if (preg_match('/^\d+[.,]\d+$/', $value)) {
            return str_replace(',', '.', $value);
        }

        return null;
    }
}