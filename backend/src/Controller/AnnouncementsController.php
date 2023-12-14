<?php

namespace App\Controller;

use App\Exceptions\AnnouncementNotFoundException;
use App\Exceptions\AnnouncementServiceException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\AnnouncementsService;
use Symfony\Component\HttpFoundation\JsonResponse;


class AnnouncementsController extends AbstractController
{

    #[Route('api/announcement/createAnnouncement', name: 'announcement_creation', methods: ['POST'])]
    public function createAnnouncement(AnnouncementsService $announcementsService, Request $request): JsonResponse
    {
        // Récupérez les données JSON de la requête
        $data = json_decode($request->getContent(), true);
        // Appel au service pour créer une annonce
        try {
            $result = $announcementsService->createAnnouncement($data);
            if ($result !== null) {
                if ($result['status'] === 400) {
                    return $this->json(['error' => $result['message']], $result['status']);
                }
                throw new AnnouncementServiceException('Erreur lors de la création de l\'annonce');
            }

            // Répondez avec un message de succès
            return new JsonResponse(['message' => 'L\'annonce a été créée avec succès'], 201);
        } catch (AnnouncementServiceException $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/announcement/getAnnouncementById', name: 'announcement_retreview', methods: ['POST'])]
    public function getAnnouncement(AnnouncementsService $announcementService, Request $request): JsonResponse
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
                'id' => $announcement->getId(),
                'owner_id' => $announcement->getOwner()->getId(),
                'complement' => $announcement->getComplement(),
                'description' => $announcement->getDescription(),
                'title' => $announcement->getTitle(),
                'categorie' => $announcement->getCategorie(),
                'date' => $announcement->getDate()->format('Y-m-d H:i:s'),
                'limitDate' => $announcement->getLimitDate()->format('Y-m-d H:i:s'),
                'status' => $announcement->isStatus(),
                'contenu' => $announcement->getContenu(),
                'long' => $announcement->getPositionGPS()->getLong(),
                'lat' => $announcement->getPositionGPS()->getLat(),
                'creneaux' => $announcement->getListeCreneaux(),
                'numero_rue' => $announcement->getNumeroRue(),
                'rue' => $announcement->getRue(),
                'ville' => $announcement->getVille(),
                'code_postal' => $announcement->getCodePostal(),
                'allergenes' => $announcement->isAllergenes(),
                'reservation' => $reservationsData,
            ];

            return $this->json($announcementData, 200);
        } catch (AnnouncementNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (AnnouncementServiceException $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/announcement/bookAnnouncement/{id}', name: 'announcement_status_update', methods: ['POST'])]
    public function bookAnnouncement(int $id, AnnouncementsService $announcementService, Request $request): JsonResponse
    {
        try {
            $announcement = $announcementService->getAnnouncement($id);
            if ($announcement === null) {
                throw new AnnouncementNotFoundException($id);
            }

            $result = $announcementService->bookAnnouncement($id);

            if ($result !== null) {
                throw new AnnouncementServiceException('Erreur lors de la mise à jour du statut de l\'annonce');
            }

            return new JsonResponse(['message' => 'L\'annonce a été mise à jour avec succès'], 200);
        } catch (AnnouncementNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (AnnouncementServiceException $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/announcement/deleteAnnouncement/{id}', name: 'announcement_delete', methods: ['DELETE'])]
    public function deleteAnnouncement($id, AnnouncementsService $announcementService, Request $request): JsonResponse
    {
        try {
            $announcement = $announcementService->getAnnouncement($id);
            if ($announcement === null) {
                throw new AnnouncementNotFoundException($id);
            }

            $result = $announcementService->deleteAnnouncement($id);
            if ($result !== null) {
                throw new AnnouncementServiceException('Erreur lors de la suppression de l\'annonce');
            }

            return new JsonResponse(['message' => 'L\'annonce a été supprimée avec succès'], 200);
        } catch (AnnouncementNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (AnnouncementServiceException $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/announcement/getBookedAnnouncements', name: 'get_booked_announcements', methods: ['POST'])]
    public function getBookedAnnouncements(AnnouncementsService $announcementService, Request $request)
    {
        try {
            $announcements = $announcementService->getBookedAnnouncements();

            if (empty($announcements)) {
                return $this->json([], 200);
            }

            $announcementData = [];

            foreach ($announcements as $announcement) {
                $announcementData[] = [
                    'id' => $announcement->getId(),
                    'owner_id' => $announcement->getOwner()->getId(),
                    'complement' => $announcement->getComplement(),
                    'description' => $announcement->getDescription(),
                    'title' => $announcement->getTitle(),
                    'categorie' => $announcement->getCategorie(),
                    'date' => $announcement->getDate()->format('Y-m-d H:i:s'),
                    'limitDate' => $announcement->getLimitDate()->format('Y-m-d H:i:s'),
                    'status' => $announcement->isStatus(),
                    'contenu' => $announcement->getContenu(),
                    'numero_rue' => $announcement->getNumeroRue(),
                    'rue' => $announcement->getRue(),
                    'ville' => $announcement->getVille(),
                    'code_postal' => $announcement->getCodePostal(),
                    'allergenes' => $announcement->isAllergenes(),
                    'long' => $announcement->getPositionGps()->getLong(),
                    'lat' => $announcement->getPositionGps()->getLat(),
                    'creneaux' => $announcement->getListeCreneaux(),
                ];
            }

            return $this->json($announcementData, 200);
        } catch (AnnouncementNotFoundException $exception) {
            return $this->json(['error' => $exception->getMessage()], 404);
        } catch (\Exception $exception) {
            return $this->json(['error' => 'Une erreur s\'est produite.'], 500);
        }
    }

    #[Route('/api/announcement/getNotBookedAnnouncements', name: 'get_not_booked_announcements', methods: ['POST'])]
    public function getNotBookedAnnouncements(AnnouncementsService $announcementService, Request $request)
    {
        try {
            $announcements = $announcementService->getNotBookedAnnouncements();

            if (empty($announcements)) {
                return $this->json([], 200);
            }

            $announcementData = [];

            foreach ($announcements as $announcement) {
                $announcementData[] = [
                    'id' => $announcement->getId(),
                    'owner_id' => $announcement->getOwner()->getId(),
                    'complement' => $announcement->getComplement(),
                    'description' => $announcement->getDescription(),
                    'title' => $announcement->getTitle(),
                    'contenu' => $announcement->getContenu(),
                    'categorie' => $announcement->getCategorie(),
                    'date' => $announcement->getDate()->format('Y-m-d H:i:s'),
                    'limitDate' => $announcement->getLimitDate()->format('Y-m-d H:i:s'),
                    'status' => $announcement->isStatus(),
                    'numero_rue' => $announcement->getNumeroRue(),
                    'rue' => $announcement->getRue(),
                    'ville' => $announcement->getVille(),
                    'code_postal' => $announcement->getCodePostal(),
                    'allergenes' => $announcement->isAllergenes(),
                    'long' => $announcement->getPositionGps()->getLong(),
                    'lat' => $announcement->getPositionGps()->getLat(),
                    'creneaux' => $announcement->getListeCreneaux(),
                ];
            }

            return $this->json($announcementData, 200);
        } catch (AnnouncementNotFoundException $exception) {
            return $this->json(['error' => $exception->getMessage()], 404);
        } catch (\Exception $exception) {
            return $this->json(['error' => 'Une erreur s\'est produite.'], 500);
        }
    }

    #[Route('/api/announcement/getAnnouncementsOwner', name: 'get_announcement_by_owner', methods: ['POST'])]
    public function getAnnouncementOwner(announcementsService $announcementService, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ownerId = $data['owner_id'];

        try {
            $announcements = $announcementService->getAnnouncementsOwner($ownerId);

            if (empty($announcements)) {
                throw new AnnouncementNotFoundException($ownerId);
            }

            $announcementData = [];

            foreach ($announcements as $announcement) {
                $announcementData[] = [
                    'id' => $announcement->getId(),
                    'owner_id' => $announcement->getOwner()->getId(),
                    'complement' => $announcement->getComplement(),
                    'description' => $announcement->getDescription(),
                    'title' => $announcement->getTitle(),
                    'categorie' => $announcement->getCategorie(),
                    'date' => $announcement->getDate()->format('Y-m-d H:i:s'),
                    'limitDate' => $announcement->getLimitDate()->format('Y-m-d H:i:s'),
                    'status' => $announcement->isStatus(),
                    'numero_rue' => $announcement->getNumeroRue(),
                    'rue' => $announcement->getRue(),
                    'ville' => $announcement->getVille(),
                    'code_postal' => $announcement->getCodePostal(),
                    'allergenes' => $announcement->isAllergenes(),
                ];
            }

            return $this->json($announcementData, 200);
        } catch (AnnouncementNotFoundException $exception) {
            return $this->json(['error' => $exception->getMessage()], 404);
        } catch (\Exception $exception) {
            return $this->json(['error' => 'Une erreur s\'est produite.'], 500);
        }
    }

}
