(function (window) {
    "use strict";

    const TOAST_DELAY_MS = 10000;
    const ERROR_PATTERN = /\b(failed|error|required|invalid|must be|not found|insufficient|please select|please set|no stock)\b/i;

    function inferIsError(message) {
        return ERROR_PATTERN.test(String(message));
    }

    function setMessage(message, isError) {
        if (!message) {
            return;
        }

        if (isError === undefined) {
            isError = inferIsError(message);
        }

        const toastEl = document.getElementById("appToast");
        const toastBody = document.getElementById("appToastBody");
        if (!toastEl || !toastBody || typeof bootstrap === "undefined") {
            return;
        }

        toastBody.textContent = String(message);
        toastEl.classList.remove("text-bg-success", "text-bg-danger");
        toastEl.classList.add(isError ? "text-bg-danger" : "text-bg-success");

        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, {
            delay: TOAST_DELAY_MS,
            autohide: true
        });
        toast.show();
    }

    window.TOAST_DELAY_MS = TOAST_DELAY_MS;
    window.setMessage = setMessage;

    document.addEventListener("DOMContentLoaded", function () {
        const container = document.querySelector(".app-toast-container");
        if (container && container.parentElement !== document.body) {
            document.body.appendChild(container);
        }
    });
})(window);
