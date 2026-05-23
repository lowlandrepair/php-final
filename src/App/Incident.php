<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

class Incident
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        $sql = "SELECT i.*, 
                       d.unit_type AS dispatch_unit, 
                       d.status AS dispatch_status, 
                       d.dispatched_at 
                FROM incidents i 
                LEFT JOIN dispatches d ON d.incident_id = i.id 
                     AND d.id = (SELECT id FROM dispatches WHERE incident_id = i.id ORDER BY dispatched_at DESC LIMIT 1)
                ORDER BY i.created_at DESC";
        return $this->db->fetchAll($sql);
    }

    public function create(
        string $title,
        string $description,
        float $latitude,
        float $longitude,
        int $severity,
        string $incidentType
    ): int|false {
        if ($severity < 1 || $severity > 5) {
            return false;
        }

        if (!in_array($incidentType, ['police', 'fire', 'medical'], true)) {
            return false;
        }

        $sql = "INSERT INTO incidents (title, description, latitude, longitude, severity, status, incident_type) 
                VALUES (:title, :description, :latitude, :longitude, :severity, 'active', :incident_type)";
        
        $params = [
            ':title' => $title,
            ':description' => $description,
            ':latitude' => $latitude,
            ':longitude' => $longitude,
            ':severity' => $severity,
            ':incident_type' => $incidentType
        ];

        try {
            $this->db->query($sql, $params);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function dispatchUnit(int $incidentId, string $unitType): bool
    {
        if (!in_array($unitType, ['police', 'fire', 'medical'], true)) {
            return false;
        }

        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();

            $sql1 = "UPDATE incidents SET status = 'dispatched', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $statement1 = $conn->prepare($sql1);
            $statement1->execute([':id' => $incidentId]);

            $sql2 = "INSERT INTO dispatches (incident_id, unit_type, status) VALUES (:incident_id, :unit_type, 'en_route')";
            $statement2 = $conn->prepare($sql2);
            $statement2->execute([
                ':incident_id' => $incidentId,
                ':unit_type' => $unitType
            ]);

            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
    }

    public function resolve(int $incidentId): bool
    {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();

            $sql1 = "UPDATE incidents SET status = 'resolved', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $statement1 = $conn->prepare($sql1);
            $statement1->execute([':id' => $incidentId]);

            $sql2 = "UPDATE dispatches 
                     SET status = 'completed', arrived_at = COALESCE(arrived_at, CURRENT_TIMESTAMP) 
                     WHERE incident_id = :incident_id AND status != 'completed'";
            $statement2 = $conn->prepare($sql2);
            $statement2->execute([':incident_id' => $incidentId]);

            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
    }
}
