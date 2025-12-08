// Main JavaScript file for MovieBook

document.addEventListener('DOMContentLoaded', function() {
    // Add any interactive features here
    
    // Example: Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Review editing and deletion event listeners
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const reviewId = this.getAttribute('data-review-id');
            editReview(reviewId);
        });
    });
    
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const reviewId = this.getAttribute('data-review-id');
            deleteReview(reviewId);
        });
    });
    
    document.querySelectorAll('.btn-cancel').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const reviewId = this.getAttribute('data-review-id');
            cancelEdit(reviewId);
        });
    });
});

// Review editing and deletion functions
function editReview(reviewId) {
    console.log('editReview called with reviewId:', reviewId);
    const reviewText = document.getElementById('review-text-' + reviewId);
    const editForm = document.getElementById('review-edit-form-' + reviewId);
    
    console.log('reviewText element:', reviewText);
    console.log('editForm element:', editForm);
    
    if (reviewText && editForm) {
        reviewText.style.display = 'none';
        editForm.style.display = 'block';
    } else {
        console.error('Could not find review elements. reviewText:', reviewText, 'editForm:', editForm);
    }
}

function cancelEdit(reviewId) {
    const reviewText = document.getElementById('review-text-' + reviewId);
    const editForm = document.getElementById('review-edit-form-' + reviewId);
    
    if (reviewText && editForm) {
        reviewText.style.display = 'block';
        editForm.style.display = 'none';
        // Reset the textarea to original text from data attribute
        const textarea = editForm.querySelector('textarea');
        if (textarea && editForm.hasAttribute('data-original-text')) {
            textarea.value = editForm.getAttribute('data-original-text');
        }
    }
}

function deleteReview(reviewId) {
    if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_review.php';
        
        const reviewIdInput = document.createElement('input');
        reviewIdInput.type = 'hidden';
        reviewIdInput.name = 'reviewId';
        reviewIdInput.value = reviewId;
        
        form.appendChild(reviewIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

