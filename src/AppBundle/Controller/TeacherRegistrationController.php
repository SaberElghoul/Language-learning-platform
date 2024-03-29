<?php

// src/AppBundle/Controller/RegistrationProController.php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\RegistrationController as BaseController;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Form\Type\RegistrationFormType;
use AppBundle\Form\ProRegistrationType;
use FOS\UserBundle\Event\FormEvent;

/**
 * Class RegistrationProController
 * @package AppBundle\Controller
 */

class TeacherRegistrationController extends BaseController

{
    public function __construct() {
    }

    /**
     * @Route("/TeacherRegistration/", name="TeacherRegistration")
     */

    public function registerAction(Request $request)
    {   /** @var $formFactory FactoryInterface */
        $formFactory = $this->get('form.factory');
        /** @var $userManager UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->createUser();
        $user->setEnabled(true);
        $user->addRole("ROLE_TEACHER");

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $formFactory->create('AppBundle\Form\RegistrationType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $file=$user->getImage();
                $fileName=md5(uniqid()).'.'.$file->guessExtension();
                $file->move(
                    $this->getParameter('images_directory'),$fileName
                );
                $user->setImage($fileName);


                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

                $userManager->updateUser($user);

                /*****************************************************
                 * Add new functionality (e.g. log the registration) *
                 *****************************************************/
                $this->container->get('logger')->info(
                    sprintf("New user registration: %s", $user)
                );

                if (null === $response = $event->getResponse()) {
                    $url = $this->generateUrl('fos_teacher_profile_show');
                    $response = new RedirectResponse($url);
                }

                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                return $response;
            }

            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_FAILURE, $event);

            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }

        return $this->render('@FOSUser/Registration/teacher_register.html.twig', array(
            'form' => $form->createView(),
        ));

    }
}