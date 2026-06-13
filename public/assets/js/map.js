let map;
let incidents = [];
let markers = {};
let activeIncidentId = null;
let tempClickMarker = null;
let pollInterval = null;
const isAdmin = window.APP_USER_ROLE === 'admin';

const tabIncidents = document.getElementById('tabIncidents');
const tabReport = document.getElementById('tabReport');
const paneIncidents = document.getElementById('paneIncidents');
const paneReport = document.getElementById('paneReport');

const incidentsList = document.getElementById('incidentsList');
const searchFilter = document.getElementById('searchFilter');
const coordsOverlay = document.getElementById('coordsOverlay');

const reportIncidentForm = document.getElementById('reportIncidentForm');
const severitySlider = document.getElementById('severity');
const severityVal = document.getElementById('severityVal');
const latInput = document.getElementById('latitude');
const lngInput = document.getElementById('longitude');
const reportMessage = document.getElementById('reportMessage');

const incidentDetailView = document.getElementById('incidentDetailView');
const closeDetailBtn = document.getElementById('closeDetailBtn');
const detailTypeBadge = document.getElementById('detailTypeBadge');
const detailSeverityBadge = document.getElementById('detailSeverityBadge');
const detailTitle = document.getElementById('detailTitle');
const detailStatusBadge = document.getElementById('detailStatusBadge');
const detailTime = document.getElementById('detailTime');
const detailDescription = document.getElementById('detailDescription');

const dispatchInfoBox = document.getElementById('dispatchInfoBox');
const dispatchUnitBadge = document.getElementById('dispatchUnitBadge');
const dispatchStatusText = document.getElementById('dispatchStatusText');

const dispatchForm = document.getElementById('dispatchForm');
const unitTypeSelect = document.getElementById('unitTypeSelect');
const btnDispatchUnit = document.getElementById('btnDispatchUnit');
const btnResolveIncident = document.getElementById('btnResolveIncident');

document.addEventListener('DOMContentLoaded', () => {
    initMap();
    initEventListeners();
    fetchIncidents();
    setTimeout(() => {
        if (map) {
            map.invalidateSize();
        }
    }, 200);
    pollInterval = setInterval(fetchIncidents, 10000);
});

function initMap() {
    const prishtinaCenter = [42.6629, 21.1655];
    
    map = L.map('map', {
        zoomControl: false
    }).setView(prishtinaCenter, 13);
    
    L.control.zoom({
        position: 'topright'
    }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    map.on('mousemove', (e) => {
        coordsOverlay.textContent = `Lat: ${e.latlng.lat.toFixed(4)} | Lng: ${e.latlng.lng.toFixed(4)}`;
    });

    map.on('click', (e) => {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);

        if (tempClickMarker) {
            map.removeLayer(tempClickMarker);
        }

        const tempIcon = L.divIcon({
            html: `<div class="custom-marker"><div class="marker-pin" style="--marker-color: #64748B"></div></div>`,
            className: 'leaflet-div-icon',
            iconSize: [36, 36],
            iconAnchor: [18, 36]
        });

        tempClickMarker = L.marker([lat, lng], { icon: tempIcon }).addTo(map);
        tempClickMarker.bindPopup('<p><b>Selected Location</b><br>Coordinates captured for reporting.</p>').openPopup();

        switchTab('report');
    });
}

function initEventListeners() {
    tabIncidents.addEventListener('click', () => switchTab('incidents'));
    tabReport.addEventListener('click', () => switchTab('report'));

    searchFilter.addEventListener('input', renderIncidentsList);

    severitySlider.addEventListener('input', (e) => {
        severityVal.textContent = e.target.value;
    });

    reportIncidentForm.addEventListener('submit', handleReportSubmit);

    closeDetailBtn.addEventListener('click', hideIncidentDetails);
    btnDispatchUnit.addEventListener('click', handleDispatchSubmit);
    btnResolveIncident.addEventListener('click', handleResolveSubmit);
}

function switchTab(tabName) {
    if (tabName === 'incidents') {
        tabIncidents.classList.add('active');
        tabIncidents.setAttribute('aria-selected', 'true');
        tabReport.classList.remove('active');
        tabReport.setAttribute('aria-selected', 'false');

        paneIncidents.classList.add('active');
        paneReport.classList.remove('active');
    } else {
        tabReport.classList.add('active');
        tabReport.setAttribute('aria-selected', 'true');
        tabIncidents.classList.remove('active');
        tabIncidents.setAttribute('aria-selected', 'false');

        paneReport.classList.add('active');
        paneIncidents.classList.remove('active');
    }
}

async function fetchIncidents() {
    try {
        const response = await fetch('api.php?action=get_incidents');
        if (response.status === 401) {
            window.location.href = 'auth/login.php';
            return;
        }

        const res = await response.json();
        if (res.success) {
            incidents = res.data;
            plotIncidentsOnMap();
            renderIncidentsList();
            
            if (activeIncidentId !== null) {
                const activeInc = incidents.find(i => parseInt(i.id) === activeIncidentId);
                if (activeInc) {
                    showIncidentDetails(activeInc);
                } else {
                    hideIncidentDetails();
                }
            }
        }
    } catch (err) {
        console.error("Error fetching incidents: ", err);
    }
}

function plotIncidentsOnMap() {
    const currentIds = incidents.map(i => parseInt(i.id));
    Object.keys(markers).forEach(idStr => {
        const id = parseInt(idStr);
        if (!currentIds.includes(id)) {
            map.removeLayer(markers[id]);
            delete markers[id];
        }
    });

    incidents.forEach(incident => {
        const id = parseInt(incident.id);
        const lat = parseFloat(incident.latitude);
        const lng = parseFloat(incident.longitude);
        const status = incident.status;
        const severity = parseInt(incident.severity);
        const type = incident.incident_type;

        let color = '#3B82F6';
        let emoji = '';

        if (type === 'fire') {
            color = '#EF4444';
            emoji = '';
        } else if (type === 'medical') {
            color = '#10B981';
            emoji = '';
        }

        if (status === 'resolved') {
            color = '#64748B';
        }

        const severeClass = severity >= 4 && status !== 'resolved' ? 'severe' : '';
        const resolvedClass = status === 'resolved' ? 'resolved' : '';
        
        const markerHtml = `
            <div class="custom-marker">
                <div class="marker-pin ${severeClass} ${resolvedClass}" style="--marker-color: ${color}"></div>
            </div>
        `;

        const customIcon = L.divIcon({
            html: markerHtml,
            className: 'leaflet-div-icon',
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36]
        });

        if (markers[id]) {
            markers[id].setLatLng([lat, lng]);
            markers[id].setIcon(customIcon);
        } else {
            const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
            
            const popupContent = `
                <div>
                    <h4>${escapeHtml(incident.title)}</h4>
                    <p><b>Type:</b> ${type.toUpperCase()} | <b>Severity:</b> ${severity}/5</p>
                    <p><b>Status:</b> ${status.toUpperCase()}</p>
                    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="selectIncidentFromMap(${id})">
                        View Details
                    </button>
                </div>
            `;
            marker.bindPopup(popupContent);
            
            marker.on('click', () => {
                selectIncidentFromMap(id);
            });

            markers[id] = marker;
        }
    });
}

function renderIncidentsList() {
    const filterText = searchFilter.value.toLowerCase().trim();
    
    incidentsList.innerHTML = '';

    const filteredIncidents = incidents.filter(inc => {
        const titleMatch = inc.title.toLowerCase().includes(filterText);
        const descMatch = inc.description && inc.description.toLowerCase().includes(filterText);
        const typeMatch = inc.incident_type.toLowerCase().includes(filterText);
        const statusMatch = inc.status.toLowerCase().includes(filterText);
        return titleMatch || descMatch || typeMatch || statusMatch;
    });

        if (filteredIncidents.length === 0) {
        incidentsList.innerHTML = `
            <div class="text-center p-4 text-secondary">
                <p>No incidents matched your filters.</p>
            </div>
        `;
        return;
    }

    filteredIncidents.forEach(inc => {
        const item = document.createElement('div');
        item.className = `incident-item type-${inc.incident_type}`;
        if (activeIncidentId === parseInt(inc.id)) {
            item.classList.add('active-selection');
        }

        const dateStr = new Date(inc.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        let typeLabel = 'Police';
        if (inc.incident_type === 'fire') typeLabel = 'Hazard';
        if (inc.incident_type === 'medical') typeLabel = 'Medical';

        let statusClass = 'badge-error';
        if (inc.status === 'dispatched') statusClass = 'badge-warning';
        if (inc.status === 'resolved') statusClass = 'badge-success';

        item.innerHTML = `
            <div class="incident-item-header">
                <span class="incident-item-title">${escapeHtml(inc.title)}</span>
                <span class="badge ${statusClass}">${inc.status.toUpperCase()}</span>
            </div>
            <p class="incident-item-desc">${escapeHtml(inc.description || 'No description provided.')}</p>
            <div class="incident-item-footer">
                <small>${typeLabel} | Severity: <b>${inc.severity}</b></small>
                <span class="incident-item-time">${dateStr}</span>
            </div>
        `;

        item.addEventListener('click', () => {
            selectIncident(parseInt(inc.id));
        });

        incidentsList.appendChild(item);
    });
}

window.selectIncidentFromMap = function(id) {
    selectIncident(id);
};

function selectIncident(id) {
    activeIncidentId = id;
    const incident = incidents.find(i => parseInt(i.id) === id);
    if (!incident) return;

    const lat = parseFloat(incident.latitude);
    const lng = parseFloat(incident.longitude);
    map.setView([lat, lng], 15, { animate: true });
    
    if (markers[id]) {
        markers[id].openPopup();
    }

    document.querySelectorAll('.incident-item').forEach(el => el.classList.remove('active-selection'));
    renderIncidentsList();

    switchTab('incidents');

    showIncidentDetails(incident);
}

function showIncidentDetails(incident) {
    detailTitle.textContent = incident.title;
    detailDescription.textContent = incident.description || 'No description provided.';
    detailTime.textContent = 'Reported: ' + new Date(incident.created_at).toLocaleString();

    detailTypeBadge.textContent = incident.incident_type.toUpperCase();
    detailTypeBadge.className = 'badge';
    if (incident.incident_type === 'police') detailTypeBadge.classList.add('badge-info');
    if (incident.incident_type === 'fire') detailTypeBadge.classList.add('badge-error');
    if (incident.incident_type === 'medical') detailTypeBadge.classList.add('badge-success');

    detailSeverityBadge.textContent = `SEVERITY ${incident.severity}`;
    detailSeverityBadge.className = 'badge';
    if (incident.severity >= 4) {
        detailSeverityBadge.classList.add('badge-error');
    } else if (incident.severity >= 2) {
        detailSeverityBadge.classList.add('badge-warning');
    } else {
        detailSeverityBadge.classList.add('badge-success');
    }

    detailStatusBadge.textContent = incident.status.toUpperCase();
    detailStatusBadge.className = 'badge';
    if (incident.status === 'active') detailStatusBadge.classList.add('badge-error');
    if (incident.status === 'dispatched') detailStatusBadge.classList.add('badge-warning');
    if (incident.status === 'resolved') detailStatusBadge.classList.add('badge-success');

    if (incident.status === 'dispatched') {
        dispatchInfoBox.classList.remove('d-none');

        let unitText = 'Police Unit';
        if (incident.dispatch_unit === 'fire') unitText = 'Fire Station Response';
        if (incident.dispatch_unit === 'medical') unitText = 'Medical Response Team';

        dispatchUnitBadge.textContent = unitText;
        dispatchUnitBadge.className = 'badge';
        if (incident.dispatch_unit === 'police') dispatchUnitBadge.classList.add('badge-info');
        if (incident.dispatch_unit === 'fire') dispatchUnitBadge.classList.add('badge-error');
        if (incident.dispatch_unit === 'medical') dispatchUnitBadge.classList.add('badge-success');

        const dispStatus = incident.dispatch_status ? incident.dispatch_status.replace('_', ' ').toUpperCase() : 'EN ROUTE';
        dispatchStatusText.innerHTML = `State: <b>${dispStatus}</b> <br><small>Dispatched at ${new Date(incident.dispatched_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</small>`;
    } else {
        dispatchInfoBox.classList.add('d-none');
    }

    if (isAdmin && incident.status === 'active') {
        dispatchForm.classList.remove('d-none');
        btnResolveIncident.classList.add('d-none');
        
        unitTypeSelect.value = incident.incident_type;
    } else if (isAdmin && incident.status === 'dispatched') {
        dispatchForm.classList.add('d-none');
        btnResolveIncident.classList.remove('d-none');
    } else {
        dispatchForm.classList.add('d-none');
        btnResolveIncident.classList.add('d-none');
    }

    incidentDetailView.classList.remove('d-none');
}

function hideIncidentDetails() {
    incidentDetailView.classList.add('d-none');
    activeIncidentId = null;
    
    document.querySelectorAll('.incident-item').forEach(el => el.classList.remove('active-selection'));
}

async function handleDispatchSubmit() {
    if (activeIncidentId === null) return;
    
    const unitType = unitTypeSelect.value;
    
    const submitBtn = btnDispatchUnit;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Dispatching...';

    try {
        const response = await fetch('api.php?action=dispatch_incident', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                incident_id: activeIncidentId,
                unit_type: unitType
            })
        });

        const res = await response.json();
        if (res.success) {
            await fetchIncidents();
        } else {
            alert(res.message || "Unable to dispatch unit.");
        }
    } catch (err) {
        console.error("Error dispatching unit: ", err);
        alert("Server communication error occurred.");
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Dispatch';
    }
}

async function handleResolveSubmit() {
    if (activeIncidentId === null) return;
    
    const confirmResolve = confirm("Are you sure you want to mark this incident as resolved?");
    if (!confirmResolve) return;

    const submitBtn = btnResolveIncident;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Resolving...';

    try {
        const response = await fetch('api.php?action=resolve_incident', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                incident_id: activeIncidentId
            })
        });

        const res = await response.json();
        if (res.success) {
            await fetchIncidents();
        } else {
            alert(res.message || "Unable to resolve incident.");
        }
    } catch (err) {
        console.error("Error resolving incident: ", err);
        alert("Server communication error occurred.");
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '✓ Resolve Incident';
    }
}

async function handleReportSubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('btnSubmitReport');
    
    reportMessage.className = 'alert d-none';
    reportMessage.textContent = '';

    const titleVal = document.getElementById('title').value.trim();
    const descVal = document.getElementById('description').value.trim();
    const typeVal = document.getElementById('incident_type').value;
    const severityValInt = parseInt(severitySlider.value);
    const latVal = parseFloat(latInput.value);
    const lngVal = parseFloat(lngInput.value);

    if (titleVal === '' || descVal === '' || isNaN(latVal) || isNaN(lngVal)) {
        showReportMessage("All fields must be filled and coordinates must be valid numbers.", "error");
        return;
    }

    if (latVal < -90 || latVal > 90 || lngVal < -180 || lngVal > 180) {
        showReportMessage("Coordinates are out of bounds. Lat: [-90, 90], Lng: [-180, 180].", "error");
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Saving Report...';

    try {
        const response = await fetch('api.php?action=create_incident', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: titleVal,
                description: descVal,
                incident_type: typeVal,
                severity: severityValInt,
                latitude: latVal,
                longitude: lngVal
            })
        });

        const res = await response.json();
        if (res.success) {
            showReportMessage(res.message || "Incident reported successfully!", "success");
            
            if (tempClickMarker) {
                map.removeLayer(tempClickMarker);
                tempClickMarker = null;
            }

            reportIncidentForm.reset();
            severityVal.textContent = '3';

            await fetchIncidents();
            
            if (res.incident_id) {
                selectIncident(parseInt(res.incident_id));
            }
        } else {
            showReportMessage(res.message || "Unable to save report.", "error");
        }
    } catch (err) {
        console.error("Error submitting report: ", err);
        showReportMessage("Server communication error. Please try again.", "error");
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Report Incident';
    }
}

function showReportMessage(message, type) {
    reportMessage.textContent = message;
    reportMessage.className = `alert alert-${type === 'error' ? 'error' : 'success'}`;
    reportMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
