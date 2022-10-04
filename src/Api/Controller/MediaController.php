<?php declare(strict_types=1);

namespace Sas\Esd\Api\Controller;

use Sas\Esd\Service\EsdMediaService;
use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\Exception\MissingFileExtensionException;
use Shopware\Core\Content\Media\File\FileNameProvider;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class MediaController extends AbstractController
{
    private EsdMediaService $esdMediaService;

    private FileNameProvider $fileNameProvider;

    public function __construct(EsdMediaService $esdMediaService, FileNameProvider $fileNameProvider)
    {
        $this->esdMediaService = $esdMediaService;
        $this->fileNameProvider = $fileNameProvider;
    }

    /**
     * @Route("/api/_action/media/esd/provide-name", name="api.action.media-esd.provide-name", methods={"GET"})
     */
    public function provideName(Request $request, Context $context): JsonResponse
    {
        $fileName = (string) $request->query->get('fileName');
        $fileExtension = (string) $request->query->get('extension');
        $mediaId = $request->query->has('mediaId') ? (string) $request->query->get('mediaId') : null;

        if ($fileName === '') {
            throw new EmptyMediaFilenameException();
        }
        if ($fileExtension === '') {
            throw new MissingFileExtensionException();
        }

        $name = $context->scope(Context::SYSTEM_SCOPE, function ($context) use ($fileName, $fileExtension, $mediaId) {
            return $this->fileNameProvider->provide($fileName, $fileExtension, $mediaId, $context);
        });

        return new JsonResponse(['fileName' => $name]);
    }

    /**
     * Get media entity by file name and extension
     *
     * @Route("/api/_action/media/esd", name="api.action.media-esd.get", methods={"GET"})
     */
    public function getAdminSystemMedia(Request $request, Context $context): JsonResponse
    {
        $fileName = (string) $request->get('fileName');
        $fileExtension = (string) $request->get('extension');

        if ($fileName === '') {
            throw new EmptyMediaFilenameException();
        }
        if ($fileExtension === '') {
            throw new MissingFileExtensionException();
        }

        $media = $this->esdMediaService->getAdminSystemMedia($fileName, $fileExtension, $context);

        return new JsonResponse($media);
    }

    /**
     * Get media entity by id
     *
     * @Route("/api/_action/media/esd/{mediaId}", name="api.action.media-esd.get.byId", methods={"GET"})
     */
    public function getAdminSystemMediaById(string $mediaId, Context $context): JsonResponse
    {
        $media = $this->esdMediaService->getAdminSystemMediaById($mediaId, $context);

        return new JsonResponse($media);
    }
}
