<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\Gallery;
use App\Models\Image;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Importação correta para a facade Log
use Illuminate\Support\Facades\DB; // Para transações de DB

class ProcessImageWithGd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $tempRelativePath;
    protected int $galleryId;
    protected string $originalFileName;
    protected ?string $watermarkFile;

    public function __construct(string $tempRelativePath, int $galleryId, string $originalFileName, ?string $watermarkFile = null)
    {
        $this->tempRelativePath = $tempRelativePath;
        $this->galleryId = $galleryId;
        $this->originalFileName = $originalFileName;
        $this->watermarkFile = $watermarkFile;
    }

    public function handle(): void
    {
        $diskLocal = Storage::disk('local');
        $diskPublic = Storage::disk('public');

        $imgOriginal = null;
        $resizedImage = null;
        $watermark = null;
        $scaledWatermark = null;
        $watermarkedImage = null;
        $thumb = null;

        try {
            // Verifica se o arquivo temporário existe no disco 'local'
            if (!$diskLocal->exists($this->tempRelativePath)) {
                Log::warning("ProcessImageWithGd Job: Arquivo temporário não encontrado no disco 'local' para o caminho: {$this->tempRelativePath}. Pulando.");
                return;
            }

            $gallery = Gallery::find($this->galleryId);
            if (!$gallery) {
                Log::error("ProcessImageWithGd Job: Galeria não encontrada (ID: {$this->galleryId}). Deletando arquivo temporário: {$this->tempRelativePath}.");
                $diskLocal->delete($this->tempRelativePath);
                return;
            }

            // Lê o conteúdo da imagem
            $imageContent = $diskLocal->get($this->tempRelativePath);
            $extension = strtolower(pathinfo($this->originalFileName, PATHINFO_EXTENSION));

            // Cria imagem a partir do conteúdo
            // Esta é a linha corrigida para usar imagecreatefromstring, que é universal.
            $imgOriginal = imagecreatefromstring($imageContent);
            if (!$imgOriginal) {
                throw new Exception("Não foi possível criar imagem a partir do string. Formato inválido ou corrompido para '{$this->originalFileName}'.");
            }

            $originalWidth = imagesx($imgOriginal);
            $originalHeight = imagesy($imgOriginal);

            $maxWidth = 1920;
            $maxHeight = 1080;
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;

            // Redimensionamento proporcional
            if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
                $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
                $newWidth = (int)($originalWidth * $ratio);
                $newHeight = (int)($originalHeight * $ratio);
            }

            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            $this->preserveTransparency($resizedImage, $extension); // Preserva transparência para PNG

            imagecopyresampled($resizedImage, $imgOriginal, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            imagedestroy($imgOriginal); // Libera memória da imagem original

            // --- Gerar nomes de arquivo únicos e slugs para armazenamento ---
            $uuid = Str::uuid();
            $originalSlug = Str::slug(pathinfo($this->originalFileName, PATHINFO_FILENAME));

            $finalOriginalName = $uuid . '_' . $originalSlug . '.' . $extension;
            $finalThumbName = $uuid . '_' . $originalSlug . '_thumb.' . $extension;
            $finalWatermarkedName = $uuid . '_' . $originalSlug . '_wm.' . $extension;

            $watermarkApplied = false;
            $watermarkedStoragePath = null;

            // --- SALVAR A IMAGEM ORIGINAL (SEM MARCA D'ÁGUA) ---
            $originalStoragePath = 'galleries/' . $gallery->id . '/' . $finalOriginalName;
            $this->saveImageToDisk($resizedImage, $diskPublic, $originalStoragePath, $extension);

            // --- Processamento e Aplicação da Marca D'água ---
            if ($this->watermarkFile && !empty($this->watermarkFile)) {
                $watermarkDiskPath = 'watermarks/' . $this->watermarkFile;
                if ($diskPublic->exists($watermarkDiskPath)) {
                    $watermarkFileContent = $diskPublic->get($watermarkDiskPath);
                    $watermarkExtension = strtolower(pathinfo($this->watermarkFile, PATHINFO_EXTENSION));
                    // Esta é a linha corrigida para usar imagecreatefromstring para a marca d'água.
                    $watermark = imagecreatefromstring($watermarkFileContent);

                    if ($watermark) {
                        $watermarkedImage = imagecreatetruecolor($newWidth, $newHeight);
                        $this->preserveTransparency($watermarkedImage, $extension); // Para a imagem de destino
                        imagecopy($watermarkedImage, $resizedImage, 0, 0, 0, 0, $newWidth, $newHeight); // Copia a imagem redimensionada

                        $watermarkWidth = imagesx($watermark);
                        $watermarkHeight = imagesy($watermark);

                        $targetWatermarkWidth = min($newWidth * 0.20, 200);
                        $watermarkRatio = ($watermarkWidth > 0) ? $targetWatermarkWidth / $watermarkWidth : 1;
                        $scaledWatermarkWidth = (int)($watermarkWidth * $watermarkRatio);
                        $scaledWatermarkHeight = (int)($watermarkHeight * $watermarkRatio);

                        $scaledWatermark = imagecreatetruecolor($scaledWatermarkWidth, $scaledWatermarkHeight);
                        $this->preserveTransparency($scaledWatermark, $watermarkExtension); // Para a marca d'água escalada
                        imagecopyresampled($scaledWatermark, $watermark, 0, 0, 0, 0, $scaledWatermarkWidth, $scaledWatermarkHeight, $watermarkWidth, $watermarkHeight);
                        imagedestroy($watermark);

                        $padding = 10;
                        $destX = $newWidth - $scaledWatermarkWidth - $padding;
                        $destY = $newHeight - $scaledWatermarkHeight - $padding;

                        // Aplica a marca d'água na imagem
                        imagecopy($watermarkedImage, $scaledWatermark, $destX, $destY, 0, 0, $scaledWatermarkWidth, $scaledWatermarkHeight);
                        imagedestroy($scaledWatermark);

                        $watermarkedStoragePath = 'galleries/' . $gallery->id . '/watermarked/' . $finalWatermarkedName;
                        $this->saveImageToDisk($watermarkedImage, $diskPublic, $watermarkedStoragePath, $extension);
                        imagedestroy($watermarkedImage); // Libera memória da imagem com marca d'água
                        $watermarkApplied = true;
                    } else {
                        Log::warning("ProcessImageWithGd Job: Não foi possível carregar a marca d'água '{$this->watermarkFile}'. Formato inválido?");
                    }
                } else {
                    Log::warning("ProcessImageWithGd Job: Marca d'água não encontrada em '{$watermarkDiskPath}'. Por favor, verifique se o arquivo está em 'storage/app/public/watermarks/'.");
                }
            }


            // --- Gerar Thumbnail ---
            $thumbWidth = 300;
            $thumbHeight = 200; // Altura fixa para thumbnails

            $srcRatio = $newWidth / $newHeight;
            $targetRatio = $thumbWidth / $thumbHeight;

            $srcX = 0;
            $srcY = 0;
            $srcW = $newWidth;
            $srcH = $newHeight;

            if ($srcRatio > $targetRatio) { // Imagem original mais larga que o thumbnail
                $srcW = $newHeight * $targetRatio;
                $srcX = ($newWidth - $srcW) / 2;
            } else { // Imagem original mais alta ou igual que o thumbnail
                $srcH = $newWidth / $targetRatio;
                $srcY = ($newHeight - $srcH) / 2;
            }

            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            $this->preserveTransparency($thumb, $extension); // Preserva transparência para PNG

            imagecopyresampled($thumb, $resizedImage, 0, 0, (int)$srcX, (int)$srcY, $thumbWidth, $thumbHeight, (int)$srcW, (int)$srcH);
            imagedestroy($resizedImage); // Libera memória da imagem redimensionada

            $thumbStoragePath = 'galleries/' . $gallery->id . '/thumbs/' . $finalThumbName; // Criando pasta 'thumbs'
            $this->saveImageToDisk($thumb, $diskPublic, $thumbStoragePath, $extension);
            imagedestroy($thumb); // Libera memória do thumbnail

            // --- Salvar informações da imagem no banco de dados ---
            DB::beginTransaction();
            try {
                $image = new Image();
                $image->gallery_id = $gallery->id;
                $image->original_file_name = $this->originalFileName;
                $image->path_original = $originalStoragePath;
                $image->path_thumb = $thumbStoragePath;
                $image->watermark_applied = $watermarkApplied; // Define se a marca d'água foi aplicada
                $image->metadata = [
                    'original_width' => $originalWidth,
                    'original_height' => $originalHeight,
                    'watermarked_path' => $watermarkedStoragePath, // Caminho da imagem com marca d'água
                    'watermark_file_used' => $this->watermarkFile, // Nome do arquivo da marca d'água usada
                    'final_width' => $newWidth,
                    'final_height' => $newHeight,
                    'file_size' => $diskPublic->size($originalStoragePath),
                    // CORREÇÃO AQUI: Inferindo mime_type da extensão, mais robusto.
                    'mime_type' => 'image/' . $extension,
                ];
                $image->save();
                DB::commit();
                Log::info("ProcessImageWithGd Job: Imagem '{$this->originalFileName}' processada e salva para galeria {$this->galleryId}.");
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("ProcessImageWithGd Job: Falha ao salvar imagem no DB para galeria {$this->galleryId}: " . $e->getMessage() . " na linha " . $e->getLine() . " em " . $e->getFile());
                // Se falhar aqui, você pode considerar deletar os arquivos que foram salvos para evitar órfãos
                $diskPublic->delete([$originalStoragePath, $thumbStoragePath, $watermarkedStoragePath]);
                $this->fail($e); // Marca o Job como falho explicitamente
            }
        } catch (Exception $e) {
            Log::error("ProcessImageWithGd Job: Falha geral ao processar imagem '{$this->originalFileName}' para galeria {$this->galleryId}: " . $e->getMessage() . " na linha " . $e->getLine() . " em " . $e->getFile());
            $this->fail($e); // Marca o Job como falho explicitamente
        } finally {
            // Deleta o arquivo temporário do disco 'local' no final, independente do sucesso ou falha
            try {
                if ($diskLocal->exists($this->tempRelativePath)) {
                    $diskLocal->delete($this->tempRelativePath);
                    Log::info("ProcessImageWithGd Job: Arquivo temporário '{$this->tempRelativePath}' deletado do disco local.");
                }
            } catch (Exception $e) {
                Log::error("ProcessImageWithGd Job: Erro ao deletar arquivo temporário '{$this->tempRelativePath}': " . $e->getMessage());
            }
        }
    }

    /**
     * Helper para criar recurso de imagem GD a partir de conteúdo e extensão.
     * Esta função (comentada abaixo) foi REMOVIDA na correção anterior porque
     * imagecreatefromstring é usada diretamente no handle() e é mais universal.
     * Seu arquivo atual *NÃO DEVE* ter esta função.
     */
    /* protected function createImageFromContent(string $content, string $extension)
    {
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                return imagecreatefromstring($content);
            case 'png':
                return imagecreatefromstring($content);
            case 'gif':
                return imagecreatefromstring($content);
            case 'webp': // Suporte básico, pode não estar disponível em todas as instalações GD
                 if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($content);
                }
                Log::warning("GD: Suporte a WebP não disponível. Tente instalar a extensão.");
                return false;
            default:
                return false;
        }
    } */

    /**
     * Helper para preservar transparência para imagens PNG.
     * @param \GdImage $image
     * @param string $extension
     */
    protected function preserveTransparency(\GdImage $image, string $extension): void
    {
        if (strtolower($extension) === 'png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
            imagefill($image, 0, 0, $transparent); // Usar imagefill para preencher todo o fundo
        }
    }

    /**
     * Helper para salvar recurso de imagem GD para disco.
     * @param \GdImage $imageResource
     * @param \Illuminate\Contracts\Filesystem\Filesystem $disk
     * @param string $path
     * @param string $extension
     */
    protected function saveImageToDisk(\GdImage $imageResource, $disk, string $path, string $extension): void
    {
        ob_start();
        switch (strtolower($extension)) {
            case 'jpeg':
            case 'jpg':
                imagejpeg($imageResource, null, 80);
                break;
            case 'png':
                imagepng($imageResource, null, 9); // Qualidade PNG de 0 (sem compressão) a 9 (max compressão)
                break;
            case 'gif':
                imagegif($imageResource);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    imagewebp($imageResource, null, 80);
                } else {
                    imagejpeg($imageResource, null, 80); // Fallback para JPEG
                    Log::warning("GD: Suporte a WebP não disponível. Salvando como JPEG. Tente instalar a extensão.");
                }
                break;
            default:
                imagejpeg($imageResource, null, 80); // Fallback padrão
                Log::warning("GD: Formato de imagem desconhecido '{$extension}'. Salvando como JPEG.");
        }
        $disk->put($path, ob_get_clean());
    }
}
