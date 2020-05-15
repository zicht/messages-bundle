<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;

/**
 * Class RcController
 *
 * @Route(service="zicht_messages.controller.rc")
 */
class RcController
{
    /** @var string */
    protected $cacheDir;

    /**
     * RcController constructor.
     *
     * @param string $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return JsonResponse
     *
     * @Route("/rc")
     */
    public function flushAction()
    {
        $helper = new FlushCatalogueCacheHelper($this->cacheDir);
        $removedCount = $helper();

        $response = [
            'info' => sprintf('Looking in %s', $this->cacheDir),
            'message' => sprintf('OK (%d cache items removed)', $removedCount),
        ];

        return new JsonResponse($response);
    }
}
