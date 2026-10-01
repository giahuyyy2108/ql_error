<?php
class Uploader
{
    var $mediaName;
    var $mediaType;
    var $mediaSize;
    var $mediaDimension;
    var $mediaTmpName;
    var $mediaError;

    var $uploadDir = '';

    var $allowedMimeTypes = array();

    var $maxFileSize = 0;
    var $maxWidth;
    var $maxHeight;

    var $targetFileName;

    var $prefix;

    var $errors = array();

    var $savedDestination;

    var $savedFileName;

    function __construct($uploadDir, $allowedMimeTypes, $maxFileSize, $maxWidth = null, $maxHeight = null)
    {
        if (is_array($allowedMimeTypes)) {
            $this->allowedMimeTypes =& $allowedMimeTypes;
        }
        $this->uploadDir = $uploadDir;
        $this->maxFileSize = intval($maxFileSize);
        if (isset($maxWidth)) {
            $this->maxWidth = intval($maxWidth);
        }
        if (isset($maxHeight)) {
            $this->maxHeight = intval($maxHeight);
        }
    }

    function fetchMedia($media_name, $index = null)
    {
        global $HTTP_POST_FILES;
        if (!isset($HTTP_POST_FILES[$media_name])) {
            $this->setErrors('File not found');
            return false;
        } elseif (is_array($HTTP_POST_FILES[$media_name]['name']) && isset($index)) {
            $index = intval($index);
            $this->mediaName = $HTTP_POST_FILES[$media_name]['name'][$index];
            $this->mediaType = $HTTP_POST_FILES[$media_name]['type'][$index];
            $this->mediaSize = $HTTP_POST_FILES[$media_name]['size'][$index];
            $this->mediaTmpName = $HTTP_POST_FILES[$media_name]['tmp_name'][$index];
            $this->mediaError = !empty($HTTP_POST_FILES[$media_name]['error'][$index]) ? $HTTP_POST_FILES[$media_name]['errir'][$index] : 0;
        } else {
            $media_name =& $HTTP_POST_FILES[$media_name];
            $this->mediaName = $media_name['name'];
            $this->mediaType = $media_name['type'];
            $this->mediaSize = $media_name['size'];
            $this->mediaTmpName = $media_name['tmp_name'];
            $this->mediaError = !empty($media_name['error']) ? $media_name['error'] : 0;
        }
        $this->errors = array();
        if (intval($this->mediaSize) < 0) {
            $this->setErrors('Invalid media size');
            return false;
        }
        if ($this->mediaName == '') {
            $this->setErrors('Invalid media name');
            return false;
        }
        if ($this->mediaTmpName == 'none' || !is_uploaded_file($this->mediaTmpName)) {
            $this->setErrors('No file uploaded');
            return false;
        }
        if ($this->mediaError > 0) {
            $this->setErrors('Error occurred: Error #' . $this->mediaError);
            return false;
        }
        if (isset($this->maxWidth) || isset($this->maxHeight)) {
            if (false === ($this->mediaDimension = getimagesize($this->mediaTmpName))) {
                $this->setErrors('Invalid image file');
                return false;
            }
        }
        return true;
    }

    function setTargetFileName($value)
    {
        $this->targetFileName = strval(trim($value));
    }
    function setPrefix($value)
    {
        $this->prefix = strval(trim($value));
    }

    function getMediaName()
    {
        return $this->mediaName;
    }

    function getMediaType()
    {
        return $this->mediaType;
    }

    function getMediaSize()
    {
        return $this->mediaSize;
    }

    function getMediaTmpName()
    {
        return $this->mediaTmpName;
    }

    function getMediaDimension()
    {
        return $this->mediaDimension;
    }

    function getSavedFileName()
    {
        return $this->savedFileName;
    }

    function getSavedDestination()
    {
        return $this->savedDestination;
    }

    function upload()
    {
        if ($this->uploadDir == '') {
            $this->setErrors('Upload directory not set');
            return false;
        }
        if (!is_dir($this->uploadDir)) {
            $this->setErrors('Failed opening directory: ' . $this->uploadDir);
        }
        if (!is_writeable($this->uploadDir)) {
            $this->setErrors('Failed opening directory with write permission: ' . $this->uploadDir);
        }
        if (!$this->checkMaxFileSize()) {
            $this->setErrors('File size too large: ' . $this->mediaSize);
        }
        if (!$this->checkMaxWidth()) {
            $this->setErrors('File width too large: ' . $this->mediaDimension[0]);
        }
        if (!$this->checkMaxHeight()) {
            $this->setErrors('File height too large: ' . $this->mediaDimension[1]);
        }
        if (!$this->checkMimeType()) {
            $this->setErrors('MIME type not allowed: ' . $this->mediaType);
        }
        if (count($this->errors) > 0) {
            return false;
        }
        if (!$this->copyFile()) {
            $this->setErrors('Failed uploading file: ' . $this->mediaName);
            return false;
        }
        return true;
    }

    function copyFile()
    {
        $matched = array();
        if (!preg_match("/\.([a-zA-Z0-9]+)$/", $this->mediaName, $matched)) {
            return false;
        }
        if (isset($this->targetFileName)) {
            $this->savedFileName = $this->targetFileName;
        } elseif (isset($this->prefix)) {
            $this->savedFileName = uniqid($this->prefix) . '.' . strtolower($matched[1]);
        } else {
            $this->savedFileName = strtolower($this->mediaName);
        }
        $this->savedDestination = $this->uploadDir . '/' . $this->savedFileName;
        if (!move_uploaded_file($this->mediaTmpName, $this->savedDestination)) {
            return false;
        }
        @chmod($this->savedDestination, 0644);
        return true;
    }

    function checkMaxFileSize()
    {
        if ($this->mediaSize > $this->maxFileSize) {
            return false;
        }
        return true;
    }

    function checkMaxWidth()
    {
        if (!isset($this->maxWidth)) {
            return true;
        }
        if ($this->mediaDimension[0] > $this->maxWidth) {
            return false;
        }
        return true;
    }

    function checkMaxHeight()
    {
        if (!isset($this->maxHeight)) {
            return true;
        }
        if ($this->mediaDimension[1] > $this->maxHeight) {
            return false;
        }
        return true;
    }

    function checkMimeType()
    {
        if (count($this->allowedMimeTypes) > 0 && !in_array($this->mediaType, $this->allowedMimeTypes)) {
            return false;
        } else {
            return true;
        }
    }

    function setErrors($error)
    {
        $this->errors[] = trim($error);
    }

    function getErrors($ashtml = true)
    {
        if (!$ashtml) {
            return $this->errors;
        } else {
            $ret = '';
            if (count($this->errors) > 0) {
                $ret = '<h4>Media Upload Errors</h4>';
                foreach ($this->errors as $error) {
                    $ret .= $error . '<br />';
                }
            }
            return $ret;
        }
    }
}
?>