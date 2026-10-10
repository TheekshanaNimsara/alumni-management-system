const jobList      = document.getElementById("jobList");
const noResults    = document.getElementById("noResults");
const searchInput  = document.getElementById("searchInput");
const jobTypeFilter = document.getElementById("jobTypeFilter");

let allJobs = [];

/* ── Apply Modal (injected once) ─────────────────────────────────────────── */
const applyModal = document.createElement("div");
applyModal.id = "applyModal";
applyModal.setAttribute("role", "dialog");
applyModal.setAttribute("aria-modal", "true");
applyModal.setAttribute("aria-labelledby", "applyModalTitle");
applyModal.innerHTML = `
  <div class="apply-modal-backdrop" id="applyModalBackdrop"></div>
  <div class="apply-modal-box">
    <button class="apply-modal-close" id="applyModalClose" aria-label="Close">&times;</button>
    <div class="apply-modal-icon">&#9993;</div>
    <h2 class="apply-modal-title" id="applyModalTitle">Apply for <span id="applyModalJobTitle"></span></h2>
    <p class="apply-modal-sub">Send your application directly to the hiring contact.</p>
    <div class="apply-modal-email-row">
      <span class="apply-modal-email" id="applyModalEmail"></span>
      <button class="apply-modal-copy" id="applyModalCopy" title="Copy email">&#128203; Copy</button>
    </div>
    <a class="apply-modal-mailto" id="applyModalMailto" href="#">Open Email Client &rarr;</a>
  </div>
`;
document.body.appendChild(applyModal);

function openApplyModal(title, email) {
  document.getElementById("applyModalJobTitle").textContent = title;
  document.getElementById("applyModalEmail").textContent = email;
  document.getElementById("applyModalMailto").href =
    "mailto:" + encodeURIComponent(email) +
    "?subject=" + encodeURIComponent("Application for " + title);
  applyModal.classList.add("open");
  document.getElementById("applyModalClose").focus();
}

function closeApplyModal() {
  applyModal.classList.remove("open");
}

document.getElementById("applyModalClose").addEventListener("click", closeApplyModal);
document.getElementById("applyModalBackdrop").addEventListener("click", closeApplyModal);
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") closeApplyModal();
});

document.getElementById("applyModalCopy").addEventListener("click", function () {
  const email = document.getElementById("applyModalEmail").textContent;
  const btn = this;
  navigator.clipboard.writeText(email).then(function () {
    btn.textContent = "\u2714 Copied!";
    setTimeout(function () { btn.innerHTML = "&#128203; Copy"; }, 2000);
  }).catch(function () {
    // Fallback
    const ta = document.createElement("textarea");
    ta.value = email;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand("copy");
    document.body.removeChild(ta);
    btn.textContent = "\u2714 Copied!";
    setTimeout(function () { btn.innerHTML = "&#128203; Copy"; }, 2000);
  });
});


/* ── Helpers ─────────────────────────────────────────────────────────────── */
function safe(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function tagClass(type) {
  return type.toLowerCase().replace(/\s+/g, "");
}

function formatDate(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return d.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
}

/* ── Show success alert if redirected back from job.php ──────────────────── */
(function checkBanner() {
  const params = new URLSearchParams(window.location.search);
  if (params.get("success") === "1") {
    history.replaceState({}, "", "jobs.html");
    alert("Job submitted successfully! It has been sent to the Job Admin page for review and will appear here once approved by an admin.");
  }
})();

/* ── Load jobs from unified job.php ─────────────────────────────────────── */
async function loadJobs() {
  try {
    const response = await fetch("job.php?action=get");
    if (!response.ok) throw new Error("Server error " + response.status);
    allJobs = await response.json();
    showJobs();
  } catch (err) {
    jobList.innerHTML = `<p style="color:red">Failed to load jobs: ${err.message}</p>`;
  }
}

/* ── Render filtered/searched job cards ─────────────────────────────────── */
function showJobs() {
  const searchText   = searchInput.value.toLowerCase();
  const selectedType = jobTypeFilter.value;

  const jobs = allJobs.filter(function (job) {
    const matchesStatus = (job.status || "approved") === "approved";
    const loc = job.location ? job.location.toLowerCase() : "";
    const matchesSearch =
      job.title.toLowerCase().includes(searchText) ||
      job.company.toLowerCase().includes(searchText) ||
      loc.includes(searchText);
    const effectiveType = job.job_type || job.type || "Full-Time";
    const matchesType = selectedType === "all" || effectiveType.toLowerCase() === selectedType.toLowerCase();
    return matchesStatus && matchesSearch && matchesType;
  });

  jobList.innerHTML = "";

  jobs.forEach(function (job) {
    const initial = safe(job.company ? job.company.charAt(0).toUpperCase() : "J");
    const hasEmail = job.contact_email && job.contact_email.trim() !== "";
    const effectiveType = job.job_type || job.type || "Full-Time";
    const locText = job.location ? " &middot; " + safe(job.location) : "";
    const deadlineText = job.deadline ? '<p class="deadline" style="margin-top: 4px; font-size: 0.82rem; color: var(--danger-color);">Deadline: ' + formatDate(job.deadline) + '</p>' : '';
    const reqText = job.requirements ? '<p class="muted" style="font-size: 0.82rem; margin-top: 4px;"><strong>Tech / Req:</strong> ' + safe(job.requirements) + '</p>' : '';

    const applyBtn = hasEmail
      ? `<button type="button" class="apply-btn" data-title="${safe(job.title)}" data-email="${safe(job.contact_email)}">Apply Now</button>`
      : `<a href="mailto:careers@company.com?subject=${encodeURIComponent('Application for ' + job.title)}" class="apply-btn">Apply Now</a>`;

    jobList.innerHTML += `
      <div class="job-card">
        <div class="job-header">
          <div class="job-logo">${initial}</div>
          <div class="job-header-info">
            <h3 class="job-title" title="${safe(job.title)}">${safe(job.title)}</h3>
            <p class="company-name">${safe(job.company)}${locText}</p>
          </div>
          <span class="tag ${tagClass(effectiveType)}">${safe(effectiveType)}</span>
        </div>

        ${job.description ? `<p class="job-desc">${safe(job.description)}</p>` : ""}
        ${reqText}
        ${deadlineText}

        <div class="job-actions">
          ${applyBtn}
          <button type="button" class="share-btn">Share</button>
        </div>
      </div>
    `;
  });

  noResults.style.display = jobs.length === 0 ? "block" : "none";
}

/* ── Apply & Share Button Click Handler ─────────────────────────────────── */
jobList.addEventListener("click", function (e) {
  // ── Apply button ──
  const applyBtn = e.target.closest(".apply-btn");
  if (applyBtn && applyBtn.tagName === "BUTTON") {
    e.preventDefault();
    openApplyModal(applyBtn.dataset.title, applyBtn.dataset.email);
    return;
  }

  // ── Share button ──
  const shareBtn = e.target.closest(".share-btn");
  if (!shareBtn) return;

  e.preventDefault();
  const card = shareBtn.closest(".job-card");
  const title = card ? (card.querySelector("h3") ? card.querySelector("h3").textContent : "Job") : "Job";
  const company = card ? (card.querySelector(".company") ? card.querySelector(".company").textContent : "") : "";
  const shareText = company ? `Check out this job posting for ${title} at ${company}` : `Check out this job posting: ${title}`;

  const shareData = {
    title: title,
    text: shareText,
    url: window.location.href,
  };

  if (navigator.share) {
    navigator.share(shareData).catch(function () {});
  } else if (navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href).then(function () {
      const originalText = shareBtn.textContent;
      shareBtn.textContent = "Link Copied!";
      setTimeout(function () {
        shareBtn.textContent = originalText;
      }, 2000);
    });
  } else {
    alert("Share job link: " + window.location.href);
  }
});

/* ── Event listeners ────────────────────────────────────────────────────── */
searchInput.addEventListener("input", showJobs);
jobTypeFilter.addEventListener("change", showJobs);

loadJobs();