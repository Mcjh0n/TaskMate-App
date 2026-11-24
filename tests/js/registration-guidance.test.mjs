import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import test from 'node:test';
import { runInNewContext } from 'node:vm';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import ts from 'typescript';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const require = createRequire(import.meta.url);
const source = readFileSync(
    resolve(root, 'resources/js/pages/auth/register.tsx'),
    'utf8',
);
const compiled = ts.transpileModule(source, {
    compilerOptions: {
        jsx: ts.JsxEmit.ReactJSX,
        module: ts.ModuleKind.CommonJS,
        target: ts.ScriptTarget.ES2022,
        esModuleInterop: true,
    },
}).outputText;

function element(tag, { children, ...props } = {}) {
    return React.createElement(
        tag,
        props,
        typeof children === 'function'
            ? children({ errors: {}, processing: false })
            : children,
    );
}

const modules = {
    '@inertiajs/react': {
        Form: ({
            resetOnSuccess: _resetOnSuccess,
            disableWhileProcessing: _disableWhileProcessing,
            ...props
        }) => element('form', props),
        Head: () => null,
    },
    '@/components/input-error': () => null,
    '@/components/password-input': (props) => element('input', props),
    '@/components/safety-notice': {
        SafetyNotice: () => element('p', { children: 'Safety notice' }),
    },
    '@/components/text-link': (props) => element('a', props),
    '@/components/ui/button': { Button: (props) => element('button', props) },
    '@/components/ui/input': { Input: (props) => element('input', props) },
    '@/components/ui/label': { Label: (props) => element('label', props) },
    '@/components/ui/spinner': { Spinner: () => null },
    '@/components/ui/textarea': {
        Textarea: (props) => element('textarea', props),
    },
    '@/routes': { login: () => '/login' },
    '@/routes/register': {
        store: { form: () => ({ action: '/register', method: 'post' }) },
    },
};

const module = { exports: {} };
const mockRequire = (name) => {
    if (name === 'react/jsx-runtime') return require('react/jsx-runtime');
    if (name === 'react') return React;
    if (!(name in modules))
        throw new Error(
            `Unexpected module in registration guidance render test: ${name}`,
        );
    const value = modules[name];
    if (typeof value === 'function') return value;
    return { __esModule: true, ...value };
};
runInNewContext(compiled, {
    require: mockRequire,
    module: module,
    exports: module.exports,
});
const Register = module.exports.default;

void test('student ID input strips letters and limits typing and paste to ten digits', () => {
    assert.equal(
        module.exports.sanitizeStudentId('12AB34567890'),
        '1234567890',
    );
    assert.equal(module.exports.sanitizeStudentId('12-34 56'), '123456');
});

void test('registration keeps only short guidance needed to complete the form', () => {
    const markup = renderToStaticMarkup(
        React.createElement(Register, {
            passwordRules: '',
            institutionalEmailDomains: ['cmu.edu.ph', 'google.com'],
        }),
    );

    assert.doesNotMatch(markup, /How account review works/);
    assert.doesNotMatch(markup, /Browse approved tasks now/);
    assert.doesNotMatch(markup, /Accepted domains|@cmu\.edu\.ph|@google\.com/);
    assert.doesNotMatch(markup, /Account approval is a separate step/);
    assert.match(markup, /Student ID number/);
    assert.match(markup, /Full name/);
    assert.doesNotMatch(markup, /First name|Middle name|Last name/);
    assert.match(markup, /inputMode="numeric"/);
    assert.match(markup, /maxLength="10"/);
    assert.match(markup, /Enter exactly 10 digits/);
});
