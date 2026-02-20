document.addEventListener("DOMContentLoaded", function () {
  const tabs = document.querySelectorAll(".category-tab");
  const profileToggle = document.getElementById("profileToggle");
  const profileMenu = document.getElementById("profileMenu");

  // Initial Load
  fetchContent("home");

  // ... (rest of the setup logic stays same)
  // Profile Dropdown Toggle
  if (profileToggle) {
    profileToggle.addEventListener("click", (e) => {
      e.stopPropagation();
      profileMenu.classList.toggle("show");
    });
  }

  // Close menu when clicking outside
  document.addEventListener("click", () => {
    if (profileMenu) profileMenu.classList.remove("show");
  });

  // Tab Switching Logic
  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
      tab.scrollIntoView({
        behavior: "smooth",
        inline: "center",
        block: "nearest",
      });
      fetchContent(tab.dataset.target);
    });
  });

  // --- SWIPE NAVIGATION ---
  let touchstartX = 0;
  let touchstartY = 0;
  let touchendX = 0;
  let touchendY = 0;
  const swipeThreshold = 80;

  const handleSwipe = () => {
    const diffX = touchstartX - touchendX;
    const diffY = touchstartY - touchendY;

    // If vertical scroll is significant, ignore horizontal swipe
    if (Math.abs(diffY) > Math.abs(diffX) || Math.abs(diffY) > 50) {
      return;
    }

    const currentActive = document.querySelector(".category-tab.active");
    if (!currentActive) return;

    const allTabs = Array.from(tabs);
    const currentIndex = allTabs.indexOf(currentActive);

    if (Math.abs(diffX) > swipeThreshold) {
      if (diffX > 0 && currentIndex < allTabs.length - 1) {
        // Swipe Left -> Next Tab
        allTabs[currentIndex + 1].click();
      } else if (diffX < 0 && currentIndex > 0) {
        // Swipe Right -> Previous Tab
        allTabs[currentIndex - 1].click();
      }
    }
  };

  const contentArea = document.getElementById("landingContent");
  contentArea.addEventListener(
    "touchstart",
    (e) => {
      // Prevent tab swipe if user is interacting with a carousel
      if (e.target.closest(".media-carousel")) return;

      touchstartX = e.changedTouches[0].clientX;
      touchstartY = e.changedTouches[0].clientY;
    },
    { passive: true },
  );

  contentArea.addEventListener(
    "touchend",
    (e) => {
      if (e.target.closest(".media-carousel")) return;

      touchendX = e.changedTouches[0].clientX;
      touchendY = e.changedTouches[0].clientY;
      handleSwipe();
    },
    { passive: true },
  );
});

let currentGalleryItems = [];

async function fetchContent(category) {
  const content = document.getElementById("landingContent");
  content.classList.remove("fade-in");
  content.innerHTML =
    '<div class="col-12 text-center py-5"><div class="spinner-border text-primary pulse"></div></div>';

  try {
    if (category === "home") {
      // Fetch advertisements for hero video banner
      const response = await fetch("../api/admin/advertisements.php");
      const result = await response.json();
      renderHome(result.success ? result.data : []);
    } else if (category === "my-network") {
      const response = await fetch("../api/user/network.php");
      const result = await response.json();
      if (result.success) {
        renderNetwork(result.data);
      } else {
        content.innerHTML = `<div class="col-12 text-center py-5">
                    <p class="text-muted mb-3">${result.message}</p>
                    <a href="index.php" class="btn-primary" style="padding: 8px 16px; border-radius: 12px; font-size: 0.9rem;">Sign In to View</a>
                </div>`;
      }
    } else {
      let endpoint =
        category === "properties"
          ? "../api/admin/properties.php"
          : "../api/admin/advertisements.php";
      const response = await fetch(endpoint);
      const result = await response.json();
      if (result.success) {
        currentGalleryItems = result.data;
        renderGallery(result.data, category);
      }
    }

    setTimeout(() => content.classList.add("fade-in"), 10);
  } catch (error) {
    content.innerHTML =
      '<div class="col-12 text-center py-5">Failed to load content. Please try again.</div>';
  }
}

// ====== HOME PAGE ======
function renderHome(ads) {
  const content = document.getElementById("landingContent");
  const userElement = document.querySelector(".dropdown-header strong");
  const userName = userElement ? userElement.innerText.split(" ")[0] : "Member";

  // Find the first video from advertisements for hero banner
  let heroVideo = null;
  let heroImage = null;
  for (const ad of ads) {
    if (ad.media && ad.media.length > 0) {
      for (const m of ad.media) {
        if (m.file_type === "video" && !heroVideo) {
          heroVideo = "../" + m.file_path;
        }
        if (m.file_type === "image" && !heroImage) {
          heroImage = "../" + m.file_path;
        }
      }
    }
    if (!heroImage && ad.image_path) {
      heroImage = "../" + ad.image_path;
    }
    if (heroVideo) break;
  }

  const heroBanner = heroVideo
    ? `<div style="position:relative;width:100%;height:100%;"><video src="${heroVideo}" class="home-hero-bg-video" autoplay muted loop playsinline></video><video src="${heroVideo}" class="home-hero-fg-video" autoplay muted loop playsinline></video></div>`
    : heroImage
      ? `<img src="${heroImage}" alt="Aalaya" style="width:100%;height:100%;object-fit:cover;">`
      : `<div style="width:100%;height:100%;background:linear-gradient(135deg,#1a1a2e,#16213e);display:flex;align-items:center;justify-content:center;"><h1 class="lotus-gradient-text" style="font-size:3rem;font-weight:900;">AALAYA</h1></div>`;

    content.innerHTML = `
        <!-- Hero Video Banner -->
        <div class="col-12 home-section">
            <div class="home-hero-banner">
                ${heroBanner}
                <div class="home-hero-overlay">
                    <h1 class="home-hero-title animate-slide-up">Welcome, ${userName}</h1>
                    <p class="home-hero-subtitle animate-slide-up delay-1">Your gateway to premium property acquisitions</p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-12 home-section">
            <div class="row g-3 mt-2">
                <div class="col-6 col-md-3">
                    <div class="home-stat-card animate-fade-up delay-1">
                        <i class="bi bi-shield-check" style="font-size:1.8rem;color:var(--accent);"></i>
                        <h4>Verified</h4>
                        <p>All listings legally verified</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="home-stat-card animate-fade-up delay-2">
                        <i class="bi bi-graph-up-arrow" style="font-size:1.8rem;color:var(--accent);"></i>
                        <h4>Growth</h4>
                        <p>High value assets</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="home-stat-card animate-fade-up delay-3">
                        <i class="bi bi-lock-fill" style="font-size:1.8rem;color:var(--accent);"></i>
                        <h4>Secure</h4>
                        <p>Transparent transactions</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="home-stat-card animate-fade-up delay-4">
                        <i class="bi bi-people-fill" style="font-size:1.8rem;color:var(--accent);"></i>
                        <h4>Network</h4>
                        <p>Exclusive member community</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- About Section -->
        <div class="col-12 home-section">
            <div class="home-about-card animate-fade-up delay-2">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <span class="home-section-label">About Aalaya</span>
                        <h2 class="home-section-title">Redefining Property Ownership</h2>
                        <p class="home-section-text">
                            Aalaya is a next-generation property acquisition platform connecting verified buyers and sellers. 
                            We bring transparency, security, and premium opportunities to real estate ownership.
                        </p>
                        <ul class="home-feature-list">
                            <li><i class="bi bi-check-circle-fill"></i> Legally verified property listings</li>
                            <li><i class="bi bi-check-circle-fill"></i> Expert evaluation reports</li>
                            <li><i class="bi bi-check-circle-fill"></i> Competitive bidding system</li>
                            <li><i class="bi bi-check-circle-fill"></i> End-to-end transaction support</li>
                        </ul>
                    </div>
                    <div class="col-md-6 mt-4 mt-md-0 text-center">
                        <div class="home-about-visual">
                            <i class="bi bi-buildings" style="font-size:6rem;opacity:0.15;"></i>
                            <div class="home-about-glow"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div class="col-12 home-section">
            <span class="home-section-label text-center d-block">How It Works</span>
            <h2 class="home-section-title text-center mb-4">Own in 3 Simple Steps</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="home-step-card animate-fade-up delay-1">
                        <div class="home-step-number">01</div>
                        <h3>Discover</h3>
                        <p>Browse verified premium properties and exclusive acquisition opportunities curated by our experts.</p>
                        <i class="bi bi-search home-step-icon"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="home-step-card animate-fade-up delay-2">
                        <div class="home-step-number">02</div>
                        <h3>Evaluate</h3>
                        <p>Access legal opinions, evaluation reports, and complete documentation for informed decisions.</p>
                        <i class="bi bi-file-earmark-check home-step-icon"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="home-step-card animate-fade-up delay-3">
                        <div class="home-step-number">03</div>
                        <h3>Acquire</h3>
                        <p>Place competitive bids and secure your asset with our transparent transaction system.</p>
                        <i class="bi bi-cash-stack home-step-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="col-12 home-section">
            <div class="home-cta-card animate-fade-up delay-2">
                <h2>Ready to Own?</h2>
                <p>Explore premium property listings and exclusive campaigns available now.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <button class="btn-primary px-5 py-3" onclick="document.querySelector('[data-target=properties]').click()">
                        <i class="bi bi-houses me-2"></i> Browse Properties
                    </button>
                    <button class="btn-outline-brand px-5 py-3" style="background:transparent; color:white; border-color:rgba(255,255,255,0.3);" onclick="document.querySelector('[data-target=advertisements]').click()">
                        <i class="bi bi-megaphone me-2"></i> View Campaigns
                    </button>
                </div>
            </div>
        </div>
    `;

  // Trigger staggered animations
  requestAnimationFrame(() => {
    content
      .querySelectorAll(".animate-slide-up, .animate-fade-up")
      .forEach((el, index) => {
        el.style.animationPlayState = "running";
      });
  });
}

function showPropertyDetails(id) {
  const item = currentGalleryItems.find((p) => p.id == id);
  if (!item) return;

  const modal = document.getElementById("detailsModal");
  const modalBody = document.getElementById("modalDetailsContent");

  // Prepare Media Carousel for Modal
  let mediaItems = [];
  if (item.media && item.media.length > 0) {
    mediaItems = item.media;
  } else {
    mediaItems.push({
      file_path: item.image_path || "assets/images/logo-placeholder.png",
      file_type: "image",
    });
  }

  const modalCarouselHTML = `
        <div class="modal-media-wrapper mb-4">
            <div class="media-carousel" onscroll="updateIndicators(this)">
                ${mediaItems
                  .map(
                    (media, idx) => `
                    <div class="media-item" id="modal-media-${item.id}-${idx}">
                        <div class="expand-btn" onclick="openLightbox('../${media.file_path}', '${media.file_type}')">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </div>
                        ${
                          media.file_type === "video"
                            ? `<video src="../${media.file_path}" muted loop playsinline onclick="this.paused ? this.play() : this.pause()"></video>
                               <div class="video-overlay" onclick="const v = this.previousElementSibling; v.paused ? v.play() : v.pause();">
                                   <i class="bi bi-play-fill text-white h1"></i>
                               </div>`
                            : `<img src="../${media.file_path}" alt="${item.title}">`
                        }
                    </div>
                `,
                  )
                  .join("")}
            </div>
            ${
              mediaItems.length > 1
                ? `
                <div class="carousel-indicators-custom">
                    ${mediaItems
                      .map(
                        (_, idx) => `
                        <div class="carousel-dot ${idx === 0 ? "active" : ""}"></div>
                    `,
                      )
                      .join("")}
                </div>
            `
                : ""
            }
        </div>
    `;

  const displayTitle = item.title || "Luxury Property";
  const statusBadge = `<span class="badge ${item.status === "sold" ? "bg-danger" : "bg-success"} mb-3">${(item.status || "AVAILABLE").toUpperCase()}</span>`;

  modalBody.innerHTML = `
        <div class="property-details-view">
            ${modalCarouselHTML}
            
            <div class="mb-4">
                ${statusBadge}
                <h2 class="fw-extrabold mb-1">${displayTitle}</h2>
                <p class="text-white-50"><i class="bi bi-geo-alt"></i> ${item.location || "Premium Location"}</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="p-3 rounded-4 h-100" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); overflow: hidden;">
                        <small class="text-white-50 d-block mb-1">Owner</small>
                        <span class="fw-bold text-white d-block text-truncate">${item.owner_name || "Verified Partner"}</span>
                    </div>
                </div>
                ${
                  item.price && parseFloat(item.price) > 0
                    ? `
                <div class="col-6">
                    <div class="p-3 rounded-4 h-100" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); overflow: hidden;">
                        <small class="text-white-50 d-block mb-1">Price</small>
                        <span class="fw-bold lotus-gradient-text d-block text-truncate" style="font-size: 1rem;" title="₹${parseFloat(item.price).toLocaleString()}">₹${parseFloat(item.price).toLocaleString()}</span>
                    </div>
                </div>
                `
                    : ""
                }
            </div>

            <div class="mb-4">
                <h5 class="fw-bold mb-3">About this Property</h5>
                <p class="text-white-50" style="line-height: 1.6;">${item.description || "No description provided."}</p>
            </div>

            <div class="mb-4">
                <h5 class="fw-bold mb-3">Verified Documents</h5>
                <div class="d-grid gap-2">
                    ${
                      item.legal_opinion_path
                        ? `
                        <a href="../${item.legal_opinion_path}" target="_blank" class="btn btn-outline-light border-1 rounded-4 text-start p-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <i class="bi bi-file-earmark-check-fill me-2 text-primary"></i> Legal Opinion Report
                        </a>
                    `
                        : '<div class="text-white-50 small p-3 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);"><i class="bi bi-info-circle me-2"></i> Legal document pending verification</div>'
                    }
                    
                    ${
                      item.evaluation_path
                        ? `
                        <a href="../${item.evaluation_path}" target="_blank" class="btn btn-outline-light border-1 rounded-4 text-start p-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <i class="bi bi-file-earmark-bar-graph-fill me-2 text-primary"></i> Evaluation Report
                        </a>
                    `
                        : '<div class="text-white-50 small p-3 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);"><i class="bi bi-info-circle me-2"></i> Evaluation report pending</div>'
                    }
                </div>
            </div>
            
            /*
            ${
              item.status !== "sold"
                ? `
            <div class="mb-4">
                <h5 class="fw-bold mb-3">Place a Bid</h5>
                <div class="p-3 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                    ${item.highest_bid ? `<p class="mb-2 text-success"><i class="bi bi-hammer me-2"></i><strong>Current Highest Bid: ₹${parseFloat(item.highest_bid).toLocaleString()}</strong></p>` : ""}
                    <p class="small text-white-50 mb-3">Investors can bid for this property. Bids are reviewed by admin.</p>
                    <form id="biddingForm" class="d-flex gap-2">
                        <input type="number" name="bid_amount" class="form-control bg-dark border-secondary text-white rounded-3" placeholder="Enter bid amount" required min="1">
                        <button type="submit" class="btn btn-primary rounded-3 px-4">Bid</button>
                    </form>
                </div>
            </div>
            `
                : `
            <div class="mb-4 p-3 rounded-4 text-center" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);">
                <i class="bi bi-lock-fill text-danger me-2"></i>
                <span class="text-danger fw-bold">This property has been sold. Bidding is closed.</span>
            </div>
            `
            }
            */

            <div class="mb-4 p-4 rounded-4 text-center" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05);">
                <p class="text-white-50 mb-0">For more details or to express interest, please contact:</p>
                <h2 class="lotus-gradient-text mt-2 mb-0">9902766999</h2>
            </div>

            <!-- <button class="btn-primary w-100 py-3 mt-2" onclick="handleEnquire(this, 'property', ${item.id}, '${displayTitle.replace(/'/g, "\\'")}')">
                Express Interest & Enquire
            </button> -->
        </div>
    `;

  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();

  // Attach bidding form listener
  const bidForm = document.getElementById("biddingForm");
  if (bidForm) {
    bidForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      const btn = this.querySelector("button");
      const originalHTML = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

      try {
        const formData = new FormData(this);
        formData.append("property_id", id);
        const response = await fetch("../api/user/place_bid.php", {
          method: "POST",
          body: formData,
        });
        const result = await response.json();
        if (result.success) {
          showToast.success(result.message);
          this.reset();
          bsModal.hide();
        } else {
          showToast.error(result.message);
        }
      } catch (err) {
        showToast.error("Failed to place bid.");
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
      }
    });
  }
}

async function handleEnquire(button, type, id, title) {
  const originalContent = button.innerHTML;
  button.disabled = true;
  button.innerHTML =
    '<span class="spinner-border spinner-border-sm"></span> Processing...';

  try {
    const formData = new FormData();
    formData.append("type", type);
    formData.append("reference_id", id);
    formData.append("subject", `Interest in: ${title}`);
    formData.append("message", `User expressed interest in "${title}".`);

    const response = await fetch("../api/user/enquiries.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();

    if (result.success) {
      showToast.success(
        "Our team has received your interest and will contact you shortly!",
      );
      const modalEl = document.getElementById("detailsModal");
      const modalInstance = bootstrap.Modal.getInstance(modalEl);
      if (modalInstance) modalInstance.hide();
    } else {
      showToast.error(result.message || "Failed to register interest.");
    }
  } catch (e) {
    showToast.error("Network error. Please try again.");
  } finally {
    button.innerHTML = originalContent;
    button.disabled = false;
  }
}

function renderGallery(items, category) {
  const content = document.getElementById("landingContent");
  const userElement = document.querySelector(".dropdown-header strong");
  const userName = userElement ? userElement.innerText.split(" ")[0] : "Member";

  let heroHTML = "";
  if (category === "properties") {
    heroHTML = `
        <div class="col-12" style="display: none;">
            <section class="hero-section">
                <h2>Welcome, ${userName}.</h2>
                <p>Discover hand-picked premium real estate opportunities verified by our legal and financial experts.</p>
            </section>
        </div>
        `;
  }

  if (!items || items.length === 0) {
    content.innerHTML =
      heroHTML +
      '<div class="col-12 text-center py-5"><p class="text-muted">No featured listings found. Check back later!</p></div>';
    return;
  }

  const cardsHTML = items
    .map((item) => {
      const isProperty = item.property_type !== undefined;
      const displayTitle =
        item.title && item.title.length > 3
          ? item.title
          : isProperty
            ? "Luxury Property"
            : "Exclusive Campaign";
      const displayType = (
        item.property_type ||
        item.ad_type ||
        "Premium"
      ).toUpperCase();
      const location = item.location || "Premium Location";
      const isSold = item.status === "sold";

      // ... media carousel stays same ...
      let mediaItems = [];
      if (item.media && item.media.length > 0) {
        mediaItems = item.media;
      } else {
        mediaItems.push({
          file_path: item.image_path || "assets/images/logo-placeholder.png",
          file_type: "image",
        });
      }

      const carouselHTML = `
            <div class="media-carousel" onscroll="updateIndicators(this)">
                ${mediaItems
                  .map(
                    (media, idx) => `
                    <div class="media-item" id="media-${item.id}-${idx}">
                        <div class="expand-btn" onclick="event.stopPropagation(); openLightbox('../${media.file_path}', '${media.file_type}')">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </div>
                        ${
                          media.file_type === "video"
                            ? `<video src="../${media.file_path}" style="object-fit: contain; background: #000;" muted loop playsinline onmouseover="this.play()" onmouseout="this.pause()" onclick="event.stopPropagation(); this.paused ? this.play() : this.pause();"></video>
                               <div class="video-overlay" onclick="event.stopPropagation(); const v = this.previousElementSibling; v.paused ? v.play() : v.pause();">
                                   <i class="bi bi-play-fill text-white h1"></i>
                               </div>`
                            : `<img src="../${media.file_path}" loading="lazy" alt="${displayTitle}">`
                        }
                    </div>
                `,
                  )
                  .join("")}
            </div>
            ${
              mediaItems.length > 1
                ? `
                <button class="carousel-nav prev" onclick="event.stopPropagation(); navigateCarousel(this, -1)"><i class="bi bi-chevron-left"></i></button>
                <button class="carousel-nav next" onclick="event.stopPropagation(); navigateCarousel(this, 1)"><i class="bi bi-chevron-right"></i></button>
                <div class="carousel-indicators-custom">
                    ${mediaItems
                      .map(
                        (_, idx) => `
                        <div class="carousel-dot ${idx === 0 ? "active" : ""}"></div>
                    `,
                      )
                      .join("")}
                </div>
            `
                : ""
            }
            <span class="badge-tag ${isSold ? "bg-danger" : ""}">${isSold ? "SOLD" : displayType}</span>
        `;

      if (isProperty) {
        return `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="property-card ${isSold ? "sold-out" : ""}">
                    <div class="card-img-wrapper" onclick="showPropertyDetails(${item.id})">
                        ${carouselHTML}
                    </div>
                    <div class="card-body">
                        <h3 class="card-title" onclick="showPropertyDetails(${item.id})" style="cursor:pointer">${displayTitle}</h3>
                        <div class="card-meta text-white-50">
                            <span><i class="bi bi-geo-alt"></i> ${location}</span>
                            <span><i class="bi bi-patch-check-fill text-primary"></i> Verified</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <div>
                                <span class="card-price">₹${parseFloat(item.price || 0).toLocaleString()}</span>
                                ${item.highest_bid ? `<br><small class="text-success"><i class="bi bi-hammer"></i> Highest Bid: ₹${parseFloat(item.highest_bid).toLocaleString()}</small>` : ""}
                            </div>
                            <button class="btn-primary" onclick="showPropertyDetails(${item.id})">
                                Details <i class="bi bi-arrow-right-short"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            `;
      }

      // Advertisement Card Template
      return `
            <div class="col-12 mb-5">
                <div class="campaign-card professional-ad">
                    <div class="campaign-img-wrapper">
                         ${carouselHTML}
                         
                         <!-- Always Visible Title (Bottom Left) -->
                         <div class="card-basic-info">
                            <h3 class="mb-0 text-white text-shadow">${displayTitle}</h3>
                            <small class="text-white-50">${item.company_name || "Aalaya Partner"}</small>
                         </div>

                         <button class="info-toggle-btn" onclick="toggleAdInfo(this)" title="View Details">
                            <i class="bi bi-info-circle-fill"></i> Info
                         </button>

                         <div class="campaign-overlay">
                            <button class="overlay-close-btn" onclick="toggleAdInfo(this)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <span class="badge-tag mb-3 px-3 py-2" style="letter-spacing: 2px;">${displayType}</span>
                            <div class="overlay-content text-center">
                                <h2 class="display-5 fw-bold text-white mb-2">${displayTitle}</h2>
                                <p class="text-white-50 lead mb-3">${item.company_name || "Aalaya Partner"}</p>
                                <p class="text-white d-none d-md-block mx-auto mb-4" style="max-width: 600px; opacity: 0.9;">${item.description || ""}</p>
                                <!-- <button class="btn btn-primary btn-lg rounded-pill px-5 fw-bold" onclick="handleEnquire(this, 'advertisement', ${item.id}, '${displayTitle.replace(/'/g, "\\'")}')">
                                    Enquire Now
                                </button> -->
                                <p class="text-white mt-4">For more details contact: <strong>9902766999</strong></p>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
            `;
    })
    .join("");

  content.innerHTML = heroHTML + cardsHTML;
}

// Toggle Advertisement Info Overlay
window.toggleAdInfo = function (btn) {
  const wrapper = btn.closest(".campaign-img-wrapper");
  const overlay = wrapper.querySelector(".campaign-overlay");
  overlay.classList.toggle("show");
};

function renderNetwork(data) {
  const content = document.getElementById("landingContent");
  content.innerHTML = `
        <div class="col-12">
            <div class="data-card p-4" style="border-radius: 24px; background: linear-gradient(135deg, #F969AA, #BD2D6B); color: white; border: none; box-shadow: 0 20px 40px rgba(217, 69, 137, 0.4);">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-1" style="font-weight: 800;">My Referral Network</h2>
                        <p class="opacity-75 mb-0">You have referred <strong>${data.referrals_count}</strong> active members.</p>
                        <p class="opacity-75 small">Your Referral Code: <strong>${data.referral_code}</strong></p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <button class="btn btn-light rounded-pill px-4 fw-bold" onclick="showUserRegisterModal('${data.referral_code}')">
                            <i class="bi bi-person-plus-fill me-2"></i> Register Member
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mt-5">
            <h4 class="mb-4 fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-wallet2 text-primary" style="font-size: 1.2rem;"></i>
            <span>AALAYA POINTS</span>
            </h4>
            <div class="data-card p-4 text-center" style="border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.05); background: #141417;">
                <div class="mb-1">
                    <span class="text-white opacity-50 fw-bold text-uppercase small" style="letter-spacing: 0.1em;">Aalaya Points</span>
                </div>
                <h1 class="display-4 fw-bold mb-0 d-block lotus-gradient-text">${parseFloat(data.total_points || 0).toLocaleString()}</h1>
            <p class="mt-3 text-white-50 small">AALAYA POINTS are updated in real-time.</p>
            </div>
        </div>

        <div class="col-md-6 mt-5">
          <h4 class="mb-4 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-info-circle text-primary" style="font-size: 1.2rem;"></i>
            <span>Overview</span>
          </h4>
          <div class="data-card p-4" style="border-radius: 24px; background: #141417; border: 1px solid rgba(255, 255, 255, 0.05);">
            <p style="display:none;" class="mb-2 text-white-50">Transaction and referral tables are currently hidden.</p>
            <p class="mb-0 text-white">Registered members: <strong>${data.referrals_count}</strong></p>
            </div>
        </div>
    `;
}

// Global function to update indicators on scroll
window.updateIndicators = function (carousel) {
  const card =
    carousel.closest(".card-img-wrapper") ||
    carousel.closest(".campaign-img-wrapper") ||
    carousel.closest(".modal-media-wrapper");
  if (!card) return;

  const indicators = card.querySelectorAll(".carousel-dot");
  if (indicators.length === 0) return;

  const scrollLeft = carousel.scrollLeft;
  const width = carousel.offsetWidth;
  const index = Math.round(scrollLeft / width);

  indicators.forEach((dot, idx) => {
    if (idx === index) dot.classList.add("active");
    else dot.classList.remove("active");
  });
};

// Full-screen Lightbox Logic
window.openLightbox = function (src, type) {
  const lightbox = document.getElementById("lightboxModal");
  const container = document.getElementById("lightboxMediaContainer");

  if (type === "video") {
    container.innerHTML = `<video src="${src}" controls autoplay loop class="w-100 h-auto" style="border-radius:12px;"></video>`;
  } else {
    container.innerHTML = `<img src="${src}" class="w-100 h-auto" style="border-radius:12px; object-fit: contain; max-height:85vh;">`;
  }

  lightbox.classList.add("show");
  document.body.style.overflow = "hidden";
};

window.closeLightbox = function () {
  const lightbox = document.getElementById("lightboxModal");
  const container = document.getElementById("lightboxMediaContainer");
  container.innerHTML = "";
  lightbox.classList.remove("show");
  document.body.style.overflow = "";
};

// Desktop Carousel Navigation
window.navigateCarousel = function (button, direction) {
  const wrapper =
    button.closest(".card-img-wrapper") ||
    button.closest(".campaign-img-wrapper") ||
    button.closest(".modal-media-wrapper");
  if (!wrapper) return;

  const carousel = wrapper.querySelector(".media-carousel");
  if (!carousel) return;

  const itemWidth = carousel.offsetWidth;
  const currentScroll = carousel.scrollLeft;
  const newScroll = currentScroll + direction * itemWidth;

  carousel.scrollTo({
    left: newScroll,
    behavior: "smooth",
  });
};

// --- ADMIN REGISTRATION LOGIC ---
window.showRegisterModal = function () {
  let modal = document.getElementById("registerMemberModal");
  if (!modal) {
    // Create Modal HTML if not exists
    const modalHTML = `
            <div class="modal fade" id="registerMemberModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 24px; border: none; background: #141417; color: #f8fafc;">
                        <div class="modal-header border-0 p-4 pb-0">
                            <h5 class="modal-title fw-bold">Register New Member</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <form id="adminRegisterForm">
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">Full Name</label>
                                    <input type="text" name="full_name" class="form-control bg-dark border-secondary text-white rounded-3" placeholder="Enter Full Name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control bg-dark border-secondary text-white rounded-3" placeholder="+91 0000000000" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">Aadhaar Number</label>
                                    <input type="text" name="aadhaar_number" class="form-control bg-dark border-secondary text-white rounded-3" placeholder="0000 0000 0000" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">PAN Number</label>
                                    <input type="text" name="pan_number" class="form-control bg-dark border-secondary text-white rounded-3" placeholder="ABCDE1234F" style="text-transform: uppercase;" required>
                                </div>
                                <button type="submit" class="btn-primary w-100 py-3 mt-3">Register Member</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
    document.body.insertAdjacentHTML("beforeend", modalHTML);
    modal = document.getElementById("registerMemberModal");

    // Add listener for form
    document
      .getElementById("adminRegisterForm")
      .addEventListener("submit", async function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML =
          '<span class="spinner-border spinner-border-sm"></span> Registering...';

        try {
          const formData = new FormData(this);
          const response = await fetch("../api/user/admin_register_user.php", {
            method: "POST",
            body: formData,
          });
          const result = await response.json();

          if (result.success) {
            showToast.success(result.message);
            bootstrap.Modal.getInstance(modal).hide();
            this.reset();
            // Refresh network list
            fetchContent("my-network");
          } else {
            showToast.error(result.message);
          }
        } catch (error) {
          showToast.error("Request failed. Please check connection.");
        } finally {
          btn.disabled = false;
          btn.innerHTML = originalHTML;
        }
      });
  }

  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();
};

window.showUserRegisterModal = function (referralCode) {
  let modal = document.getElementById("userRegisterModal");
  if (!modal) {
    const modalHTML = `
            <div class="modal fade" id="userRegisterModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 24px; border: none; background: #141417; color: #f8fafc;">
                        <div class="modal-header border-0 p-4 pb-0">
                            <h5 class="modal-title fw-bold">Register New Member</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <form id="userRegisterForm">
                                <input type="hidden" name="referrer_code" id="modal_referrer_code">
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">Full Name</label>
                                    <input type="text" name="full_name" class="form-control bg-dark border-secondary text-white rounded-3" placeholder="Enter Full Name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control bg-dark border-secondary text-white rounded-3" placeholder="10-digit Phone Number" maxlength="10" required pattern="[0-9]{10}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">Password</label>
                                  <div class="position-relative">
                                    <input type="password" name="password" id="reg_password" class="form-control bg-dark border-secondary text-white rounded-3 pe-5" placeholder="Create Password" required minlength="6">
                                    <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y text-white-50" data-toggle-password="reg_password" style="background:none; border:none;">
                                      <i class="bi bi-eye"></i>
                                    </button>
                                  </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">Confirm Password</label>
                                  <div class="position-relative">
                                    <input type="password" name="confirm_password" id="reg_confirm_password" class="form-control bg-dark border-secondary text-white rounded-3 pe-5" placeholder="Confirm Password" required>
                                    <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y text-white-50" data-toggle-password="reg_confirm_password" style="background:none; border:none;">
                                      <i class="bi bi-eye"></i>
                                    </button>
                                  </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">Aadhaar Number</label>
                                    <input type="text" name="aadhaar_number" class="form-control bg-dark border-secondary text-white rounded-3" placeholder="12-digit Aadhaar Number" maxlength="12" required pattern="[0-9]{12}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-white-50">PAN Number</label>
                                    <input type="text" name="pan_number" class="form-control bg-dark border-secondary text-white rounded-3" placeholder="10-char PAN Number" maxlength="10" style="text-transform: uppercase;" required pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}">
                                </div>
                                <button type="submit" class="btn-primary w-100 py-3 mt-3">Register Member</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
    document.body.insertAdjacentHTML("beforeend", modalHTML);
    modal = document.getElementById("userRegisterModal");

    document
      .getElementById("userRegisterForm")
      .addEventListener("submit", async function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML =
          '<span class="spinner-border spinner-border-sm"></span> Registering...';

        try {
          const formData = new FormData(this);
          const response = await fetch("../api/user/admin_register_user.php", {
            method: "POST",
            body: formData,
          });
          const result = await response.json();

          if (result.success) {
            showToast.success(result.message);
            bootstrap.Modal.getInstance(modal).hide();
            this.reset();
            fetchContent("my-network");
          } else {
            showToast.error(result.message);
          }
        } catch (error) {
          showToast.error("Request failed. Please check connection.");
        } finally {
          btn.disabled = false;
          btn.innerHTML = originalHTML;
        }
      });
  }

  document.getElementById("modal_referrer_code").value = referralCode;
  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();

  modal.querySelectorAll('[data-toggle-password]').forEach(button => {
    button.onclick = function () {
      const input = document.getElementById(this.getAttribute('data-toggle-password'));
      if (!input) return;
      const icon = this.querySelector('i');
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    };
  });
};
