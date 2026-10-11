// ============================================================
// University Alumni Network - Event Moderation Admin Console
// Server-Side Authorization Handlers • Approve / Reject / Delete
// Dynamic Counters & Glassmorphic Presentation
// ============================================================

const grid            = document.getElementById("adminGrid");
const searchInput     = document.getElementById("adminSearchInput");
const tabs            = document.querySelectorAll("#adminTabs .tab");
const emptyState      = document.getElementById("adminEmptyState");

const pendingCountEl  = document.getElementById("pendingCount");
const approvedCountEl = document.getElementById("approvedCount");
const totalCountEl    = document.getElementById("totalCount");

let activeFilter = "pending";
let allEvents = [];

// ---- Small helpers ----
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function formatDate(iso) {
  if (!iso) return "";
  const d = new Date(iso + "T00:00:00");
  if (isNaN(d.getTime())) return iso;
  return d.toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
    year: "numeric"
  });
}

function formatTime(time) {
  if (!time) return "";
  const [h, m] = time.split(":").map(Number);
  const hour = h % 12 === 0 ? 12 : h % 12;
  return hour + ":" + String(m).padStart(2, "0") + (h >= 12 ? " PM" : " AM");
}

// ---- Update Dashboard Counters ----
function updateCounters() {
  const pending  = allEvents.filter(e => (e.status || "approved") === "pending").length;
  const approved = allEvents.filter(e => (e.status || "approved") === "approved").length;

  if (pendingCountEl)  pendingCountEl.textContent  = pending;
  if (approvedCountEl) approvedCountEl.textContent = approved;
  if (totalCountEl)    totalCountEl.textContent    = allEvents.length;
}

// ---- Build one admin card ----
function buildAdminCard(ev) {
  const card = document.createElement("article");
  card.className = "job-card admin-card";
  card.dataset.id = ev.id;
  const status = ev.status || "approved";
  card.dataset.status = status;

  const initial = escapeHtml(ev.title ? ev.title.charAt(0).toUpperCase() : "E");

  let statusBadge = "";
  if (status === "pending") {
    statusBadge = '<span class="badge badge-pending" style="background: rgba(245, 158, 11, 0.18); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.35); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Pending Approval</span>';
  } else if (status === "rejected") {
    statusBadge = '<span class="badge badge-rejected" style="background: rgba(239, 68, 68, 0.18); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.35); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Rejected</span>';
  } else {
    statusBadge = '<span class="badge badge-approved" style="background: rgba(16, 185, 129, 0.18); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.35); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Approved</span>';
  }

  let moderationButtons = "";
  if (status === "pending") {
    moderationButtons = `
      <button type="button" class="apply-btn btn-approve-admin" data-id="${ev.id}">&#10004; Approve</button>
      <button type="button" class="share-btn btn-reject-admin" data-id="${ev.id}" style="color: #F59E0B; border-color: rgba(245, 158, 11, 0.4);">&#10008; Reject</button>
    `;
  } else if (status === "approved") {
    moderationButtons = `
      <button type="button" class="share-btn" disabled style="opacity: 0.6; cursor: default;">&#10004; Approved</button>
      <button type="button" class="share-btn btn-reject-admin" data-id="${ev.id}" style="color: #F59E0B; border-color: rgba(245, 158, 11, 0.4);">&#10008; Reject</button>
    `;
  } else {
    moderationButtons = `
      <button type="button" class="apply-btn btn-approve-admin" data-id="${ev.id}">&#10004; Approve</button>
      <button type="button" class="share-btn" disabled style="opacity: 0.6; cursor: default; color: #EF4444;">Rejected</button>
    `;
  }

  const deleteBtn = `<button type="button" class="share-btn btn-delete-admin" data-id="${ev.id}" style="color: #EF4444; border-color: rgba(239, 68, 68, 0.35);">Delete</button>`;

  const dateLine = formatDate(ev.date) + (ev.start && ev.end ? " &middot; " + formatTime(ev.start) + " - " + formatTime(ev.end) : "");
  const regDeadline = ev.regDate ? '<p class="deadline" style="margin-top:6px; font-size: 13px; color: var(--danger-color);">&#9203; Reg. Deadline: ' + formatDate(ev.regDate) + '</p>' : "";
  const descLine = ev.description ? '<p class="job-desc" style="margin-top: 8px;">' + escapeHtml(ev.description) + '</p>' : "";

  card.innerHTML =
    '<div class="job-header">' +
    '<div class="job-logo">' + initial + '</div>' +
    '<div class="job-header-info">' +
    '<h3 class="job-title" title="' + escapeHtml(ev.title) + '">' + escapeHtml(ev.title) + '</h3>' +
    '<p class="company-name">' + escapeHtml(ev.location || "Main Campus") + '</p>' +
    '</div>' +
    statusBadge +
    '</div>' +
    '<p class="job-desc" style="margin-top:8px; font-weight: 600; color: var(--text-color);">&#128197; ' + dateLine + '</p>' +
    descLine +
    regDeadline +
    '<div class="job-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: auto; padding-top: 1rem;">' +
    moderationButtons +
    deleteBtn +
    '</div>';

  return card;
}

// ---- Render Grid & Apply Filters ----
function renderGrid() {
  grid.innerHTML = "";
  const search = searchInput ? searchInput.value.trim().toLowerCase() : "";
  let visible = 0;

  allEvents.forEach(function (ev) {
    const status = ev.status || "approved";
    let matchesTab = false;

    if (activeFilter === "pending") {
      matchesTab = status === "pending";
    } else if (activeFilter === "approved") {
      matchesTab = status === "approved";
    } else if (activeFilter === "all") {
      matchesTab = true;
    }

    const searchTarget = ((ev.title || "") + " " + (ev.location || "") + " " + (ev.description || "")).toLowerCase();
    const matchesSearch = searchTarget.includes(search);

    if (matchesTab && matchesSearch) {
      grid.appendChild(buildAdminCard(ev));
      visible++;
    }
  });

  if (emptyState) emptyState.hidden = (visible > 0);
  updateCounters();
}

// ---- Fetch all events for admin review ----
async function fetchAdminEvents() {
  const statsEl = document.querySelector(".admin-stats");
  const tabsEl = document.getElementById("adminTabs");
  const searchEl = document.getElementById("adminSearchInput");

  function showAccessDenied(reason) {
    document.querySelectorAll(".admin-only-nav").forEach(el => el.style.display = "none");
    if (statsEl) statsEl.style.display = "none";
    if (tabsEl) tabsEl.style.display = "none";
    if (searchEl) searchEl.style.display = "none";
    grid.innerHTML = `
      <div style="grid-column: 1 / -1; padding: 3rem 2rem; text-align: center; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 12px; margin: 2rem 0;">
        <h3 style="color: #EF4444; font-size: 1.5rem; margin-bottom: 0.5rem;">Access Denied</h3>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem auto;">${escapeHtml(reason || "Server-side administrator credentials are required to inspect pending event submissions and manage approvals.")}</p>
        <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
          <a href="auth/login.php?redirect=${encodeURIComponent("../eventadmin.html")}" class="btn btn-primary">Log In as Administrator</a>
          <a href="event.html" class="btn btn-outline">&larr; Back to Events Board</a>
          <a href="index.html" class="btn btn-return-home btn-outline-glass">&larr; Return to Homepage</a>
        </div>
      </div>
    `;
    if (emptyState) emptyState.hidden = true;
  }

  try {
    // 1. Validate session first
    const sessRes = await fetch("event.php?action=session");
    if (!sessRes.ok) {
      showAccessDenied();
      return;
    }
    const sessText = await sessRes.text();
    if (sessText.trim().startsWith("<")) {
      showAccessDenied("Administrative moderation requires an active PHP/MySQL backend session.");
      return;
    }
    const sessData = JSON.parse(sessText);
    if (!sessData || sessData.is_admin !== true) {
      showAccessDenied("Your account does not possess administrator privileges.");
      return;
    }

    // Authenticated administrator verified: reveal admin-only navigation
    document.querySelectorAll(".admin-only-nav").forEach(el => el.style.display = "inline-block");
    if (statsEl) statsEl.style.display = "";
    if (tabsEl) tabsEl.style.display = "";
    if (searchEl) searchEl.style.display = "";

    // 2. Fetch all events
    const res = await fetch("event.php?action=get&status=all");
    if (res.status === 403) {
      showAccessDenied("Forbidden: Administrator privileges required.");
      return;
    }

    const text = await res.text();
    const data = JSON.parse(text);
    allEvents = Array.isArray(data) ? data : [];
    renderGrid();
  } catch (err) {
    showAccessDenied("Could not verify administrative session. Please log in.");
  }
}

// ---- Event Delegation for Admin Actions ----
grid.addEventListener("click", function (e) {
  // ── Delete ──
  const deleteBtn = e.target.closest(".btn-delete-admin");
  if (deleteBtn) {
    const id = deleteBtn.dataset.id;
    if (!confirm("Are you sure you want to delete this event? This action cannot be undone.")) return;

    deleteBtn.disabled = true;

    fetch("event.php?action=delete", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          allEvents = allEvents.filter(ev => String(ev.id) !== String(id));
          renderGrid();
        } else {
          alert(result.error || "Could not delete this event.");
          deleteBtn.disabled = false;
        }
      })
      .catch(() => {
        alert("Server error when trying to delete event.");
        deleteBtn.disabled = false;
      });
    return;
  }

  // ── Approve ──
  const approveBtn = e.target.closest(".btn-approve-admin");
  if (approveBtn) {
    const id = approveBtn.dataset.id;
    approveBtn.disabled = true;
    approveBtn.textContent = "Approving...";

    fetch("event.php?action=approve", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          const targetEv = allEvents.find(ev => String(ev.id) === String(id));
          if (targetEv) targetEv.status = "approved";
          renderGrid();
        } else {
          alert(result.error || "Could not approve this event.");
          approveBtn.disabled = false;
          approveBtn.textContent = "✔ Approve";
        }
      })
      .catch(() => {
        alert("Server error when trying to approve event.");
        approveBtn.disabled = false;
        approveBtn.textContent = "✔ Approve";
      });
    return;
  }

  // ── Reject ──
  const rejectBtn = e.target.closest(".btn-reject-admin");
  if (rejectBtn) {
    const id = rejectBtn.dataset.id;
    rejectBtn.disabled = true;
    rejectBtn.textContent = "Rejecting...";

    fetch("event.php?action=reject", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          const targetEv = allEvents.find(ev => String(ev.id) === String(id));
          if (targetEv) targetEv.status = "rejected";
          renderGrid();
        } else {
          alert(result.error || "Could not reject this event.");
          rejectBtn.disabled = false;
          rejectBtn.textContent = "✖ Reject";
        }
      })
      .catch(() => {
        alert("Server error when trying to reject event.");
        rejectBtn.disabled = false;
        rejectBtn.textContent = "✖ Reject";
      });
    return;
  }
});

// ---- Tab Switching ----
tabs.forEach(function (tab) {
  tab.addEventListener("click", function () {
    tabs.forEach(t => t.classList.remove("active"));
    tab.classList.add("active");
    activeFilter = tab.dataset.filter;
    renderGrid();
  });
});

// ---- Live Search ----
if (searchInput) searchInput.addEventListener("input", renderGrid);

// Initial Load
fetchAdminEvents();
