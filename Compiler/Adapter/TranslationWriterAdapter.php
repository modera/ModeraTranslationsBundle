<?php

namespace Modera\TranslationsBundle\Compiler\Adapter;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * @author    Sergei Vizel <sergei.vizel@modera.org>
 * @copyright 2019 Modera Foundation
 */
class TranslationWriterAdapter implements AdapterInterface
{
    /**
     * @var TranslationWriter
     */
    private $writer;

    /**
     * @var string
     */
    private $translationsDir;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @param TranslationWriter $writer
     * @param $translationsDir
     */
    public function __construct(TranslationWriter $writer, $translationsDir, $cacheDir)
    {
        $this->writer = $writer;
        $this->translationsDir = $translationsDir;
        $this->cacheDir = $cacheDir;
    }

    public function clear()
    {
        $fs = new Filesystem();
        if ($fs->exists($this->translationsDir)) {
            foreach (Finder::create()->files()->in($this->translationsDir) as $file) {
                $fs->remove($file->getRealPath());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dump(MessageCatalogueInterface $catalogue)
    {
        $outputFormat = 'yml';

        // check format
        $supportedFormats = $this->writer->getFormats();
        if (!in_array($outputFormat, $supportedFormats)) {
            throw new \RuntimeException(sprintf(
                'Wrong output format. Supported formats are %s.',
                implode(', ', $supportedFormats)
            ));
        }

        $fs = new Filesystem();

        try {
            if (!$fs->exists(dirname($this->translationsDir))) {
                $fs->mkdir(dirname($this->translationsDir));
                $fs->chmod(dirname($this->translationsDir), 0777);
            }
        } catch (IOExceptionInterface $e) {
            throw new \RuntimeException(sprintf(
                'An error occurred while creating your directory at %s',
                $e->getPath()
            ));
        }

        $this->writer->write($catalogue, $outputFormat, array('path' => $this->translationsDir));
        $fs->chmod($this->translationsDir, 0777, 0000, true);

        if ($fs->exists($this->cacheDir)) {
            $filter = function (\SplFileInfo $file) use ($catalogue) {
                $prefix = 'catalogue.' . preg_replace('/[^a-z0-9_]/i', '_', $catalogue->getLocale()) . '.';
                return strpos($file->getBasename(), $prefix) === 0;
            };

            /* @var \SplFileInfo $file */
            foreach (Finder::create()->files()->filter($filter)->in($this->cacheDir) as $file) {
                $fs->remove($file->getRealPath());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadCatalogue($locale)
    {
        return new MessageCatalogue($locale);
    }
}
