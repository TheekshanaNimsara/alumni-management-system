// ============================================================
// University Alumni Network - Job Moderation Admin Console
// Server-Side Authorization Handlers • Approve / Reject / Delete
// Dynamic Counters & Glassmorphic Card Presentation
// ============================================================

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

  let statusBadge = "";
  if (status === "pending") {
    statusBadge = '<span class="badge badge-pending" style="background: rgba(245, 158, 11, 0.18); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.35); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Pending Approval</span>';
  } else if (status === "rejected") {
    statusBadge = '<span class="badge badge-rejected" style="background: rgba(239, 68, 68, 0.18); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.35); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Rejected</span>';
  } else {
    statusBadge = '<span class="badge badge-approved" style="background: rgba(16, 185, 129, 0.18); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.35); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Approved</span>';
  }

  const effectiveType = job.job_type || job.type || "Full-Time";
  const typeTag = '<span class="tag ' + tagClass(effectiveType) + '">' + escapeHtml(effectiveType) + '</span>';

  let moderationButtons = "";
  if (status === "pending") {
    moderationButtons = `
      <button type="button" class="apply-btn btn-approve-admin" data-id="${job.id}">&#10004; Approve</button>
      <button type="button" class="share-btn btn-reject-admin" data-id="${job.id}" style="color: #F59E0B; border-color: rgba(245, 158, 11, 0.4);">&#10008; Reject</button>
    `;
  } else if (status === "approved") {
    moderationButtons = `
      <button type="button" class="share-btn" disabled style="opacity: 0.6; cursor: default;">&#10004; Approved</button>
      <button type="button" class="share-btn btn-reject-admin" data-id="${job.id}" style="color: #F59E0B; border-color: rgba(245, 158, 11, 0.4);">&#10008; Reject</button>
    `;
  } else {
    moderationButtons = `
      <button type="button" class="apply-btn btn-approve-admin" data-id="${job.id}">&#10004; Approve</button>
      <button type="button" class="share-btn" disabled style="opacity: 0.6; cursor: default; color: #EF4444;">Rejected</button>
    `;
  }

  const deleteBtn = `<button type="button" class="share-btn btn-delete-admin" data-id="${job.id}" style="color: #EF4444; border-color: rgba(239, 68, 68, 0.35);">Delete</button>`;

  const emailLine = job.contact_email
    ? '<p class="muted" style="font-size:13px; color:var(--text-muted); margin-top:4px;"><strong>Contact:</strong> ' + escapeHtml(job.contact_email) + '</p>'
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
    '<div class="job-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: auto;">' +
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

    const searchTarget = ((job.title || "") + " " + (job.company || "") + " " + (job.location || "")).toLowerCase();
    const matchesSearch = searchTarget.includes(search);

    if (matchesTab && matchesSearch) {
      grid.appendChild(buildAdminCard(job));
      visible++;
    }
  });

  if (emptyState) emptyState.hidden = (visible > 0);
  updateCounters();
}

// ---- Fetch all jobs for admin review ----
async function fetchAdminJobs() {
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
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem auto;">${escapeHtml(reason || "Server-side administrator credentials are required to inspect pending submissions and moderate listings.")}</p>
        <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
          <a href="auth/login.php?redirect=${encodeURIComponent("../jobadmin.html")}" class="btn btn-primary">Log In as Administrator</a>
          <a href="jobs.html" class="btn btn-outline">&larr; Back to Jobs Board</a>
          <a href="index.html" class="btn btn-outline-glass">&larr; Return to Homepage</a>
        </div>
      </div>
    `;
    if (emptyState) emptyState.hidden = true;
  }

  try {
    // 1. Validate session first
    const sessRes = await fetch("job.php?action=session");
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

    // 2. Fetch all jobs
    const res = await fetch("job.php?action=get&status=all");
    if (res.status === 403) {
      showAccessDenied("Forbidden: Administrator privileges required.");
      return;
    }

    const text = await res.text();
    const data = JSON.parse(text);
    allJobs = Array.isArray(data) ? data : [];
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
    if (!confirm("Are you sure you want to delete this job posting permanently?")) return;

    deleteBtn.disabled = true;

    fetch("job.php?action=delete", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          allJobs = allJobs.filter(j => String(j.id) !== String(id));
          renderGrid();
        } else {
          alert(result.error || "Could not delete this job.");
          deleteBtn.disabled = false;
        }
      })
      .catch(() => {
        alert("Server error when deleting job.");
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

    fetch("job.php?action=approve", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          const targetJob = allJobs.find(j => String(j.id) === String(id));
          if (targetJob) targetJob.status = "approved";
          renderGrid();
        } else {
          alert(result.error || "Could not approve this job.");
          approveBtn.disabled = false;
          approveBtn.textContent = "✔ Approve";
        }
      })
      .catch(() => {
        alert("Server error when trying to approve job.");
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

    fetch("job.php?action=reject", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id),
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          const targetJob = allJobs.find(j => String(j.id) === String(id));
          if (targetJob) targetJob.status = "rejected";
          renderGrid();
        } else {
          alert(result.error || "Could not reject this job.");
          rejectBtn.disabled = false;
          rejectBtn.textContent = "✖ Reject";
        }
      })
      .catch(() => {
        alert("Server error when rejecting job.");
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
fetchAdminJobs();
