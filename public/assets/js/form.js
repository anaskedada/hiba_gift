const steps = Array.from(document.querySelectorAll(".step"));
const nextBtn = document.getElementById("next");
const progressEl = document.getElementById("progress");
const clearBtn = document.getElementById("clear");

// ✅ Loader overlay (doit exister dans le HTML)
const loadingOverlay = document.getElementById("loadingOverlay");

let current = 0;

// ✅ Objet data pour envoyer à l’API
let data = {};

// -----------------------------
// Loader
// -----------------------------
function setLoading(isLoading) {
  if (!loadingOverlay) return;

  loadingOverlay.classList.toggle("active", isLoading);
  loadingOverlay.setAttribute("aria-hidden", String(!isLoading));

  // Empêche double clic
  nextBtn.disabled = isLoading;
  clearBtn.disabled = isLoading;
}

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
  const answers = JSON.parse(localStorage.getItem("loveAnswers") || "{}");
  answers[key] = value;
  localStorage.setItem("loveAnswers", JSON.stringify(answers));

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
// Click bouton Suivant / Terminer
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
    submit(); // Terminer
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
// Envoi vers API (avec loader)
// -----------------------------
async function submit() {
  try {
    setLoading(true);

    // 1) Save
    const resSave = await fetch("api/save.php?nocache=" + Date.now(), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
      cache: "no-store",
    });

    if (!resSave.ok) {
      throw new Error("save.php a échoué: " + resSave.status);
    }

    // 2) Generate PDF (attendre la fin) + récupérer JSON
    const resPdf = await fetch("api/generate-pdf.php?nocache=" + Date.now(), {
      method: "GET",
      cache: "no-store",
    });

    if (!resPdf.ok) {
      throw new Error("generate-pdf.php a échoué: " + resPdf.status);
    }

    // ✅ si generate-pdf.php retourne JSON (recommandé)
    const out = await resPdf.json(); // { success:true, pdf:'...', png:'...' }

    if (!out || out.success !== true) {
      throw new Error(out?.message || "Génération PDF échouée");
    }

    // ✅ Optionnel: mettre à jour le lien cadeau (si présent sur la page suivante)
    // Si tu as un lien <a id="giftLink" ...> dans valentine.html,
    // on peut passer le chemin via querystring :
    const qs = new URLSearchParams();
    if (out.png) qs.set("png", out.png);
    if (out.pdf) qs.set("pdf", out.pdf);

    // ✅ Optionnel: clear storage après génération OK
    localStorage.removeItem("loveAnswers");

    // 3) Redirect avec les nouveaux fichiers (anti-cache)
    location.href = "valentine.html?" + qs.toString();
  } catch (err) {
    console.error(err);
    alert("Une erreur est survenue 💔");
    setLoading(false);
  }
}

// -----------------------------
// Init
// -----------------------------
loadAnswers();
showStep(0);
