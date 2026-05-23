<?php

require_once __DIR__ . '/Incident.php';

class IncidentController
{
    private Incident $incidentModel;

    public function __construct()
    {
        $this->incidentModel = new Incident();
    }

    public function getIncidents(): array
    {
        $incidents = $this->incidentModel->getAll();
        return [
            'success' => true,
            'data' => $incidents
        ];
    }

    public function createIncident(array $data): array
    {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $latitude = filter_var($data['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($data['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $severity = filter_var($data['severity'] ?? null, FILTER_VALIDATE_INT);
        $incidentType = trim($data['incident_type'] ?? '');

        if ($title === '' || $latitude === false || $longitude === false || $severity === false || $incidentType === '') {
            return ['success' => false, 'message' => 'Required fields are missing or invalid.'];
        }

        if ($severity < 1 || $severity > 5) {
            return ['success' => false, 'message' => 'Severity must be an integer between 1 and 5.'];
        }

        if (!in_array($incidentType, ['police', 'fire', 'medical'], true)) {
            return ['success' => false, 'message' => 'Invalid incident type.'];
        }

        $incidentId = $this->incidentModel->create($title, $description, $latitude, $longitude, $severity, $incidentType);

        if ($incidentId === false) {
            return ['success' => false, 'message' => 'Unable to save the incident.'];
        }

        return [
            'success' => true,
            'message' => 'Incident reported successfully!',
            'incident_id' => $incidentId
        ];
    }

    public function dispatchIncident(array $data): array
    {
        $incidentId = filter_var($data['incident_id'] ?? null, FILTER_VALIDATE_INT);
        $unitType = trim($data['unit_type'] ?? '');

        if ($incidentId === false || $unitType === '') {
            return ['success' => false, 'message' => 'Incident ID and unit type are required.'];
        }

        $result = $this->incidentModel->dispatchUnit($incidentId, $unitType);

        if (!$result) {
            return ['success' => false, 'message' => 'Failed to dispatch unit.'];
        }

        return [
            'success' => true,
            'message' => ucfirst($unitType) . ' unit has been dispatched!'
        ];
    }

    public function resolveIncident(array $data): array
    {
        $incidentId = filter_var($data['incident_id'] ?? null, FILTER_VALIDATE_INT);

        if ($incidentId === false) {
            return ['success' => false, 'message' => 'Incident ID is required.'];
        }

        $result = $this->incidentModel->resolve($incidentId);

        if (!$result) {
            return ['success' => false, 'message' => 'Failed to resolve incident.'];
        }

        return [
            'success' => true,
            'message' => 'Incident marked as resolved!'
        ];
    }
}
