<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig\variables;

use Craft;
use craft\helpers\UrlHelper;
use yii\base\Exception;

Craft::$app->requireEdition(Craft::Client);

/**
 * Rebranding functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Rebrand
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_paths = [];

    /**
     * @var
     */
    private $_imageVariables = [];

    // Public Methods
    // =========================================================================

    /**
     * Returns whether a custom logo has been uploaded.
     *
     * @return bool
     */
    public function isLogoUploaded(): bool
    {
        return $this->isImageUploaded('logo');
    }

    /**
     * Returns whether a custom site icon has been uploaded.
     *
     * @return bool
     */
    public function isIconUploaded(): bool
    {
        return $this->isImageUploaded('icon');
    }

    /**
     * Return whether the specified type of image has been uploaded for the site.
     *
     * @param string $type 'logo' or 'icon'.
     *
     * @return bool
     */
    public function isImageUploaded(string $type): bool
    {
        return in_array($type, ['logo', 'icon'], true) && ($this->_getImagePath($type) !== false);
    }

    /**
     * Returns the logo'sw Image variable, or null if a logo hasn't been uploaded.
     *
     * @return Image|null
     */
    public function getLogo()
    {
        return $this->getImageVariable('logo');
    }

    /**
     * Returns the icons variable, or null if a site icon hasn't been uploaded.
     *
     * @return Image|null
     */
    public function getIcon()
    {
        return $this->getImageVariable('icon');
    }

    /**
     * Get the ImageVariable for type.
     *
     * @param string $type
     *
     * @return Image|null
     */
    public function getImageVariable(string $type)
    {
        if (!in_array($type, ['logo', 'icon'], true)) {
            return null;
        }

        if (!isset($this->_imageVariables[$type])) {
            $path = $this->_getImagePath($type);

            if ($path !== false) {
                $url = $this->_getImageUrl($path, $type);
                $this->_imageVariables[$type] = new Image($path, $url);
            } else {
                $this->_imageVariables[$type] = false;
            }
        }

        return $this->_imageVariables[$type] ?: null;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the path to a rebrand image by type or false if it hasn't ben uploaded.
     *
     * @param string $type logo or image.
     *
     * @return string|false
     * @throws Exception in case of failure
     */
    private function _getImagePath(string $type)
    {
        if (isset($this->_paths[$type])) {
            return $this->_paths[$type];
        }

        $dir = Craft::$app->getPath()->getRebrandPath().DIRECTORY_SEPARATOR.$type;

        if (!is_dir($dir)) {
            $this->_paths[$type] = false;

            return false;
        }

        $handle = opendir($dir);
        if ($handle === false) {
            throw new Exception("Unable to open directory: $dir");
        }
        while (($subDir = readdir($handle)) !== false) {
            if ($subDir === '.' || $subDir === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$subDir;
            if (is_dir($path)) {
                continue;
            }

            // Found a file - cache and return.
            $this->_paths[$type] = $path;

            return $path;
        }
        closedir($handle);

        // Couldn't find a file
        $this->_paths[$type] = false;

        return false;
    }

    /**
     * Returns the URL to a rebrand image.
     *
     * @param string $path
     * @param string $type
     *
     * @return string
     */
    private function _getImageUrl(string $path, string $type): string
    {
        return UrlHelper::resourceUrl('rebrand/'.$type.'/'.pathinfo($path, PATHINFO_BASENAME));
    }
}
