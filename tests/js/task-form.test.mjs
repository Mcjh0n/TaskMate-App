import assert from 'node:assert/strict';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import test from 'node:test';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import ts from 'typescript';
import { readFileSync } from 'node:fs';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const require = createRequire(import.meta.url);

function element(tag, { children, ...props } = {}) {
    return React.createElement(tag, props, typeof children === 'function' ? children({ errors: {}, processing: false }) : children);
}

function loadTaskForm() {
    const source = readFileSync(resolve(root, 'resources/js/components/task-form.tsx'), 'utf8');
    const compiled = ts.transpileModule(source, {
        compilerOptions: { jsx: ts.JsxEmit.ReactJSX, module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022, esModuleInterop: true },
    }).outputText;
    const loaded = { exports: {} };
    const Form = ({ children, ...props }) => element('form', { ...props, children });
    const passthrough = (tag) => (props) => element(tag, props);
    const modules = {
        '@inertiajs/react': { Form },
        '@/components/input-error': () => null,
        '@/components/ui/button': { Button: passthrough('button') },
        '@/components/ui/input': { Input: passthrough('input') },
        '@/components/ui/label': { Label: passthrough('label') },
        '@/components/ui/textarea': { Textarea: passthrough('textarea') },
    };
    const mockRequire = (name) => {
        if (name === 'react/jsx-runtime') return require('react/jsx-runtime');
        if (name === 'react') return React;
        if (!(name in modules)) throw new Error(`Unexpected module in task form render test: ${name}`);
        const value = modules[name];
        return typeof value === 'function' ? value : { __esModule: true, ...value };
    };
    new Function('require', 'module', 'exports', compiled)(mockRequire, loaded, loaded.exports);
    return loaded.exports;
}

test('task form keeps only the essentials and required workplace details', () => {
    const { default: TaskForm } = loadTaskForm();
    const markup = renderToStaticMarkup(React.createElement(TaskForm, {
        action: { action: '/tasks', method: 'post' },
        options: {
            categories: [{ value: 'other', label: 'Other' }],
            workModes: [{ value: 'remote', label: 'Remote' }, { value: 'onsite', label: 'On-site' }],
            guidelines: ['Follow the platform rules.'],
        },
        task: null,
        resetWarning: false,
    }));

    for (const text of ['Task title', 'Category', 'What needs to be done?', 'Estimated budget (PHP)', 'Work mode', 'Deadline (optional)', 'I confirm that this task follows the platform rules.']) {
        assert.match(markup, new RegExp(text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
    }
    assert.match(markup, /type="text" inputMode="decimal" maxLength="9"/);
    assert.doesNotMatch(markup, /Maximum budget: ₱999,999\.99|TaskMate records agreements and payment status only/);
    for (const field of ['complexity', 'required_activities', 'deliverables', 'estimated_duration_hours', 'skill_requirements', 'physical_demand', 'academic_involvement', 'hazard_exposure', 'private_meeting_required', 'workplace_setting']) {
        assert.doesNotMatch(markup, new RegExp(`name="${field}"`), `The simplified form should not ask for ${field}.`);
    }
});

test('budget input rejects exponent letters and limits whole and decimal digits', () => {
    const { sanitizeTaskBudget } = loadTaskForm();
    assert.equal(sanitizeTaskBudget('999999999999999999999999'), '999999');
    assert.equal(sanitizeTaskBudget('1e6'), '16');
    assert.equal(sanitizeTaskBudget('999999.9999'), '999999.99');
    assert.equal(sanitizeTaskBudget('12a.3e4'), '12.34');
});
