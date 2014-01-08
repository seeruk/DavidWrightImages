<?php

/**
 * David Wright Images
 *
 * @author  Elliot Wright <wright.elliot@gmail.com>
 * @since   2013
 * @package DWI
 */

namespace DWI\PortfolioBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\NoResultException;
use DWI\CoreBundle\HttpFoundation\RestJsonResponse;
use DWI\PortfolioBundle\Entity\Gallery;

/**
 * Image Controller
 */
class ImageController extends Controller
{
    /**
     * Upload Images
     *
     * @param  Gallery $gallery
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @throws Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function uploadImagesFormAction(Gallery $gallery)
    {
        if ( ! $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        if ( ! $gallery) {
            throw $this->createNotFoundException('That gallery doesn\'t exist!');
        }

        $request = $this->get('request');
        $form    = $this->get('dwi_portfolio.upload_image_form');

        $uip = $this->get('dwi_portfolio.upload_image_presenter')
            ->setVariable('form', $form)
            ->setVariable('gallery', $gallery);

        return $this->render('DWIPortfolioBundle:Portfolio/Admin:image-upload.html.twig', array(
            'model' => $uip->prepareView(),
        ));
    }


    /**
     * Upload Image
     *
     * @param  integer $id
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function uploadImageAction($id)
    {
        $request  = $this->get('request');
        $response = new RestJsonResponse();

        if ( ! $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            return $response->addError(RestJsonResponse::GENERIC_BAD_AUTH)
                ->setStatusCode(403);
        } elseif ('POST' !== $request->getMethod()) {
            return $response->addError(RestJsonResponse::BAD_REQUEST_METHOD)
                ->setStatusCode(400);
        }

        $gr = $this->get('dwi_portfolio.gallery_repository');

        // Try fetch gallery
        try {
            $gallery = $gr->findById($id);
        } catch (NoResultException $e) {
            return $response->addError(RestJsonResponse::ENTITY_NOT_FOUND)
                ->setStatusCode(404);
        }

        $form = $this->get('dwi_portfolio.upload_image_form')
            ->handleRequest($request);

        if ($form->isValid()) {
            $image = $form->getData();
            $image->setGallery($gallery);

            $this->get('dwi_portfolio.image_repository')
                ->persist($image);

            $response->setData(array(
                'id' => $image->getId(),
            ));
        } else {
            if (count($form->getErrors())) {
                foreach ($form->getErrors() as $error) {
                    $response->addError($error->getMessage());
                }
            } else {
                $response->addError(RestJsonResponse::GENERIC_BAD_REQUEST);
            }

            $response->setStatusCode(400);
        }

        return $response;
    }
}