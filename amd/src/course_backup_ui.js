// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Integrates course-owned question banks with the interactive backup form.
 *
 * @module     local_copyquestionbank/course_backup_ui
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Config from 'core/config';
import Notification from 'core/notification';
import Templates from 'core/templates';

const OPTION_REGION = '[data-region="local-copyquestionbank-option"]';
const OPTION_SELECTOR = '[data-action="copy-question-bank"]';
const QUESTION_BANK_GROUP = '[data-region="local-copyquestionbank-group"]';
const SCHEMA_WARNING = '[data-region="local-copyquestionbank-schema-warning"]';
const JUMP_TO_FINAL = '[name="oneclickbackup"]';
const ROOT_ACTIVITIES = 'setting_root_activities';
const ROOT_USERS = 'setting_root_users';
const INITIAL_STAGE = 1;
const SCHEMA_STAGE = 2;
const initialisations = new WeakMap();

/**
 * Finds a native checkbox, excluding hidden controls with the same name.
 *
 * @param {HTMLFormElement} form Backup form.
 * @param {string} name Backup setting name.
 * @return {HTMLInputElement|null} Checkbox element.
 */
const getCheckbox = (form, name) => form.querySelector(`input[type="checkbox"][name="${CSS.escape(name)}"]`);

/**
 * Finds a native setting on the editable or confirmation stage.
 *
 * @param {HTMLFormElement} form Backup form.
 * @param {string} name Backup setting name.
 * @return {HTMLElement|null} Setting element.
 */
const getSettingElement = (form, name) => getCheckbox(form, name) || form.querySelector(`[name="${CSS.escape(name)}"]`);

/**
 * Changes an editable setting and notifies the form dependencies.
 *
 * @param {HTMLInputElement|null} checkbox Checkbox to change.
 * @param {boolean} checked Desired state.
 */
const setCheckbox = (checkbox, checked) => {
    if (!checkbox || checkbox.matches(':disabled') || checkbox.checked === checked) {
        return;
    }

    checkbox.checked = checked;
    checkbox.dispatchEvent(new Event('change', {bubbles: true}));
};

/**
 * Refreshes the native disabledIf rules after a group of changes.
 *
 * @param {HTMLFormElement} form Backup form.
 */
const updateDependencies = form => {
    if (form.id && window.M && window.M.form && typeof window.M.form.updateFormState === 'function') {
        window.M.form.updateFormState(form.id);
    }
};

/**
 * Returns a storage key scoped to this site and backup controller.
 *
 * @param {HTMLFormElement} form Backup form.
 * @return {string|null} Storage key.
 */
const getStorageKey = form => {
    const backup = form.querySelector('input[name="backup"]');
    return backup && backup.value ? `local_copyquestionbank:${Config.wwwroot}:${backup.value}` : null;
};

/**
 * Reads the preference and whether it has already been applied to the schema.
 *
 * @param {Object} config Module configuration.
 * @param {HTMLFormElement} form Backup form.
 * @return {Object} Current backup state.
 */
const readState = (config, form) => {
    const state = {preference: config.qbankSettings.length > 0, applied: false, known: false};
    const key = getStorageKey(form);
    try {
        const stored = key ? JSON.parse(window.sessionStorage.getItem(key)) : null;
        if (stored && typeof stored.preference === 'boolean' && typeof stored.applied === 'boolean') {
            state.preference = stored.preference;
            state.applied = stored.applied;
            state.known = true;
        }
    } catch {
        // Submitted fields remain available when session storage is disabled or unavailable.
    }

    if (config.requestPreference === 0 || config.requestPreference === 1) {
        const preference = config.requestPreference === 1;
        state.applied = config.schemaApplied === true || (state.applied && state.preference === preference);
        state.preference = preference;
        state.known = true;
    }

    return state;
};

/**
 * Updates one plugin-owned hidden field without duplicating it.
 *
 * @param {HTMLFormElement} form Backup form.
 * @param {string} name Field name.
 * @param {string} value Field value.
 */
const setHiddenValue = (form, name, value) => {
    let input = form.querySelector(`input[type="hidden"][name="${name}"]`);
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.append(input);
    }
    input.value = value;
};

/**
 * Carries the state between stages, including when session storage is blocked.
 *
 * @param {HTMLFormElement} form Backup form.
 * @param {Object} state Current backup state.
 */
const persistState = (form, state) => {
    if (!state.known) {
        return;
    }

    setHiddenValue(form, 'local_copyquestionbank_present', '1');
    setHiddenValue(form, 'local_copyquestionbank_applied', state.applied ? '1' : '0');
    if (!form.querySelector(OPTION_SELECTOR)) {
        setHiddenValue(form, 'local_copyquestionbank_copy', state.preference ? '1' : '0');
    }

    const key = getStorageKey(form);
    if (key) {
        try {
            window.sessionStorage.setItem(key, JSON.stringify({preference: state.preference, applied: state.applied}));
        } catch {
            // The hidden fields preserve navigation without browser storage.
        }
    }
};

/**
 * Applies the preference once, leaving later individual selections intact.
 *
 * @param {Object} config Module configuration.
 * @param {HTMLFormElement} form Backup form.
 * @param {Object} state Current backup state.
 * @return {Promise<void>} Completion promise.
 */
const applySchemaPreference = async(config, form, state) => {
    if (!state.known || state.applied) {
        return;
    }

    const checkboxes = config.qbankSettings.map(name => getCheckbox(form, name)).filter(Boolean);
    if (state.preference && checkboxes.length) {
        config.sectionSettings.forEach(name => setCheckbox(getCheckbox(form, name), true));
        updateDependencies(form);
        // Allow listeners for the parent sections to update the child controls first.
        await new Promise(resolve => window.setTimeout(resolve, 0));
    }

    checkboxes.forEach(checkbox => setCheckbox(checkbox, state.preference));
    updateDependencies(form);
    state.applied = true;
    persistState(form, state);
};

/**
 * Moves the native question bank rows into a dedicated visual group.
 *
 * @param {Object} config Module configuration.
 * @param {HTMLFormElement} form Backup form.
 * @param {boolean} isSchemaStage Whether the schema is editable.
 * @return {Promise<void>} Rendering promise.
 */
const organiseQuestionBankGroup = async(config, form, isSchemaStage) => {
    if (form.querySelector(QUESTION_BANK_GROUP)) {
        return;
    }

    const activityGroups = [...new Set(config.qbankSettings
        .map(name => getSettingElement(form, name))
        .filter(Boolean)
        .map(element => element.closest('.grouped_settings.activity_level'))
        .filter(Boolean))];
    if (!activityGroups.length) {
        return;
    }

    const container = activityGroups[0].closest('.fcontainer');
    if (!container) {
        return;
    }

    const {html, js} = await Templates.renderForPromise('local_copyquestionbank/question_bank_group', {
        grouplabel: isSchemaStage ? config.bankGroupLabel : config.includedBankGroupLabel,
        userinfolabel: config.userInfoLabel,
    });
    if (!form.isConnected || form.querySelector(QUESTION_BANK_GROUP)) {
        return;
    }

    const placeholder = document.createElement('div');
    const firstSection = [...container.children].find(element => element.matches('.grouped_settings.section_level'));
    container.insertBefore(placeholder, firstSection || null);
    Templates.replaceNode(placeholder, html, js);

    const group = container.querySelector(QUESTION_BANK_GROUP);
    if (group) {
        activityGroups.forEach(activity => group.append(activity));
    }
};

/**
 * Inserts the option above the native initial settings.
 *
 * @param {Object} config Module configuration.
 * @param {HTMLFormElement} form Backup form.
 * @param {Object} state Current backup state.
 * @return {Promise<void>} Rendering promise.
 */
const insertInitialOption = async(config, form, state) => {
    if (form.querySelector(OPTION_REGION)) {
        return;
    }

    const anchorSetting = getSettingElement(form, ROOT_USERS) || getSettingElement(form, ROOT_ACTIVITIES);
    const anchor = anchorSetting && (anchorSetting.closest('.fitem') || anchorSetting.closest('.root_setting'));
    if (!anchor) {
        return;
    }

    const activities = getCheckbox(form, ROOT_ACTIVITIES);
    const activitiesSetting = getSettingElement(form, ROOT_ACTIVITIES);
    const lockedActivities = activities ? activities.matches(':disabled') && !activities.checked :
        activitiesSetting && activitiesSetting.value === '0';
    const noBanks = config.qbankSettings.length === 0;
    const disabled = noBanks || Boolean(lockedActivities);
    const {html, js} = await Templates.renderForPromise('local_copyquestionbank/backup_setting_ui', {
        checked: state.preference && !disabled,
        disabled,
        helpicon: config.helpIcon,
        label: config.label,
        schemawarningicon: config.schemaWarningIcon,
        warningicon: noBanks ? config.warningIcon : config.activitiesWarningIcon,
    });
    if (!form.isConnected || form.querySelector(OPTION_REGION)) {
        return;
    }

    const placeholder = document.createElement('div');
    anchor.before(placeholder);
    Templates.replaceNode(placeholder, html, js);

    const option = form.querySelector(OPTION_SELECTOR);
    if (!option) {
        return;
    }

    const jumpToFinal = form.querySelector(JUMP_TO_FINAL);
    const schemaWarning = form.querySelector(SCHEMA_WARNING);
    const jumpWasDisabled = jumpToFinal ? jumpToFinal.disabled : false;
    const update = () => {
        if (option.checked && activities) {
            setCheckbox(activities, true);
            updateDependencies(form);
        }
        if (state.preference !== option.checked) {
            state.applied = false;
        }
        state.preference = option.checked;
        state.known = true;
        persistState(form, state);
        if (jumpToFinal) {
            jumpToFinal.disabled = jumpWasDisabled || option.checked;
        }
        if (schemaWarning) {
            schemaWarning.hidden = !option.checked;
        }
    };

    update();
    option.addEventListener('change', update);
    form.addEventListener('submit', update);
    if (activities) {
        activities.addEventListener('change', () => {
            if (!activities.checked && option.checked) {
                option.checked = false;
                update();
            }
        });
    }
};

/**
 * Initialises each backup form once.
 *
 * @param {Object} config Module configuration.
 * @return {Promise<void>} Completion promise.
 */
export const init = config => {
    const form = [...document.forms].find(candidate => candidate.querySelector('input[name="backup"]'));
    if (!form) {
        return Promise.resolve();
    }
    if (initialisations.has(form)) {
        return initialisations.get(form);
    }

    const initialise = async() => {
        const stage = form.querySelector('input[name="stage"]');
        const stageNumber = stage ? Number(stage.value) : 0;
        const state = readState(config, form);
        if (stageNumber === INITIAL_STAGE) {
            await insertInitialOption(config, form, state);
            return;
        }
        if (stageNumber === SCHEMA_STAGE) {
            await applySchemaPreference(config, form, state);
        }
        persistState(form, state);
        await organiseQuestionBankGroup(config, form, stageNumber === SCHEMA_STAGE);
    };

    const promise = initialise().catch(Notification.exception);
    initialisations.set(form, promise);
    return promise;
};
