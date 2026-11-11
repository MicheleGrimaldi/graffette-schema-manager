(function () {
  const dayOrder = [
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday'
  ];

  function initTabs() {
    const tabs = document.querySelectorAll('.gsm-tab');
    const panels = document.querySelectorAll('.gsm-panel');

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        const target = tab.dataset.target;

        tabs.forEach(function (item) {
          item.classList.remove('is-active');
        });

        panels.forEach(function (panel) {
          panel.classList.remove('is-active');
        });

        tab.classList.add('is-active');

        const panel = document.querySelector('.gsm-panel[data-panel="' + target + '"]');

        if (panel) {
          panel.classList.add('is-active');
        }
      });
    });
  }

  function getDayValues(dayEl) {
    return {
      closed: dayEl.querySelector('[name*="[closed]"]')?.checked || false,
      morningOpens: dayEl.querySelector('[name*="[morning][opens]"]')?.value || '',
      morningCloses: dayEl.querySelector('[name*="[morning][closes]"]')?.value || '',
      afternoonOpens: dayEl.querySelector('[name*="[afternoon][opens]"]')?.value || '',
      afternoonCloses: dayEl.querySelector('[name*="[afternoon][closes]"]')?.value || ''
    };
  }

  function setDayValues(dayEl, values) {
    const closed = dayEl.querySelector('[name*="[closed]"]');
    const morningOpens = dayEl.querySelector('[name*="[morning][opens]"]');
    const morningCloses = dayEl.querySelector('[name*="[morning][closes]"]');
    const afternoonOpens = dayEl.querySelector('[name*="[afternoon][opens]"]');
    const afternoonCloses = dayEl.querySelector('[name*="[afternoon][closes]"]');

    if (closed) closed.checked = values.closed;
    if (morningOpens) morningOpens.value = values.morningOpens;
    if (morningCloses) morningCloses.value = values.morningCloses;
    if (afternoonOpens) afternoonOpens.value = values.afternoonOpens;
    if (afternoonCloses) afternoonCloses.value = values.afternoonCloses;
  }

  function copyValues(sourceEl, targetDayKeys) {
    const values = getDayValues(sourceEl);

    targetDayKeys.forEach(function (dayKey) {
      const targetEl = document.querySelector('.gsm-hours-day[data-day="' + dayKey + '"]');

      if (targetEl && targetEl !== sourceEl) {
        setDayValues(targetEl, values);
      }
    });
  }

  function initHoursCopy() {
    document.addEventListener('click', function (event) {
      const button = event.target;
      const sourceEl = button.closest('.gsm-hours-day');

      if (button.classList.contains('gsm-apply-office-preset')) {
        applyOfficePreset();
        return;
      }

      if (button.classList.contains('gsm-copy-json')) {
        copyDebugJson(button);
        return;
      }

      if (!sourceEl) return;

      const sourceDay = sourceEl.dataset.day;
      const sourceIndex = dayOrder.indexOf(sourceDay);

      if (button.classList.contains('gsm-copy-next')) {
        copyValues(sourceEl, dayOrder.slice(sourceIndex + 1));
      }

      if (button.classList.contains('gsm-copy-all')) {
        copyValues(sourceEl, dayOrder);
      }

      if (button.classList.contains('gsm-copy-weekdays')) {
        copyValues(sourceEl, [
          'monday',
          'tuesday',
          'wednesday',
          'thursday',
          'friday'
        ]);
      }
    });
  }

  function applyOfficePreset() {
    dayOrder.forEach(function (dayKey) {
      const dayEl = document.querySelector('.gsm-hours-day[data-day="' + dayKey + '"]');
      const isWeekend = dayKey === 'saturday' || dayKey === 'sunday';

      if (!dayEl) return;

      setDayValues(dayEl, {
        closed: isWeekend,
        morningOpens: isWeekend ? '' : '09:00',
        morningCloses: isWeekend ? '' : '13:00',
        afternoonOpens: isWeekend ? '' : '14:00',
        afternoonCloses: isWeekend ? '' : '18:00'
      });
    });
  }

  function copyDebugJson(button) {
    const textarea = document.querySelector('.gsm-debug-json');

    if (!textarea || !navigator.clipboard) return;

    navigator.clipboard.writeText(textarea.value).then(function () {
      const previous = button.textContent;
      button.textContent = 'Copiato';

      window.setTimeout(function () {
        button.textContent = previous;
      }, 1200);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initHoursCopy();
  });
})();
