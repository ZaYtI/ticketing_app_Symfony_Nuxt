<?php

namespace App\DataFixtures;

use App\Entity\Ticket;
use App\Entity\TicketStatusHistory;
use App\Entity\User;
use App\Entity\Utils\Priority;
use App\Entity\Utils\Status;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $users = [];

        //Admin permanent
        $adminUser = new User();
        $adminUser->setEmail('admin@ticketing.com');
        $adminUser->setRoles(['ROLE_USER','ROLE_SUPPORT','ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'password');
        $adminUser->setPassword($hashedPassword);

        $manager->persist($adminUser);
        $users[] = $adminUser;


        //Support permanent
        $supportUser = new User();
        $supportUser->setEmail('support@ticketing.com');
        $supportUser->setRoles(['ROLE_USER','ROLE_SUPPORT']);
        $hashedPassword = $this->passwordHasher->hashPassword($supportUser, 'password');
        $supportUser->setPassword($hashedPassword);

        $manager->persist($supportUser);
        $users[] = $supportUser;


        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail($faker->email());
            $user->setRoles(['ROLE_USER']);
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);
            $manager->persist($user);
            $users[] = $user;
        }

        $tickets = [];
            for($y = 1; $y <= 12; $y++) {
                $numberOfTicketsPerMonth = $faker->numberBetween(1, 20);
                for ($m = 1; $m <= $numberOfTicketsPerMonth; $m++) {
                    $ticket = new Ticket();
                    $ticket->setTitle($faker->title());
                    $ticket->setDescription($faker->text());
                    $ticket->setStatus($faker->randomElement(Status::class));
                    $ticket->setPriority($faker->randomElement(Priority::class));
                    $ticket->setDeadLine((new \DateTime())->modify("+$y days"));
                    $ticket->setCreatedAt((new \DateTime())->modify("-1 year")->modify("+$y months"));
                    $ticket->setCreatedBy($users[array_rand($users)]);
                    $ticket->setAssignedTo($users[array_rand($users)]);
                    $manager->persist($ticket);
                    $tickets[] = $ticket;
                }
            }

        foreach ($tickets as $ticket) {
            for ($j = 0; $j < 3; $j++) {
                $statusHistory = new TicketStatusHistory();
                $statusHistory->setTicket($ticket);
                $statusHistory->setStatus($faker->randomElement(Status::class));
                $statusHistory->setChangeAt((new \DateTime())->modify("-$j days"));
                $statusHistory->setChangedBy($users[array_rand($users)]);

                $manager->persist($statusHistory);
            }
        }

        $manager->flush();
    }
}
