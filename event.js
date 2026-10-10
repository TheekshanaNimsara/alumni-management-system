// ---- Elements ----
const grid = document.getElementById("eventGrid");
const searchInput = document.getElementById("searchInput");
const tabs = document.querySelectorAll(".tab");
const emptyState = document.getElementById("emptyState");
const sectionHeading = document.getElementById("sectionHeading");

let activeFilter = "all";

// ---- Small helpers ----
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function formatDate(iso) {
  if (!iso) return "";
  return new Date(iso + "T00:00:00").toLocaleDateString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric",
    year: "numeric",
  });
}

function formatTime(time) {
  if (!time) return "";
  const [h, m] = time.split(":").map(Number);
  const hour = h % 12 === 0 ? 12 : h % 12;
  return hour + ":" + String(m).padStart(2, "0") + (h >= 12 ? " PM" : " AM");
}

// ---- Check if event is expired ----
function isEventExpired(dateStr, endTimeStr) {
  if (!dateStr) return false;
  const now = new Date();
  const time = endTimeStr && endTimeStr.trim() ? endTimeStr : "23:59:59";
  const eventEnd = new Date(dateStr + "T" + (time.length === 5 ? time + ":00" : time));
  if (isNaN(eventEnd.getTime())) {
    const todayStr = now.toISOString().split("T")[0];
    return dateStr < todayStr;
  }
  return eventEnd < now;
}

// ---- Build one card for an event ----
// ---- Build one card for an event ----
function buildCard(ev) {
  const card = document.createElement("article");
  card.className = "event-card";
  card.dataset.id = ev.id || "";
  card.dataset.date = ev.date || "";
  card.dataset.end = ev.end || "23:59:59";
  card.dataset.regDate = ev.regDate || "";
  const status = ev.status || "approved";
  card.dataset.status = status;

  const expired = isEventExpired(ev.date, ev.end);
  card.dataset.expired = expired ? "true" : "false";

  let statusBadge = "";
  if (status === "pending") {
    statusBadge = '<span class="badge badge-pending">Pending Approval</span>';
  } else if (expired) {
    statusBadge = '<span class="badge badge-expired">Expired</span>';
  } else {
    statusBadge = '<span class="badge badge-approved">Approved</span>';
  }

  const isRegistered = !!ev.is_registered;
  const registerBtn = expired
    ? '<button type="button" class="btn btn-outline" disabled style="opacity: 0.6; cursor: not-allowed;">Event Closed</button>'
    : (isRegistered
        ? '<button type="button" class="btn btn-primary btn-register registered">Registered &#10004;</button>'
        : '<button type="button" class="btn btn-primary btn-register">Register</button>');

  const eventTitle = ev.title && ev.title.trim() ? ev.title : "University Gathering";
  const attendees = typeof ev.attendee_count !== "undefined" ? parseInt(ev.attendee_count, 10) : 1;
  const attendeeText = attendees === 1 ? "1 person is going" : attendees + " people are going";
  const capacityText = ev.capacity && parseInt(ev.capacity, 10) > 0 ? " &middot; Max Cap: " + ev.capacity : "";
  const descHtml = ev.description && ev.description.trim() ? '<p class="event-desc">' + escapeHtml(ev.description) + '</p>' : "";
  const deadlineHtml = ev.regDate ? '<p class="deadline">Reg. Deadline &mdash; ' + formatDate(ev.regDate) + "</p>" : "";

  card.innerHTML =
    '<div class="card-body">' +
    '<div class="card-title-row">' +
    "<h3>" + escapeHtml(eventTitle) + "</h3>" +
    statusBadge +
    "</div>" +
    descHtml +
    '<p class="muted">' +
    formatDate(ev.date) +
    (ev.start && ev.end ? " &middot; " + formatTime(ev.start) + " - " + formatTime(ev.end) : "") +
    "</p>" +
    '<p class="muted">' +
    escapeHtml(ev.location || "Main Campus") +
    "</p>" +
    '<p class="going"><span class="going-count">' + attendees + '</span> ' + (attendees === 1 ? "person is going" : "people are going") + capacityText + '</p>' +
    deadlineHtml +
    '<div class="card-actions">' +
    registerBtn +
    '<button type="button" class="btn btn-outline btn-share">Share</button>' +
    "</div>" +
    "</div>";

  return card;
}

// ---- Handle registration and share clicks ----
grid.addEventListener("click", function (e) {
  const regBtn = e.target.closest(".btn-register");
  if (regBtn && !regBtn.disabled) {
    const card = regBtn.closest(".event-card");
    if (!card) return;

    const eventId = card.dataset.id;
    regBtn.disabled = true;

    fetch("event.php?action=register", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "event_id=" + encodeURIComponent(eventId),
    })
      .then(function (res) {
        if (res.status === 401) {
          window.location.href = "auth/login.php";
          return null;
        }
        return res.json();
      })
      .then(function (data) {
        regBtn.disabled = false;
        if (!data) return;
        if (data.success) {
          const goingEl = card.querySelector(".going");
          const count = data.attendee_count;
          if (data.is_registered) {
            regBtn.classList.add("registered");
            regBtn.innerHTML = "Registered &#10004;";
          } else {
            regBtn.classList.remove("registered");
            regBtn.textContent = "Register";
          }
          if (goingEl) {
            const label = count === 1 ? "person is going" : "people are going";
            goingEl.innerHTML = '<span class="going-count">' + count + "</span> " + label;
          }
        } else {
          alert(data.error || "Could not register for event.");
        }
      })
      .catch(function () {
        regBtn.disabled = false;
        // Client fallback toggle
        const goingEl = card.querySelector(".going");
        const countSpan = card.querySelector(".going-count");
        let count = countSpan ? parseInt(countSpan.textContent, 10) : 1;
        const isRegistered = regBtn.classList.contains("registered");
        if (!isRegistered) {
          count++;
          regBtn.classList.add("registered");
          regBtn.innerHTML = "Registered &#10004;";
        } else {
          count = Math.max(0, count - 1);
          regBtn.classList.remove("registered");
          regBtn.textContent = "Register";
        }
        if (goingEl) {
          const label = count === 1 ? "person is going" : "people are going";
          goingEl.innerHTML = '<span class="going-count">' + count + "</span> " + label;
        }
      });
    return;
  }

  const shareBtn = e.target.closest(".btn-share");
  if (shareBtn) {
    const card = shareBtn.closest(".event-card");
    const title = card ? (card.querySelector("h3") ? card.querySelector("h3").textContent : "Event") : "Event";
    const shareData = {
      title: title,
      text: "Check out this event: " + title,
      url: window.location.href,
    };

    if (navigator.share) {
      navigator.share(shareData).catch(function () { });
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(window.location.href).then(function () {
        const originalText = shareBtn.textContent;
        shareBtn.textContent = "Link Copied!";
        setTimeout(function () {
          shareBtn.textContent = originalText;
        }, 2000);
      });
    } else {
      alert("Share event link: " + window.location.href);
    }
  }
});

// ---- Show/hide cards based on active filter + search text ----
function filterEvents() {
  const search = searchInput.value.trim().toLowerCase();
  const cards = document.querySelectorAll(".event-card");
  let visible = 0;

  if (sectionHeading) {
    if (activeFilter === "upcoming") {
      sectionHeading.textContent = "Upcoming Events";
    } else if (activeFilter === "my") {
      sectionHeading.textContent = "My Events";
    } else {
      sectionHeading.textContent = "All Events";
    }
  }

  cards.forEach(function (card) {
    const status = card.dataset.status || "approved";
    const date = card.dataset.date;
    const end = card.dataset.end;
    const expired = isEventExpired(date, end);
    card.dataset.expired = expired ? "true" : "false";

    // Update status badge & register button if expired status changed dynamically
    const badge = card.querySelector(".badge");
    if (badge && status === "approved") {
      if (expired) {
        badge.className = "badge badge-expired";
        badge.textContent = "Expired";
        const regBtn = card.querySelector(".btn-register");
        if (regBtn) {
          regBtn.className = "btn btn-outline";
          regBtn.disabled = true;
          regBtn.style.opacity = "0.6";
          regBtn.style.cursor = "not-allowed";
          regBtn.textContent = "Event Closed";
        }
      } else {
        badge.className = "badge badge-approved";
        badge.textContent = "Approved";
      }
    }

    let matchesTab = false;

    if (activeFilter === "upcoming" || activeFilter === "new") {
      // In "Upcoming Events", ONLY show approved events that are NOT expired
      matchesTab = status === "approved" && !expired;
    } else if (activeFilter === "all") {
      // In "All Events", show all approved events
      matchesTab = status === "approved";
    } else if (activeFilter === "my") {
      // In "My Events", show all user-submitted events (pending & approved)
      matchesTab = true;
    }

    const matchesSearch = card.textContent.toLowerCase().includes(search);
    const show = matchesTab && matchesSearch;

    card.hidden = !show;
    if (show) visible++;
  });

  emptyState.hidden = visible > 0;
  if (activeFilter === "upcoming" && visible === 0) {
    emptyState.textContent = "No upcoming events scheduled right now. Check back soon!";
  } else if (visible === 0) {
    emptyState.textContent = "No events found matching your filter or search.";
  }
}

// Default fallback sample events
const fallbackEvents = [
  {
    id: "fb-1",
    title: "Annual Alumni Tech & Innovation Symposium 2026",
    date: "2026-11-15",
    start: "10:00",
    end: "16:00",
    location: "Main Campus Auditorium",
    regDate: "2026-11-10",
    status: "approved"
  },
  {
    id: "fb-2",
    title: "Career Mentorship & Leadership Workshop",
    date: "2026-10-25",
    start: "14:00",
    end: "17:00",
    location: "Online (Zoom)",
    regDate: "2026-10-20",
    status: "approved"
  },
  {
    id: "fb-3",
    title: "Spring Alumni Gala & Networking Dinner",
    date: "2026-09-01",
    start: "18:00",
    end: "22:00",
    location: "Grand Ballroom, City Hotel",
    regDate: "2026-08-25",
    status: "approved"
  }
];

// ---- Fetch events from event.php ----
function loadSubmittedEvents() {
  fetch("event.php?action=get")
    .then(function (res) {
      return res.json();
    })
    .then(function (events) {
      grid.innerHTML = "";
      if (Array.isArray(events) && events.length > 0) {
        events.forEach(function (ev) {
          grid.appendChild(buildCard(ev));
        });
      } else {
        fallbackEvents.forEach(function (ev) {
          grid.appendChild(buildCard(ev));
        });
      }
      filterEvents();
    })
    .catch(function () {
      grid.innerHTML = "";
      fallbackEvents.forEach(function (ev) {
        grid.appendChild(buildCard(ev));
      });
      filterEvents();
    });
}

// ---- Handle URL tab parameters (e.g. event.html?tab=my or tab=upcoming) ----
const params = new URLSearchParams(window.location.search);
const tabParam = params.get("tab");
if (tabParam === "upcoming" || tabParam === "new" || tabParam === "my" || tabParam === "all") {
  activeFilter = tabParam === "new" ? "upcoming" : tabParam;
  tabs.forEach(function (t) {
    t.classList.toggle("active", t.dataset.filter === activeFilter);
  });
}

tabs.forEach(function (tab) {
  tab.addEventListener("click", function () {
    tabs.forEach(function (t) {
      t.classList.remove("active");
    });
    tab.classList.add("active");
    activeFilter = tab.dataset.filter;
    filterEvents();
  });
});

searchInput.addEventListener("input", filterEvents);

// Periodically update expiration & filtering so expired events automatically drop out of upcoming view
setInterval(filterEvents, 10000);

loadSubmittedEvents();
