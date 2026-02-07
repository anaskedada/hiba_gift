const steps = Array.from(document.querySelectorAll(".step"));
const nextBtn = document.getElementById("next");
const progressEl = document.getElementById("progress");
const clearBtn = document.getElementById("clear");

let current = 0;

// ✅ Objet data pour envoyer à l’API
let data = {};

// -----------------------------
// Progression
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
    textarea: step.querySelector("textarea[data-key]"),
  };
}

function showStep(index) {
  steps.forEach(s => s.classList.remove("active"));
  steps[index].classList.add("active");

  current = index;
  updateProgress();

  const { select, otherInput } = getStepElements(steps[current]);

  if (select && otherInput) {
    otherInput.style.display = select.value === "other" ? "block" : "none";
  }

  nextBtn.textContent =
    current === steps.length - 1 ? "Terminer ✨" : "Suivant ✨";
}

// -----------------------------
// Sauvegarde Réponse (local + data)
// -----------------------------
function saveAnswer(key, value) {
  // Sauvegarde localStorage
  const answers = JSON.parse(localStorage.getItem("loveAnswers") || "{}");
  answers[key] = value;
  localStorage.setItem("loveAnswers", JSON.stringify(answers));

  // Sauvegarde payload API
  data[key] = value;
}

// -----------------------------
// Charger les réponses enregistrées
// -----------------------------
function loadAnswers() {
  data = JSON.parse(localStorage.getItem("loveAnswers") || "{}");

  steps.forEach(step => {
    const { select, otherInput, textarea } = getStepElements(step);

    // textarea
    if (textarea) {
      const key = textarea.dataset.key;
      if (data[key]) textarea.value = data[key];
      return;
    }

    // select
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
      if (otherInput) otherInput.style.display = "none";
    } else {
      select.value = "other";
      if (otherInput) {
        otherInput.value = saved;
        otherInput.style.display = "block";
      }
    }
  });
}

// -----------------------------
// Événement changement select
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
// Click bouton Suivant
// -----------------------------
nextBtn.addEventListener("click", () => {
  const step = steps[current];
  const { select, otherInput, textarea } = getStepElements(step);

  // ✅ Étape textarea obligatoire
  if (textarea) {
    const value = textarea.value.trim();

    if (!value) {
      alert("Merci d’écrire une réponse 💕");
      return;
    }

    saveAnswer(textarea.dataset.key, value);
  }

  // ✅ Étape select
  if (select) {
    let value = select.value;

    if (!value) {
      alert("Merci de choisir une réponse 💕");
      return;
    }

    if (value === "other") {
      value = otherInput.value.trim();

      if (!value) {
        alert("Merci d’écrire ta réponse 💕");
        return;
      }
    }

    saveAnswer(select.dataset.key, value);
  }

  // Next step
  if (current < steps.length - 1) {
    showStep(current + 1);
  } else {
    submit();
  }
});

// -----------------------------
// Bouton Effacer
// -----------------------------
clearBtn.addEventListener("click", () => {
  localStorage.removeItem("loveAnswers");
  data = {};
  location.reload();
});

// -----------------------------
// Envoi vers API
// -----------------------------
function submit() {
  fetch("api/save.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then(() => fetch("api/generate-pdf.php"))
    .then(() => {
      location.href = "valentine.html";
    })
    .catch(err => {
      console.error(err);
      alert("Une erreur est survenue 💔");
    });
}

// -----------------------------
// Init
// -----------------------------
loadAnswers();
showStep(0);
