<?php declare(strict_types=1);

namespace Sas\Esd\Api\Controller;

use Sas\Esd\Service\EsdMailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class EsdSendMailController extends AbstractController
{
    /**
     * @var EsdMailService
     */
    private $esdMailService;

    public function __construct(EsdMailService $esdMailService)
    {
        $this->esdMailService = $esdMailService;
    }

    /**
     * @Route("/api/v{version}/esd-mail/download", name="api.action.sas-esd.send-mail-download", methods={"POST"})
     */
    public function sendMailDownload(Request $request, Context $context): Response
    {
        $orderId = $request->get('orderId');
        if (!empty($orderId)) {
            $this->esdMailService->sendMailDownload($orderId, $context);

            return new Response(null, Response::HTTP_OK);
        }

        return new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @Route("/api/v{version}/esd-mail/serial", name="api.action.sas-esd.send-mail-serial", methods={"POST"})
     */
    public function sendMailSerial(Request $request, Context $context): Response
    {
        $orderId = $request->get('orderId');
        if (!empty($orderId)) {
            $this->esdMailService->sendMailSerial($orderId, $context);

            return new Response(null, Response::HTTP_OK);
        }

        return new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @Route("/api/v{version}/esd-mail/{orderId}/buttons", name="api.action.sas-esd.mail-buttons", methods={"GET"})
     */
    public function getAllowMailButtons(string $orderId, Context $context): JsonResponse
    {
        $enableMailButton = $this->esdMailService->enableMailButtons($orderId, $context);

        return new JsonResponse($enableMailButton);
    }
}
