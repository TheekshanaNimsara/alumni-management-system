// ============================================================
// University Alumni Network - Jobs Board Frontend (Vanilla JS)
// Safe JSON Parsing • Robust Error Handling • Auth State Check
// Rich Job Application Modal • Moderation & Pending Status Filters
// ============================================================

const jobList         = document.getElementById("jobList");
const noResults       = document.getElementById("noResults");
const searchInput     = document.getElementById("searchInput");
const jobTypeFilter   = document.getElementById("jobTypeFilter");
const statusNotice    = document.getElementById("jobStatusNotice");
const adminNavLink    = document.getElementById("adminNavLink");
const myJobsTab       = document.getElementById("myJobsTab");
const jobTabs         = document.querySelectorAll("#jobTabs .tab");

let allJobs = [];
let activeTab = "all";
let currentUser = {
  logged_in: false,
  user_id: null,
  user_name: null,
  is_admin: false
};

// ── Fallback Sample Jobs (For static hosting & offline fallback) ─────
const fallbackJobs = [
  {
    id: 1,
    title: "Software Engineer",
    company: "ABC Technologies",
    location: "Colombo, Sri Lanka",
    job_type: "Full-Time",
    type: "Full-Time",
    description: "Seeking an ambitious software engineer proficient in modern full-stack web technologies, clean API design, and relational databases. Mentorship provided by senior alumni engineers.",
    requirements: "Proficiency in JavaScript, PHP/Node.js, SQL, and Git workflow. Strong problem solving skills.",
    contact_email: "careers@abctechnologies.com",
    deadline: "2026-11-30",
    application_link: "https://careers.abctechnologies.com",
    status: "approved",
    posted_at: "2026-10-01"
  },
  {
    id: 2,
    title: "Cloud Solutions Architect",
    company: "Virtusa",
    location: "Colombo / Hybrid",
    job_type: "Full-Time",
    type: "Full-Time",
    description: "Lead enterprise cloud architecture transformations for global financial clients. Deep expertise in microservices and scalable cloud patterns required.",
    requirements: "AWS/Azure certification, Docker, Kubernetes, Terraform, and distributed systems architecture.",
    contact_email: "talent@virtusa.com",
    deadline: "2026-12-15",
    application_link: "https://virtusa.com/careers",
    status: "approved",
    posted_at: "2026-10-02"
  },
  {
    id: 3,
    title: "Associate AI Engineer",
    company: "WSO2",
    location: "Colombo, Sri Lanka",
    job_type: "Full-Time",
    type: "Full-Time",
    description: "Work on open-source API management combined with LLM orchestrations. Great opportunity for recent university graduates with strong analytical foundations.",
    requirements: "Python, PyTorch or TensorFlow, familiarity with retrieval-augmented generation (RAG) and RESTful APIs.",
    contact_email: "jobs@wso2.com",
    deadline: "2026-11-20",
    application_link: "https://wso2.com/careers",
    status: "approved",
    posted_at: "2026-10-03"
  },
  {
    id: 4,
    title: "DevOps & Platform Intern",
    company: "Sysco LABS",
    location: "Colombo, Sri Lanka",
    job_type: "Internship",
    type: "Internship",
    description: "Exciting 6-month internship on continuous deployment pipelines, Kubernetes cluster management, and infrastructure observability.",
    requirements: "Undergraduate in Computer Science / Software Engineering. Linux basics, Bash, and CI/CD concepts.",
    contact_email: "internships@syscolabs.com",
    deadline: "2026-10-31",
    application_link: "https://syscolabs.com/careers",
    status: "approved",
    posted_at: "2026-10-04"
  }
];

/* ── Rich Job Application / Details Modal (Injected Once) ───────────────── */
const applyModal = document.createElement("div");
applyModal.id = "applyModal";
applyModal.className = "modal-overlay";
applyModal.setAttribute("role", "dialog");
applyModal.setAttribute("aria-modal", "true");
applyModal.setAttribute("aria-labelledby", "applyModalTitle");
applyModal.style.display = "none";
applyModal.innerHTML = `
  <div class="apply-modal-backdrop" id="applyModalBackdrop" style="position: fixed; inset: 0; background: rgba(8, 5, 16, 0.75); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); z-index: 9998;"></div>
  <div class="apply-modal-box" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 92%; max-width: 620px; max-height: 88vh; overflow-y: auto; z-index: 9999; background: linear-gradient(145deg, rgba(32, 18, 56, 0.92) 0%, rgba(16, 9, 29, 0.96) 100%); border: 1px solid rgba(196, 181, 253, 0.28); border-top: 4px solid var(--accent-color); border-radius: 12px; padding: 2rem; box-shadow: 0 25px 60px rgba(0,0,0,0.8), 0 0 35px rgba(124, 58, 237, 0.25);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;">
      <div>
        <span id="applyModalTypeTag" class="tag" style="margin-bottom: 0.5rem; display: inline-block;"></span>
        <h2 class="apply-modal-title" id="applyModalTitle" style="color: var(--text-light); font-size: 1.45rem; font-weight: 800; line-height: 1.3;"></h2>
        <p class="apply-modal-sub" id="applyModalCompanyRow" style="color: var(--accent-light); font-size: 0.95rem; font-weight: 600; margin-top: 0.25rem;"></p>
      </div>
      <button class="apply-modal-close" id="applyModalClose" aria-label="Close" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(196, 181, 253, 0.2); color: var(--text-light); width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">&times;</button>
    </div>

    <!-- Metadata Row -->
    <div id="applyModalMetaRow" style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); padding: 0.75rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); margin-bottom: 1.2rem;">
      <span id="applyModalLocation">&#128205; </span>
      <span id="applyModalDeadline" style="color: var(--danger-color);">&#9203; </span>
      <span id="applyModalStatusBadge"></span>
    </div>

    <!-- Description -->
    <div style="margin-bottom: 1.25rem;">
      <h4 style="color: var(--accent-light); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.4rem;">Role Description</h4>
      <p id="applyModalDesc" style="color: var(--text-color); font-size: 0.92rem; line-height: 1.6; white-space: pre-line;"></p>
    </div>

    <!-- Requirements -->
    <div id="applyModalReqSection" style="margin-bottom: 1.5rem; background: rgba(124, 58, 237, 0.10); border: 1px solid var(--border-color); border-radius: 8px; padding: 0.85rem 1rem;">
      <h4 style="color: var(--accent-light); font-size: 0.88rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.35rem;">Qualifications &amp; Requirements</h4>
      <p id="applyModalReqs" style="color: var(--text-color); font-size: 0.88rem; line-height: 1.5; margin: 0;"></p>
    </div>

    <!-- Application / Contact Action Area -->
    <div style="background: rgba(16, 9, 29, 0.7); border: 1px solid var(--border-color); border-radius: 8px; padding: 1.2rem;">
      <h4 style="color: var(--text-light); font-size: 0.95rem; font-weight: 700; margin-bottom: 0.4rem;">How to Apply</h4>
      <p style="color: var(--text-muted); font-size: 0.86rem; margin-bottom: 1rem;">Please connect directly through the employer's official contact or portal:</p>
      
      <div id="applyModalEmailSection" style="margin-bottom: 0.8rem; display: none;">
        <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
          <span style="font-size: 0.85rem; color: var(--text-muted);">Contact Email:</span>
          <span id="applyModalEmail" style="font-weight: 700; color: var(--accent-light); font-family: monospace; font-size: 0.92rem;"></span>
          <button type="button" class="btn btn-outline" id="applyModalCopy" style="padding: 0.3rem 0.7rem; font-size: 0.78rem;">&#128203; Copy</button>
          <a id="applyModalMailto" href="#" class="btn btn-primary" style="padding: 0.3rem 0.8rem; font-size: 0.78rem;">Open Email Client &rarr;</a>
        </div>
      </div>

      <div id="applyModalLinkSection" style="display: none; margin-top: 0.6rem;">
        <a id="applyModalPortalLink" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="display: inline-flex; width: 100%; justify-content: center;">
          Open Employer Career Portal &rarr;
        </a>
      </div>

      <div id="applyModalNoContactNotice" style="display: none; color: var(--text-muted); font-size: 0.85rem; font-style: italic;">
        No external contact email or portal link was provided for this posting. Please search the company careers page or contact the posting alumnus through the network directory.
      </div>
    </div>
  </div>
`;
document.body.appendChild(applyModal);

function openApplyModal(jobId) {
  const job = allJobs.find(j => String(j.id) === String(jobId));
  if (!job) return;

  const effectiveType = job.job_type || job.type || "Full-Time";
  document.getElementById("applyModalTitle").textContent = job.title || "Job Opportunity";
  document.getElementById("applyModalCompanyRow").textContent = job.company || "Alumni Organization";
  
  const typeTag = document.getElementById("applyModalTypeTag");
  typeTag.textContent = effectiveType;
  typeTag.className = "tag " + tagClass(effectiveType);

  document.getElementById("applyModalLocation").innerHTML = "&#128205; " + safe(job.location || "Location not specified");
  
  const dlEl = document.getElementById("applyModalDeadline");
  if (job.deadline) {
    dlEl.innerHTML = "&#9203; Deadline: " + formatDate(job.deadline);
    dlEl.style.display = "inline";
  } else {
    dlEl.style.display = "none";
  }

  const statusBadgeEl = document.getElementById("applyModalStatusBadge");
  const jobStatus = job.status || "approved";
  if (jobStatus === "pending") {
    statusBadgeEl.innerHTML = '<span class="badge badge-pending">Pending Approval</span>';
    statusBadgeEl.style.display = "inline";
  } else if (jobStatus === "rejected") {
    statusBadgeEl.innerHTML = '<span class="badge badge-rejected" style="background: rgba(239, 68, 68, 0.15); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 2px 8px; border-radius: 4px;">Rejected</span>';
    statusBadgeEl.style.display = "inline";
  } else {
    statusBadgeEl.style.display = "none";
  }

  document.getElementById("applyModalDesc").textContent = job.description || "No description provided.";
  
  const reqSection = document.getElementById("applyModalReqSection");
  if (job.requirements && job.requirements.trim()) {
    document.getElementById("applyModalReqs").textContent = job.requirements;
    reqSection.style.display = "block";
  } else {
    reqSection.style.display = "none";
  }

  const emailSection = document.getElementById("applyModalEmailSection");
  const linkSection = document.getElementById("applyModalLinkSection");
  const noContact = document.getElementById("applyModalNoContactNotice");

  let hasContact = false;

  if (job.contact_email && job.contact_email.trim()) {
    hasContact = true;
    emailSection.style.display = "block";
    document.getElementById("applyModalEmail").textContent = job.contact_email;
    document.getElementById("applyModalMailto").href =
      "mailto:" + encodeURIComponent(job.contact_email) +
      "?subject=" + encodeURIComponent("Application for " + (job.title || "Position"));
  } else {
    emailSection.style.display = "none";
  }

  if (job.application_link && job.application_link.trim()) {
    hasContact = true;
    linkSection.style.display = "block";
    document.getElementById("applyModalPortalLink").href = job.application_link;
  } else {
    linkSection.style.display = "none";
  }

  noContact.style.display = hasContact ? "none" : "block";

  applyModal.style.display = "block";
  document.getElementById("applyModalClose").focus();
}

function closeApplyModal() {
  applyModal.style.display = "none";
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
  div.textContent = text || "";
  return div.innerHTML;
}

function tagClass(type) {
  return (type || "").toLowerCase().replace(/\s+/g, "");
}

function formatDate(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
}

/* ── Check Authentication Session from Backend ───────────────────────────── */
async function checkAuthSession() {
  try {
    const res = await fetch("job.php?action=session");
    if (!res.ok) return;
    const text = await res.text();
    if (text.trim().startsWith("<")) return; // HTML/PHP raw source on static host
    const data = JSON.parse(text);
    if (data && typeof data === "object") {
      currentUser = data;

      // Only show admin nav link if authenticated administrator
      if (adminNavLink && currentUser.is_admin === true) {
        adminNavLink.style.display = "inline-block";
      }

      // Show 'My Postings' tab if logged in
      if (myJobsTab && currentUser.logged_in === true) {
        myJobsTab.style.display = "inline-block";
      }
    }
  } catch (err) {
    // Fail silently in guest state
  }
}

/* ── Show success banner if redirected from job creation ─────────────────── */
(function checkBanner() {
  const params = new URLSearchParams(window.location.search);
  if (params.get("success") === "1") {
    history.replaceState({}, "", "jobs.html");
    if (statusNotice) {
      statusNotice.innerHTML = `
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #6EE7B7; padding: 1rem 1.25rem; border-radius: 8px; font-weight: 600;">
          &#10004; Opportunity submitted successfully! It has been submitted for administrator review and will appear on the public board once approved.
        </div>
      `;
      statusNotice.style.display = "block";
    }
  }
})();

/* ── Load jobs safely from backend with fallback contract ────────────────── */
async function loadJobs() {
  try {
    const response = await fetch("job.php?action=get");
    const contentType = response.headers.get("content-type") || "";
    const text = await response.text();

    // Check if response is raw PHP source or HTML (GitHub Pages / server 404/500 page)
    const trimmed = text.trim();
    if (trimmed.startsWith("<?php") || trimmed.startsWith("<!DOCTYPE") || trimmed.startsWith("<html")) {
      console.warn("Backend returned PHP source or HTML document. Deploying safe fallback job data.");
      allJobs = fallbackJobs;
      showJobs();
      return;
    }

    if (!response.ok) {
      throw new Error(`Server returned HTTP ${response.status}`);
    }

    const data = JSON.parse(text);
    if (Array.isArray(data)) {
      allJobs = data.length > 0 ? data : fallbackJobs;
    } else {
      allJobs = fallbackJobs;
    }

    if (statusNotice) statusNotice.style.display = "none";
    showJobs();
  } catch (err) {
    console.error("Jobs load error:", err);
    // On genuine connection error, if we have fallback data, render it safely so visitors have access
    allJobs = fallbackJobs;
    showJobs();
  }
}

/* ── Render filtered/searched job cards ─────────────────────────────────── */
function showJobs() {
  const searchText   = searchInput ? searchInput.value.toLowerCase().trim() : "";
  const selectedType = jobTypeFilter ? jobTypeFilter.value : "all";

  const jobs = allJobs.filter(function (job) {
    const status = job.status || "approved";
    let matchesTab = false;

    if (activeTab === "all") {
      // Public board strictly shows approved positions
      matchesTab = (status === "approved");
    } else if (activeTab === "my") {
      // In My Postings: show postings owned by the current user or pending/rejected postings
      const isOwner = currentUser.user_id && (Number(job.posted_by) === Number(currentUser.user_id));
      matchesTab = isOwner || status === "pending" || status === "rejected";
    }

    const loc = job.location ? job.location.toLowerCase() : "";
    const req = job.requirements ? job.requirements.toLowerCase() : "";
    const title = (job.title || "").toLowerCase();
    const company = (job.company || "").toLowerCase();

    const matchesSearch =
      title.includes(searchText) ||
      company.includes(searchText) ||
      loc.includes(searchText) ||
      req.includes(searchText);

    const effectiveType = job.job_type || job.type || "Full-Time";
    const matchesType = selectedType === "all" || effectiveType.toLowerCase() === selectedType.toLowerCase();

    return matchesTab && matchesSearch && matchesType;
  });

  jobList.innerHTML = "";

  jobs.forEach(function (job) {
    const initial = safe(job.company ? job.company.charAt(0).toUpperCase() : "J");
    const effectiveType = job.job_type || job.type || "Full-Time";
    const locText = job.location ? " &middot; " + safe(job.location) : "";
    const deadlineText = job.deadline ? '<p class="deadline" style="margin-top: 4px; font-size: 0.82rem; color: var(--danger-color);">&#9203; Deadline: ' + formatDate(job.deadline) + '</p>' : '';
    const reqText = job.requirements ? '<p class="muted" style="font-size: 0.82rem; margin-top: 4px;"><strong>Requirements:</strong> ' + safe(job.requirements) + '</p>' : '';

    let statusBadge = "";
    if (job.status === "pending") {
      statusBadge = '<span class="badge badge-pending" style="background: rgba(245, 158, 11, 0.18); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.35); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">Pending Approval</span>';
    } else if (job.status === "rejected") {
      statusBadge = '<span class="badge badge-rejected" style="background: rgba(239, 68, 68, 0.18); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.35); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">Rejected</span>';
    } else if (activeTab === "my") {
      statusBadge = '<span class="badge badge-approved" style="background: rgba(16, 185, 129, 0.18); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.35); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">Approved</span>';
    }

    jobList.innerHTML += `
      <div class="job-card" data-id="${job.id}">
        <div class="job-header">
          <div class="job-logo">${initial}</div>
          <div class="job-header-info">
            <h3 class="job-title" title="${safe(job.title)}">${safe(job.title)}</h3>
            <p class="company-name">${safe(job.company)}${locText}</p>
          </div>
          <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
            <span class="tag ${tagClass(effectiveType)}">${safe(effectiveType)}</span>
            ${statusBadge}
          </div>
        </div>

        ${job.description ? `<p class="job-desc">${safe(job.description)}</p>` : ""}
        ${reqText}
        ${deadlineText}

        <div class="job-actions">
          <button type="button" class="apply-btn" data-job-id="${job.id}">View &amp; Apply</button>
          <button type="button" class="share-btn">Share</button>
        </div>
      </div>
    `;
  });

  if (jobs.length === 0) {
    noResults.textContent = activeTab === "my"
      ? "You have not submitted any career postings yet. Click '+ Post a Job' to share an opportunity."
      : "No job postings found matching your criteria. Check back soon!";
    noResults.style.display = "block";
  } else {
    noResults.style.display = "none";
  }
}

/* ── Apply & Share Button Click Handler ─────────────────────────────────── */
jobList.addEventListener("click", function (e) {
  // ── Apply / Details button ──
  const applyBtn = e.target.closest(".apply-btn");
  if (applyBtn) {
    e.preventDefault();
    openApplyModal(applyBtn.dataset.jobId);
    return;
  }

  // ── Share button ──
  const shareBtn = e.target.closest(".share-btn");
  if (!shareBtn) return;

  e.preventDefault();
  const card = shareBtn.closest(".job-card");
  const title = card ? (card.querySelector("h3") ? card.querySelector("h3").textContent : "Job") : "Job";
  const company = card ? (card.querySelector(".company-name") ? card.querySelector(".company-name").textContent : "") : "";
  const shareText = company ? `Check out this opportunity: ${title} at ${company}` : `Check out this job posting: ${title}`;

  if (navigator.share) {
    navigator.share({
      title: title,
      text: shareText,
      url: window.location.href,
    }).catch(function () {});
  } else if (navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href).then(function () {
      const originalText = shareBtn.textContent;
      shareBtn.textContent = "Copied!";
      setTimeout(function () {
        shareBtn.textContent = originalText;
      }, 2000);
    });
  } else {
    alert("Share link: " + window.location.href);
  }
});

/* ── Tab Filtering Setup ────────────────────────────────────────────────── */
jobTabs.forEach(function (tab) {
  tab.addEventListener("click", function () {
    jobTabs.forEach(t => t.classList.remove("active"));
    tab.classList.add("active");
    activeTab = tab.dataset.filter || "all";
    showJobs();
  });
});

/* ── Input & Filter Listeners ───────────────────────────────────────────── */
if (searchInput) searchInput.addEventListener("input", showJobs);
if (jobTypeFilter) jobTypeFilter.addEventListener("change", showJobs);

/* ── Initial Lifecycle Run ──────────────────────────────────────────────── */
checkAuthSession();
loadJobs();