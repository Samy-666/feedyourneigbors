<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ReservationsService;
use App\Service\AnnouncementsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Exceptions\AnnouncementServiceException;
use App\Exceptions\AnnouncementNotFoundException;



class ReservationsController extends AbstractController
{
    #[Route('api/reservations/createReservation', name: 'reservation_creation', methods: ['POST'])]
    public function createReservation(ReservationsService $reservationsService, AnnouncementsService $announcementsService, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        try {
            $result = $reservationsService->createReservation($data);
            if ($result !== null) {
                return new JsonResponse(['message' => $result['message']], $result['status']);
            }
            $announcementsService->bookAnnouncement($data['announcement_id']);
            return new JsonResponse(['message' => 'La reservation a été créée avec succès'], 200);
        } catch (AnnouncementServiceException $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('api/reservations/getReservationAnnonce', name: 'reservation_annoncement_get', methods: ['POST'])]
    public function getReservation(AnnouncementsService $announcementService, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id_announcement'];

        try {
            $announcement = $announcementService->getAnnouncement($id);

            if ($announcement === null) {
                throw new AnnouncementNotFoundException($id);
            }

            $reservationsData = [];
            $reservations = $announcement->getReservations();

            if (!$reservations->isEmpty()) {
                foreach ($reservations as $reservation) {
                    $reservationsData[] = [
                        'id' => $reservation->getId(),
                        'beneficiary_id' => $reservation->getBenef()->getId(),
                        'creneau_start' => $reservation->getCreneauStart()->format('Y-m-d H:i:s'),
                        'creneau_end' => $reservation->getCreneauEnd()->format('Y-m-d H:i:s'),
                        'status' => $reservation->getStatus(),
                        'comment' => $reservation->getComment(),
                    ];
                }
            }

            $announcementData = [
                'reservation' => $reservationsData,
            ];

            return $this->json($announcementData, 200);
        } catch (AnnouncementNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (AnnouncementServiceException $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

}