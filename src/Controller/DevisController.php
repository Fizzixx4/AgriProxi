<?php

namespace App\Controller;

use App\Entity\Devis;
use App\Entity\LigneDevis;
use App\Repository\DevisRepository;
use App\Repository\LigneDevisRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class DevisController extends AbstractController
{
    //affichage page devis
    #[Route('/devis', name: 'app_devis')]
    public function index(LigneDevisRepository $ligneRepo): Response
    {
        $user = $this->getUser(); //user connecté
        $entreprise = $user->getEntreprise(); //entreprise de user
        $id_entreprise = $entreprise->getId(); // id de l'entreprise de user
        $type = $entreprise->getTypeEntreprise(); //type d'entreprise
        $typeid = $type->getId(); //id du type

        if ($typeid == 2) { //si restaurateur
            $devis = $entreprise->getDevis();
            foreach ($devis as $ligneDevis) {
                $ligneDevis = $ligneDevis->getLigneDevis();
                foreach ($ligneDevis as $produit) {
                    $produit->ligneDevis = $ligneDevis; //ajout de la propriété LigneDevis
                    $produits[] = $produit;
                }
            }
            if ($produits ?? false) {
                $grouped_produits = array_reduce($produits, function ($carry, $item) {
                    $carry[$item->getEntrepriseId()][] = $item;
                    return $carry;
                }, []);
            }
        } else {
            $pouet = $ligneRepo->findBy(['entrepriseId' =>  $id_entreprise]);
            $grouped_produits = array_reduce($pouet, function ($carry, $item) {
                $carry[$item->getUsers()->getId()][] = $item;
                return $carry;
            }, []);
        }
        if (empty($grouped_produits)) { //si on à encore rien mis dans notre panier
            return $this->render('devis/index.html.twig', [
                "message" => "Vous n'avez pas encore ajouté de produit à votre liste de devis !"
            ]);
        } else {
            return $this->render('devis/index.html.twig', [
                "type" => $typeid, //type d'entreprise de l'user
                "grouped_produits" =>  $grouped_produits, //les lignes de demande de devis
            ]);
        };
    }

    //Restaurateur : ajouter un produit d'un producteur a la liste devis
    #[Route('/devis/add/{id}', name: 'add_devis')]
    public function addProductDevis($id, ProduitRepository $produitRepo, DevisRepository $devisRepo, LigneDevisRepository $ligneRepo)
    {
        // Je récupère le produit à ajouter au panier
        $product = $produitRepo->find($id);
        $entreprise_id = $product->getEntreprise()->getId();

        // Récupérez ou créez un panier pour l'utilisateur connecté
        $user = $this->getUser();
        $entreprise = $user->getEntreprise();

        $line = new LigneDevis();
        $line->setProduit($product);
        $line->setUsers($user);
        $line->setEtat(0);
        $line->setEntrepriseId($entreprise_id); //Produit->entreprise->id
        // Vérifie si une ligne de devis existe déjà pour le user en cours avec un même produit->entreprise->id
        $existingLine = $ligneRepo->findOneBy(['users' => $user, 'entrepriseId' => $entreprise_id]);

        // Si une ligne existe déjà, récupérez l'id de devis de cette ligne
        if ($existingLine) {
            $devis = $existingLine->getDevis();
        } else {
            // Sinon, créez un nouveau devis pour l'entreprise en cours
            $devis = new Devis();
            $devis->setEntreprise($entreprise);
            $devisRepo->save($devis, true);
        }

        //je lie ligne devis à devis
        $line->setDevis($devis);
        $ligneRepo->save($line, true);
        return $this->redirectToRoute('app_devis');
    }

    #[Route('/savedevis', name: 'save_devis')]
    public function saveDevis(HttpFoundationRequest $request, LigneDevisRepository $ligneRepo)
    { {
            $produits = [];
            $grouped_produits = [];
            $user = $this->getUser();
            $entreprise = $user->getEntreprise();
            $id_entreprise = $entreprise->getId();
            $devis = $entreprise->getDevis();
            foreach ($devis as $ligneDevis) {
                $ligneDevis = $ligneDevis->getLigneDevis();
                foreach ($ligneDevis as $produit) {
                    $produits[] = $produit->getProduit();
                }
            }
            $grouped_produits = array_reduce($produits, function ($carry, $item) {
                $carry[$item->getEntreprise()->getId()][] = $item;
                return $carry;
            }, []);

            $product_id = $request->request->get('pro');
            foreach ($grouped_produits as $produit) {
                foreach ($produits as $pro) {
                    if ($request->isMethod('POST') && $request->request->get('quantity')) {
                        $quantity = $request->request->get('quantity');
                        // $price = $request->request->get('price');
                        if ($pro->getId() == $product_id) {
                            $ligneDevis = $ligneRepo->findOneBy(['produit' => $product_id]);
                            $ligneDevis->setQuantity($quantity);
                            // $ligneDevis->setPrice($price);
                            $ligneDevis->setEtat(1);
                            $ligneRepo->save($ligneDevis, true);
                        }
                    }
                    if ($request->isMethod('POST') && $request->request->get('accepte')) {
                        // $price = $request->request->get('price');
                        if ($pro->getId() == $product_id) {
                            $ligneDevis = $ligneRepo->findOneBy(['produit' => $product_id]);
                            $ligneDevis->setEtat(3);
                            $ligneRepo->save($ligneDevis, true);
                        }
                    }
                    if ($request->isMethod('POST') && $request->request->get('reffuse')) {
                        // $price = $request->request->get('price');
                        if ($pro->getId() == $product_id) {
                            $ligneDevis = $ligneRepo->findOneBy(['produit' => $product_id]);
                            $ligneDevis->setEtat(5);
                            $ligneRepo->save($ligneDevis, true);
                        }
                    }
                }
            }
            $lignedevis = $ligneRepo->findBy(['entrepriseId' =>  $id_entreprise]);
            foreach ($lignedevis as $ligne) {
                $id = $ligne->getProduit();
                $ids = $id->getId();
                if ($request->isMethod('POST') && $request->request->get('price')) {
                    $price = $request->request->get('price');
                    if ($ids == $product_id) {
                        $ligneDevis = $ligneRepo->findOneBy(['produit' => $product_id]);
                        $ligneDevis->setPrice($price);
                        $ligneDevis->setEtat(2);
                        $ligneRepo->save($ligneDevis, true);
                    }
                }
                if ($request->isMethod('POST') && $request->request->get('valid')) {
                    if ($ids == $product_id) {
                        $ligneDevis = $ligneRepo->findOneBy(['produit' => $product_id]);
                        $ligneDevis->setEtat(4);
                        $ligneRepo->save($ligneDevis, true);
                    }
                    // }
                }
            }
            return $this->redirectToRoute('app_devis');
        }
    }
}
