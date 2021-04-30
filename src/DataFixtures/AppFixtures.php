<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Liior\Faker\Prices;
use Symfony\Component\String\Slugger\SluggerInterface;


class AppFixtures extends Fixture
{
    protected $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');
        $faker->addProvider(new Prices($faker));
        //$faker->addProvider(new \Bezhanov\Faker\Provider\Commerce($faker));
        $faker->addProvider(new \Bluemmb\Faker\PicsumPhotosProvider($faker));

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
    
                $manager->persist($product);
            }
        }

       

        $manager->flush();
    }
}
