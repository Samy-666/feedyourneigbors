<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Announcements;
use App\Entity\Reservations;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReservationsService
{
    private $doctrine;
    private $tokenStorage;
    public function __construct(PersistenceManagerRegistry $doctrine, TokenStorageInterface $tokenStorage)
    {
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
    }


    public function getAnnouncementReservation($id_announcement){
        $entityManager = $this->doctrine->getManager();
        $announcement = $entityManager->find(Announcements::class, $id_announcement);
        $reservation = $entityManager->getRepository(Reservations::class)->findOneBy(['announcement' => $announcement]);
        if ($reservation == null) {
            $message = "Aucune réservation n'existe pour cette annonce.";
            return ['message' => $message, 'status' => 404];
        } else {
            return $reservation;
        }
    }
    
    public function createReservation($data)
    {
        $message = "";
        $token = $this->tokenStorage->getToken();
        $userId = $token->getUser()->getId();
        $entityManager = $this->doctrine->getManager();
        $reservation = new Reservations();
        $user = $entityManager->find(User::class, $userId);
        $announcement = $entityManager->find(Announcements::class, $data['announcement_id']);
        $reservation->setBenef($user);
        $start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['start']);
        $end = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['end']);
        $reservation->setCreneauStart($start);
        $reservation->setCreneauEnd($end);
        $reservation->setAnnouncement($announcement);
        $reservation->setStatus($data['status']);
        $reservation->setComment($data['comment']);
        $existingReservation = $entityManager->getRepository(Reservations::class)->findOneBy(['announcement' => $announcement]);
        if ($existingReservation != null) {
            $message = "Une réservation existe déjà pour cette annonce.";
            return ['message' => $message, 'status' => 409];
        } else if ($announcement == null) {
            $message = "L'annonce n'existe pas.";
            return ['message' => $message, 'status' => 404];
        } else if ($announcement->getOwner() == $user) {
            $message = "L'utilisateur ne peut pas réserver sa propre annonce.";
            return ['message' => $message, 'status' => 403];
        } else {
            $entityManager->persist($reservation);
            try {
                $entityManager->flush();
                return null;
            } catch (\Exception $e) {
                return ['message' => $message, 'status' => 500];
            }
        }
    }
}