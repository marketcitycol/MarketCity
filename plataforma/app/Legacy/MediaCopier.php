<?php

namespace App\Legacy;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Copia a la versión 1 las fotos que todavía se enlazan al sitio anterior (marketcity.co/wp-content/uploads).
 * Por lotes pequeños, para el hosting compartido. Cada foto se guarda una sola vez (por su huella SHA-1).
 * Sin esto, al apagar WordPress los productos se quedarían sin foto (AUD-R-08).
 */
class MediaCopier
{
    private const MAX_BYTES = 6 * 1024 * 1024;

    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    /** Productos con foto externa que aún no se intentó copiar o que falló. */
    public function pendingQuery()
    {
        return Product::where('image_path', 'like', 'http%')->whereNotIn('id', $this->failed());
    }

    /** @return array{copied:int, failed:int, remaining:int} */
    public function copyBatch(int $limit = 15): array
    {
        $copied = 0;
        $failedNow = [];
        foreach ($this->pendingQuery()->orderBy('id')->take($limit)->get() as $product) {
            $path = $this->copy((string) $product->image_path);
            if ($path) {
                $product->update(['image_path' => $path]);
                $copied++;
            } else {
                $failedNow[] = $product->id;
            }
        }
        if ($failedNow) {
            Setting::put('legacy_media_failed', json_encode(array_values(array_unique([...$this->failed(), ...$failedNow]))));
        }
        // Con el lote de fotos principales terminado, siguen las de la galería.
        $left = $limit - $copied - count($failedNow);
        if ($left > 0) {
            $copied += $this->copyGalleryBatch($left);
        }

        return ['copied' => $copied, 'failed' => count($this->failed()) + count($this->failedGallery()), 'remaining' => $this->pendingQuery()->count() + $this->pendingGalleryQuery()->count()];
    }

    public function pendingGalleryQuery()
    {
        return ProductImage::where('path', 'like', 'http%')->whereNotIn('id', $this->failedGallery());
    }

    private function copyGalleryBatch(int $limit): int
    {
        $copied = 0;
        $failedNow = [];
        foreach ($this->pendingGalleryQuery()->orderBy('id')->take($limit)->get() as $image) {
            $path = $this->copy((string) $image->path);
            if ($path) {
                $image->update(['path' => $path]);
                $copied++;
            } else {
                $failedNow[] = $image->id;
            }
        }
        if ($failedNow) {
            Setting::put('legacy_gallery_failed', json_encode(array_values(array_unique([...$this->failedGallery(), ...$failedNow]))));
        }

        return $copied;
    }

    /** @return list<int> */
    public function failedGallery(): array
    {
        return array_map('intval', json_decode((string) Setting::get('legacy_gallery_failed', '[]'), true) ?: []);
    }

    public function retryFailed(): void
    {
        Setting::put('legacy_media_failed', '[]');
        Setting::put('legacy_gallery_failed', '[]');
    }

    /** @return list<int> */
    public function failed(): array
    {
        return array_map('intval', json_decode((string) Setting::get('legacy_media_failed', '[]'), true) ?: []);
    }

    private function copy(string $url): ?string
    {
        if (! preg_match('#^https://#i', $url)) {
            return null;
        }
        try {
            $res = Http::timeout(20)->withHeaders(['User-Agent' => 'MarketCity-v1/1.0'])->get($url);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
        $type = strtolower(trim(explode(';', (string) $res->header('Content-Type'))[0]));
        $body = $res->body();
        if (! $res->successful() || ! isset(self::TYPES[$type]) || $body === '' || strlen($body) > self::MAX_BYTES || @getimagesizefromstring($body) === false) {
            return null;
        }
        $hash = sha1($body);
        $path = 'productos/legado/'.substr($hash, 0, 2).'/'.$hash.'.'.self::TYPES[$type];
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            $disk->put($path, $body);
        }

        return $path;
    }
}
