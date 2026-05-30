<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$incidents = [
    [
        'title' => 'Traffic collision on Rruga B',
        'description' => 'Two cars involved, minor injuries reported.',
        'latitude' => 42.661667,
        'longitude' => 21.165833,
        'severity' => 3,
        'incident_type' => 'police'
    ],
    [
        'title' => 'Small fire near downtown market',
        'description' => 'Trash bin fire spreading to nearby stall, contained by bystanders.',
        'latitude' => 42.662500,
        'longitude' => 21.160000,
        'severity' => 2,
        'incident_type' => 'fire'
    ],
    [
        'title' => 'Medical emergency at university',
        'description' => 'Person fainted, ambulance en route.',
        'latitude' => 42.665000,
        'longitude' => 21.168000,
        'severity' => 4,
        'incident_type' => 'medical'
    ],
    [
        'title' => 'Suspicious package reported',
        'description' => 'Unattended bag at bus station; area cordoned off.',
        'latitude' => 42.666000,
        'longitude' => 21.160500,
        'severity' => 5,
        'incident_type' => 'police'
    ],
    [
        'title' => 'Water leak on side street',
        'description' => 'Large water leak affecting traffic flow.',
        'latitude' => 42.660800,
        'longitude' => 21.170200,
        'severity' => 2,
        'incident_type' => 'police'
    ]
];

$db = Database::getInstance();
$inserted = [];
foreach ($incidents as $inc) {
    $sql = "INSERT INTO incidents (title, description, latitude, longitude, severity, incident_type) VALUES (:title, :description, :latitude, :longitude, :severity, :incident_type)";
    $params = [
        ':title' => $inc['title'],
        ':description' => $inc['description'],
        ':latitude' => $inc['latitude'],
        ':longitude' => $inc['longitude'],
        ':severity' => $inc['severity'],
        ':incident_type' => $inc['incident_type']
    ];
    try {
        $db->query($sql, $params);
        $id = $db->lastInsertId();
        $inserted[] = array_merge(['id' => $id], $inc);
    } catch (Exception $e) {
        echo "Failed to insert: " . $e->getMessage() . PHP_EOL;
    }
}

echo "Inserted " . count($inserted) . " incidents:\n";
foreach ($inserted as $i) {
    echo "- (#{$i['id']}) {$i['title']} at {$i['latitude']}, {$i['longitude']}\n";
}

?>