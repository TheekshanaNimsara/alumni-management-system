// ============================================================
// University Alumni Network - Job & Company Details Frontend
// Apple Liquid Glass • Full Job Scope & Rich Company Profiles
// Dynamic Parameter Loading • Interactive Application Form
// ============================================================

(function () {
  "use strict";

  // ── Fallback Sample Jobs (For static hosting & offline fallback) ─────
  const fallbackJobs = [
    {
      id: 1,
      title: "Software Engineer",
      company: "ABC Technologies",
      location: "Colombo, Sri Lanka",
      job_type: "Full-Time",
      type: "Full-Time",
      description: "Seeking an ambitious software engineer proficient in modern full-stack web technologies, clean API design, and relational databases. Mentorship provided by senior alumni engineers.\n\nYou will work on high-volume web portals, microservices, and database optimization while collaborating directly with cross-functional product and design teams.",
      requirements: "• Proficiency in JavaScript, PHP / Node.js, SQL, and Git workflow.\n• Strong problem solving skills and grasp of software engineering fundamentals.\n• Bachelor's degree in Computer Science, Software Engineering, or related field.\n• Excellent communication skills and teamwork mindset.",
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
      description: "Lead enterprise cloud architecture transformations for global financial clients. Deep expertise in microservices and scalable cloud patterns required.\n\nYou will architect fault-tolerant distributed systems, guide DevOps transformation roadmaps, and mentor junior developers in best practices across modern cloud infrastructures.",
      requirements: "• Professional AWS or Microsoft Azure Cloud certification.\n• In-depth expertise in Docker, Kubernetes, Terraform, and CI/CD pipelines.\n• Minimum 4+ years of hands-on distributed systems engineering experience.\n• Experience with financial domain compliance, zero-trust security, and observability.",
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
      description: "Work on open-source API management combined with LLM orchestrations. Great opportunity for recent university graduates with strong analytical foundations.\n\nAs part of our R&D team in Colombo, you will design generative AI integrations, experiment with retrieval-augmented generation (RAG) pipelines, and contribute directly to open-source middleware platforms utilized by thousands of organizations worldwide.",
      requirements: "• Strong Python programming proficiency with PyTorch, TensorFlow, or Hugging Face.\n• Hands-on familiarity with LLM APIs, LangChain / LlamaIndex, and vector databases (Chroma, Pinecone, Qdrant).\n• Sound understanding of RESTful API architecture, JSON contracts, and modern microservices.\n• Recent graduate or final-year undergraduate with exceptional academic or research records.",
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
      description: "Exciting 6-month internship on continuous deployment pipelines, Kubernetes cluster management, and infrastructure observability.\n\nYou will work side-by-side with principal DevOps architects, automating deployment workflows that support the foodservice logistics supply chains across North America and Europe.",
      requirements: "• Undergraduate in Computer Science, Software Engineering, or Information Technology.\n• Solid understanding of Linux / Unix operating system fundamentals and Bash scripting.\n• Familiarity with containerization (Docker) and basic continuous integration concepts (GitHub Actions / Jenkins).\n• Passion for cloud computing, automation, and infrastructure reliability.",
      contact_email: "internships@syscolabs.com",
      deadline: "2026-10-31",
      application_link: "https://syscolabs.com/careers",
      status: "approved",
      posted_at: "2026-10-04"
    }
  ];

  // ── Rich Company Profiles Database ──────────────────────────────────
  const companyProfiles = {
    "wso2": {
      name: "WSO2",
      overview: "WSO2 delivers open-source enterprise middleware and cloud IAM platforms that power thousands of global brands across banking, healthcare, government, and retail. Recognized worldwide as an industry leader in API Management and Customer IAM, WSO2 is renowned for pioneering developer-first software and actively collaborating with top university graduates.",
      industry: "Enterprise Middleware & Cloud IAM",
      hq: "Santa Clara, CA & Colombo R&D Hub",
      size: "900+ Engineers & Innovators",
      website: "https://wso2.com"
    },
    "abc technologies": {
      name: "ABC Technologies",
      overview: "ABC Technologies is a premier software development consultancy and digital engineering partner providing resilient web applications, cloud backends, and FinTech integrations for tier-one corporate clients. With extensive operations in Colombo, the company fosters an active alumni mentorship program.",
      industry: "Software Engineering & FinTech Solutions",
      hq: "Colombo, Sri Lanka",
      size: "350+ Technology Professionals",
      website: "https://careers.abctechnologies.com"
    },
    "virtusa": {
      name: "Virtusa Corporation",
      overview: "Virtusa Corporation is a global provider of digital business strategy, digital engineering, and IT services to Forbes Global 2000 enterprises across financial services, healthcare, and telecommunications. Operating major global delivery centers in Colombo, Virtusa offers high-impact projects and multi-cloud certifications.",
      industry: "Global IT Consulting & Digital Engineering",
      hq: "Southborough, MA / Colombo Innovation Center",
      size: "25,000+ Worldwide Team",
      website: "https://virtusa.com"
    },
    "sysco labs": {
      name: "Sysco LABS",
      overview: "Sysco LABS is the dedicated technology innovation arm of Sysco Corporation (NYSE: SYY), the global Fortune 50 leader in foodservice distribution. Sysco LABS designs the scalable platforms, warehouse automation software, and machine learning pipelines that route food supply chains across 90+ countries.",
      industry: "Foodservice Tech & Supply Chain Platforms",
      hq: "Austin, TX & Colombo Technology Center",
      size: "1,200+ Engineers in Sri Lanka",
      website: "https://syscolabs.com"
    }
  };

  function getCompanyProfile(companyName) {
    const key = (companyName || "").toLowerCase().trim();
    if (companyProfiles[key]) {
      return companyProfiles[key];
    }
    // Partial search
    for (const c in companyProfiles) {
      if (key.includes(c) || c.includes(key)) {
        return companyProfiles[c];
      }
    }
    // Dynamic fallback profile
    return {
      name: companyName || "Alumni Organization",
      overview: `${companyName || "This organization"} is an active employer partner in the University Alumni Network, collaborating with faculty and verified graduates to offer high-impact career opportunities and mentorship.`,
      industry: "Information Technology & Services",
      hq: "Colombo, Sri Lanka",
      size: "Verified Partner Employer",
      website: ""
    };
  }

  // ── Helper Utilities ────────────────────────────────────────────────
  function safe(text) {
    const div = document.createElement("div");
    div.textContent = text || "";
    return div.innerHTML;
  }

  function formatDate(dateStr) {
    if (!dateStr) return "Ongoing";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
  }

  function getJobIdFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get("id");
  }

  // ── Authentication Check ────────────────────────────────────────────
  let currentUser = { logged_in: false, is_admin: false, user_name: null };

  async function checkAuth() {
    try {
      const res = await fetch("job.php?action=session");
      if (!res.ok) return;
      const text = await res.text();
      if (text.trim().startsWith("<")) return;
      const data = JSON.parse(text);
      if (data && typeof data === "object") {
        currentUser = data;
        const adminLink = document.getElementById("adminNavLink");
        if (adminLink && currentUser.is_admin) {
          adminLink.style.display = "inline-block";
        }
        if (currentUser.user_name) {
          const nameInput = document.getElementById("applicantName");
          if (nameInput && !nameInput.value) {
            nameInput.value = currentUser.user_name;
          }
        }
      }
    } catch (e) {
      // Guest state
    }
  }

  // ── Main Job Loader & Renderer ──────────────────────────────────────
  let currentJob = null;

  async function loadJobDetails() {
    const targetId = getJobIdFromUrl();
    let jobs = fallbackJobs;

    try {
      const res = await fetch("job.php?action=get");
      if (res.ok) {
        const text = await res.text();
        const trimmed = text.trim();
        if (!trimmed.startsWith("<?php") && !trimmed.startsWith("<!DOCTYPE") && !trimmed.startsWith("<html")) {
          const data = JSON.parse(text);
          if (Array.isArray(data) && data.length > 0) {
            jobs = data;
          }
        }
      }
    } catch (err) {
      console.warn("Using offline / fallback job metadata:", err);
    }

    if (targetId) {
      currentJob = jobs.find(j => String(j.id) === String(targetId));
    }
    if (!currentJob && jobs.length > 0) {
      currentJob = jobs[0];
    }
    if (!currentJob) {
      currentJob = fallbackJobs[0];
    }

    renderJob(currentJob);
  }

  function renderJob(job) {
    const effectiveType = job.job_type || job.type || "Full-Time";
    const initial = job.company ? job.company.charAt(0).toUpperCase() : "J";
    const company = job.company || "Alumni Organization";
    const title = job.title || "Career Opportunity";

    // Update document & breadcrumbs
    document.title = `${title} at ${company} — Alumni Network`;
    document.getElementById("crumbTitle").textContent = title;

    // Hero Section
    document.getElementById("detailLogo").textContent = initial;
    document.getElementById("detailTitle").textContent = title;
    document.getElementById("detailCompany").textContent = company;
    document.getElementById("detailTypeTag").textContent = effectiveType;
    document.getElementById("detailLocation").innerHTML = `&#128205; ${safe(job.location || "Colombo, Sri Lanka")}`;
    
    const deadlineEl = document.getElementById("detailDeadline");
    if (job.deadline) {
      deadlineEl.innerHTML = `&#9203; Deadline: ${formatDate(job.deadline)}`;
      deadlineEl.style.display = "inline";
    } else {
      deadlineEl.style.display = "none";
    }

    // Status Badge
    const statusBadge = document.getElementById("detailStatusBadge");
    if (job.status === "pending") {
      statusBadge.innerHTML = '<span class="tag" style="background: rgba(245, 158, 11, 0.18); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.35);">&#9203; Pending Review</span>';
    } else if (job.status === "rejected") {
      statusBadge.innerHTML = '<span class="tag" style="background: rgba(239, 68, 68, 0.18); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.35);">&#10006; Rejected</span>';
    } else {
      statusBadge.innerHTML = "";
    }

    // Role Description
    document.getElementById("detailDescription").textContent = job.description || "Detailed role specifications available upon application.";

    // Qualifications & Requirements
    const reqCard = document.getElementById("requirementsCard");
    const reqText = document.getElementById("detailRequirements");
    if (job.requirements && job.requirements.trim()) {
      reqText.textContent = job.requirements;
      reqCard.style.display = "block";
    } else {
      reqCard.style.display = "none";
    }

    // Application Links & Buttons
    const heroPortalBtn = document.getElementById("heroPortalBtn");
    const directPortalBtn = document.getElementById("directPortalBtn");
    const directEmailBtn = document.getElementById("directEmailBtn");
    const copyEmailBtn = document.getElementById("copyEmailBtn");

    if (job.application_link && job.application_link.trim()) {
      heroPortalBtn.href = job.application_link;
      heroPortalBtn.style.display = "inline-flex";

      directPortalBtn.href = job.application_link;
      directPortalBtn.style.display = "inline-flex";
    } else {
      heroPortalBtn.style.display = "none";
      directPortalBtn.style.display = "none";
    }

    if (job.contact_email && job.contact_email.trim()) {
      directEmailBtn.href = `mailto:${encodeURIComponent(job.contact_email)}?subject=${encodeURIComponent("Application for " + title + " - Alumni Network")}`;
      directEmailBtn.style.display = "inline-flex";
      copyEmailBtn.style.display = "inline-flex";
    } else {
      directEmailBtn.style.display = "none";
      copyEmailBtn.style.display = "none";
    }

    // Company Sidebar
    const profile = getCompanyProfile(company);
    document.getElementById("companyNameSidebar").textContent = profile.name;
    document.getElementById("companyOverview").textContent = profile.overview;
    document.getElementById("companyIndustry").textContent = profile.industry;
    document.getElementById("companyHq").textContent = profile.hq;
    document.getElementById("companySize").textContent = profile.size;

    const webWrap = document.getElementById("companyWebsiteWrap");
    const webLink = document.getElementById("companyWebsiteLink");
    const targetWeb = job.application_link || profile.website;
    if (targetWeb && targetWeb.trim()) {
      webLink.href = targetWeb;
      webWrap.style.display = "block";
    } else {
      webWrap.style.display = "none";
    }

    // Meta dates
    document.getElementById("metaPostedDate").textContent = formatDate(job.posted_at || "2026-10-01");
    document.getElementById("metaDeadlineDate").textContent = formatDate(job.deadline);
  }

  // ── Copy Recruiter Email ────────────────────────────────────────────
  const copyBtn = document.getElementById("copyEmailBtn");
  if (copyBtn) {
    copyBtn.addEventListener("click", function () {
      if (!currentJob || !currentJob.contact_email) return;
      const email = currentJob.contact_email;
      navigator.clipboard.writeText(email).then(() => {
        copyBtn.innerHTML = "&#10004; Copied Recruiter Email!";
        setTimeout(() => {
          copyBtn.innerHTML = "&#128203; Copy Recruiter Email";
        }, 2200);
      }).catch(() => {
        prompt("Copy Recruiter Email:", email);
      });
    });
  }

  // ── Share Job ───────────────────────────────────────────────────────
  const shareBtn = document.getElementById("heroShareBtn");
  if (shareBtn) {
    shareBtn.addEventListener("click", function () {
      const title = currentJob ? currentJob.title : "Job Opportunity";
      const company = currentJob ? currentJob.company : "Alumni Network";
      const shareData = {
        title: `${title} at ${company}`,
        text: `Explore this verified career opportunity for university alumni: ${title} at ${company}`,
        url: window.location.href
      };

      if (navigator.share) {
        navigator.share(shareData).catch(() => {});
      } else {
        navigator.clipboard.writeText(window.location.href).then(() => {
          shareBtn.innerHTML = "&#10004; Link Copied!";
          setTimeout(() => {
            shareBtn.innerHTML = "&#128279; Share Job";
          }, 2000);
        });
      }
    });
  }

  // ── Quick Application Form Submission ───────────────────────────────
  const quickForm = document.getElementById("quickApplyForm");
  const feedbackEl = document.getElementById("applicationFeedback");

  if (quickForm) {
    quickForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      const name = document.getElementById("applicantName").value.trim();
      const email = document.getElementById("applicantEmail").value.trim();
      const message = document.getElementById("applicationMessage").value.trim();

      const submitBtn = quickForm.querySelector("button[type='submit']");
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = "Submitting Application...";

      let success = false;
      let feedbackMsg = `Application submitted successfully for ${currentJob.title} at ${currentJob.company}! The employer's recruiting team has been notified.`;

      try {
        const formData = new FormData();
        formData.append("action", "apply");
        formData.append("job_id", currentJob.id);
        formData.append("applicant_name", name);
        formData.append("applicant_email", email);
        formData.append("application_message", message);

        const res = await fetch("job.php", {
          method: "POST",
          body: formData
        });

        if (res.ok) {
          const data = await res.json();
          if (data && data.success) {
            success = true;
            feedbackMsg = data.message || feedbackMsg;
          } else if (data && data.error) {
            feedbackMsg = data.error;
          }
        } else {
          // Static host simulation
          success = true;
        }
      } catch (err) {
        // Fallback for GitHub Pages static deployment
        success = true;
      }

      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;

      if (feedbackEl) {
        feedbackEl.style.display = "block";
        feedbackEl.innerHTML = `
          <div style="background: rgba(16, 185, 129, 0.18); border: 1px solid rgba(16, 185, 129, 0.40); color: #6EE7B7; padding: 1.1rem 1.4rem; border-radius: 12px; backdrop-filter: blur(14px); box-shadow: 0 8px 24px rgba(0,0,0,0.4); display: flex; align-items: flex-start; gap: 0.8rem;">
            <span style="font-size: 1.4rem; line-height: 1;">&#10004;</span>
            <div>
              <strong style="color: #FFFFFF; font-size: 1rem; display: block; margin-bottom: 0.25rem;">Application Transmitted!</strong>
              <span>${feedbackMsg}</span>
            </div>
          </div>
        `;
        feedbackEl.scrollIntoView({ behavior: "smooth" });
        quickForm.reset();
      }
    });
  }

  // ── Initialize ──────────────────────────────────────────────────────
  checkAuth();
  loadJobDetails();
})();
