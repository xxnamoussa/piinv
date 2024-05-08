<?php

namespace App\Controller;

use App\Entity\Don;
use App\Form\DonType;
use App\Repository\DonRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Bridge\Twilio\TwilioTransport;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class DonController extends AbstractController
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    #[Route('/don', name: 'app_don')]
    public function index(DonRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $dons = $repository->findAll();
        return $this->render('don/index.html.twig', ['dons' => $dons]);
    }

    #[Route('/don/ajouter', name: 'don_ajouter')]
    public function ajouter(ManagerRegistry $doctrine, Request $request): Response
    {
        $don = new Don();
        $form = $this->createForm(DonType::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($don);
            $entityManager->flush();

            $this->sendEmail($don->getDonateur());

            return $this->redirectToRoute('app_don');
        }

        return $this->render('don/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/don/{id}', name: 'don_voir')]
    public function voir(Don $don): Response
    {
        return $this->render('don/show.html.twig', ['don' => $don]);
    }

    #[Route('/don/{id}/modifier', name: 'don_modifier')]
    public function modifier(Don $don, Request $request): Response
    {
        $form = $this->createForm(DonType::class, $don);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('app_don');
        }

        return $this->render('don/edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Update'
        ]);
    }

    #[Route('/don/{id}/supprimer', name: 'don_supprimer')]
    public function supprimer(Don $don): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($don);
        $entityManager->flush();
        return $this->redirectToRoute('app_don');
    }

    public function sendEmail($emaildon)
    {
        $email = (new Email())
            ->from('yourweb@test.com')
            ->to($emaildon)          
            ->subject('Donation Success!')
            ->text('Donation Success! Text')
            ->html('<p>Html example</p>');

        $this->mailer->send($email);
    }

    public function sendSms($phonedon, TexterInterface $texter)
    {
        $transport = new TwilioTransport('US9a928c1a9488bb0c5a367344c080b985', '9ZGKG5G7XHQP31N34SAG437Q', '+17206084561');
        $message = new SmsMessage('A new sms for your phone!');
        $message->recipient($phonedon);
        $texter->send($message, $transport);
    }

    
}
