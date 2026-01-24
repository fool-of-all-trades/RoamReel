document.addEventListener("DOMContentLoaded", () => {
  // ZDJĘCIE PROFILOWE
  const editIcon = document.getElementById("edit-icon");
  const fileInput = document.getElementById("pfp-upload-input");
  const profilePicContainer = document.getElementById("profile-picture");

  if (editIcon) {
    editIcon.addEventListener("click", () => {
      fileInput.click();
    });
  }

  if (fileInput) {
    fileInput.addEventListener("change", async () => {
      const file = fileInput.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("pfp", file);

      if (profilePicContainer) profilePicContainer.style.opacity = "0.5";

      try {
        const response = await fetch("/upload_pfp", {
          method: "POST",
          body: formData,
        });

        const result = await response.json();

        if (result.status === "success") {
          const newPfpUrl = result.url;
          let imgElement = profilePicContainer.querySelector("img");

          if (!imgElement) {
            profilePicContainer.innerHTML = "";
            imgElement = document.createElement("img");
            imgElement.style.width = "100%";
            imgElement.style.height = "100%";
            imgElement.style.objectFit = "cover";
            imgElement.style.borderRadius = "50%";
            profilePicContainer.appendChild(imgElement);
          }
          imgElement.src = newPfpUrl + "?t=" + new Date().getTime();
        } else {
          alert("Błąd: " + result.message);
        }
      } catch (error) {
        console.error(error);
        alert("Błąd sieci.");
      } finally {
        if (profilePicContainer) profilePicContainer.style.opacity = "1";
        fileInput.value = "";
      }
    });
  }

  // NAWIGACJA ZAKŁADKI
  const btnTimeline = document.getElementById("btn-timeline");
  const btnStats = document.getElementById("btn-stats");
  const sectionTimeline = document.getElementById("timeline-section");
  const sectionStats = document.getElementById("stats-section");

  function switchToTimeline() {
    sectionStats.classList.add("hidden");
    sectionTimeline.classList.remove("hidden");
    btnStats.classList.remove("active");
    btnTimeline.classList.add("active");
  }

  function switchToStats() {
    sectionTimeline.classList.add("hidden");
    sectionStats.classList.remove("hidden");
    btnTimeline.classList.remove("active");
    btnStats.classList.add("active");
  }

  if (btnTimeline && btnStats) {
    btnTimeline.addEventListener("click", switchToTimeline);
    btnStats.addEventListener("click", switchToStats);
  }

  // MODAL WIDEO

  const modal = document.getElementById("videoModal");
  const modalVideo = document.getElementById("modalVideoPlayer");
  const closeModalBtn = document.getElementById("closeModal");
  const tripCards = document.querySelectorAll(".trip-card");

  const shareBtn = document.getElementById("shareBtn");
  const shareStatus = document.getElementById("shareStatus");
  let currentVideoSrc = null;

  function openVideoModal(videoSrc) {
    currentVideoSrc = videoSrc;
    modalVideo.src = videoSrc;
    modal.classList.add("active");
    if (shareStatus) shareStatus.textContent = "";
    if (shareBtn) shareBtn.disabled = false;
    modalVideo.play().catch((e) => console.log("Autoplay blocked:", e));
  }

  async function copyToClipboard(text) {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      return;
    }
    // fallback
    const el = document.createElement("textarea");
    el.value = text;
    document.body.appendChild(el);
    el.select();
    document.execCommand("copy");
    document.body.removeChild(el);
  }

  if (shareBtn) {
    shareBtn.addEventListener("click", async () => {
      if (!currentVideoSrc) return;

      shareBtn.disabled = true;
      if (shareStatus) shareStatus.textContent = "Tworzę link...";

      try {
        const fd = new FormData();
        fd.append("videoPath", currentVideoSrc);

        const res = await fetch("/createShareLink", {
          method: "POST",
          body: fd,
        });
        const data = await res.json().catch(() => null);

        if (!res.ok || !data || data.status !== "success") {
          if (shareStatus)
            shareStatus.textContent = "Nie udało się utworzyć linku.";
          shareBtn.disabled = false;
          return;
        }

        const url = data.url;

        if (navigator.share) {
          try {
            await navigator.share({ title: "My reel", url });
            if (shareStatus) shareStatus.textContent = "Udostępniono ✅";
          } catch (e) {
            // user anulował
            if (shareStatus) shareStatus.textContent = "";
          }
        } else {
          await copyToClipboard(url);
          if (shareStatus) shareStatus.textContent = "Link skopiowany ✅";
        }
      } catch (e) {
        if (shareStatus) shareStatus.textContent = "Błąd sieci.";
      } finally {
        shareBtn.disabled = false;
      }
    });
  }

  function closeVideoModal() {
    modal.classList.remove("active");
    modalVideo.pause();
    modalVideo.src = "";
  }

  tripCards.forEach((card) => {
    card.addEventListener("click", function () {
      const videoPath = this.getAttribute("data-video-src");
      openVideoModal(videoPath);
    });
  });

  if (closeModalBtn) {
    closeModalBtn.addEventListener("click", closeVideoModal);
  }

  if (modal) {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        closeVideoModal();
      }
    });
  }
});
