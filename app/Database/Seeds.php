<?php

namespace App\Database;

use PDO;

class Seeds {
    public static function seed(PDO $pdo): void {
        $movies = [
            [
                'title' => 'Tears of Steel',
                'slug' => 'tears-of-steel',
                'tagline' => 'A dystopian sci-fi showdown at the Oude Kerk.',
                'description' => 'In a dystopian future, a group of rebel warriors and scientists gather at the Amsterdam Oude Kerk to stage a crucial memory recreation that could save the world from robotic destruction.',
                'poster_url' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80',
                'backdrop_url' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1600&auto=format&fit=crop&q=80',
                'video_url' => 'https://archive.org/download/Tears-of-Steel/tears_of_steel_720p.mp4',
                'duration_seconds' => 734,
                'release_year' => 2024,
                'age_rating' => 'PG-13',
                'match_percentage' => 99,
                'genre' => 'Sci-Fi',
                'category' => 'trending',
                'featured' => 1,
                'director' => 'Ian Hubert',
                'cast_members' => 'Derek de Lint, Sergio Hasselbaink, Rogier Schippers, Vanja Rukavina'
            ],
            [
                'title' => 'Cosmos Laundromat',
                'slug' => 'cosmos-laundromat',
                'tagline' => 'First Cycle: On a desolate island, a suicidal sheep meets a mysterious salesman.',
                'description' => 'On a desolate windswept island, Franck, a depressed sheep, meets a mysterious salesman named Victor who offers him the gift of infinite alternate lives across the cosmos.',
                'poster_url' => 'https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?w=600&auto=format&fit=crop&q=80',
                'backdrop_url' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1600&auto=format&fit=crop&q=80',
                'video_url' => 'https://archive.org/download/BigBuckBunny_124/Content/big_buck_bunny_720p_surround.mp4',
                'duration_seconds' => 596,
                'release_year' => 2023,
                'age_rating' => 'TV-MA',
                'match_percentage' => 97,
                'genre' => 'Animation',
                'category' => 'trending',
                'featured' => 0,
                'director' => 'Mathieu Auvray',
                'cast_members' => 'Pierre Bokma, Reinout Scholten van Aschat'
            ],
            [
                'title' => 'Sintel: The Dragon Whisperer',
                'slug' => 'sintel',
                'tagline' => 'The epic journey of a lonely warrior across frozen lands.',
                'description' => 'A lonely young woman searches the dangerous wilderness for a baby dragon she befriended and nursed back to health after it was violently snatched away by a massive winged predator.',
                'poster_url' => 'https://images.unsplash.com/photo-1514539079130-25950c84af65?w=600&auto=format&fit=crop&q=80',
                'backdrop_url' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1600&auto=format&fit=crop&q=80',
                'video_url' => 'https://archive.org/download/Sintel/sintel-2048-surround.mp4',
                'duration_seconds' => 888,
                'release_year' => 2024,
                'age_rating' => 'PG-13',
                'match_percentage' => 96,
                'genre' => 'Fantasy',
                'category' => 'popular',
                'featured' => 0,
                'director' => 'Colin Levy',
                'cast_members' => 'Halina Reijn, Thom Hoffman'
            ],
            [
                'title' => 'Cyber City: Outlaws',
                'slug' => 'cyber-city-outlaws',
                'tagline' => 'Neon skies, synthetic dreams, and high-stakes heists.',
                'description' => 'A cyber-enhanced rogue team navigates the sprawling megalopolis of Neo-Veridia, executing dangerous high-tech heists under the surveillance of ruthless mega-corporations.',
                'poster_url' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&auto=format&fit=crop&q=80',
                'backdrop_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1600&auto=format&fit=crop&q=80',
                'video_url' => 'https://archive.org/download/ElephantsDream/ed_1024_512kb.mp4',
                'duration_seconds' => 653,
                'release_year' => 2024,
                'age_rating' => 'R',
                'match_percentage' => 95,
                'genre' => 'Action',
                'category' => 'popular',
                'featured' => 0,
                'director' => 'Bassam Kurdali',
                'cast_members' => 'Tygo Gernandt, Cas Jansen'
            ],
            [
                'title' => 'Big Buck Odyssey',
                'slug' => 'big-buck-odyssey',
                'tagline' => 'Peace was never an option in the enchanted forest.',
                'description' => 'When three bullying woodland rodents harass innocent forest creatures and destroy beautiful butterfly habitats, a giant fluffy rabbit decides it is time for comedic retribution.',
                'poster_url' => 'https://images.unsplash.com/photo-1535905557558-afc4877a26fc?w=600&auto=format&fit=crop&q=80',
                'backdrop_url' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1600&auto=format&fit=crop&q=80',
                'video_url' => 'https://archive.org/download/BigBuckBunny_124/Content/big_buck_bunny_720p_surround.mp4',
                'duration_seconds' => 596,
                'release_year' => 2023,
                'age_rating' => 'G',
                'match_percentage' => 94,
                'genre' => 'Comedy',
                'category' => 'popular',
                'featured' => 0,
                'director' => 'Sacha Goedegebure',
                'cast_members' => 'Jan Morgenstern, Ton Roosendaal'
            ],
            [
                'title' => 'The Chrono Heist',
                'slug' => 'chrono-heist',
                'tagline' => 'Stealing time before the timeline collapses.',
                'description' => 'A team of temporal thieves enters a cascading time anomaly to intercept an experimental quantum core before a corrupt syndicate weaponizes reality itself.',
                'poster_url' => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=600&auto=format&fit=crop&q=80',
                'backdrop_url' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=1600&auto=format&fit=crop&q=80',
                'video_url' => 'https://archive.org/download/Tears-of-Steel/tears_of_steel_720p.mp4',
                'duration_seconds' => 734,
                'release_year' => 2024,
                'age_rating' => 'PG-13',
                'match_percentage' => 98,
                'genre' => 'Action',
                'category' => 'action',
                'featured' => 0,
                'director' => 'Elena Vance',
                'cast_members' => 'Marcus Holloway, Wrench, Clara Lille'
            ],
            [
                'title' => 'Nebula Protocol',
                'slug' => 'nebula-protocol',
                'tagline' => 'Lost at the edge of known space.',
                'description' => 'Deep space explorers on board the orbital research vessel Icarus discover an ancient alien artifact transmitting a hypnotic signal that alters their perception of time and space.',
                'poster_url' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=600&auto=format&fit=crop&q=80',
                'backdrop_url' => 'https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?w=1600&auto=format&fit=crop&q=80',
                'video_url' => 'https://archive.org/download/Sintel/sintel-2048-surround.mp4',
                'duration_seconds' => 888,
                'release_year' => 2024,
                'age_rating' => 'TV-MA',
                'match_percentage' => 93,
                'genre' => 'Sci-Fi',
                'category' => 'action',
                'featured' => 0,
                'director' => 'Julian Drake',
                'cast_members' => 'Sarah Connor, Kyle Reese, John Connor'
            ],
            [
                'title' => 'Shadows of Manhattan',
                'slug' => 'shadows-of-manhattan',
                'tagline' => 'The city never sleeps. Neither do its detectives.',
                'description' => 'A hard-boiled detective races against the clock to unravel a conspiracy that runs from the darkest underground subways to the penthouse suites of Wall Street.',
                'poster_url' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=600&auto=format&fit=crop&q=80',
                'backdrop_url' => 'https://images.unsplash.com/photo-1514539079130-25950c84af65?w=1600&auto=format&fit=crop&q=80',
                'video_url' => 'https://archive.org/download/ElephantsDream/ed_1024_512kb.mp4',
                'duration_seconds' => 653,
                'release_year' => 2023,
                'age_rating' => 'R',
                'match_percentage' => 91,
                'genre' => 'Drama',
                'category' => 'drama',
                'featured' => 0,
                'director' => 'Vince Gilligan',
                'cast_members' => 'Bryan Cranston, Aaron Paul, Bob Odenkirk'
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO movies (title, slug, tagline, description, poster_url, backdrop_url, video_url, duration_seconds, release_year, age_rating, match_percentage, genre, category, featured, director, cast_members) VALUES (:title, :slug, :tagline, :description, :poster_url, :backdrop_url, :video_url, :duration_seconds, :release_year, :age_rating, :match_percentage, :genre, :category, :featured, :director, :cast_members)");

        foreach ($movies as $m) {
            $stmt->execute($m);
        }
    }
}
