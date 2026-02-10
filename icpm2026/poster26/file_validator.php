<?php

class FileValidator {
    private $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
    private $maxSize = 1048576; // 1 MB
    
    // Map extensions to allowed MIME types (arrays to support multiple variants)
    private $allowedMimeTypes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif']
    ];

    /**
     * Validate the uploaded file
     * 
     * @param array $file The $_FILES['input_name'] array
     * @return array Result ['valid' => bool, 'message' => string]
     */
    public function validate($file) {
        // Check for upload errors
        if (!isset($file['error']) || is_array($file['error'])) {
             $this->logError("Invalid file parameter structure.");
             return ['valid' => false, 'message' => 'Invalid file upload parameters.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = $this->getUploadErrorMessage($file['error']);
            $this->logError("Upload error: " . $msg);
            return ['valid' => false, 'message' => 'Upload failed: ' . $msg];
        }

        // 1. Size Validation
        if ($file['size'] > $this->maxSize) {
            $this->logError("File size exceeds limit. Size: " . $file['size'] . " bytes.");
            return ['valid' => false, 'message' => 'File is too large. Max size is 1MB.'];
        }

        // 2. Extension Validation
        $fileName = $file['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $this->allowedExtensions)) {
            $this->logError("Invalid file extension: " . $fileExt);
            return ['valid' => false, 'message' => 'Invalid file format. Allowed: PDF, DOC, DOCX, JPG, PNG, GIF.'];
        }

        // 3. MIME Type & Content Validation (Security)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!$this->isValidMimeType($fileExt, $mimeType)) {
            $this->logError("MIME type mismatch/invalid. Ext: $fileExt, Detected MIME: $mimeType");
            return ['valid' => false, 'message' => 'Security check failed: File content does not match extension.'];
        }

        return ['valid' => true, 'message' => 'File is valid.'];
    }

    private function isValidMimeType($extension, $mimeType) {
        if (!isset($this->allowedMimeTypes[$extension])) {
            return false;
        }
        return in_array($mimeType, $this->allowedMimeTypes[$extension]);
    }

    private function logError($message) {
        error_log("[FileValidator] " . $message);
    }

    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
}
?>
