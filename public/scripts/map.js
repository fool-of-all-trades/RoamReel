    const panZoom = svgPanZoom('#allSvg', {
        zoomEnabled: true,
        panEnabled: true,
        minZoom: 1,
        maxZoom: 5,
        fit: true,
        center: true,
        beforePan: function(oldPan, newPan) {
        const padding = 80;
        const sizes = this.getSizes();
        const zoom = this.getZoom();

        const maxWidthOffset = sizes.width - sizes.viewBox.width * zoom;
        const maxHeightOffset = sizes.height - sizes.viewBox.height * zoom + padding;
        const minPanX = sizes.width - (sizes.viewBox.width * zoom);

        return {
            x: Math.min(padding, Math.max(newPan.x, Math.max(maxWidthOffset, minPanX))),
            y: Math.min(padding, Math.max(newPan.y, maxHeightOffset))
        };
    }

    });

const countries = document.querySelectorAll("#allSvg .allPaths");
    const panel = document.getElementById("countryGallery");
    const closeBtn = document.getElementById("closePanel");
    const titleElement = document.getElementById("countryNameTitle");
    const reelsContainer = document.getElementById("countryReelsContainer");

    const modal = document.getElementById("videoModal");
    const modalVideo = document.getElementById("modalVideoPlayer");
    const closeModalBtn = document.getElementById("closeModal");

    function openVideoModal(videoSrc) {
        modalVideo.src = videoSrc;
        modal.classList.add("active");
        modalVideo.play().catch(e => console.log("Autoplay blocked:", e));
    }

    function closeVideoModal() {
        modal.classList.remove("active");
        modalVideo.pause();
        modalVideo.src = "";
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener("click", closeVideoModal);
    }
    
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) closeVideoModal();
        });
    }

    // Obsługa kliknięcia w kraj
    countries.forEach(country => {
        country.addEventListener("click", () => {
            const countryName = country.id;
            titleElement.textContent = countryName;

            reelsContainer.innerHTML = `<p>Loading reels for ${countryName}...</p>`;
            panel.classList.add("active");

            const formData = new FormData();
            formData.append('country', countryName);

            fetch('/getCountryReels', {
                method: "POST",
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                reelsContainer.innerHTML = ""; 

                if (data.reels && data.reels.length > 0) {
                    data.reels.forEach(reel => {

                        const imgPath = reel.thumbnail_name; 
                        const videoPath = reel.video_name; 

                        const dateObj = new Date(reel.created_at);
                        const dateDisplay = dateObj.toLocaleDateString('en-US', {
                            month: 'long',
                            year: 'numeric'
                        });

                        const reelWrapper = document.createElement("div");
                        reelWrapper.classList.add("trip-card");
                        
                        reelWrapper.setAttribute("data-video-src", videoPath);

   
                        reelWrapper.innerHTML = `
                            <img src="${imgPath}" alt="Trip thumbnail" onerror="this.src='/public/assets/placeholder.jpg'">
                            <div class="trip-info">
                                <span class="trip-date">${dateDisplay}</span>
                            </div>
                        `;

                        reelWrapper.addEventListener("click", () => {
                            openVideoModal(videoPath);
                        });

                        reelsContainer.appendChild(reelWrapper);
                    });
                } else {
                    reelsContainer.innerHTML = "<p>You haven't been in this country yet</p>";
                }
            })
            .catch(error => {
                console.error("Error fetching reels:", error);
                reelsContainer.innerHTML = "<p>Error loading content.</p>";
            });
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener("click", () => {
            panel.classList.remove("active");
        });
    }
