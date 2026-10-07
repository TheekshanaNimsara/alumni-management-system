// ---- Elements ----
const form          = document.getElementById("eventForm");
const errorBox       = document.getElementById("errorBox");
const dateField      = document.getElementById("date");
const regDateField   = document.getElementById("regDate");
const startField      = document.getElementById("start");
const endField        = document.getElementById("end");

// Don't allow picking dates in the past
const today = new Date().toISOString().split("T")[0];
dateField.min = today;
regDateField.min = today;

// ---- If save-event.php sent us back with ?error=..., show it and
//      refill the fields the visitor already typed ----
const params = new URLSearchParams(window.location.search);
if (params.get("error")) {
  errorBox.hidden = false;
  errorBox.innerHTML = params.get("error")
    .split("|")
    .map(function (msg) { return "<p>" + msg + "</p>"; })
    .join("");

  ["title", "date", "start", "end", "location", "regDate"].forEach(function (name) {
    const field = document.getElementById(name);
    if (field && params.get(name)) field.value = params.get(name);
  });
}

// ---- Friendly client-side checks (save-event.php checks again too) ----
function checkTimes() {
  if (startField.value && endField.value && endField.value <= startField.value) {
    endField.setCustomValidity("End time must be after the start time.");
  } else {
    endField.setCustomValidity("");
  }
}

function checkDeadline() {
  if (regDateField.value && dateField.value && regDateField.value > dateField.value) {
    regDateField.setCustomValidity("Registration deadline must be on or before the event date.");
  } else {
    regDateField.setCustomValidity("");
  }
}

startField.addEventListener("change", checkTimes);
endField.addEventListener("change", checkTimes);
dateField.addEventListener("change", checkDeadline);
regDateField.addEventListener("change", checkDeadline);