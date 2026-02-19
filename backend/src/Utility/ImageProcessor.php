<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * ImageProcessor Utility
 * Handles image optimization similar to Facebook:
 * - Resizes to optimal dimensions
 * - Compresses at high quality
 * - Applies sharpening after resize
 * - Prevents blurry stretched images
 */
class ImageProcessor
{
    // Maximum dimensions 
    private const MAX_WIDTH = 2048;
    private const MAX_HEIGHT = 2048;
    
    // Quality settings
    private const JPEG_QUALITY = 88; // 85-90 
    private const PNG_COMPRESSION = 6; // 0-9, 
    private const WEBP_QUALITY = 88;
    
    /**
     * Process and optimize an uploaded image
     * 
     * @param string $sourcePath Source image path
     * @param string $destinationPath Destination path for optimized image
     * @param array $options Optional settings: maxWidth, maxHeight, quality
     * @return bool Success status
     */
    public static function processImage(string $sourcePath, string $destinationPath, array $options = []): bool
    {
        try {
            // Get image info
            $imageInfo = @getimagesize($sourcePath);
            if ($imageInfo === false) {
                error_log("ImageProcessor: Failed to get image info for $sourcePath");
                return false;
            }
            
            [$width, $height, $type] = $imageInfo;
            
           
            $sourceImage = self::loadImage($sourcePath, $type);
            if ($sourceImage === false) {
                error_log("ImageProcessor: Failed to load image $sourcePath");
                return false;
            }
          
            $maxWidth = $options['maxWidth'] ?? self::MAX_WIDTH;
            $maxHeight = $options['maxHeight'] ?? self::MAX_HEIGHT;
            
            [$newWidth, $newHeight] = self::calculateDimensions($width, $height, $maxWidth, $maxHeight);
            
            if ($newWidth < $width || $newHeight < $height) {
                $outputImage = imagecreatetruecolor($newWidth, $newHeight);
                
               
                if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
                    imagealphablending($outputImage, false);
                    imagesavealpha($outputImage, true);
                    $transparent = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
                    imagefill($outputImage, 0, 0, $transparent);
                }
                
              
                imagecopyresampled(
                    $outputImage, $sourceImage,
                    0, 0, 0, 0,
                    $newWidth, $newHeight,
                    $width, $height
                );
                
              
                self::sharpenImage($outputImage);
                
                imagedestroy($sourceImage);
                $sourceImage = $outputImage;
                $width = $newWidth;
                $height = $newHeight;
            }
            
           
            $result = self::saveImage($sourceImage, $destinationPath, $type, $options);
            
            imagedestroy($sourceImage);
            
            if ($result) {
                error_log("ImageProcessor: Successfully processed image - Original: {$imageInfo[0]}x{$imageInfo[1]}, New: {$width}x{$height}");
            }
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("ImageProcessor: Error processing image: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load image based on type
     */
    private static function loadImage(string $path, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return @imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Save image with optimization
     */
    private static function saveImage($image, string $path, int $type, array $options = []): bool
    {
        $quality = $options['quality'] ?? null;
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                $jpegQuality = $quality ?? self::JPEG_QUALITY;
                return @imagejpeg($image, $path, $jpegQuality);
                
            case IMAGETYPE_PNG:
                $pngCompression = self::PNG_COMPRESSION;
                return @imagepng($image, $path, $pngCompression);
                
            case IMAGETYPE_GIF:
                return @imagegif($image, $path);
                
            case IMAGETYPE_WEBP:
                $webpQuality = $quality ?? self::WEBP_QUALITY;
                return @imagewebp($image, $path, $webpQuality);
                
            default:
                return false;
        }
    }
    
    /**
 * Calculate new dimensions maintaining aspect ratio
     */
    private static function calculateDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        // If image is smaller than max, keep original size
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return [$width, $height];
        }
        
        // Calculate scaling ratio
        $widthRatio = $maxWidth / $width;
        $heightRatio = $maxHeight / $height;
        $ratio = min($widthRatio, $heightRatio);
        
        return [
            (int)round($width * $ratio),
            (int)round($height * $ratio)
        ];
    }
    
    /**
     * Apply unsharp mask to sharpen image
     * This is what makes images look crisp after compression (Facebook technique)
     */
    private static function sharpenImage($image): void
    {
        
        $sharpenMatrix = [
            [-1, -1, -1],
            [-1, 16, -1],
            [-1, -1, -1]
        ];
        
        $divisor = 8;
        $offset = 0;
        
        @imageconvolution($image, $sharpenMatrix, $divisor, $offset);
    }
    
    /**
     * Get optimized file extension based on original type
     * Optionally convert PNG to JPEG if no transparency
     */
    public static function getOptimizedExtension(string $sourcePath): string
    {
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return 'jpg';
        }
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_PNG:
                return 'png';
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_WEBP:
                return 'webp';
            case IMAGETYPE_JPEG:
            default:
                return 'jpg';
        }
    }
    
    /**
     * Check if GD library is available
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('gd');
    }
}
