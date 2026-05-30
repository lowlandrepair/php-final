<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
// Access is already enforced by AuthMiddleware::requireAdmin() in index.php
// before this view is ever included. No redundant check needed here.
$adminId   = (int)($_SESSION['user']['id']        ?? 0);
$adminName = htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminInitial = strtoupper(substr($_SESSION['user']['full_name'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="description" content="Admin CRUD dashboard — <?php echo htmlspecialchars(APP_NAME); ?> Crime Mapping Platform">
  <title>Admin Dashboard — <?php echo htmlspecialchars(APP_NAME); ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="/php-final/public/assets/css/dashboard.css">

  <!-- Pass PHP session data to JS safely -->
  <script>
    window.__ADMIN_ID   = <?php echo json_encode($adminId); ?>;
    window.__ADMIN_NAME = <?php echo json_encode($adminName); ?>;
    window.__ADMIN_ROLE = "admin";
  </script>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════
     TOP HEADER
═══════════════════════════════════════════════════════ -->
<header class="dash-header" role="banner">
  <a href="/php-final/public/index.php?route=dashboard" class="dash-logo" aria-label="San Andreas Crime Map Admin">
    <div class="dash-logo-icon" aria-hidden="true"></div>
    <?php echo htmlspecialchars(APP_NAME); ?>
  </a>

  <span class="dash-badge-admin" aria-label="Administrator access">Admin</span>

  <div class="dash-header-sep"></div>

  <a href="/php-final/public/index.php?route=map" style="font-size:12px;color:var(--txt-secondary);text-decoration:none;margin-right:4px;" aria-label="Go to live map">
    Live Map
  </a>

  <div class="dash-user-chip" aria-label="Signed in as <?php echo $adminName; ?>">
    <div class="dash-user-avatar" aria-hidden="true">
      <?php echo $adminInitial; ?>
    </div>
    <?php echo $adminName; ?>
  </div>

  <a href="/php-final/public/index.php?route=logout" class="dash-btn-logout" aria-label="Sign out">
    Sign out
  </a>
</header>

<!-- ═══════════════════════════════════════════════════════
     MAIN LAYOUT
═══════════════════════════════════════════════════════ -->
<main class="dash-layout" role="main">

  <!-- ── LEFT PANEL ────────────────────────────────────── -->
  <section class="dash-panel-left" aria-label="Incidents management panel">

    <!-- Stats strip -->
    <div class="dash-stats" role="region" aria-label="Incident statistics">
      <div class="dash-stat-item total">
        <span class="dash-stat-val" id="statTotal">—</span>
        <span class="dash-stat-label">Total</span>
      </div>
      <div class="dash-stat-item active">
        <span class="dash-stat-val" id="statActive">—</span>
        <span class="dash-stat-label">Active</span>
      </div>
      <div class="dash-stat-item disp">
        <span class="dash-stat-val" id="statDisp">—</span>
        <span class="dash-stat-label">Dispatched</span>
      </div>
      <div class="dash-stat-item res">
        <span class="dash-stat-val" id="statRes">—</span>
        <span class="dash-stat-label">Resolved</span>
      </div>
    </div>

    <!-- Bulk action bar (appears when rows are selected) -->
    <div class="dash-bulk-bar" id="bulkBar" role="toolbar" aria-label="Bulk actions">
      <span class="dash-bulk-count" id="bulkCount" aria-live="polite"></span>
      <button class="dash-bulk-btn" id="bulkResolveBtn" aria-label="Mark selected as resolved">
        ✓ Resolve
      </button>
      <button class="dash-bulk-btn" id="bulkExportBtn" aria-label="Export selected to CSV">
        ↓ Export CSV
      </button>
      <button class="dash-bulk-btn danger" id="bulkDeleteBtn" aria-label="Delete selected incidents">
        Delete
      </button>
      <button class="dash-bulk-btn" id="bulkClearBtn" aria-label="Clear selection">
        Clear
      </button>
    </div>

    <!-- Normal toolbar -->
    <div class="dash-toolbar" id="normalToolbar" role="toolbar" aria-label="Table controls">
      <span class="dash-toolbar-title">Incidents</span>
      <span class="dash-toolbar-count" id="toolbarCount" aria-live="polite">—</span>

      <!-- Status filters -->
      <div class="dash-filters" role="group" aria-label="Filter by status">
        <button class="dash-filter-btn active" data-group="status" data-val="all" aria-pressed="true">All</button>
        <button class="dash-filter-btn" data-group="status" data-val="active">Active</button>
        <button class="dash-filter-btn" data-group="status" data-val="dispatched">Dispatched</button>
        <button class="dash-filter-btn" data-group="status" data-val="resolved">Resolved</button>
      </div>

      <!-- Type filters -->
      <div class="dash-filters" role="group" aria-label="Filter by type">
        <button class="dash-filter-btn active" data-group="type" data-val="all" aria-pressed="true">All Types</button>
        <button class="dash-filter-btn" data-group="type" data-val="police">Police</button>
        <button class="dash-filter-btn" data-group="type" data-val="fire">Fire</button>
        <button class="dash-filter-btn" data-group="type" data-val="medical">Medical</button>
      </div>

      <div class="dash-toolbar-spacer"></div>

      <!-- Search -->
      <div class="dash-search-wrap">
        <span class="dash-search-icon" aria-hidden="true">⌕</span>
        <input type="search" id="searchInput" class="dash-search"
               placeholder="Search incidents…"
               aria-label="Search incidents by title, description or ID"
               autocomplete="off">
      </div>

      <!-- Export all -->
      <button class="dash-row-btn" id="exportAllBtn" aria-label="Export all to CSV" style="padding:6px 12px;opacity:.7">
        ↓ CSV
      </button>

      <!-- Add new -->
      <button class="dash-btn-add" id="addBtn" aria-label="Create new incident">
        <span aria-hidden="true">+</span> New Incident
      </button>
    </div>

    <!-- Data table -->
    <div class="dash-table-wrap" role="region" aria-label="Incidents table">
      <table class="dash-table" aria-label="Incidents" aria-rowcount="-1">
        <thead>
          <tr>
            <th class="dash-cb-cell" scope="col">
              <input type="checkbox" id="masterCb" class="dash-cb" aria-label="Select all incidents">
            </th>
            <th scope="col" class="sortable" data-col="id">ID <span class="sort-icon">↕</span></th>
            <th scope="col" class="sortable" data-col="title">Title <span class="sort-icon">↕</span></th>
            <th scope="col" class="sortable" data-col="incident_type">Type <span class="sort-icon">↕</span></th>
            <th scope="col" class="sortable" data-col="severity">Severity <span class="sort-icon">↕</span></th>
            <th scope="col" class="sortable" data-col="status">Status <span class="sort-icon">↕</span></th>
            <th scope="col">Coordinates</th>
            <th scope="col" class="sortable" data-col="created_at">Created <span class="sort-icon">↓</span></th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody id="tableBody" aria-live="polite"></tbody>
      </table>

      <!-- Empty state -->
      <div class="dash-empty" id="tableEmpty" hidden role="status" aria-live="polite">
        <div class="dash-empty-icon" aria-hidden="true"></div>
        <p>No incidents match the current filters.</p>
      </div>
    </div>
  </section>

  <!-- ── RIGHT PANEL — detail ───────────────────────────── -->
  <aside class="dash-panel-right" aria-label="Incident detail panel">
    <div class="dash-detail-header">
      <span class="dash-detail-header-title" id="detailHeader">Incident Detail</span>
      <button class="dash-detail-close" id="detailCloseBtn" aria-label="Close detail panel">×</button>
    </div>
    <div class="dash-detail-body" id="detailBody">
      <div class="dash-detail-placeholder" role="status">
        <div class="dash-detail-placeholder-icon" aria-hidden="true"></div>
        <p>Select a row to view incident details and its location on the map.</p>
      </div>
    </div>
  </aside>

</main>

<!-- ═══════════════════════════════════════════════════════
     SIDE DRAWER — Create / Edit form
═══════════════════════════════════════════════════════ -->
<div class="dash-drawer-overlay" id="drawerOverlay" role="dialog"
     aria-modal="true" aria-labelledby="drawerTitle">
  <div class="dash-drawer">

    <!-- Drawer header -->
    <div class="dash-drawer-head">
      <h2 class="dash-drawer-title" id="drawerTitle">New Incident</h2>
      <button class="dash-drawer-close" id="drawerCloseBtn" aria-label="Close form">×</button>
    </div>

    <!-- Step indicator -->
    <nav class="dash-steps" aria-label="Form steps">
      <div class="dash-step active" data-step="1" aria-label="Step 1: Classification">
        <div class="dash-step-num">1</div>
        <div class="dash-step-label">Classification</div>
      </div>
      <div class="dash-step" data-step="2" aria-label="Step 2: Location">
        <div class="dash-step-num">2</div>
        <div class="dash-step-label">Location</div>
      </div>
      <div class="dash-step" data-step="3" aria-label="Step 3: Narrative">
        <div class="dash-step-num">3</div>
        <div class="dash-step-label">Narrative</div>
      </div>
    </nav>

    <!-- Drawer body -->
    <div class="dash-drawer-body">

      <!-- ── Step 1: Core Metadata ── -->
      <div class="dash-step-panel active" data-step="1">
        <div class="dform-group">
          <label class="dform-label" for="f-title">
            Incident Title <span class="req" aria-hidden="true">*</span>
          </label>
          <input type="text" id="f-title" class="dform-input"
                 placeholder="e.g. Armed Robbery on Main St"
                 maxlength="200" autocomplete="off"
                 aria-required="true" aria-describedby="f-title-err">
          <div class="dform-error" id="f-title-err" role="alert" aria-live="polite"></div>
        </div>

        <div class="dform-group">
          <label class="dform-label" for="f-description">Description</label>
          <textarea id="f-description" class="dform-textarea"
                    placeholder="Provide a brief narrative of the incident…"
                    maxlength="1000" rows="3"></textarea>
        </div>

        <!-- Severity selector -->
        <div class="dform-group">
          <div class="dform-label">
            Threat Level <span class="req" aria-hidden="true">*</span>
          </div>
          <div class="dform-sev-track" role="group" aria-label="Select severity 1 to 5">
            <button type="button" class="dform-sev-btn" data-sev="1"
                    aria-label="Severity 1 — Low" title="Low">1</button>
            <button type="button" class="dform-sev-btn" data-sev="2"
                    aria-label="Severity 2 — Guarded" title="Guarded">2</button>
            <button type="button" class="dform-sev-btn" data-sev="3"
                    aria-label="Severity 3 — Elevated" title="Elevated">3</button>
            <button type="button" class="dform-sev-btn" data-sev="4"
                    aria-label="Severity 4 — High" title="High">4</button>
            <button type="button" class="dform-sev-btn" data-sev="5"
                    aria-label="Severity 5 — Critical" title="Critical">5</button>
          </div>
          <div class="dform-help">1 = Low, 5 = Critical</div>
          <div class="dform-error" id="sev-err" role="alert" aria-live="polite"></div>
        </div>

        <!-- Type picker -->
        <div class="dform-group">
          <div class="dform-label">
            Incident Type <span class="req" aria-hidden="true">*</span>
          </div>
          <div class="dform-type-row" role="group" aria-label="Select incident type">
            <label class="dform-type-label" id="type-police-lbl">
              <input type="radio" name="f-type" value="police" aria-label="Police">
              Police
            </label>
            <label class="dform-type-label" id="type-fire-lbl">
              <input type="radio" name="f-type" value="fire" aria-label="Fire">
              Fire
            </label>
            <label class="dform-type-label" id="type-medical-lbl">
              <input type="radio" name="f-type" value="medical" aria-label="Medical">
              Medical
            </label>
          </div>
          <div class="dform-error" id="type-err" role="alert" aria-live="polite"></div>
        </div>

        <!-- Status -->
        <div class="dform-group">
          <label class="dform-label" for="f-status">
            Status <span class="req" aria-hidden="true">*</span>
          </label>
          <select id="f-status" class="dform-select" aria-required="true" aria-describedby="f-status-err">
            <option value="">— Select status —</option>
            <option value="active">Active</option>
            <option value="dispatched">Dispatched</option>
            <option value="resolved">Resolved</option>
          </select>
          <div class="dform-error" id="f-status-err" role="alert" aria-live="polite"></div>
        </div>
      </div>
      <!-- end step 1 -->

      <!-- ── Step 2: Geospatial ── -->
      <div class="dash-step-panel" data-step="2">
        <p style="font-size:12px;color:var(--txt-muted);margin-bottom:16px">
          Enter precise coordinates for the incident location. Latitude must be between −90 and 90; Longitude between −180 and 180.
        </p>

        <div class="dform-row">
          <div class="dform-group">
            <label class="dform-label" for="f-latitude">
              Latitude <span class="req" aria-hidden="true">*</span>
            </label>
            <input type="number" id="f-latitude" class="dform-input"
                   placeholder="42.660000" step="0.000001"
                   min="-90" max="90" data-type="coord"
                   aria-required="true" aria-describedby="f-latitude-err">
            <div class="dform-error" id="f-latitude-err" role="alert" aria-live="polite"></div>
          </div>
          <div class="dform-group">
            <label class="dform-label" for="f-longitude">
              Longitude <span class="req" aria-hidden="true">*</span>
            </label>
            <input type="number" id="f-longitude" class="dform-input"
                   placeholder="21.165000" step="0.000001"
                   min="-180" max="180" data-type="coord"
                   aria-required="true" aria-describedby="f-longitude-err">
            <div class="dform-error" id="f-longitude-err" role="alert" aria-live="polite"></div>
          </div>
        </div>

        <div class="dform-group" style="margin-top:8px">
          <div class="dform-label">Preview</div>
          <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-md);padding:12px;font-size:11px;color:var(--txt-secondary);font-family:'JetBrains Mono',monospace;">
            Enter coordinates above to preview on the map after saving.
          </div>
          <div class="dform-help">
            Tip: Use Google Maps to right-click a location and copy the coordinates.
          </div>
        </div>
      </div>
      <!-- end step 2 -->

      <!-- ── Step 3: Narrative & Audit ── -->
      <div class="dash-step-panel" data-step="3">
        <div class="dform-group">
          <label class="dform-label" for="f-notes">Incident Narrative / Notes</label>
          <textarea id="f-notes" class="dform-textarea"
                    placeholder="Additional operational notes, evidence references, witness accounts…"
                    rows="5" maxlength="2000"></textarea>
          <div class="dform-help">Optional. These notes are stored with the incident log.</div>
        </div>

        <!-- Audit trail preview (populated by JS) -->
        <div class="dash-audit-box" id="auditPreviewBox" aria-label="Audit trail preview" role="region">
          <div class="dash-audit-box-title">Audit Trail Preview</div>
          <div class="dash-audit-row"><span>Loading…</span><span></span></div>
        </div>
      </div>
      <!-- end step 3 -->

    </div><!-- end drawer-body -->

    <!-- Drawer footer -->
    <div class="dash-drawer-foot">
      <button class="dash-drawer-foot-btn secondary" id="drawerPrev"
              aria-label="Go to previous step" style="display:none">
        ← Back
      </button>
      <button class="dash-drawer-foot-btn primary" id="drawerNext"
              aria-label="Go to next step">
        Next →
      </button>
      <button class="dash-drawer-foot-btn primary" id="drawerSave"
              aria-label="Save incident" style="display:none">
        ✓ Save Incident
      </button>
    </div>
  </div>
</div><!-- end drawer overlay -->

<!-- ═══════════════════════════════════════════════════════
     CONFIRM DIALOG
═══════════════════════════════════════════════════════ -->
<div class="dash-confirm-overlay" id="confirmOverlay"
     role="alertdialog" aria-modal="true"
     aria-labelledby="confirmTitle" aria-describedby="confirmText">
  <div class="dash-confirm-box">
    <div class="dash-confirm-icon" aria-hidden="true">⚠️</div>
    <div class="dash-confirm-title" id="confirmTitle">Confirm Action</div>
    <p class="dash-confirm-text" id="confirmText"></p>
    <div class="dash-confirm-btns">
      <button class="dash-confirm-cancel" id="confirmCancel">Cancel</button>
      <button class="dash-confirm-ok"     id="confirmOk">Delete</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     TOAST CONTAINER
═══════════════════════════════════════════════════════ -->
<div class="dash-toast-wrap" id="toastWrap" aria-live="assertive" aria-atomic="false"></div>

<script src="/php-final/public/assets/js/dashboard.js"></script>
</body>
</html>
