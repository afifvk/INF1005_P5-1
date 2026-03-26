<?php

function getAllowedProductImageMimeTypes() {
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
}

function getProductImageUploadMaxBytes() {
    return 5 * 1024 * 1024;
}

function getProductImageUploadErrorMessage($errorCode) {
    switch ((int) $errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'The image is too large.';
        case UPLOAD_ERR_PARTIAL:
            return 'The image upload was interrupted. Please try again.';
        case UPLOAD_ERR_NO_FILE:
            return 'Please select an image file.';
        default:
            return 'Unable to upload the image right now.';
    }
}

function detectUploadedFileMimeType($tmpPath) {
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mimeType = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);

            if (is_string($mimeType) && $mimeType !== '') {
                return $mimeType;
            }
        }
    }

    if (function_exists('mime_content_type')) {
        $mimeType = mime_content_type($tmpPath);
        if (is_string($mimeType) && $mimeType !== '') {
            return $mimeType;
        }
    }

    return null;
}

function matchesUploadedProductImageSignature($tmpPath, $mimeType) {
    $handle = @fopen($tmpPath, 'rb');
    if ($handle === false) {
        return false;
    }

    $header = (string) fread($handle, 8);
    fclose($handle);

    switch ($mimeType) {
        case 'image/jpeg':
            return strlen($header) >= 3 && substr($header, 0, 3) === "\xFF\xD8\xFF";
        case 'image/png':
            return $header === "\x89PNG\r\n\x1A\n";
        default:
            return false;
    }
}

function generateProductImageFilename($extension) {
    return 'product-' . bin2hex(random_bytes(16)) . '.' . $extension;
}

function createProductImageResource($tmpPath, $mimeType) {
    switch ($mimeType) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($tmpPath);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($tmpPath);
            break;
        default:
            $image = false;
            break;
    }

    if ($image === false) {
        throw new RuntimeException('The uploaded image could not be processed.');
    }

    return $image;
}

function createSanitizedProductImageCanvas($image, $mimeType) {
    $width = imagesx($image);
    $height = imagesy($image);

    if ($width <= 0 || $height <= 0) {
        throw new RuntimeException('The uploaded image is invalid.');
    }

    $sanitized = imagecreatetruecolor($width, $height);
    if ($sanitized === false) {
        throw new RuntimeException('Unable to sanitize the uploaded image.');
    }

    if ($mimeType === 'image/png') {
        if (function_exists('imagealphablending')) {
            imagealphablending($sanitized, false);
        }
        if (function_exists('imagesavealpha')) {
            imagesavealpha($sanitized, true);
        }

        $transparent = imagecolorallocatealpha($sanitized, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($sanitized, 0, 0, $width, $height, $transparent);
        }
    }

    if (!imagecopy($sanitized, $image, 0, 0, 0, 0, $width, $height)) {
        imagedestroy($sanitized);
        throw new RuntimeException('Unable to sanitize the uploaded image.');
    }

    return $sanitized;
}

function rotateProductImage($image, $degrees) {
    if (!function_exists('imagerotate')) {
        return $image;
    }

    $rotated = imagerotate($image, $degrees, 0);
    if ($rotated === false) {
        return $image;
    }

    imagedestroy($image);
    return $rotated;
}

function orientUploadedJpeg($image, $tmpPath) {
    if (!function_exists('exif_read_data')) {
        return $image;
    }

    $exif = @exif_read_data($tmpPath);
    if (!is_array($exif) || empty($exif['Orientation'])) {
        return $image;
    }

    switch ((int) $exif['Orientation']) {
        case 2:
            if (function_exists('imageflip')) {
                imageflip($image, IMG_FLIP_HORIZONTAL);
            }
            break;
        case 3:
            $image = rotateProductImage($image, 180);
            break;
        case 4:
            if (function_exists('imageflip')) {
                imageflip($image, IMG_FLIP_VERTICAL);
            }
            break;
        case 5:
            if (function_exists('imageflip')) {
                $image = rotateProductImage($image, -90);
                imageflip($image, IMG_FLIP_HORIZONTAL);
            }
            break;
        case 6:
            $image = rotateProductImage($image, -90);
            break;
        case 7:
            if (function_exists('imageflip')) {
                $image = rotateProductImage($image, 90);
                imageflip($image, IMG_FLIP_HORIZONTAL);
            }
            break;
        case 8:
            $image = rotateProductImage($image, 90);
            break;
    }

    return $image;
}

function writeProductImageResource($image, $uploadPath, $mimeType) {
    switch ($mimeType) {
        case 'image/jpeg':
            return @imagejpeg($image, $uploadPath, 90);
        case 'image/png':
            if (function_exists('imagealphablending')) {
                imagealphablending($image, false);
            }
            if (function_exists('imagesavealpha')) {
                imagesavealpha($image, true);
            }
            return @imagepng($image, $uploadPath, 6);
        default:
            return false;
    }
}

function canProcessUploadedProductImage($mimeType) {
    switch ($mimeType) {
        case 'image/jpeg':
            return function_exists('imagecreatefromjpeg')
                && function_exists('imagecreatetruecolor')
                && function_exists('imagecopy')
                && function_exists('imagejpeg');
        case 'image/png':
            return function_exists('imagecreatefrompng')
                && function_exists('imagecreatetruecolor')
                && function_exists('imagecopy')
                && function_exists('imagepng');
        default:
            return false;
    }
}

function getLastPhpErrorMessage() {
    $lastError = error_get_last();

    if (!is_array($lastError) || empty($lastError['message'])) {
        return null;
    }

    return (string) $lastError['message'];
}

function ensureProductImageUploadDirectoryWritable($uploadDir) {
    clearstatcache(true, $uploadDir);

    if (is_writable($uploadDir)) {
        return;
    }

    @chmod($uploadDir, 0775);
    clearstatcache(true, $uploadDir);

    if (!is_writable($uploadDir)) {
        error_log('Product image upload directory is not writable: ' . $uploadDir);
        throw new RuntimeException('The image upload folder is not writable on the server.');
    }
}

function persistVerifiedUploadedProductFile($tmpPath, $uploadPath) {
    if (@move_uploaded_file($tmpPath, $uploadPath)) {
        return;
    }

    $moveError = getLastPhpErrorMessage();

    if (@copy($tmpPath, $uploadPath)) {
        @unlink($tmpPath);
        return;
    }

    $copyError = getLastPhpErrorMessage();
    $errorMessage = $copyError !== null ? $copyError : $moveError;

    if ($errorMessage !== null && stripos($errorMessage, 'Permission denied') !== false) {
        error_log('Product image upload permission error for ' . $uploadPath . ': ' . $errorMessage);
        throw new RuntimeException('The image upload folder is not writable on the server.');
    }

    if ($errorMessage !== null) {
        error_log('Product image upload save failed for ' . $uploadPath . ': ' . $errorMessage);
    } else {
        error_log('Product image upload save failed for ' . $uploadPath . ': unknown persistence error');
    }

    throw new RuntimeException('The server could not save the uploaded image.');
}

function storeUploadedProductImage(array $file, $uploadDir) {
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException(getProductImageUploadErrorMessage($errorCode));
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Invalid upload request.');
    }

    $fileSize = (int) ($file['size'] ?? 0);
    if ($fileSize <= 0) {
        throw new RuntimeException('The uploaded image is empty.');
    }

    $maxBytes = getProductImageUploadMaxBytes();
    if ($fileSize > $maxBytes) {
        throw new RuntimeException('The image must be 5 MB or smaller.');
    }

    $allowedMimeTypes = getAllowedProductImageMimeTypes();
    $detectedMimeType = detectUploadedFileMimeType($tmpPath);
    $imageInfo = @getimagesize($tmpPath);
    $imageMimeType = is_array($imageInfo) && isset($imageInfo['mime'])
        ? (string) $imageInfo['mime']
        : '';
    $trustedMimeType = null;

    if ($detectedMimeType !== null && isset($allowedMimeTypes[$detectedMimeType])) {
        $trustedMimeType = $detectedMimeType;
    } elseif ($imageMimeType !== '' && isset($allowedMimeTypes[$imageMimeType])) {
        $trustedMimeType = $imageMimeType;
    }

    if (
        $trustedMimeType === null
        || ($detectedMimeType !== null
            && $imageMimeType !== ''
            && $imageMimeType !== $detectedMimeType)
        || !matchesUploadedProductImageSignature($tmpPath, $trustedMimeType)
    ) {
        throw new RuntimeException('Invalid image type. Allowed: JPG and PNG.');
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Unable to prepare the image upload directory.');
    }

    ensureProductImageUploadDirectoryWritable($uploadDir);

    $extension = $allowedMimeTypes[$trustedMimeType];
    do {
        $imageName = generateProductImageFilename($extension);
        $uploadPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $imageName;
    } while (file_exists($uploadPath));

    if (canProcessUploadedProductImage($trustedMimeType)) {
        $image = createProductImageResource($tmpPath, $trustedMimeType);

        if ($trustedMimeType === 'image/jpeg') {
            $image = orientUploadedJpeg($image, $tmpPath);
        }

        $sanitizedImage = createSanitizedProductImageCanvas($image, $trustedMimeType);
        imagedestroy($image);

        try {
            if (!writeProductImageResource($sanitizedImage, $uploadPath, $trustedMimeType)) {
                throw new RuntimeException('The server could not save the sanitized image.');
            }
        } catch (Throwable $e) {
            if (is_file($uploadPath)) {
                @unlink($uploadPath);
            }
            error_log('Product image sanitization save failed for ' . $uploadPath . ': ' . $e->getMessage());
            imagedestroy($sanitizedImage);
            throw $e instanceof RuntimeException
                ? $e
                : new RuntimeException('The server could not save the sanitized image.');
        }

        imagedestroy($sanitizedImage);
    } else {
        persistVerifiedUploadedProductFile($tmpPath, $uploadPath);
    }
    return $imageName;
}
