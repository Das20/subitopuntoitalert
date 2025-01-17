<?php
declare(strict_types=1);

namespace SubitoPuntoItAlert\Database\Repository;

use Generator;
use PDO;
use SubitoPuntoItAlert\Database\Configuration;
use SubitoPuntoItAlert\Database\Model\Notification;

class NotificationRepository
{
    /**
     * @var PDO
     */
    private $db;

    public function __construct()
    {
        $this->db = Configuration::getDB();
    }

    /**
     * @return Generator
     */
    public function getNotifications(): Generator
    {
        $stmt = $this->getDb()->prepare(
            'SELECT * FROM Notification'
        );
        $stmt->execute();
        while ($row = $stmt->fetch()){
            $notification = new Notification($row['endpoint']);
            $notification->setMessage($row['message']);
            yield $notification;
        }
    }

    public function deleteAll(): void
    {
        $this->getDb()->exec('TRUNCATE TABLE Notification ');
    }

    /**
     * @param Notification $notification
     */
    public function delete(Notification $notification): void
    {
        $stmt = $this->getDb()->prepare(
            'DELETE FROM Notification '.
            'WHERE endpoint = ?'
        );
        $stmt->execute([
            $notification->getEndpoint()
        ]);
    }

    /**
     * @param Notification $notification
     */
    public function save(Notification $notification): void
    {
        $this->delete($notification);
        $stmt = $this->getDb()->prepare(
            'INSERT INTO Notification (endpoint, message) '.
            'VALUES (?, ?)'
        );
        $stmt->execute([
            $notification->getEndpoint(),
            $notification->getMessage()
        ]);
    }

    /**
     * @return PDO
     */
    private function getDb(): PDO
    {
        return $this->db;
    }
}
