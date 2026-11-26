const nativeSelector = 'input[type="date"], input[type="datetime-local"], input[type="time"]';
const initializedAttribute = 'data-tk-date-picker-initialized';
const nativeInputClass = 'tk-date-picker__native';

const localDateValue = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const dateFromValue = (value) => {
    const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
    return match
        ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
        : new Date();
};

const timeFromValue = (value) => {
    const match = value.match(/(?:T|^)(\d{2}):(\d{2})/);

    return match
        ? { hour: Number(match[1]), minute: Number(match[2]) }
        : { hour: 9, minute: 0 };
};

const parseDisplayedValue = (value, mode) => {
    const datePattern = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
    const dateTimePattern = /^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})\s*(AM|PM)$/i;
    const timePattern = /^(\d{1,2}):(\d{2})\s*(AM|PM)$/i;
    const makeDate = (month, day, year) => {
        const date = new Date(year, month - 1, day);
        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
            ? `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
            : null;
    };
    const makeTime = (hourText, minuteText, periodText) => {
        let hour = Number(hourText);
        const minute = Number(minuteText);
        if (hour < 1 || hour > 12 || minute < 0 || minute > 59) return null;
        if (periodText.toUpperCase() === 'AM') hour = hour === 12 ? 0 : hour;
        else hour = hour === 12 ? 12 : hour + 12;
        return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
    };

    if (mode === 'time') {
        const match = value.match(timePattern);
        return match ? makeTime(match[1], match[2], match[3]) : null;
    }
    if (mode === 'datetime') {
        const match = value.match(dateTimePattern);
        if (!match) return null;
        const date = makeDate(Number(match[1]), Number(match[2]), Number(match[3]));
        const time = makeTime(match[4], match[5], match[6]);
        return date && time ? `${date}T${time}` : null;
    }

    const match = value.match(datePattern);
    return match ? makeDate(Number(match[1]), Number(match[2]), Number(match[3])) : null;
};

const formattedDate = (date) => new Intl.DateTimeFormat('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: 'numeric',
}).format(date);

const formattedTime = ({ hour, minute }) => {
    const hour12 = hour % 12 || 12;

    return `${hour12}:${String(minute).padStart(2, '0')} ${hour >= 12 ? 'PM' : 'AM'}`;
};

const iconMarkup = (kind) => kind === 'time'
    ? '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"></circle><path d="M12 7v5l3.2 2"></path></svg>'
    : '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2"></rect><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9h17"></path></svg>';

const enhanceDateInput = (nativeInput) => {
    if (nativeInput.hasAttribute(initializedAttribute)) return;

    const type = nativeInput.type;
    const mode = type === 'datetime-local' ? 'datetime' : type;
    const label = nativeInput.labels?.[0]?.textContent?.trim().replace(/\s+/g, ' ')
        || nativeInput.getAttribute('aria-label')
        || (mode === 'time' ? 'Time' : mode === 'datetime' ? 'Date and time' : 'Date');
    const originalId = nativeInput.id;
    const pickerId = originalId ? `${originalId}-picker` : `tk-date-picker-${Math.random().toString(36).slice(2)}`;
    const required = nativeInput.required;
    const disabled = nativeInput.disabled;
    const readOnly = nativeInput.readOnly;
    const minimum = nativeInput.getAttribute('min') || '';
    const maximum = nativeInput.getAttribute('max') || '';
    const placeholder = nativeInput.getAttribute('placeholder')
        || (mode === 'time' ? 'Choose a time' : mode === 'datetime' ? 'Choose date and time' : 'Choose a date');

    nativeInput.setAttribute(initializedAttribute, 'true');
    nativeInput.dataset.pickerMode = mode;
    nativeInput.dataset.pickerMin = minimum;
    nativeInput.dataset.pickerMax = maximum;
    nativeInput.required = false;
    nativeInput.removeAttribute('min');
    nativeInput.removeAttribute('max');
    nativeInput.classList.add(nativeInputClass);
    nativeInput.tabIndex = -1;
    nativeInput.setAttribute('aria-hidden', 'true');

    const wrapper = document.createElement('span');
    wrapper.className = 'tk-date-picker';
    wrapper.dataset.mode = mode;

    const visibleInput = document.createElement('input');
    visibleInput.type = 'text';
    visibleInput.id = pickerId;
    visibleInput.className = `tk-date-picker__field ${nativeInput.className.replace(nativeInputClass, '').trim()}`.trim();
    visibleInput.readOnly = readOnly;
    visibleInput.required = required;
    visibleInput.disabled = disabled;
    visibleInput.setAttribute('autocomplete', 'off');
    visibleInput.setAttribute('inputmode', 'text');
    visibleInput.setAttribute('aria-haspopup', 'dialog');
    visibleInput.setAttribute('aria-expanded', 'false');
    visibleInput.setAttribute('aria-label', label);
    visibleInput.setAttribute('aria-controls', `${pickerId}-popover`);
    visibleInput.placeholder = placeholder;
    if (nativeInput.getAttribute('aria-describedby')) {
        visibleInput.setAttribute('aria-describedby', nativeInput.getAttribute('aria-describedby'));
    }

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'tk-date-picker__trigger';
    trigger.disabled = disabled || readOnly;
    trigger.setAttribute('aria-label', `Open ${label.toLowerCase()} picker`);
    trigger.setAttribute('aria-controls', `${pickerId}-popover`);
    trigger.innerHTML = iconMarkup(mode === 'time' ? 'time' : 'date');

    const popover = document.createElement('div');
    popover.className = 'tk-date-picker__popover';
    popover.id = `${pickerId}-popover`;
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', `${label} picker`);
    popover.hidden = true;

    const monthHeading = mode === 'time' ? '' : `
        <div class="tk-date-picker__month-heading">
            <button type="button" class="tk-date-picker__month-button" data-calendar-previous aria-label="Previous month">‹</button>
            <strong data-calendar-month aria-live="polite"></strong>
            <button type="button" class="tk-date-picker__month-button" data-calendar-next aria-label="Next month">›</button>
        </div>
        <div class="tk-date-picker__weekdays" aria-hidden="true">
            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
        </div>
        <div class="tk-date-picker__days" data-calendar-days></div>`;
    const timeHeading = mode === 'date' ? '' : `
        <div class="tk-date-picker__time-heading">Choose time</div>
        <div class="tk-date-picker__time-columns" data-time-columns role="group" aria-label="Choose time">
            <div class="tk-date-picker__time-column" data-time-hours aria-label="Hour"></div>
            <div class="tk-date-picker__time-column" data-time-minutes aria-label="Minute"></div>
            <div class="tk-date-picker__time-column" data-time-period aria-label="AM or PM"></div>
        </div>`;
    const todayAction = mode === 'time'
        ? ''
        : '<button type="button" class="tk-date-picker__quick-action" data-calendar-today>Today</button>';

    popover.innerHTML = `
        <div class="tk-date-picker__calendar">${monthHeading}</div>
        <div class="tk-date-picker__time">${timeHeading}</div>
        <div class="tk-date-picker__footer">
            <button type="button" class="tk-date-picker__quick-action" data-calendar-clear>Clear</button>
            <span></span>
            ${todayAction}
            <button type="button" class="tk-date-picker__done" data-calendar-done>Done</button>
        </div>`;

    nativeInput.insertAdjacentElement('beforebegin', wrapper);
    wrapper.append(visibleInput, trigger);
    document.body.append(popover);
    if (originalId) {
        document.querySelectorAll(`label[for="${CSS.escape(originalId)}"]`).forEach((boundLabel) => {
            boundLabel.htmlFor = pickerId;
        });
    }

    let viewDate = dateFromValue(nativeInput.value);
    let isOpen = false;

    const parsedTime = () => timeFromValue(nativeInput.value);
    const isoTime = ({ hour, minute }) => `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
    const currentValueForDate = (date) => {
        const day = localDateValue(date);
        if (mode === 'date') return day;
        if (mode === 'time') return nativeInput.value || '09:00';
        return `${day}T${isoTime(parsedTime())}`;
    };
    const emitChange = () => {
        nativeInput.dispatchEvent(new Event('input', { bubbles: true }));
        nativeInput.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const isWithinRange = (value) => {
        const min = nativeInput.dataset.pickerMin;
        const max = nativeInput.dataset.pickerMax;
        return (!min || value >= min) && (!max || value <= max);
    };
    const updateField = () => {
        const value = nativeInput.value;
        if (!value) {
            visibleInput.value = '';
            visibleInput.setCustomValidity(required ? `Choose ${mode === 'time' ? 'a time' : mode === 'datetime' ? 'a date and time' : 'a date'}.` : '');
            return;
        }

        const selectedDate = dateFromValue(value);
        const time = parsedTime();
        visibleInput.value = mode === 'time'
            ? formattedTime(time)
            : `${formattedDate(selectedDate)}${mode === 'datetime' ? ` ${formattedTime(time)}` : ''}`;
        visibleInput.setCustomValidity(isWithinRange(value) ? '' : 'Choose a value within the allowed date and time range.');
    };
    const syncTypedValue = () => {
        const typed = visibleInput.value.trim();
        if (!typed) {
            nativeInput.value = '';
            visibleInput.setCustomValidity(required ? `Choose ${mode === 'time' ? 'a time' : mode === 'datetime' ? 'a date and time' : 'a date'}.` : '');
            emitChange();
            return;
        }

        const parsed = parseDisplayedValue(typed, mode);
        if (!parsed) {
            visibleInput.setCustomValidity(mode === 'time' ? 'Use the format h:mm AM/PM.' : mode === 'datetime' ? 'Use the format mm/dd/yyyy h:mm AM/PM.' : 'Use the format mm/dd/yyyy.');
            return;
        }
        if (!isWithinRange(parsed)) {
            visibleInput.setCustomValidity('Choose a value within the allowed date and time range.');
            return;
        }

        nativeInput.value = parsed;
        visibleInput.setCustomValidity('');
        emitChange();
        render();
    };
    const isUnavailableDay = (date) => {
        const day = localDateValue(date);
        const minimumDay = nativeInput.dataset.pickerMin?.slice(0, 10);
        const maximumDay = nativeInput.dataset.pickerMax?.slice(0, 10);
        return (minimumDay && day < minimumDay) || (maximumDay && day > maximumDay);
    };
    const canMoveMonth = (date) => {
        const target = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        const minMonth = nativeInput.dataset.pickerMin?.slice(0, 7);
        const maxMonth = nativeInput.dataset.pickerMax?.slice(0, 7);
        return (!minMonth || target >= minMonth) && (!maxMonth || target <= maxMonth);
    };
    const renderCalendar = () => {
        if (mode === 'time') return;
        const heading = popover.querySelector('[data-calendar-month]');
        const dayGrid = popover.querySelector('[data-calendar-days]');
        const previous = popover.querySelector('[data-calendar-previous]');
        const next = popover.querySelector('[data-calendar-next]');
        const todayButton = popover.querySelector('[data-calendar-today]');
        const year = viewDate.getFullYear();
        const month = viewDate.getMonth();
        const first = new Date(year, month, 1);
        const selected = nativeInput.value ? localDateValue(dateFromValue(nativeInput.value)) : '';
        const today = localDateValue(new Date());

        heading.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(first);
        previous.disabled = !canMoveMonth(new Date(year, month - 1, 1));
        next.disabled = !canMoveMonth(new Date(year, month + 1, 1));
        if (todayButton) todayButton.disabled = isUnavailableDay(new Date());
        dayGrid.replaceChildren();

        const calendarStart = new Date(year, month, 1 - first.getDay());
        for (let index = 0; index < 42; index++) {
            const day = new Date(calendarStart);
            day.setDate(calendarStart.getDate() + index);
            const dayValue = localDateValue(day);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'tk-date-picker__day';
            button.textContent = String(day.getDate());
            button.dataset.calendarDate = dayValue;
            button.setAttribute('aria-label', new Intl.DateTimeFormat('en-US', {
                weekday: 'long', month: 'long', day: 'numeric', year: 'numeric',
            }).format(day));
            button.setAttribute('aria-pressed', String(dayValue === selected));
            button.disabled = day.getMonth() !== month || isUnavailableDay(day);
            if (day.getMonth() !== month) button.classList.add('is-outside-month');
            if (dayValue === today) button.classList.add('is-today');
            if (dayValue === selected) button.classList.add('is-selected');
            dayGrid.append(button);
        }
    };
    const timeParts = () => {
        const { hour, minute } = parsedTime();
        return {
            hour: hour % 12 || 12,
            minute,
            period: hour >= 12 ? 'PM' : 'AM',
        };
    };
    const renderTimeColumn = (container, options, selected, key) => {
        if (!container) return;
        container.replaceChildren();
        options.forEach((value) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `tk-date-picker__time-option${String(value) === String(selected) ? ' is-selected' : ''}`;
            button.textContent = key === 'hour' || key === 'minute' ? String(value).padStart(2, '0') : value;
            button.dataset[`time${key[0].toUpperCase()}${key.slice(1)}`] = String(value);
            button.setAttribute('aria-pressed', String(String(value) === String(selected)));
            container.append(button);
        });
        const selectedButton = container.querySelector('.is-selected');
        if (selectedButton) container.scrollTop = Math.max(0, selectedButton.offsetTop - container.offsetTop - container.clientHeight / 2 + selectedButton.clientHeight / 2);
    };
    const renderTime = () => {
        if (mode === 'date') return;
        const time = timeParts();
        renderTimeColumn(popover.querySelector('[data-time-hours]'), Array.from({ length: 12 }, (_, index) => index + 1), time.hour, 'hour');
        renderTimeColumn(popover.querySelector('[data-time-minutes]'), Array.from({ length: 60 }, (_, index) => index), time.minute, 'minute');
        renderTimeColumn(popover.querySelector('[data-time-period]'), ['AM', 'PM'], time.period, 'period');
    };
    const render = () => {
        renderCalendar();
        renderTime();
        updateField();
    };
    const reposition = () => {
        if (!isOpen) return;
        const bounds = wrapper.getBoundingClientRect();
        const width = Math.min(360, window.innerWidth - 24);
        const left = Math.min(Math.max(12, bounds.left), window.innerWidth - width - 12);
        popover.style.width = `${width}px`;
        popover.style.left = `${left}px`;
        popover.style.maxHeight = `${Math.max(240, window.innerHeight - 24)}px`;
        const height = popover.getBoundingClientRect().height;
        let top = bounds.bottom + 8;
        if (top + height > window.innerHeight - 12) top = bounds.top - height - 8;
        popover.style.top = `${Math.max(12, top)}px`;
    };
    const close = (restoreFocus = false) => {
        if (!isOpen) return;
        isOpen = false;
        popover.hidden = true;
        visibleInput.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus) visibleInput.focus();
    };
    const open = () => {
        if (disabled || readOnly || isOpen) return;
        isOpen = true;
        if (nativeInput.value && mode !== 'time') viewDate = dateFromValue(nativeInput.value);
        render();
        popover.hidden = false;
        visibleInput.setAttribute('aria-expanded', 'true');
        trigger.setAttribute('aria-expanded', 'true');
        reposition();
    };
    const setValue = (value) => {
        nativeInput.value = value;
        updateField();
        emitChange();
        render();
    };
    const setDate = (date) => {
        const value = currentValueForDate(date);
        if (!isWithinRange(value)) return;
        viewDate = dateFromValue(localDateValue(date));
        setValue(value);
    };
    const setTimePart = (key, value) => {
        const time = timeParts();
        let hour12 = time.hour;
        let minute = time.minute;
        let period = time.period;
        if (key === 'hour') hour12 = Number(value);
        if (key === 'minute') minute = Number(value);
        if (key === 'period') period = value;
        const hour = (hour12 % 12) + (period === 'PM' ? 12 : 0);
        const timeValue = isoTime({ hour, minute });
        const fullValue = mode === 'time'
            ? timeValue
            : `${localDateValue(dateFromValue(nativeInput.value || localDateValue(viewDate)))}T${timeValue}`;
        if (!isWithinRange(fullValue)) {
            visibleInput.setCustomValidity('Choose a value within the allowed date and time range.');
            return;
        }
        setValue(fullValue);
    };

    nativeInput.addEventListener('input', updateField);
    nativeInput.addEventListener('change', updateField);
    visibleInput.addEventListener('input', () => {
        if (!visibleInput.value.trim()) {
            nativeInput.value = '';
            visibleInput.setCustomValidity(required ? `Choose ${mode === 'time' ? 'a time' : mode === 'datetime' ? 'a date and time' : 'a date'}.` : '');
            return;
        }
        const parsed = parseDisplayedValue(visibleInput.value.trim(), mode);
        nativeInput.value = parsed && isWithinRange(parsed) ? parsed : '';
        visibleInput.setCustomValidity(!parsed
            ? mode === 'time' ? 'Use the format h:mm AM/PM.' : mode === 'datetime' ? 'Use the format mm/dd/yyyy h:mm AM/PM.' : 'Use the format mm/dd/yyyy.'
            : isWithinRange(parsed) ? '' : 'Choose a value within the allowed date and time range.');
    });
    visibleInput.addEventListener('change', syncTypedValue);
    visibleInput.addEventListener('blur', () => {
        if (!isOpen && visibleInput.value.trim()) syncTypedValue();
    });
    visibleInput.addEventListener('focus', open);
    visibleInput.addEventListener('click', open);
    visibleInput.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            open();
        }
        if (event.key === 'Escape') close();
    });
    trigger.addEventListener('click', open);
    popover.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button || button.disabled) return;
        if (button.matches('[data-calendar-previous]')) {
            viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
            renderCalendar();
        } else if (button.matches('[data-calendar-next]')) {
            viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
            renderCalendar();
        } else if (button.matches('[data-calendar-date]')) {
            setDate(dateFromValue(button.dataset.calendarDate));
        } else if (button.matches('[data-calendar-today]')) {
            setDate(new Date());
        } else if (button.matches('[data-calendar-clear]')) {
            setValue('');
        } else if (button.matches('[data-calendar-done]')) {
            close(true);
        } else if (button.dataset.timeHour) {
            setTimePart('hour', button.dataset.timeHour);
        } else if (button.dataset.timeMinute) {
            setTimePart('minute', button.dataset.timeMinute);
        } else if (button.dataset.timePeriod) {
            setTimePart('period', button.dataset.timePeriod);
        }
    });
    document.addEventListener('pointerdown', (event) => {
        if (!isOpen || wrapper.contains(event.target) || popover.contains(event.target)) return;
        close();
    });
    document.addEventListener('keydown', (event) => {
        if (isOpen && event.key === 'Escape') close(true);
    });
    document.addEventListener('scroll', reposition, true);
    window.addEventListener('resize', reposition);
    nativeInput.form?.addEventListener('reset', () => window.setTimeout(() => {
        updateField();
        if (mode !== 'time') viewDate = dateFromValue(nativeInput.value);
    }, 0));

    updateField();
};

const enhanceDateInputs = (root) => {
    if (root instanceof HTMLInputElement && root.matches(nativeSelector)) enhanceDateInput(root);
    root.querySelectorAll?.(nativeSelector).forEach(enhanceDateInput);
};

enhanceDateInputs(document);

const dateInputObserver = new MutationObserver((records) => {
    records.forEach((record) => record.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement) enhanceDateInputs(node);
    }));
});
dateInputObserver.observe(document.documentElement, { childList: true, subtree: true });
