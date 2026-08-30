<?php

/**
 * shared/modal/modal.php
 * -----------------------------------------------------------------------
 * Generic confirm-dialog component. Include this once per page (anywhere
 * in the body). Any button elsewhere on the page can open it by setting
 * these data attributes instead of submitting directly:
 *
 *   <button type="button"
 *       data-mr-confirm
 *       data-mr-confirm-action="../../auth/logout.php"
 *       data-mr-confirm-method="post"
 *       data-mr-confirm-title="Log out?"
 *       data-mr-confirm-message="You'll need to sign in again to continue."
 *       data-mr-confirm-label="Log out"
 *       data-mr-confirm-icon="ph-sign-out"
 *       data-mr-confirm-variant="danger">
 *       Log out
 *   </button>
 *
 * data-mr-confirm-variant is optional ("default" | "danger"), everything
 * else falls back to a sensible generic confirmation if omitted.
 * See shared/modal/modal.js for the wiring.
 * -----------------------------------------------------------------------
 */
?>
<div class="mr-modal-overlay" data-mr-modal aria-hidden="true">
    <div class="mr-modal" role="alertdialog" aria-modal="true" aria-labelledby="mr-modal-title" aria-describedby="mr-modal-message">
        <div class="mr-modal-icon" data-mr-modal-icon>
            <i class="ph ph-question"></i>
        </div>
        <div class="mr-modal-title" id="mr-modal-title" data-mr-modal-title>Are you sure?</div>
        <p class="mr-modal-message" id="mr-modal-message" data-mr-modal-message>This action cannot be undone.</p>
        <div class="mr-modal-actions">
            <button type="button" class="mr-btn-secondary" data-mr-modal-cancel>Cancel</button>
            <button type="button" class="mr-btn" data-mr-modal-confirm>Confirm</button>
        </div>
    </div>
</div>

<form data-mr-modal-form method="post" style="display:none"></form>