document.addEventListener("DOMContentLoaded", () => {
    // Ensure the redirectMapping object exists.
    if (typeof redirectMapping !== "undefined") {
        // Add click event listeners to all links on the page.
        document.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", event => {
                const href = link.getAttribute("href");

                // Check if the link's href matches any key in the redirectMapping.
                if (redirectMapping[href]) {
                    // Prevent the default navigation behavior.
                    event.preventDefault();

                    // Redirect to the mapped destination.
                    const targetUrl = redirectMapping[href];

                    // Handle redirection for target="_blank" or other cases.
                    if (link.getAttribute("target") === "_blank") {
                        // Open the target URL in a new tab/window.
                        window.open(targetUrl, "_blank");
                    } else {
                        // Redirect in the same tab/window.
                        window.location.href = targetUrl;
                    }
                }
            });
        });
    }
});
