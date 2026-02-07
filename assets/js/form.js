const steps = Array.from(document.querySelectorAll(".step"));
const nextBtn = document.getElementById("next");
const progressEl = document.getElementById("progress");
const clearBtn = document.getElementById("clear");

let current = 0;

// ✅ Data object for API submit
let data = {};

// -----------------------------
// Progress
// -----------------------------
function updateProgress() {
  progressEl.textContent = `${current + 1} / ${steps.length}`;
}

// -----------------------------
// Helpers
// -----------------------------
function getStepElements(step) {
  return {
    select: step.querySelector("select[data-key]"),
    otherInput: step.querySelector(".other-input"),
  };
}

function showStep(index) {
  steps.forEach(s => s.classList.remove("active"));
  steps[index].classList.add("active");

  current = index;
  updateProgress();

  const { select, otherInput } = getStepElements(steps[current]);

  if (select && otherInput) {
    if (select.value === "other") {
      otherInput.style.display = "block";
    } else {
      otherInput.style.display = "none";
    }
  }

  nextBtn.textContent =
    current === steps.length - 1 ? "Finish ✨" : "Next ✨";
}

// -----------------------------
// Save Answer (local + data)
// -----------------------------
function saveAnswer(key, value) {
  // Save to localStorage
  const answers = JSON.parse(localStorage.getItem("loveAnswers") || "{}");
  answers[key] = value;
  localStorage.setItem("loveAnswers", JSON.stringify(answers));

  // Save to API payload object
  data[key] = value;
}

// -----------------------------
// Load saved answers
// -----------------------------
function loadAnswers() {
  data = JSON.parse(localStorage.getItem("loveAnswers") || "{}");

  steps.forEach(step => {
    const { select, otherInput } = getStepElements(step);
    if (!select) return;

    const key = select.dataset.key;
    const saved = data[key];

    if (!saved) return;

    const options = Array.from(select.options).map(
      o => o.value || o.textContent
    );

    const match = options.find(o => o === saved);

    if (match && match !== "other") {
      select.value = saved;
      otherInput.style.display = "none";
    } else {
      select.value = "other";
      otherInput.value = saved;
      otherInput.style.display = "block";
    }
  });
}

// -----------------------------
// Select change event
// -----------------------------
steps.forEach(step => {
  const { select, otherInput } = getStepElements(step);
  if (!select || !otherInput) return;

  select.addEventListener("change", () => {
    if (select.value === "other") {
      otherInput.style.display = "block";
      otherInput.focus();
    } else {
      otherInput.style.display = "none";
      otherInput.value = "";
    }
  });
});

// -----------------------------
// Next Button Click
// -----------------------------
nextBtn.addEventListener("click", () => {
  const step = steps[current];
  const { select, otherInput } = getStepElements(step);

  if (!select) return;

  let value = select.value;

  if (!value) {
    alert("Please choose an answer 💕");
    return;
  }

  if (value === "other") {
    value = otherInput.value.trim();
    if (!value) {
      alert("Write your answer 💕");
      return;
    }
  }

  const key = select.dataset.key;
  saveAnswer(key, value);

  if (current < steps.length - 1) {
    showStep(current + 1);
  } else {
    submit();
  }
});

// -----------------------------
// Clear Button
// -----------------------------
clearBtn.addEventListener("click", () => {
  localStorage.removeItem("loveAnswers");
  data = {};
  location.reload();
});

// -----------------------------
// Submit to API
// -----------------------------
function submit() {
  fetch("api/save.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then(() => fetch("api/generate-pdf.php"))
    // .then(() => fetch("api/send-mail.php"))
    .then(() => {
      location.href = "valentine.html";
    })
    .catch(err => {
      console.error(err);
      alert("Something went wrong 💔");
    });
}

// -----------------------------
// Init
// -----------------------------
loadAnswers();
showStep(0);
