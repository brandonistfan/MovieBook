// Main JavaScript file for MovieBook

document.addEventListener("DOMContentLoaded", function () {
  // Add any interactive features here

  // Example: Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
        });
      }
    });
  });

  // Auto-dismiss alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0";
      alert.style.transition = "opacity 0.5s";
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  // Review editing and deletion event listeners
  document.querySelectorAll(".btn-edit").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const reviewId = this.getAttribute("data-review-id");
      editReview(reviewId);
    });
  });

  document.querySelectorAll(".btn-delete").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const reviewId = this.getAttribute("data-review-id");
      deleteReview(reviewId);
    });
  });

  document.querySelectorAll(".btn-cancel").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const reviewId = this.getAttribute("data-review-id");
      cancelEdit(reviewId);
    });
  });
});

// Review editing and deletion functions
function editReview(reviewId) {
  console.log("editReview called with reviewId:", reviewId);
  const reviewText = document.getElementById("review-text-" + reviewId);
  const editForm = document.getElementById("review-edit-form-" + reviewId);

  console.log("reviewText element:", reviewText);
  console.log("editForm element:", editForm);

  if (reviewText && editForm) {
    reviewText.style.display = "none";
    editForm.style.display = "block";
  } else {
    console.error(
      "Could not find review elements. reviewText:",
      reviewText,
      "editForm:",
      editForm
    );
  }
}

function cancelEdit(reviewId) {
  const reviewText = document.getElementById("review-text-" + reviewId);
  const editForm = document.getElementById("review-edit-form-" + reviewId);

  if (reviewText && editForm) {
    reviewText.style.display = "block";
    editForm.style.display = "none";
    // Reset the textarea to original text from data attribute
    const textarea = editForm.querySelector("textarea");
    if (textarea && editForm.hasAttribute("data-original-text")) {
      textarea.value = editForm.getAttribute("data-original-text");
    }

    // Reset rating to original value
    const ratingInput = editForm.querySelector(".rating-value-edit");
    const originalRating =
      parseInt(editForm.getAttribute("data-original-rating")) || 0;
    if (ratingInput) {
      ratingInput.value = originalRating;

      // Update star display
      const stars = editForm.querySelectorAll(".star");
      const ratingDisplay = editForm.querySelector(".rating-display-edit");
      stars.forEach((star) => {
        const starValue = parseInt(star.getAttribute("data-rating"));
        if (starValue <= originalRating) {
          star.textContent = "★";
          star.classList.add("active");
        } else {
          star.textContent = "☆";
          star.classList.remove("active");
        }
      });
      if (ratingDisplay) {
        ratingDisplay.textContent =
          originalRating > 0 ? originalRating + " / 10" : "Click to rate";
      }
    }
  }
}

function deleteReview(reviewId) {
  if (
    confirm(
      "Are you sure you want to delete this review and rating? This action cannot be undone."
    )
  ) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "delete_review.php";

    const reviewIdInput = document.createElement("input");
    reviewIdInput.type = "hidden";
    reviewIdInput.name = "reviewId";
    reviewIdInput.value = reviewId;

    form.appendChild(reviewIdInput);
    document.body.appendChild(form);
    form.submit();
  }
}

// Star rating interactive functionality
document.addEventListener("DOMContentLoaded", function () {
  const starContainer = document.getElementById("star-rating");
  if (starContainer) {
    const stars = starContainer.querySelectorAll(".star");
    const ratingInput = document.getElementById("rating-value");
    const ratingDisplay = document.getElementById("rating-display");
    let currentRating = 0;

    stars.forEach((star, index) => {
      // Click to set rating
      star.addEventListener("click", function () {
        currentRating = parseInt(this.getAttribute("data-rating"));
        ratingInput.value = currentRating;
        updateStars(currentRating);
        ratingDisplay.textContent = currentRating + " / 10";
      });

      // Hover effect
      star.addEventListener("mouseenter", function () {
        const hoverRating = parseInt(this.getAttribute("data-rating"));
        updateStars(hoverRating);
      });
    });

    // Reset to current rating on mouse leave
    starContainer.addEventListener("mouseleave", function () {
      updateStars(currentRating);
    });

    function updateStars(rating) {
      stars.forEach((star, index) => {
        const starValue = parseInt(star.getAttribute("data-rating"));
        if (starValue <= rating) {
          star.textContent = "★"; // Filled star
          star.classList.add("active");
        } else {
          star.textContent = "☆"; // Empty star
          star.classList.remove("active");
        }
      });
    }

    // Form validation
    const reviewForm = document.getElementById("review-form");
    if (reviewForm) {
      reviewForm.addEventListener("submit", function (e) {
        const rating = parseInt(ratingInput.value);
        if (rating < 1 || rating > 10) {
          e.preventDefault();
          alert("Please select a rating by clicking on the stars (1-10).");
          return false;
        }
      });
    }
  }

  // Edit form star rating functionality
  const editStarContainers = document.querySelectorAll(".star-rating-edit");
  editStarContainers.forEach((starContainer) => {
    const reviewId = starContainer.getAttribute("data-review-id");
    const stars = starContainer.querySelectorAll(".star");
    const form = starContainer.closest(".review-edit-form-inner");
    const ratingInput = form.querySelector(".rating-value-edit");
    const ratingDisplay = form.querySelector(".rating-display-edit");
    let currentRating = parseInt(ratingInput.value) || 0;

    stars.forEach((star) => {
      // Click to set rating
      star.addEventListener("click", function () {
        currentRating = parseInt(this.getAttribute("data-rating"));
        ratingInput.value = currentRating;
        updateEditStars(stars, currentRating);
        ratingDisplay.textContent = currentRating + " / 10";
      });

      // Hover effect
      star.addEventListener("mouseenter", function () {
        const hoverRating = parseInt(this.getAttribute("data-rating"));
        updateEditStars(stars, hoverRating);
      });
    });

    // Reset to current rating on mouse leave
    starContainer.addEventListener("mouseleave", function () {
      updateEditStars(stars, currentRating);
    });

    // Form validation
    form.addEventListener("submit", function (e) {
      const rating = parseInt(ratingInput.value);
      if (rating < 1 || rating > 10) {
        e.preventDefault();
        alert("Please select a rating by clicking on the stars (1-10).");
        return false;
      }
    });
  });

  function updateEditStars(stars, rating) {
    stars.forEach((star) => {
      const starValue = parseInt(star.getAttribute("data-rating"));
      if (starValue <= rating) {
        star.textContent = "★"; // Filled star
        star.classList.add("active");
      } else {
        star.textContent = "☆"; // Empty star
        star.classList.remove("active");
      }
    });
  }
});
