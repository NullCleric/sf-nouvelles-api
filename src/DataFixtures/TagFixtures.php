<?php

namespace App\DataFixtures;

use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TagFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tags = [
            'Space Opera' => 'space-opera',
            'Anticipation' => 'anticipation',
            'Dystopie' => 'dystopie',
            'Cyberpunk' => 'cyberpunk',
            'Hard Science' => 'hard-science',
            'Post-apocalyptique' => 'post-apocalyptique',
            'Voyage temporel' => 'voyage-temporel',
            'Premier contact' => 'premier-contact',
            'Intelligence artificielle' => 'intelligence-artificielle',
            'Utopie' => 'utopie',
            'Science-fiction militaire' => 'science-fiction-militaire',
            'Biopunk' => 'biopunk',
            'Transhumanisme' => 'transhumanisme',
            'Réalité virtuelle' => 'realite-virtuelle',
            'Univers parallèles' => 'univers-paralleles',
        ];

        foreach ($tags as $title => $slug) {
            $tag = new Tag();
            $tag->setTitle($title);
            $tag->setSlug($slug);
            $manager->persist($tag);
        }

        $manager->flush();
    }
}
