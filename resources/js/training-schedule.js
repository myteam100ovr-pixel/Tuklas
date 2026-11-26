const scheduleDate = (value) => {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(Date.UTC(year, month - 1, day));
};

const formatDate = (date) => date.toISOString().slice(0, 10);

const createNonWorkingDateField = () => {
    const label = document.createElement('label');
    label.className = 'training-date-planner__date';

    const helper = document.createElement('span');
    helper.className = 'sr-only';
    helper.textContent = 'Non-working date';

    const input = document.createElement('input');
    input.type = 'date';
    input.name = 'non_working_dates[]';
    input.dataset.nonWorkingDate = '';

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'operations-secondary';
    remove.dataset.removeNonWorkingDate = '';
    remove.setAttribute('aria-label', 'Remove non-working date');
    remove.textContent = 'Remove';

    label.append(helper, input, remove);

    return label;
};

const setupScheduleForm = (form) => {
    if (form.dataset.scheduleInitialized === 'true') return;
    form.dataset.scheduleInitialized = 'true';

    const program = form.querySelector('[data-schedule-program]');
    const center = form.querySelector('[data-schedule-center]');
    const trainer = form.querySelector('[data-schedule-trainer]');
    const trainerHelp = form.querySelector('[data-trainer-help]');
    const start = form.querySelector('[data-schedule-start]');
    const end = form.querySelector('[data-schedule-end]');
    const skipWeekends = form.querySelector('[data-schedule-skip-weekends]');
    const dateList = form.querySelector('[data-training-date-list]');
    const summary = form.querySelector('[data-training-date-summary]');

    if (!program || !center || !trainer || !start || !end || !dateList || !summary) return;

    const selectedProgram = () => program.selectedOptions[0];
    const duration = () => Number.parseInt(selectedProgram()?.dataset.durationDays ?? '', 10);

    const filterTrainers = () => {
        const programId = program.value;
        const programCenter = selectedProgram()?.dataset.programCenter || center.value;
        let available = 0;

        Array.from(trainer.options).forEach((option) => {
            if (!option.value) return;
            const matches = option.dataset.assignedProgram === programId
                && (!option.dataset.trainerCenter || option.dataset.trainerCenter === programCenter);
            option.hidden = !matches;
            option.disabled = !matches;
            if (matches) available++;
            if (!matches && option.selected) option.selected = false;
        });

        trainerHelp.textContent = available > 0
            ? `${available} TESDA staff member${available === 1 ? '' : 's'} assigned to this course.`
            : 'No TESDA staff are assigned to this course yet. Use the Trainers tab to assign staff first.';
    };

    const syncTimes = (firstDate, lastDate) => {
        const startTime = start.value.split('T')[1] || '08:00';
        const endTime = end.value.split('T')[1] || '17:00';
        start.value = `${firstDate}T${startTime}`;
        end.value = `${lastDate}T${endTime}`;
    };

    const selectedNonWorkingDates = () => Array.from(form.querySelectorAll('[data-non-working-date]'))
        .map((input) => input.value)
        .filter(Boolean);

    const generateDates = () => {
        const requiredDays = duration();
        const startDate = start.value.slice(0, 10);

        if (!Number.isInteger(requiredDays) || requiredDays < 1) {
            summary.textContent = 'This course has no listed duration. Enter the session start and end manually.';
            dateList.replaceChildren();
            return;
        }

        if (!startDate) {
            summary.textContent = `Choose a start date to generate ${requiredDays} training date${requiredDays === 1 ? '' : 's'}.`;
            dateList.replaceChildren();
            return;
        }

        const excludedDates = new Set(selectedNonWorkingDates());
        const dates = [];
        let candidate = scheduleDate(startDate);
        let checkedDays = 0;

        while (dates.length < requiredDays && checkedDays < 365000) {
            const value = formatDate(candidate);
            const weekend = candidate.getUTCDay() === 0 || candidate.getUTCDay() === 6;
            if (!excludedDates.has(value) && (!skipWeekends?.checked || !weekend)) {
                dates.push(value);
            }
            candidate.setUTCDate(candidate.getUTCDate() + 1);
            checkedDays++;
        }

        if (dates.length !== requiredDays) {
            summary.textContent = 'Could not generate all training dates. Review your non-working dates.';
            return;
        }

        const fields = dates.map((date, index) => {
            const label = document.createElement('label');
            label.className = 'training-date-planner__date';

            const caption = document.createElement('span');
            caption.textContent = `Training day ${index + 1}`;

            const input = document.createElement('input');
            input.type = 'date';
            input.name = 'training_dates[]';
            input.value = date;
            input.required = true;
            input.dataset.trainingDate = '';

            label.append(caption, input);

            return label;
        });

        dateList.replaceChildren(...fields);
        syncTimes(dates[0], dates[dates.length - 1]);
        summary.textContent = `Generated ${dates.length} dates to match this course duration. Edit individual dates as needed.`;
    };

    const updateManualDates = () => {
        const dates = Array.from(dateList.querySelectorAll('[data-training-date]')).map((input) => input.value).filter(Boolean);
        if (dates.length > 0) {
            syncTimes(dates[0], dates[dates.length - 1]);
            summary.textContent = `${dates.length} of ${duration() || dates.length} training dates listed. Keep the dates in chronological order.`;
        }
    };

    program.addEventListener('change', () => {
        const courseCenter = selectedProgram()?.dataset.programCenter;
        if (courseCenter) center.value = courseCenter;
        filterTrainers();
        generateDates();
    });
    center.addEventListener('change', filterTrainers);
    trainer.addEventListener('change', filterTrainers);
    start.addEventListener('change', generateDates);
    skipWeekends?.addEventListener('change', generateDates);
    form.querySelector('[data-add-non-working-date]')?.addEventListener('click', () => {
        form.querySelector('[data-non-working-list]')?.append(createNonWorkingDateField());
    });

    form.addEventListener('change', (event) => {
        if (event.target.matches('[data-non-working-date]')) generateDates();
        if (event.target.matches('[data-training-date]')) updateManualDates();
    });

    form.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-non-working-date]');
        if (!removeButton) return;
        removeButton.closest('label')?.remove();
        generateDates();
    });

    const initialCourseCenter = selectedProgram()?.dataset.programCenter;
    if (initialCourseCenter) center.value = initialCourseCenter;
    filterTrainers();
    const existingDates = dateList.querySelectorAll('[data-training-date]');
    if (existingDates.length > 0) {
        updateManualDates();
    } else {
        generateDates();
    }
};

document.querySelectorAll('[data-training-schedule-form]').forEach(setupScheduleForm);
