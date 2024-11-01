
jQuery(document).ready(function($) {
    // Use event delegation on a parent element that exists when the page loads
    $('#wpsummarize-meta-box').on('click', '#delete-meta', function(e) {
        e.preventDefault();
        var postId = $(this).data('postid');
        
        $.ajax({
            url: wpSummarizeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_wpsummarize_meta',
                post_id: postId,
                nonce: wpSummarizeAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wpsummarize-meta-box').html(response.data.html);
                    wpsummarize_initializeToggleEditor();
                    wpsummarize_initializeToggleLinksGeneral();
                } else {
                    console.log('Failed to delete meta.');
                }
            }
        });
    });
});



(function($) {
    $(document).ready(function() {
        // Make sure the wp object and its components are available
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            var previousIsSaving = false;

            wp.data.subscribe(function() {
                var isSaving = wp.data.select('core/editor').isSavingPost();
                var isAutosaving = wp.data.select('core/editor').isAutosavingPost();

                if (isSaving && !isAutosaving && !previousIsSaving) {
                    // Post is being saved (not autosaved)
                    var postId = wp.data.select('core/editor').getCurrentPostId();
                    wpsummarize_checkSummaryStatus(postId);
                }

                previousIsSaving = isSaving;
            });
        }
    });


    function wpsummarize_checkSummaryStatus(postId, lastState = null) {
        var attempts = 0;
        var maxAttempts = 7; // Maximum number of attempts before forcing an update
    
        var interval = setInterval(function() {
        
        attempts++;
        
        jQuery.ajax({
            url: wpSummarizeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'check_summary_status',
                post_id: postId,
                nonce: wpSummarizeAjax.nonce                
            },
            success: function(response) {

                if (response.data.summary_running && lastState !== 'running') {
                    // State change to running
                    jQuery('#wpsummarize-meta-box').html(response.data.data.html_running);
                    lastState = 'running';
                } else if ((!response.data.summary_running && lastState === 'running') || (attempts >= maxAttempts && lastState === null)) {
                    // State change to completed
                    jQuery('#wpsummarize-meta-box').html(response.data.data.html_completed);
                    wpsummarize_initializeToggleEditor();
                    wpsummarize_initializeToggleLinksGeneral();
                    clearInterval(interval);
                    lastState = 'completed';
                    attempts=0;
                }
            }
        });
    }, 1500); // Check every 1 second
}
})(jQuery);



function wpsummarize_initializeToggleLinksGeneral() {
    var toggleLink = document.getElementById('wpsummarize_edit_meta_box_link');
    var customizationDiv = document.getElementById('wpsummarize_edit_meta_box');

    if (toggleLink && customizationDiv) {
        toggleLink.addEventListener('click', function(e) {
            e.preventDefault();
            // Toggle the display style between 'block' and 'none'
            if (customizationDiv.style.display === 'block') {
                customizationDiv.style.display = 'none';
            } else {
                customizationDiv.style.display = 'block';
            }
        });
    }
};


function wpsummarize_initializeToggleEditor() {
    var toggleLink = document.getElementById('wpsummarize_edit_summary_meta_box_link');
    var customizationDiv = document.getElementById('wpsummarize_edit_summary_meta_box');

    if (toggleLink && customizationDiv) {
        toggleLink.addEventListener('click', function(e) {
            e.preventDefault();
            // Toggle the display style between 'block' and 'none'
            if (customizationDiv.style.display === 'block') {
                customizationDiv.style.display = 'none';
            } else {
                customizationDiv.style.display = 'block';
            }
        });
    }
};



// Function to uncheck the default checkbox
function wpsummarize_uncheckDefaultCheckbox(checkbox) {
  checkbox.checked = false;
}

// Function to find the closest parent with a specific class
function wpsummarize_findClosestParent(element, className) {
  while (element && !element.classList.contains(className)) {
    element = element.parentElement;
  }
  return element;
}


// Function to handle input interactions
function wpsummarize_handleInputInteraction(event) {
  const container = wpsummarize_findClosestParent(event.target, 'wpsummarize-customization-control');
  if (container) {
    const defaultCheckbox = container.querySelector('.wpsummarize-default-checkbox');
    if (defaultCheckbox) {
      wpsummarize_uncheckDefaultCheckbox(defaultCheckbox);
    }
  }
}

// Function to set up event listeners for a container
function wpsummarize_setupContainerListeners(container) {
  const customizationOptions = container.querySelector('.customization-options');
  if (customizationOptions) {
    customizationOptions.addEventListener('focus', wpsummarize_handleInputInteraction, true);
    customizationOptions.addEventListener('change', wpsummarize_handleInputInteraction, true);
  }
}

// Set up MutationObserver to watch for dynamically added content
const observer = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    if (mutation.type === 'childList') {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          if (node.classList.contains('wpsummarize-customization-control')) {
            wpsummarize_setupContainerListeners(node);
          } else {
            const containers = node.querySelectorAll('.wpsummarize-customization-control');
            containers.forEach(wpsummarize_setupContainerListeners);
          }
        }
      });
    }
  });
});

// Initial setup
document.addEventListener('DOMContentLoaded', function() {
  const containers = document.querySelectorAll('.wpsummarize-customization-control');
  containers.forEach(wpsummarize_setupContainerListeners);

  // Start observing the document body for changes
  observer.observe(document.body, { childList: true, subtree: true });
});





// Function to check the update checkbox
function wpsummarize_rem_check_updateCheckbox() {
  const checkbox = document.querySelector('input[name="wpsummarize_update_on_edit"]');
  if (checkbox) {
    checkbox.checked = true;
  }
}

// Function to handle input interactions
function wpsummarize_rem_check_handleInputInteraction(event) {
  if (event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT' || event.target.tagName === 'TEXTAREA') {
    wpsummarize_rem_check_updateCheckbox();
  }
}

// Function to set up event listeners for a container
function wpsummarize_rem_check_setupContainerListeners(container) {
  container.addEventListener('focus', wpsummarize_rem_check_handleInputInteraction, true);
  container.addEventListener('change', wpsummarize_rem_check_handleInputInteraction, true);
}

// Function to set up event listeners
function wpsummarize_rem_check_setupFormListeners() {
  const form = document.querySelector('form'); // Adjust this selector if needed
  if (form) {
    const containers = form.querySelectorAll('.wpsummarize-watched-input');
    containers.forEach(wpsummarize_rem_check_setupContainerListeners);
  }
}

// Set up MutationObserver to watch for dynamically added content
const wpsummarize_rem_check_observer = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    if (mutation.type === 'childList') {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          if (node.classList.contains('wpsummarize-watched-input')) {
            wpsummarize_rem_check_setupContainerListeners(node);
          } else {
            const containers = node.querySelectorAll('.wpsummarize-watched-input');
            containers.forEach(wpsummarize_rem_check_setupContainerListeners);
          }
        }
      });
    }
  });
});

// Initialize the functionality when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
  wpsummarize_rem_check_setupFormListeners();
  
  // Start observing the document body for changes
  wpsummarize_rem_check_observer.observe(document.body, { childList: true, subtree: true });
});

jQuery(document).ready(function($) {
    // Your existing code here

    // Add this new part for notice dismissal
    $(document).on('click', '#wpsummarize-api-key-notice .notice-dismiss', function() {
        $.ajax({
            url: wpSummarizeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: wpSummarizeAjax.dismiss_notice_action,
                nonce: wpSummarizeAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wpsummarize-api-key-notice').fadeOut();
                }
            }
        });
    });
});
