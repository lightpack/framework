<?php

namespace Lightpack\Uploads;

use Lightpack\Database\Lucid\Model;
use Lightpack\Storage\Storage;
use Lightpack\Container\Container;

/**
 * UploadModel
 * 
 * Represents an uploaded file in the database with methods
 * to access its URL, path, and other metadata.
 */
class UploadModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'uploads';
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'json',
    ];
    
    /**
     * Get the storage instance.
     *
     * @return \Lightpack\Storage\Storage
     */
    protected function storage()
    {
        return Container::getInstance()->resolve('storage');
    }
    
    /**
     * Get the URL for the file.
     *
     * @param string|null $variant The variant name (e.g., 'thumbnail')
     * @return string
     */
    public function url(?string $variant = null): string
    {
        return $this->storage()->url($this->getPath($variant));
    }
    
    /**
     * Get the full path for the file.
     *
     * @param string|null $variant The variant name (e.g., 'thumbnail')
     * @return string
     */
    public function getPath(?string $variant = null): string
    {
        $path = $this->path ?? "media/{$this->id}";
        $filename = $this->getFilename();
        
        // Determine if this is a private or public file
        $visibility = $this->is_private ? 'private' : 'public';
        
        if ($variant) {
            return "uploads/{$visibility}/{$path}/{$variant}/{$filename}";
        }
        
        return "uploads/{$visibility}/{$path}/{$filename}";
    }
    
    /**
     * Get the filename, optionally with a variant prefix.
     *
     * @param string|null $variant The variant name (e.g., 'thumbnail')
     * @return string
     */
    public function getFilename(): string
    {
        return $this->file_name;
    }
    
    /**
     * Get the file extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }
    
    /**
     * Check if the file exists.
     *
     * @param string|null $variant The variant name (e.g., 'thumbnail')
     * @return bool
     */
    public function exists(?string $variant = null): bool
    {
        $path = $this->getPath();
        
        if ($variant) {
            $path = $this->getPath($variant);
        }
        
        return $this->storage()->exists($path);
    }
    
    /**
     * Get the file's MIME type.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mime_type;
    }
    
    /**
     * Get the file size in bytes.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }
    
    /**
     * Get the file's metadata.
     *
     * @param string|null $key The specific metadata key to retrieve
     * @param mixed $default The default value if the key doesn't exist
     * @return mixed
     */
    public function getMeta(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->meta ?? [];
        }
        
        return $this->meta[$key] ?? $default;
    }
    
    /**
     * Get the file type based on MIME type.
     * 
     * @return string One of: 'image', 'video', 'audio', 'document', 'spreadsheet', 'presentation', 'archive', 'code', 'other'
     */
    public function getFileType(): string
    {
        $uploadService = Container::getInstance()->resolve(UploadService::class);
        
        return $uploadService->getFileType($this->mime_type);
    }
    
    /**
     * Check if the file is an image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->getFileType() === 'image';
    }
    
    /**
     * Check if the file is a document.
     *
     * @return bool
     */
    public function isDocument(): bool
    {
        return $this->getFileType() === 'document';
    }
    
    /**
     * Check if the file is a video.
     *
     * @return bool
     */
    public function isVideo(): bool
    {
        return $this->getFileType() === 'video';
    }
    
    /**
     * Check if the file is an audio file.
     *
     * @return bool
     */
    public function isAudio(): bool
    {
        return $this->getFileType() === 'audio';
    }
    
    /**
     * Check if the file is a spreadsheet.
     *
     * @return bool
     */
    public function isSpreadsheet(): bool
    {
        return $this->getFileType() === 'spreadsheet';
    }
    
    /**
     * Check if the file is a presentation.
     *
     * @return bool
     */
    public function isPresentation(): bool
    {
        return $this->getFileType() === 'presentation';
    }
    
    /**
     * Check if the file is an archive.
     *
     * @return bool
     */
    public function isArchive(): bool
    {
        return $this->getFileType() === 'archive';
    }
}
