<?php

namespace App\Controller\Purchase;

use DateTime;
use App\Entity\Purchase;
use App\Cart\CartService;
use App\Entity\PurchaseItem;
use App\Form\CartConfirmationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PurchaseConfirmationController extends AbstractController
{
    protected $cartService;
    protected $em;

    public function __construct( CartService $cartService, EntityManagerInterface $em)
    {
        $this->cartService = $cartService;
        $this->em = $em;

    }

    /**
     * @Route("/purchase/confirm", name="purchase_confirm")
     * @IsGranted("ROLE_USER", message="Vous devez être connecté pour confirmer une commande")
     */
    public function confirm(Request $request)
    {
        //1? Lire les donnée du formulaires ? 
        // FormFactoryInterface / Request 
        $form = $this->createForm(CartConfirmationType::class);

        $form->handleRequest($request);

        // 2. Si le formulaire n'a pas été osumis : dégager 
        if (!$form->isSubmitted()) {
            // Message flash 
            $this->addFlash('warning', 'Vous devez remplir les formumaire de confirmation');

            return $this->redirectToRoute('cart_show');
        }

        // 3. Si je suis pas connecté : dégagé (Sécurity)
        $user = $this->getUser();

        //4. S'il n'y a pas de produits dans mon paner : dégager (CartService)
        $cartItems = $this->cartService->getDetailedCartItems();

        if (count($cartItems) === 0) {
            $this->addFlash('warning', 'Vous ne pouvez confirmer une commande avec un panier vide');

            return $this->redirectToRoute('cart_show');
           
        }

        //5. Nous allons créer une Purchase 
        /** @var Purchase */
        $purchase = $form->getData();

        //6. Nous allons les lier avec les l'utilisateur actuellement connecté (Sécurity)
        $purchase->setUser($user)
            ->setPurchasedAt(new DateTime())
            ->setTotal($this->cartService->getTotal());

            $this->em->persist($purchase);

        //7. Nous allons la lier avec les produits qui sont dans le panier (CartService)
        
        foreach($this->cartService->getDetailedCartItems() as $cartItem) {
            $purchaseItem = new PurchaseItem;
            $purchaseItem->setPurchase($purchase)
                ->setProduct($cartItem->product)
                ->setProductName($cartItem->product->getPrice())
                ->setQuantity($cartItem->qty)
                ->setTotal($cartItem->getTotal())
                ->setProductPrice($cartItem->product->getPrice());

            

            $this->em->persist($purchaseItem);
        }



        //8. Nous allons enregistrer la commande (EntityManagerInterfarce)
        $this->em->flush();

        $this->cartService->empty();

        $this->addFlash('success', "La commande a bien été enregistrée");
        
        
        return $this->redirectToRoute('purchase_index');
    }
}