<?php

namespace App\Seed;

/**
 * Hand-written demo data for app:seed. Plain arrays, no logic.
 *
 * Images: Steam CDN artwork, built from the Steam app ID ({id} placeholder). Console
 * and other-launcher products of multi-platform games reuse the Steam artwork.
 * Images belong to their respective owners (noted in the README).
 */
final class SeedCatalog
{
    /** Portrait 600x900 cover, like Eneba's product cards. */
    public const COVER_URL = 'https://shared.steamstatic.com/store_item_assets/steam/apps/{id}/library_600x900.jpg';

    /** Wide hero artwork, used by the homepage banners. */
    public const HERO_URL = 'https://shared.steamstatic.com/store_item_assets/steam/apps/{id}/library_hero.jpg';

    /**
     * 'popular' => true makes a product more likely in the generated past orders,
     * so the best-sellers block has a realistic ranking.
     *
     * @var list<array{name: string, appId: int, platform: string, region: string, msrpCents: int, released: string, description: string, popular?: bool}>
     */
    public const PRODUCTS = [
        ['name' => 'ELDEN RING Steam Key GLOBAL', 'appId' => 1245620, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2022-02-25', 'popular' => true,
            'description' => 'An action RPG from FromSoftware set in the Lands Between, a vast open world written with George R. R. Martin. Explore freely, build your own character and take on demanding bosses.'],
        ['name' => 'ELDEN RING (PS5) PSN Key EUROPE', 'appId' => 1245620, 'platform' => 'PSN', 'region' => 'EUROPE', 'msrpCents' => 5999, 'released' => '2022-02-25',
            'description' => 'The PlayStation 5 edition of FromSoftware\'s open-world action RPG. Rise, Tarnished, and become an Elden Lord.'],
        ['name' => 'Cyberpunk 2077 Steam Key GLOBAL', 'appId' => 1091500, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2020-12-10', 'popular' => true,
            'description' => 'An open-world action-adventure RPG set in Night City, a megalopolis obsessed with power, glamour and body modification. Play as V, a mercenary chasing a one-of-a-kind implant.'],
        ['name' => 'Cyberpunk 2077 (Xbox Series X|S) Xbox Live Key EUROPE', 'appId' => 1091500, 'platform' => 'Xbox', 'region' => 'EUROPE', 'msrpCents' => 5999, 'released' => '2022-02-15',
            'description' => 'The current-gen Xbox edition of CD PROJEKT RED\'s open-world RPG, with faster loading and ray-traced lighting.'],
        ['name' => 'The Witcher 3: Wild Hunt - Complete Edition Steam Key GLOBAL', 'appId' => 292030, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 4999, 'released' => '2015-05-19',
            'description' => 'Geralt of Rivia hunts monsters and searches for his adopted daughter in a war-torn open world. Includes both expansions, Hearts of Stone and Blood and Wine.'],
        ['name' => 'The Witcher 3: Wild Hunt (Nintendo Switch) eShop Key EUROPE', 'appId' => 292030, 'platform' => 'Nintendo', 'region' => 'EUROPE', 'msrpCents' => 3999, 'released' => '2019-10-15',
            'description' => 'The award-winning story-driven RPG on Nintendo Switch, playable anywhere.'],
        ['name' => 'Red Dead Redemption 2 Steam Key GLOBAL', 'appId' => 1174180, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2019-12-05', 'popular' => true,
            'description' => 'Rockstar\'s epic tale of outlaw Arthur Morgan and the Van der Linde gang at the end of the Wild West era. Includes Red Dead Online.'],
        ['name' => 'Red Dead Redemption 2 (Xbox One) Xbox Live Key NORTH AMERICA', 'appId' => 1174180, 'platform' => 'Xbox', 'region' => 'NORTH AMERICA', 'msrpCents' => 5999, 'released' => '2018-10-26',
            'description' => 'The Xbox One edition of Rockstar\'s open-world western.'],
        ['name' => 'Grand Theft Auto V (GTA 5) Steam Key GLOBAL', 'appId' => 271590, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 2999, 'released' => '2015-04-14', 'popular' => true,
            'description' => 'Three very different criminals plan a series of heists across Los Santos and Blaine County. Includes the ever-growing world of GTA Online.'],
        ['name' => 'Grand Theft Auto V (GTA 5) (PS5) PSN Key EUROPE', 'appId' => 271590, 'platform' => 'PSN', 'region' => 'EUROPE', 'msrpCents' => 3999, 'released' => '2022-03-15',
            'description' => 'The expanded and enhanced PS5 version of GTA V, with faster loading and improved visuals.'],
        ['name' => 'Minecraft Dungeons (PC/Xbox) Xbox Live Key GLOBAL', 'appId' => 1672970, 'platform' => 'Xbox', 'region' => 'GLOBAL', 'msrpCents' => 1999, 'released' => '2020-05-26', 'popular' => true,
            'description' => 'A dungeon crawler set in the Minecraft universe. Fight alone or with up to three friends through action-packed, treasure-stuffed levels to defeat the evil Arch-Illager.'],
        ['name' => 'Baldur\'s Gate 3 Steam Key GLOBAL', 'appId' => 1086940, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2023-08-03', 'popular' => true,
            'description' => 'A story-rich party-based RPG set in the Dungeons & Dragons universe. Gather your party and return to the Forgotten Realms in a tale of fellowship, betrayal and survival.'],
        ['name' => 'Baldur\'s Gate 3 (PS5) PSN Key EUROPE', 'appId' => 1086940, 'platform' => 'PSN', 'region' => 'EUROPE', 'msrpCents' => 5999, 'released' => '2023-09-06',
            'description' => 'Larian Studios\' award-winning RPG on PlayStation 5, with split-screen co-op.'],
        ['name' => 'Hogwarts Legacy Steam Key GLOBAL', 'appId' => 990080, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2023-02-10', 'popular' => true,
            'description' => 'An open-world action RPG set in the 1800s wizarding world. Attend classes at Hogwarts, master spells and uncover a hidden truth of the past.'],
        ['name' => 'Stardew Valley Steam Key GLOBAL', 'appId' => 413150, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 1399, 'released' => '2016-02-26',
            'description' => 'Inherit your grandfather\'s old farm plot and turn it into a thriving home. Grow crops, raise animals, fish, mine and befriend the townspeople.'],
        ['name' => 'Stardew Valley (Nintendo Switch) eShop Key EUROPE', 'appId' => 413150, 'platform' => 'Nintendo', 'region' => 'EUROPE', 'msrpCents' => 1399, 'released' => '2017-10-05',
            'description' => 'The beloved country-life RPG on Nintendo Switch, with local and online co-op.'],
        ['name' => 'Hollow Knight Steam Key GLOBAL', 'appId' => 367520, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 1479, 'released' => '2017-02-24',
            'description' => 'A challenging 2D action-adventure through Hallownest, a vast ruined kingdom of insects and heroes. Hand-drawn art and tight combat.'],
        ['name' => 'Hollow Knight (Nintendo Switch) eShop Key NORTH AMERICA', 'appId' => 367520, 'platform' => 'Nintendo', 'region' => 'NORTH AMERICA', 'msrpCents' => 1499, 'released' => '2018-06-12',
            'description' => 'Team Cherry\'s acclaimed metroidvania on Nintendo Switch, including all free content packs.'],
        ['name' => 'Hades Steam Key GLOBAL', 'appId' => 1145360, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 2450, 'released' => '2020-09-17',
            'description' => 'A god-like rogue-like dungeon crawler. Defy the god of the dead as you hack and slash out of the Underworld of Greek myth.'],
        ['name' => 'Hades II Steam Key GLOBAL', 'appId' => 1145350, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 2950, 'released' => '2025-09-25',
            'description' => 'Battle beyond the Underworld as Melinoë, Princess of the Underworld, using dark sorcery to take on the Titan of Time in this sequel to the award-winning rogue-like.'],
        ['name' => 'Sekiro: Shadows Die Twice Steam Key GLOBAL', 'appId' => 814380, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2019-03-22',
            'description' => 'Carve your own path to vengeance as a shinobi in late 1500s Sengoku Japan. Game of the Year 2019 from FromSoftware.'],
        ['name' => 'God of War Steam Key GLOBAL', 'appId' => 1593500, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 4999, 'released' => '2022-01-14',
            'description' => 'Kratos, now a father, journeys with his son Atreus through the Norse realms. A brutal, emotional reimagining of the series.'],
        ['name' => 'God of War Ragnarök (PS5) PSN Key EUROPE', 'appId' => 2322010, 'platform' => 'PSN', 'region' => 'EUROPE', 'msrpCents' => 7999, 'released' => '2022-11-09',
            'description' => 'Kratos and Atreus journey to each of the Nine Realms in search of answers as Asgardian forces prepare for Ragnarök.'],
        ['name' => 'Horizon Zero Dawn Remastered Steam Key GLOBAL', 'appId' => 2561580, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 4999, 'released' => '2024-10-31',
            'description' => 'Aloy\'s journey through a lush post-apocalyptic world ruled by machines, remastered with improved visuals and the Frozen Wilds expansion.'],
        ['name' => 'Marvel\'s Spider-Man Remastered Steam Key GLOBAL', 'appId' => 1817070, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2022-08-12',
            'description' => 'Swing through a vibrant Marvel\'s New York as an experienced Peter Parker fighting crime and iconic villains.'],
        ['name' => 'Ghost of Tsushima Director\'s Cut Steam Key GLOBAL', 'appId' => 2215430, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2024-05-16',
            'description' => 'Samurai Jin Sakai fights the Mongol invasion of Tsushima in 1274. Includes the Iki Island expansion and Legends co-op mode.'],
        ['name' => 'Forza Horizon 5 (PC/Xbox) Xbox Live Key GLOBAL', 'appId' => 1551360, 'platform' => 'Xbox', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2021-11-09',
            'description' => 'Explore the vibrant open-world landscapes of Mexico with hundreds of the world\'s greatest cars.'],
        ['name' => 'Starfield (PC/Xbox Series X|S) Xbox Live Key GLOBAL', 'appId' => 1716740, 'platform' => 'Xbox', 'region' => 'GLOBAL', 'msrpCents' => 6999, 'released' => '2023-09-06',
            'description' => 'Bethesda Game Studios\' first new universe in 25 years. Create a character and explore the settled systems in search of humanity\'s greatest mystery.'],
        ['name' => 'Fallout 4 Steam Key GLOBAL', 'appId' => 377160, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 1999, 'released' => '2015-11-10',
            'description' => 'As the sole survivor of Vault 111, you enter a post-nuclear Boston in search of your son. Build settlements, craft gear and shape the wasteland.'],
        ['name' => 'The Elder Scrolls V: Skyrim Special Edition Steam Key GLOBAL', 'appId' => 489830, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 3999, 'released' => '2016-10-28',
            'description' => 'The epic fantasy open world of Skyrim with remastered art and effects, including the Dawnguard, Hearthfire and Dragonborn add-ons.'],
        ['name' => 'DOOM Eternal Steam Key GLOBAL', 'appId' => 782330, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 3999, 'released' => '2020-03-20',
            'description' => 'Hell\'s armies have invaded Earth. Become the Slayer and rip and tear through demons across dimensions.'],
        ['name' => 'Resident Evil 4 (Remake) Steam Key GLOBAL', 'appId' => 2050650, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 3999, 'released' => '2023-03-24',
            'description' => 'Leon S. Kennedy is sent to rescue the president\'s kidnapped daughter in a remake of the survival-horror classic.'],
        ['name' => 'Resident Evil Village Steam Key GLOBAL', 'appId' => 1196590, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 3999, 'released' => '2021-05-07',
            'description' => 'Ethan Winters searches a mysterious European village for his kidnapped daughter in this first-person survival horror.'],
        ['name' => 'Monster Hunter Wilds Steam Key GLOBAL', 'appId' => 2246340, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 6999, 'released' => '2025-02-28',
            'description' => 'Hunt powerful monsters across a living, shifting ecosystem in the newest entry of Capcom\'s action series, solo or with friends.'],
        ['name' => 'Sea of Thieves (PC/Xbox) Xbox Live Key GLOBAL', 'appId' => 1172620, 'platform' => 'Xbox', 'region' => 'GLOBAL', 'msrpCents' => 3999, 'released' => '2018-03-20',
            'description' => 'A shared-world pirate adventure. Sail, plunder and battle with your crew on the open seas.'],
        ['name' => 'It Takes Two (PC) EA App Key GLOBAL', 'appId' => 1426210, 'platform' => 'EA App', 'region' => 'GLOBAL', 'msrpCents' => 3999, 'released' => '2021-03-26',
            'description' => 'A genre-bending co-op platform adventure about a couple on the verge of divorce, shrunk into dolls. Built purely for two players.'],
        ['name' => 'Split Fiction (PC) EA App Key GLOBAL', 'appId' => 2001120, 'platform' => 'EA App', 'region' => 'GLOBAL', 'msrpCents' => 4999, 'released' => '2025-03-06',
            'description' => 'Two writers trapped in their own sci-fi and fantasy stories must work together to escape, in this co-op adventure from the makers of It Takes Two.'],
        ['name' => 'EA SPORTS FC 25 (PC) EA App Key GLOBAL', 'appId' => 2669320, 'platform' => 'EA App', 'region' => 'GLOBAL', 'msrpCents' => 6999, 'released' => '2024-09-27', 'popular' => true,
            'description' => 'The world\'s game with more than 19,000 licensed players. Play together in the new 5v5 Rush mode, Ultimate Team and Career.'],
        ['name' => 'Mass Effect Legendary Edition (PC) EA App Key GLOBAL', 'appId' => 1328670, 'platform' => 'EA App', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2021-05-14',
            'description' => 'The complete Mass Effect trilogy, remastered and optimized, with over 40 DLC included.'],
        ['name' => 'Assassin\'s Creed Shadows (PC) Ubisoft Connect Key EUROPE', 'appId' => 3159330, 'platform' => 'Ubisoft Connect', 'region' => 'EUROPE', 'msrpCents' => 6999, 'released' => '2025-03-20',
            'description' => 'Play as a lethal shinobi and a legendary samurai in feudal Japan, with seasons that change the world around you.'],
        ['name' => 'Assassin\'s Creed Mirage (PC) Ubisoft Connect Key GLOBAL', 'appId' => 3035570, 'platform' => 'Ubisoft Connect', 'region' => 'GLOBAL', 'msrpCents' => 4999, 'released' => '2023-10-05',
            'description' => 'Basim, a cunning street thief, becomes a master assassin in ninth-century Baghdad. A return to the series\' stealth roots.'],
        ['name' => 'Far Cry 6 (PC) Ubisoft Connect Key EUROPE', 'appId' => 2369390, 'platform' => 'Ubisoft Connect', 'region' => 'EUROPE', 'msrpCents' => 5999, 'released' => '2021-10-07',
            'description' => 'Join a modern-day guerrilla revolution to liberate the tropical island of Yara from the dictator Antón Castillo.'],
        ['name' => 'Diablo IV (PC) Battle.net Key GLOBAL', 'appId' => 2344520, 'platform' => 'Battle.net', 'region' => 'GLOBAL', 'msrpCents' => 6999, 'released' => '2023-06-06',
            'description' => 'Join the fight for Sanctuary in Blizzard\'s action RPG. Battle the demon Lilith across an open world, solo or with friends.'],
        ['name' => 'Black Myth: Wukong Steam Key GLOBAL', 'appId' => 2358720, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2024-08-20', 'popular' => true,
            'description' => 'An action RPG rooted in Chinese mythology, based on Journey to the West. Set out as the Destined One to uncover a hidden truth.'],
        ['name' => 'Palworld Steam Key GLOBAL', 'appId' => 1623730, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 2899, 'released' => '2024-01-19',
            'description' => 'Befriend and collect mysterious creatures called Pals, then build, farm and fight together in a vast open-world survival game.'],
        ['name' => 'HELLDIVERS 2 Steam Key GLOBAL', 'appId' => 553850, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 3999, 'released' => '2024-02-08', 'popular' => true,
            'description' => 'Join the Helldivers and fight for freedom across a hostile galaxy in this fast, frantic and ferocious co-op third-person shooter.'],
        ['name' => 'Terraria Steam Key GLOBAL', 'appId' => 105600, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 999, 'released' => '2011-05-16',
            'description' => 'Dig, fight, explore and build in a 2D sandbox world where nothing is impossible.'],
        ['name' => 'Celeste (Nintendo Switch) eShop Key EUROPE', 'appId' => 504230, 'platform' => 'Nintendo', 'region' => 'EUROPE', 'msrpCents' => 1999, 'released' => '2018-01-25',
            'description' => 'Help Madeline survive her inner demons on her journey to the top of Celeste Mountain, in this tight, hand-crafted platformer.'],
        ['name' => 'Lies of P Steam Key GLOBAL', 'appId' => 1627720, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2023-09-19',
            'description' => 'A souls-like inspired by the story of Pinocchio, set in the dark Belle Époque city of Krat.'],
        ['name' => 'Sid Meier\'s Civilization VI Steam Key GLOBAL', 'appId' => 289070, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2016-10-21',
            'description' => 'Build an empire to stand the test of time in the turn-based strategy classic. Expand across the map, advance your culture and compete with history\'s great leaders.'],
        ['name' => 'Kingdom Come: Deliverance II Steam Key GLOBAL', 'appId' => 1771300, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2025-02-04',
            'description' => 'A thrilling story-driven open-world RPG set in 15th-century Bohemia. Follow Henry on an epic coming-of-age adventure.'],
        ['name' => 'Clair Obscur: Expedition 33 Steam Key GLOBAL', 'appId' => 1903340, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 4999, 'released' => '2025-04-24',
            'description' => 'Lead the members of Expedition 33 on their quest to destroy the Paintress in a turn-based RPG with real-time mechanics, inspired by Belle Époque France.'],
        ['name' => 'The Last of Us Part I Steam Key GLOBAL', 'appId' => 1888930, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 5999, 'released' => '2023-03-28',
            'description' => 'Joel and Ellie cross a post-pandemic United States in this rebuilt version of the acclaimed survival story, including the Left Behind prologue.'],
        ['name' => 'Portal 2 Steam Key GLOBAL', 'appId' => 620, 'platform' => 'Steam', 'region' => 'GLOBAL', 'msrpCents' => 979, 'released' => '2011-04-19',
            'description' => 'The award-winning puzzle game returns with a single-player story and a separate two-player co-op campaign.'],
    ];

    /** @var list<array{name: string, rating: string, verified: bool}> */
    public const SELLERS = [
        ['name' => 'PixelVault', 'rating' => '4.98', 'verified' => true],
        ['name' => 'KeyHaven', 'rating' => '4.93', 'verified' => true],
        ['name' => 'GameNest', 'rating' => '4.87', 'verified' => true],
        ['name' => 'LootLane', 'rating' => '4.79', 'verified' => true],
        ['name' => 'NovaKeys', 'rating' => '4.71', 'verified' => true],
        ['name' => 'QuestMarket', 'rating' => '4.58', 'verified' => false],
        ['name' => 'ByteBazaar', 'rating' => '4.41', 'verified' => false],
        ['name' => 'DigitalDen', 'rating' => '4.23', 'verified' => false],
    ];

    /**
     * 'product' is the product name the banner links to (its slug is derived from it).
     *
     * @var list<array{title: string, subtitle: string, appId: int, product: string}>
     */
    public const BANNERS = [
        ['title' => 'ELDEN RING', 'subtitle' => 'Rise, Tarnished. Save on the open-world masterpiece.', 'appId' => 1245620, 'product' => 'ELDEN RING Steam Key GLOBAL'],
        ['title' => 'Baldur\'s Gate 3', 'subtitle' => 'Gather your party. Best prices from trusted sellers.', 'appId' => 1086940, 'product' => 'Baldur\'s Gate 3 Steam Key GLOBAL'],
        ['title' => 'Cyberpunk 2077', 'subtitle' => 'Wake up, samurai. Night City is waiting.', 'appId' => 1091500, 'product' => 'Cyberpunk 2077 Steam Key GLOBAL'],
    ];

    /** Homepage layout, in display order. Each type has its own provider (PLAN.md §2). */
    public const HOME_BLOCKS = [
        ['type' => 'hero_banner', 'title' => 'Featured'],
        ['type' => 'best_sellers', 'title' => 'Best sellers'],
        ['type' => 'deals', 'title' => 'Top deals'],
        ['type' => 'new_releases', 'title' => 'New releases'],
    ];
}
