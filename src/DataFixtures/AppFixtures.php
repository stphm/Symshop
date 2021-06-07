<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Product;
use Liior\Faker\Prices;
use App\Entity\Category;
use App\Entity\Purchase;
use App\Entity\PurchaseItem;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class AppFixtures extends Fixture
{
    protected $slugger;
    protected $encoder;

    public function __construct(SluggerInterface $slugger, UserPasswordEncoderInterface $encoder)
    {
        $this->slugger = $slugger;
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');
        $faker->addProvider(new Prices($faker));
        //$faker->addProvider(new \Bezhanov\Faker\Provider\Commerce($faker));
        $faker->addProvider(new \Bluemmb\Faker\PicsumPhotosProvider($faker));


        $admin = new User;

        $hash = $this->encoder->encodePassword($admin, "password");

        $admin
            ->setEmail("admin@gmail.com")
            ->setPassword($hash)
            ->setFullName("Admin")
            ->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        $users = [];

        for($u = 0; $u < 5; $u++) {
            $user = new User;

            $hash = $this->encoder->encodePassword($user, "password");
            $user
                ->setEmail("user$u@gmail.com")
                ->setFullName($faker->name())
                ->setPassword($hash);
            
            $users[] = $user;

            $manager->persist($user);
        }

        $products = [];

        for ($i=0; $i < 3; $i++) { 
            $category = new Category;
            $category
                ->setName($faker->sentence())
                ->setSlug(strtolower($this->slugger->slug($category->getName())));
            $manager->persist($category);
     

            for ($p = 1; $p < mt_rand(15, 20); $p++) {
                $product = new Product;
                $product
                    //->setName($faker->productName())
                    ->setName($faker->sentence())
                    ->setPrice($faker->price(4000, 20000))
                    ->setSlug(strtolower($this->slugger->slug($product->getName())))
                    ->setCategory($category)
                    ->setShortDescription($faker->paragraph())
                    ->setPicture($faker->imageUrl(400, 400, true));

                $products[] = $product;
                
                $manager->persist($product);
            }
        }

       for($p = 0; $p < mt_rand(20,40); $p++) {
           $purchase = new Purchase;

           $purchase
                ->setFullName($faker->name)
                ->setAdress($faker->streetAddress)
                ->setPostalCode($faker->postcode)
                ->setCity($faker->city)
                ->setUser($faker->randomElement($users))
                ->setTotal(mt_rand(2000, 30000))
                ->setPurchasedAt($faker->dateTimeBetween('-6 months'));

            $selectedProducts = $faker->randomElements($products, mt_rand(3,5));

            foreach ($selectedProducts as $product) {
                $purchaseItem = new PurchaseItem;
                $purchaseItem
                    ->setProduct($product)
                    ->setQuantity(mt_rand(1,5))
                    ->setProductName($product->getName())
                    ->setProductPrice($product->getPrice())
                    ->setTotal(
                        $purchaseItem->getProductPrice() * $purchaseItem->getQuantity()
                    )
                    ->setPurchase($purchase);

                $manager->persist($purchaseItem);
            }

            if ($faker->boolean(90)) {
                $purchase->setStatus(Purchase::SATUS_PAID);
            }

            $manager->persist($purchase);
       }

        $manager->flush();
    }
}
