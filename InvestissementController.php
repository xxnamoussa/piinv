<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Notifier\TexterInterface;
// for sendin sms
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Bridge\Twilio\TwilioTransport;
use Symfony\Component\Notifier\Bridge\Twilio\TwilioOptions;

// for sending emails
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use App\Entity\Investissement;
use App\Form\InvestissementType;
use App\Repository\InvestissementRepository;
use Doctrine\ORM\EntityManagerInterface;

// use Symfony\Component\Notifier\TexterInterface;





#[Route('/investissement')]
class InvestissementController extends AbstractController
{
    public function __construct(MailerInterface $mailer)
    {

        $this->mailer = $mailer;
        // $this->texter = $texter;

    }







    #[Route('/', name: 'app_investissement_index', methods: ['GET'])]
    public function index(InvestissementRepository $investissementRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        return $this->render('investissement/index.html.twig', [
            'investissements' => $investissementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_investissement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $investissement = new Investissement();
        $form = $this->createForm(InvestissementType::class, $investissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($investissement);
            $entityManager->flush();

            return $this->redirectToRoute('app_investissement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('investissement/new.html.twig', [
            'investissement' => $investissement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_investissement_show', methods: ['GET'])]
    public function show(Investissement $investissement): Response
    {
        return $this->render('investissement/show.html.twig', [
            'investissement' => $investissement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_investissement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Investissement $investissement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InvestissementType::class, $investissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_investissement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('investissement/edit.html.twig', [
            'investissement' => $investissement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_investissement_delete', methods: ['POST'])]
    public function delete(Request $request, Investissement $investissement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$investissement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($investissement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_investissement_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/generate-pdf', name: 'app_investissement_generate_pdf')]
public function generatePDF(Investissement $investissement): Response
{
    // Création d'une instance de Dompdf avec ses options par défaut
    $dompdf = new Dompdf();

    // Récupération du contenu HTML à mettre dans le PDF (utilisation de la nouvelle vue Twig)
    $html = $this->renderView('investissement/generate_pdf.html.twig', [
        'investissement' => $investissement,
    ]);

    // Chargement du contenu HTML dans Dompdf
    $dompdf->loadHtml($html);

    // Réglage du format et des marges du document
    $dompdf->setPaper('A4', 'portrait');

    // Rendu du PDF
    $dompdf->render();

    // Envoi du PDF en réponse
    return new Response(
        $dompdf->output(),
        Response::HTTP_OK,
        array(
            'Content-Type' => 'application/pdf',
        )
    );
}

#[Route('/favoris', name: 'app_favoris')]
public function favoris(): Response
{
    $user = $this->getUser();

    if (!$user) {
        // Gérer le cas où l'utilisateur n'est pas connecté
        // Redirection vers la page de connexion ou affichage d'un message d'erreur
        return $this->redirectToRoute('app_login');
    }

    // Récupérer les investissements favoris de l'utilisateur
    $favoris = $user->getFavoris();

    return $this->render('investissement/favoris.html.twig', [
        'favoris' => $favoris,
    ]);
}






//funcion sendmails
public function sendEmail($emailinvestissement)
{

    // dd($emailinvestissement);

    $email = (new Email())
    ->from('yourweb@test.com')
    ->to($emailinvestissement)          
    ->subject('inves Success!')
    ->text('inves Success! Text')
    ->html('<p>Html example</p>');

    // $mailer->send($email);

    $this->mailer->send($email);

}

//funcion send sms, you need phone number in your entity
public function sendSms($nominvestissement, TexterInterface $texter)
{
    // Récupérer le transport Twilio
    $transport = new TwilioTransport('US9a928c1a9488bb0c5a367344c080b985', '9ZGKG5G7XHQP31N34SAG437Q', '+17206084561');

    // Créer un message SMS 
    $message = new SmsMessage('A new sms for your phone!');

    // Ajouter le numéro de téléphone du donateur comme destinataire
    $message->recipient($nominvestissement);

    // Envoyer le SMS
    $texter->send($message, $transport);
}



}