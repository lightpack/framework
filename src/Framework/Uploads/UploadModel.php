<?php

namespace Lightpack\Uploads;

use Lightpack\Database\Lucid\Model;
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
        'meta' => 'array',
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
     * Get the relative storage path for the uploaded file.
     *
     * @param string|null $variant The variant name (e.g., 'thumbnail')
     * @return string
     */
    public function getPath(?string $variant = null): string
    {
        return $this->getDir($variant) . "/{$this->file_name}";
    }

    /**
     * Get the relative storage directory for the upload.
     *
     * @param string|null $variant The variant name (e.g., 'thumbnail')
     * @return string
     */
    public function getDir(?string $variant = null): string
    {
        $path = "uploads/{$this->visibility}/attachments/{$this->id}";

        if ($variant) {
            return "{$path}/{$variant}";
        }
        
        return $path;
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
     * Check if the file is an image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->type === 'image';
    }
    
    /**
     * Check if the file is a document.
     *
     * @return bool
     */
    public function isDocument(): bool
    {
        return $this->type === 'document';
    }
    
    /**
     * Check if the file is a video.
     *
     * @return bool
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }
    
    /**
     * Check if the file is an audio file.
     *
     * @return bool
     */
    public function isAudio(): bool
    {
        return $this->type === 'audio';
    }
    
    /**
     * Check if the file is a spreadsheet.
     *
     * @return bool
     */
    public function isSpreadsheet(): bool
    {
        return $this->type === 'spreadsheet';
    }
    
    /**
     * Check if the file is a presentation.
     *
     * @return bool
     */
    public function isPresentation(): bool
    {
        return $this->type === 'presentation';
    }
    
    /**
     * Check if the file is an archive.
     *
     * @return bool
     */
    public function isArchive(): bool
    {
        return $this->type === 'archive';
    }
}
