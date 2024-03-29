<?php

namespace Modera\TranslationsBundle\Compiler\Adapter;

use Doctrine\Common\Cache\Cache;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * @deprecated https://github.com/doctrine/cache
 * @author    Sergei Vizel <sergei.vizel@modera.org>
 * @copyright 2019 Modera Foundation
 */
class DoctrineCacheAdapter implements AdapterInterface
{
    const CACHE_KEY = 'modera_translations.doctrine_cache_adapter';

    private Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function dump(MessageCatalogueInterface $catalogue): void
    {
        $catalogues = [];
        if ($string = $this->cache->fetch(self::CACHE_KEY)) {
            $catalogues = unserialize($string);
        }
        $catalogues[$catalogue->getLocale()] = $catalogue->all();
        $this->cache->save(self::CACHE_KEY, serialize($catalogues));
    }

    /**
     * {@inheritdoc}
     */
    public function loadCatalogue(string $locale): MessageCatalogueInterface
    {
        if ($string = $this->cache->fetch(self::CACHE_KEY)) {
            $catalogues = unserialize($string);
            if (isset($catalogues[$locale])) {
                return new MessageCatalogue($locale, $catalogues[$locale]);
            }
        }

        return new MessageCatalogue($locale);
    }
}
