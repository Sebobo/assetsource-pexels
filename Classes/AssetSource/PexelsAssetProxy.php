<?php
namespace DL\AssetSource\Pexels\AssetSource;

/*
 * This file is part of the DL.AssetSource.Pexels package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Uri;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\HasRemoteOriginalInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\ImportedAsset;
use Neos\Media\Domain\Repository\ImportedAssetRepository;
use Psr\Http\Message\UriInterface;

final class PexelsAssetProxy implements AssetProxyInterface, HasRemoteOriginalInterface
{
    /**
     * @var array
     */
    private $photo;

    /**
     * @var PexelsAssetSource
     */
    private $assetSource;

    /**
     * @var ImportedAsset
     */
    private $importedAsset;

    /**
     * UnsplashAssetProxy constructor.
     * @param array $photo
     * @param PexelsAssetSource $assetSource
     */
    public function __construct(array $photo, PexelsAssetSource $assetSource)
    {
        $this->photo = $photo;
        $this->assetSource = $assetSource;
        $this->importedAsset = (new ImportedAssetRepository)->findOneByAssetSourceIdentifierAndRemoteAssetIdentifier($assetSource->getIdentifier(), $this->getIdentifier());
    }

    /**
     * @return AssetSourceInterface
     */
    public function getAssetSource(): AssetSourceInterface
    {
        return $this->assetSource;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return (string)$this->getProperty('id');
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        $nameSlug = $this->extractSlugFromUrl();
        return $nameSlug !== '' ? str_replace('-', ' ', $nameSlug) : $this->getIdentifier();
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        $nameSlug = $this->extractSlugFromUrl();
        return $nameSlug !== '' ? $nameSlug . '.jpg' : $this->getIdentifier() . '.jpg';
    }

    /**
     * @return string
     */
    protected function extractSlugFromUrl()
    {
        $url = $this->getProperty('url');

        if (!empty($url)) {
            $url = rtrim($url, '/');
            $urlParts = explode('/', $url);
            return trim(str_replace($this->getIdentifier(), '', end($urlParts)), '-');
        }

        return '';
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastModified(): \DateTimeInterface
    {
        return new \DateTime();
    }

    /**
     * @return int
     */
    public function getFileSize(): int
    {
        return 0;
    }

    /**
     * @return string
     */
    public function getMediaType(): string
    {
        return 'image/jpeg';
    }

    /**
     * @return int|null
     */
    public function getWidthInPixels(): ?int
    {
        return (int)$this->getProperty('width');
    }

    /**
     * @return int|null
     */
    public function getHeightInPixels(): ?int
    {
        return (int)$this->getProperty('height');
    }

    /**
     * @return null|UriInterface
     */
    public function getThumbnailUri(): ?UriInterface
    {
        return new Uri($this->getImageUrl(PexelsImageSizeInterface::TINY));
    }

    /**
     * @return null|UriInterface
     */
    public function getPreviewUri(): ?UriInterface
    {
        return new Uri($this->getImageUrl(PexelsImageSizeInterface::LARGE));
    }

    /**
     * @return resource
     */
    public function getImportStream()
    {
        return fopen($this->getImageUrl(PexelsImageSizeInterface::ORIGINAL), 'r');
    }

    /**
     * @return null|string
     */
    public function getLocalAssetIdentifier(): ?string
    {
        return $this->importedAsset instanceof ImportedAsset ? $this->importedAsset->getLocalAssetIdentifier() : '';
    }

    /**
     * Returns true if the binary data of the asset has already been imported into the Neos asset source.
     *
     * @return bool
     */
    public function isImported(): bool
    {
        return $this->importedAsset !== null;
    }

    /**
     * @param string $propertyName
     * @return mixed|null
     */
    protected function getProperty(string $propertyName)
    {
        return $this->photo[$propertyName] ?? null;
    }

    /**
     * @param string $size
     * @return string
     */
    protected function getImageUrl(string $size): string
    {
        $urls = $this->getProperty('src');
        if (isset($urls[$size])) {
            return $urls[$size];
        }
        return '';
    }
}
