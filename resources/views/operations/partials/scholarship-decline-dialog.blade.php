<dialog class="scholarship-review-dialog" aria-labelledby="{{ $dialogId }}-title" data-review-decline-dialog>
    <h3 id="{{ $dialogId }}-title">Decline application</h3>
    <p>Enter the reason for declining. This reason will be shared with the Career Seeker.</p>
    <label for="{{ $dialogId }}-reason">Reason
        <textarea id="{{ $dialogId }}-reason" name="notes" required minlength="5" maxlength="3000" disabled data-review-decline-reason></textarea>
    </label>
    <div class="scholarship-review-dialog__actions">
        <button type="button" data-review-decline-cancel>Cancel</button>
        <button type="submit" name="decision" value="{{ $decision }}">Confirm decline</button>
    </div>
</dialog>
