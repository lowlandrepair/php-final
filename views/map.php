<?php
$pageTitle = 'Live Map - ' . APP_NAME;
require_once __DIR__ . '/layouts/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<link rel="stylesheet" href="assets/css/map.css">

<div class="app-wrapper">
    <header class="app-header">
        <div class="logo-area">
            <span class="logo-text"><?php echo htmlspecialchars( "San Andreas"); ?></span>
            <span class="badge badge-success ml-2">Live Sim</span>
        </div>
        <div class="user-area">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'Guest'); ?></span>
                <span class="user-role badge badge-info"><?php echo htmlspecialchars(ucfirst($_SESSION['user']['role'] ?? 'viewer')); ?></span>
            </div>
            <a href="/index.php?route=logout" class="btn btn-secondary btn-sm logout-btn">
                <span>Sign out</span>
                <span class="logout-icon">➔</span>
            </a>
        </div>
    </header>

    <main class="map-workspace" role="main">
        <div class="map-container-wrapper">
            <div id="map" aria-label="Interactive incident map"></div>
            <div class="map-coordinates-overlay" id="coordsOverlay">
                Lat: 42.6629 | Lng: 21.1655
            </div>
        </div>

        <aside class="sidebar-panel">
            <div class="sidebar-tabs">
                <button type="button" class="tab-btn active" id="tabIncidents" aria-selected="true" role="tab">
                    🚨 Active Incidents
                </button>
                <button type="button" class="tab-btn" id="tabReport" aria-selected="false" role="tab">
                    📝 Report Incident
                </button>
            </div>

            <div class="sidebar-content">
                <div class="tab-pane active" id="paneIncidents">
                    <div id="incidentDetailView" class="detail-view card d-none">
                        <button type="button" class="close-detail-btn" id="closeDetailBtn" aria-label="Close details">✕</button>
                        <div class="detail-header">
                            <span class="badge" id="detailTypeBadge">POLICE</span>
                            <span class="badge" id="detailSeverityBadge">SEVERITY 3</span>
                        </div>
                        <h2 id="detailTitle" class="mt-2 mb-2">Vehicle Theft</h2>
                        <div class="detail-meta">
                            <span class="meta-item"><strong id="detailStatusBadge" class="badge">Active</strong></span>
                            <span class="meta-item" id="detailTime">Just now</span>
                        </div>
                        <p id="detailDescription" class="mt-3 text-secondary">No description provided.</p>
                        
                        <div class="dispatch-info-box mt-3 mb-3 d-none" id="dispatchInfoBox">
                            <h4>Dispatch Status</h4>
                            <div class="dispatch-status-flow mt-2">
                                <span class="dispatch-badge badge" id="dispatchUnitBadge">Police</span>
                                <span class="dispatch-text" id="dispatchStatusText">En Route</span>
                            </div>
                        </div>

                        <div class="detail-actions mt-4">
                            <div class="dispatch-form d-none" id="dispatchForm">
                                <label for="unitTypeSelect" class="form-label">Dispatch Response Unit</label>
                                <div class="d-flex gap-2">
                                    <select id="unitTypeSelect" class="form-select">
                                        <option value="police">👮 Police Unit</option>
                                        <option value="fire">🚒 Fire Truck</option>
                                        <option value="medical">🚑 Ambulance</option>
                                    </select>
                                    <button type="button" class="btn btn-primary" id="btnDispatchUnit">Dispatch</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success w-100 mt-2 d-none" id="btnResolveIncident">
                                ✓ Resolve Incident
                            </button>
                        </div>
                    </div>

                    <div class="incidents-list-wrapper">
                        <div class="list-filters mb-3">
                            <input type="text" id="searchFilter" class="form-input" placeholder="Search incidents...">
                        </div>
                        <div id="incidentsList" class="incidents-list" role="list">
                            <div class="loading-state text-center p-4">
                                <span class="spinner"></span>
                                <p class="mt-2 text-secondary">Loading live incident data...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="paneReport">
                    <form id="reportIncidentForm" class="report-form" novalidate>
                        <div class="alert alert-info">
                            💡 <strong>Tip:</strong> Click anywhere on the map to automatically populate the coordinates!
                        </div>

                        <div id="reportMessage" class="alert d-none" role="status"></div>

                        <div class="form-group">
                            <label for="title" class="form-label">Incident Title</label>
                            <input type="text" id="title" name="title" class="form-input" placeholder="e.g. Traffic Collision, Water Leak" required>
                        </div>

                        <div class="form-group">
                            <label for="incident_type" class="form-label">Incident Type</label>
                            <select id="incident_type" name="incident_type" class="form-select" required>
                                <option value="police">👮 Police / Security</option>
                                <option value="fire">🚒 Fire / Gas / Hazard</option>
                                <option value="medical">🚑 Medical / Emergency</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="severity" class="form-label">Severity Level: <span id="severityVal">3</span></label>
                            <input type="range" id="severity" name="severity" class="form-range" min="1" max="5" value="3" required>
                            <div class="d-flex justify-between mt-1 text-secondary">
                                <small>Low</small>
                                <small>Critical</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-textarea" placeholder="Describe the scene and status..." required></textarea>
                        </div>

                        <div class="form-row d-flex gap-2">
                            <div class="form-group w-50">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" step="any" id="latitude" name="latitude" class="form-input" placeholder="42.6629" required>
                            </div>
                            <div class="form-group w-50">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" step="any" id="longitude" name="longitude" class="form-input" placeholder="21.1655" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3" id="btnSubmitReport">
                            Report Incident
                        </button>
                    </form>
                </div>
            </div>
        </aside>
    </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="assets/js/map.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
