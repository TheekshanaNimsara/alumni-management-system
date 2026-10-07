// ---- Elements ----
const grid = document.getElementById("adminGrid");
const searchInput = document.getElementById("adminSearchInput");
const tabs = document.querySelectorAll("#adminTabs .tab");
const emptyState = document.getElementById("adminEmptyState");

const pendingCountEl = document.getElementById("pendingCount");
const approvedCountEl = document.getElementById("approvedCount");
const totalCountEl = document.getElementById("totalCount");

let activeFilter = "pending";
let allEvents = [];

// ---- Small helpers ----
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function formatDate(iso) {
  return new Date(iso + "T00:00:00").toLocaleDateString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric",
  });
}

function formatTime(time) {
  const [h, m] = time.split(":").map(Number);
  const hour = h % 12 === 0 ? 12 : h % 12;
  return hour + ":" + String(m).padStart(2, "0") + (h >= 12 ? " PM" : " AM");
}

// ---- Update Dashboard Counters ----
function updateCounters() {
  const pending = allEvents.filter(e => (e.status || "approved") === "pending").length;
  const approved = allEvents.filter(e => (e.status || "approved") === "approved").length;

  if (pendingCountEl) pendingCountEl.textContent = pending;
  if (approvedCountEl) approvedCountEl.textContent = approved;
  if (totalCountEl) totalCountEl.textContent = allEvents.length;
}

// ---- Build one admin card ----
function buildAdminCard(ev) {
  const card = document.createElement("article");
  card.className = "job-card admin-card";
  card.dataset.id = ev.id;
  const status = ev.status || "approved";
  card.dataset.status = status;

  const initial = escapeHtml(ev.title ? ev.title.charAt(0).toUpperCase() : "E");

  const statusBadge =
    status === "pending"
      ? '<span class="badge badge-pending">Pending Approval</span>'
      : '<span class="badge badge-approved">Approved</span>';

  const approveBtn =
    status === "pending"
      ? '<button type="button" class="apply-btn btn-approve-admin" data-id="' + ev.id + '">&#10004; Approve</button>'
      : '<button type="button" class="share-btn" disabled style="opacity: 0.6; cursor: default;">&#10004; Approved</button>';

  const deleteBtn =
    '<button type="button" class="share-btn btn-delete-admin" data-id="' + ev.id + '" style="color: #a84a57; border-color: #f5c6cb;">Delete</button>';

  const dateLine = formatDate(ev.date) + (ev.start && ev.end ? " &middot; " + formatTime(ev.start) + " - " + formatTime(ev.end) : "");
  const regDeadline = ev.regDate ? '<p class="deadline" style="margin-top:6px;">Reg. Last Date: ' + formatDate(ev.regDate) + '</p>' : "";

  card.innerHTML =
    '<div class="job-header">' +
    '<div class="job-logo">' + initial + '</div>' +
    '<div class="job-header-info">' +
    '<h3 class="job-title" title="' + escapeHtml(ev.title) + '">' + escapeHtml(ev.title) + '</h3>' +
    '<p class="company-name">' + escapeHtml(ev.location) + '</p>' +
    '</div>' +
    statusBadge +
    '</div>' +
    '<p class="job-desc" style="margin-top:8px;">' + dateLine + '</p>' +
    regDeadline +
    '<div class="job-actions">' +
    approveBtn +
    deleteBtn +
    '</div>';

  return card;
}

// ---- Render Grid & Apply Filters ----
function renderGrid() {
  grid.innerHTML = "";
  const search = searchInput.value.trim().toLowerCase();
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

    const searchTarget = (ev.title + " " + ev.location).toLowerCase();
    const matchesSearch = searchTarget.includes(search);

    if (matchesTab && matchesSearch) {
      grid.appendChild(buildAdminCard(ev));
      visible++;
    }
  });

  emptyState.hidden = visible > 0;
  updateCounters();
}

// ---- Fetch all events for admin review ----
function fetchAdminEvents() {
  fetch("event.php?action=get")
    .then(function (res) { return res.json(); })
    .then(function (events) {
      allEvents = events;
      renderGrid();
    })
    .catch(function () {
      emptyState.textContent = "Could not load event submissions. Please refresh.";
      emptyState.hidden = false;
    });
}

// ---- Event Delegation for Admin Actions ----
grid.addEventListener("click", function (e) {
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
      .then(function (res) { return res.json(); })
      .then(function (result) {
        if (result.success) {
          allEvents = allEvents.filter(ev => ev.id != id);
          renderGrid();
        } else {
          alert(result.error || "Could not delete this event.");
          deleteBtn.disabled = false;
        }
      })
      .catch(function () {
        alert("Server error when trying to delete event.");
        deleteBtn.disabled = false;
      });
    return;
  }

  const approveBtn = e.target.closest(".btn-approve-admin");
  if (!approveBtn) return;

  const id = approveBtn.dataset.id;
  approveBtn.disabled = true;
  approveBtn.textContent = "Approving...";

  fetch("event.php?action=approve", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "id=" + encodeURIComponent(id),
  })
    .then(function (res) { return res.json(); })
    .then(function (result) {
      if (result.success) {
        const targetEv = allEvents.find(ev => ev.id == id);
        if (targetEv) targetEv.status = "approved";
        renderGrid();
      } else {
        alert(result.error || "Could not approve this event.");
        approveBtn.disabled = false;
        approveBtn.textContent = "✔ Approve";
      }
    })
    .catch(function () {
      alert("Server error when trying to approve event.");
      approveBtn.disabled = false;
      approveBtn.textContent = "✔ Approve";
    });
});

// ---- Tab Switching ----
tabs.forEach(function (tab) {
  tab.addEventListener("click", function () {
    tabs.forEach(function (t) { t.classList.remove("active"); });
    tab.classList.add("active");
    activeFilter = tab.dataset.filter;
    renderGrid();
  });
});

// ---- Live Search ----
searchInput.addEventListener("input", renderGrid);

// Initial Load
fetchAdminEvents();
