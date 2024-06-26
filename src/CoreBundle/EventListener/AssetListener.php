<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Vich\UploaderBundle\Event\Event;

class AssetListener
{
    protected AssetRepository $assetRepository;

    public function __construct(AssetRepository $assetRepository)
    {
        $this->assetRepository = $assetRepository;
    }

    public function __invoke(Event $event): void
    {
        /** @var Asset $asset */
        $asset = $event->getObject();
        if ($asset instanceof Asset) {
            $mapping = $event->getMapping();
            $filePath = $asset->getCategory().'/'.$asset->getFile()->getFilename();
            $this->assetRepository->getFileSystem()->deleteDirectory($filePath);

            // Deletes scorm folder: example: assets/scorm/myABC .
            /*if (!empty($folder) && Asset::SCORM === $asset->getCategory()) {
                $folder = Asset::SCORM.'/'.$folder;
                $this->assetRepository->getFileSystem()->deleteDirectory($folder);
            }*/
        }
    }
}
