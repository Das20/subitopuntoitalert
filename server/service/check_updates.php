<?php
declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';
set_time_limit(1800);

use SubitoPuntoItAlert\Api\Announcement;
use SubitoPuntoItAlert\Database\Model\Notification;
use SubitoPuntoItAlert\Database\Model\Announcement as AnnouncementModel;
use SubitoPuntoItAlert\Database\Repository\AnnouncementRepository;
use SubitoPuntoItAlert\Database\Repository\NotificationRepository;
use SubitoPuntoItAlert\Database\Repository\ResearchRepository;
use SubitoPuntoItAlert\Notification\Sender;

$notificationRepository = new NotificationRepository();
$researchRepository = new ResearchRepository();
$announcementRepository = new AnnouncementRepository();
$api = new Announcement();
$sender = new Sender();

foreach ($researchRepository->getResearches() as $research){
    $response = $api->getAnnouncements($research);
    $research->setLastCheckToday();
    $researchRepository->save($research);
    $endpoint = $research->getEndpoint();

    if ($response->getHttpCode() !== 200){
        continue;
    }

    foreach ($response->getData() as $detail) {
        $announcement = new AnnouncementModel($endpoint);
        $announcement->setDetails(json_encode($detail));
        $announcementRepository->save($announcement);
    }

    $notification = new Notification($endpoint);
    $notificationRepository->save($notification);
}

try {
    foreach ($notificationRepository->getNotifications() as $notification) {
        $notification->setMessage("Hai dei nuovi annunci!");
        $sender->send($notification);
        $notificationRepository->delete($notification);
    }
    $sender->flushReports();
} catch (ErrorException $e) {
    $notificationRepository->deleteAll();
}