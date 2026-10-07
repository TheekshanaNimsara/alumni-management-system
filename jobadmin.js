// ---- Elements ----
const grid            = document.getElementById("adminGrid");
const searchInput     = document.getElementById("adminSearchInput");
const tabs            = document.querySelectorAll("#adminTabs .tab");
const emptyState      = document.getElementById("adminEmptyState");

const pendingCountEl  = document.getElementById("pendingCount");
const approvedCountEl = document.getElementById("approvedCount");
const totalCountEl    = document.getElementById("totalCount");

let activeFilter = "pending";
let allJobs = [];

// ---- Small helpers ----
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function tagClass(type) {
  if (!type) return "";
  return type.toLowerCase().replace(/\s+/g, "");
}

// ---- Update Dashboard Counters ----
function updateCounters() {
  const pending  = allJobs.filter(j => (j.status || "pending") === "pending").length;
  const approved = allJobs.filter(j => (j.status || "pending") === "approved").length;

  if (pendingCountEl)  pendingCountEl.textContent  = pending;
  if (approvedCountEl) approvedCountEl.textContent = approved;
  if (totalCountEl)    totalCountEl.textContent    = allJobs.length;
}

// ---- Build one admin card ----
function buildAdminCard(job) {
  const card = document.createElement("article");
  card.className = "job-card admin-card";
  card.dataset.id = job.id;
  const status = job.status || "pending";
  card.dataset.status = status;

  const initial = escapeHtml(job.company ? job.company.charAt(0).toUpperCase() : "J");

  const statusBadge =
    status === "pending"
      ? '<span class="badge badge-pending">Pending Approval</span>'
      : '<span class="badge badge-approved">Approved</span>';

  const typeTag = job.type
    ? '<span class="tag ' + tagClass(job.type) + '">' + escapeHtml(job.type) + '</span>'
    : "";

  const approveBtn =
    status === "pending"
      ? '<button type="button" class="apply-btn btn-approve-admin" data-id="' + job.id + '">&#10004; Approve</button>'
      : '<button type="button" class="share-btn" disabled style="opacity: 0.6; cursor: default;">&#10004; Approved</button>';

  const deleteBtn =
    '<button type="button" class="share-btn btn-delete-admin" data-id="' + job.id + '" style="color: #a84a57; border-color: #f5c6cb;">Delete</button>';

  const emailLine = job.contact_email
    ? '<p class="muted" style="font-size:13px; color:#475569; margin-top:4px;"><strong>Contact:</strong> ' + escapeHtml(job.contact_email) + '</p>'
    : "";

  const descLine = job.description
    ? '<p class="job-desc">' + escapeHtml(job.description) + '</p>'
    : "";

  const locationText = job.location ? " &middot; " + escapeHtml(job.location) : "";

  card.innerHTML =
    '<div class="job-header">' +
    '<div class="job-logo">' + initial + '</div>' +
    '<div class="job-header-info">' +
    '<h3 class="job-title" title="' + escapeHtml(job.title) + '">' + escapeHtml(job.title) + '</h3>' +
    '<p class="company-name">' + escapeHtml(job.company) + locationText + '</p>' +
    '</div>' +
    '<div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">' +
    statusBadge +
    typeTag +
    '</div>' +
    '</div>' +
    descLine +
    emailLine +
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

  allJobs.forEach(function (job) {
    const status = job.status || "pending";
    let matchesTab = false;

    if (activeFilter === "pending") {
      matchesTab = status === "pending";
    } else if (activeFilter === "approved") {
      matchesTab = status === "approved";
    } else if (activeFilter === "all") {
      matchesTab = true;
    }

    const searchTarget = ((job.title || "") + " " + (job.company || "")).toLowerCase();
    const matchesSearch = searchTarget.includes(search);

    if (matchesTab && matchesSearch) {
      grid.appendChild(buildAdminCard(job));
      visible++;
    }
  });

  emptyState.hidden = visible > 0;
  updateCounters();
}

// ---- Fetch all jobs for admin review ----
function fetchAdminJobs() {
  fetch("job.php?action=get&status=all")
    .then(function (res) { return res.json(); })
    .then(function (jobs) {
      allJobs = jobs;
      renderGrid();
    })
    .catch(function () {
      emptyState.textContent = "Could not load job submissions. Please refresh.";
      emptyState.hidden = false;
    });
}

// ---- Event Delegation for Admin Actions ----
grid.addEventListener("click", function (e) {

  // ── Delete ──
  const deleteBtn = e.target.closest(".btn-delete-admin");
  if (deleteBtn) {
    const id = deleteBtn.dataset.id;
    if (!confirm("Are you sure you want to delete this job? This action cannot be undone.")) return;

    deleteBtn.disabled = true;

    fetch("job.php?action=delete", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then(function (res) { return res.json(); })
      .then(function (result) {
        if (result.success) {
          allJobs = allJobs.filter(j => j.id != id);
          renderGrid();
        } else {
          alert(result.error || "Could not delete this job.");
          deleteBtn.disabled = false;
        }
      })
      .catch(function () {
        alert("Server error when trying to delete job.");
        deleteBtn.disabled = false;
      });
    return;
  }

  // ── Approve ──
  const approveBtn = e.target.closest(".btn-approve-admin");
  if (!approveBtn) return;

  const id = approveBtn.dataset.id;
  approveBtn.disabled = true;
  approveBtn.textContent = "Approving...";

  fetch("job.php?action=approve", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "id=" + encodeURIComponent(id),
  })
    .then(function (res) { return res.json(); })
    .then(function (result) {
      if (result.success) {
        const targetJob = allJobs.find(j => j.id == id);
        if (targetJob) targetJob.status = "approved";
        renderGrid();
      } else {
        alert(result.error || "Could not approve this job.");
        approveBtn.disabled = false;
        approveBtn.textContent = "✔ Approve";
      }
    })
    .catch(function () {
      alert("Server error when trying to approve job.");
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
fetchAdminJobs();
