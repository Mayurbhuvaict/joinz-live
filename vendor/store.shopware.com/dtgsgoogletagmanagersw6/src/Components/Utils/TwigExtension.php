<?php

declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Components\Utils;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('gtmIsActive', [$this, 'isActive']),
            new TwigFunction('gtmGetContainerIds', [$this, 'getContainerIds']),
            new TwigFunction('gtmGetJsUrl', [$this, 'getJsUrl']),

        ];
    }

    /**
     * @param string|null $containerIds
     * @return bool
     */
    public function isActive(?string $containerIds): bool
    {
        if(empty($this->getContainerIds($containerIds))) return false;

        return true;
    }

    /**
     * @param string|null $containerIds
     * @return array
     */
    public function getContainerIds(?string $containerIds): array
    {
        if(empty($containerIds) || $containerIds == '') return [];

        return explode(',', $containerIds);
    }

    /**
     * @param $config
     * @return string
     */
    public function getJsUrl($customGtmJsUrl): string
    {
        // Remove all illegal characters from url
        $customUrl = filter_var($customGtmJsUrl, FILTER_SANITIZE_URL);

        if (filter_var($customUrl, FILTER_VALIDATE_URL) !== FALSE) {
            //make sure there is a trailing slash at the end
            return rtrim($customUrl,"/") . '/';
        }

        return 'https://www.googletagmanager.com/';
    }
}
