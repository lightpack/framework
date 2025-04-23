<?php

namespace Lightpack\Uploads\Examples;

use Lightpack\Http\Response;
use Lightpack\Uploads\UploadModel;
use Lightpack\Container\Container;

/**
 * Example controller for serving private files
 * 
 * This demonstrates how to implement access control for private files.
 * You should adapt this to your application's authentication and
 * authorization requirements.
 */
class PrivateFileController
{
    /**
     * Serve a private file with access control
     * 
     * @param int $id The upload ID
     * @return Response
     */
    public function serve(int $id)
    {
        // Get the upload
        $upload = (new UploadModel())->find($id);
        
        if (!$upload) {
            return response()->setStatus(404)->send('File not found');
        }
        
        // Check if the file is private
        if (!$upload->is_private) {
            // For non-private files, redirect to a public URL
            // Note: In Lightpack, you would typically use the router to generate URLs
            return response()->setStatus(302)
                ->setHeader('Location', '/uploads/public/' . $upload->path . '/' . $upload->file_name)
                ->send();
        }
        
        // Perform access control checks here
        // This is where you would check if the current user has permission to access this file
        // Example:
        // if (!$this->userCanAccessFile($upload)) {
        //     return response()->setStatus(403)->send('Unauthorized');
        // }
        
        // Get the storage service
        $storage = Container::getInstance()->resolve('storage');
        
        // Get the file path - using the path and file_name properties directly
        $filePath = "uploads/private/{$upload->path}/{$upload->file_name}";
        
        // Check if the file exists
        if (!$storage->exists($filePath)) {
            return response()->setStatus(404)->send('File not found');
        }
        
        // Get the file contents
        $contents = $storage->read($filePath);
        
        // Create a response with the file contents
        $response = response();
        $response->setHeader('Content-Type', $upload->mime_type);
        $response->setHeader('Content-Disposition', 'inline; filename="' . $upload->file_name . '"');
        $response->setHeader('Content-Length', $upload->size);
        
        // Add cache control headers if needed
        $response->setHeader('Cache-Control', 'private, max-age=3600');
        
        return $response->send($contents);
    }
    
    /**
     * Example method to check if a user can access a file
     * 
     * @param UploadModel $upload
     * @return bool
     */
    protected function userCanAccessFile(UploadModel $upload): bool
    {
        // Implement your access control logic here
        // For example, check if the current user owns the model associated with this upload
        // or has permission to access files in this collection
        
        return true; // Replace with actual logic
    }
}
