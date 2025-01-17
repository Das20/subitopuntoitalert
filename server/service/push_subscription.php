<?php
declare(strict_types=1);

use SubitoPuntoItAlert\Database\Model\Subscription;
use SubitoPuntoItAlert\Database\Repository\AnnouncementRepository;
use SubitoPuntoItAlert\Database\Repository\ResearchRepository;
use SubitoPuntoItAlert\Database\Repository\SubscriptionRepository;
use SubitoPuntoItAlert\Exception\MissingSubscriptionException;

$researchRepository = new ResearchRepository();
$announcementRepository = new AnnouncementRepository();
$subscriptionRepository = new SubscriptionRepository();
$subscription = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

if (
    !array_key_exists('endpoint', $subscription) ||
    !array_key_exists('publicKey', $subscription) ||
    !array_key_exists('authToken', $subscription) ||
    ($method === 'POST' && !array_key_exists('contentEncoding', $subscription))
) {
    echo 'Error: not a subscription';
    return;
}

switch ($method) {
    case 'POST':
        // create a new subscription entry in your database (endpoint is unique)
        $subscriptionModel = new Subscription($subscription['endpoint']);
        $subscriptionModel->setPublicKey($subscription['publicKey']);
        $subscriptionModel->setContentEncoding($subscription['contentEncoding']);
        $subscriptionModel->setAuthToken($subscription['authToken']);
        $subscriptionRepository->save($subscriptionModel);
        break;
    case 'PUT':
        // update the key and token of subscription corresponding to the endpoint
        try {
            $subscriptionModel = $subscriptionRepository->getSubscription($subscription['endpoint']);
            $subscriptionModel->setPublicKey($subscription['publicKey']);
            $subscriptionModel->setAuthToken($subscription['authToken']);
            $subscriptionRepository->save($subscriptionModel);
        } catch (MissingSubscriptionException $e) {
            $researchRepository->deleteByEndpoint($subscription['endpoint']);
            $announcementRepository->deleteByEndpoint($subscription['endpoint']);
        }
        break;
    case 'DELETE':
        // delete the subscription corresponding to the endpoint
        $subscriptionRepository->delete($subscription['endpoint']);
        $researchRepository->deleteByEndpoint($subscription['endpoint']);
        $announcementRepository->deleteByEndpoint($subscription['endpoint']);
        break;
    default:
        echo "Error: method not handled";
        return;
}
