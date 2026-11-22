import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import test from 'node:test';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import ts from 'typescript';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const require = createRequire(import.meta.url);
const source = readFileSync(resolve(root, 'resources/js/layouts/admin-layout.tsx'), 'utf8');
const compiled = ts.transpileModule(source, {
    compilerOptions: { jsx: ts.JsxEmit.ReactJSX, module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022, esModuleInterop: true },
}).outputText;

function element(tag, { children, ...props } = {}) {
    return React.createElement(tag, props, children);
}

const Link = ({ href, children, ...props }) => element('a', { ...props, href: typeof href === 'string' ? href : href?.url ?? '#', children });
const modules = {
    '@inertiajs/react': { Link, usePage: () => ({ url: '/admin', props: { notificationPollSeconds: 10, auth: { user: { name: 'Synthetic Admin', verification_status: 'verified', is_admin: true } } } }) },
    '@/components/ui/avatar': { Avatar: ({ children }) => element('span', { children }), AvatarFallback: ({ children }) => element('span', { children }) },
    '@/components/ui/button': { Button: ({ children, ...props }) => element('button', { ...props, children }) },
    '@/components/ui/dropdown-menu': {
        DropdownMenu: ({ children }) => element('div', { children }),
        DropdownMenuContent: ({ children }) => element('div', { children }),
        DropdownMenuTrigger: ({ children }) => element('div', { children }),
    },
    '@/lib/utils': { cn: (...classes) => classes.filter(Boolean).join(' ') },
    '@/routes/admin': { default: { dashboard: () => ({ url: '/admin' }), users: { index: () => ({ url: '/admin/users' }) }, tasks: { index: () => ({ url: '/admin/tasks' }) }, reports: { index: () => ({ url: '/admin/reports' }) }, ratings: { index: () => ({ url: '/admin/ratings' }) } } },
    '@/components/prototype-banner': () => null,
    '@/components/site-footer': () => null,
    '@/components/notification-bell': { NotificationBell: () => null },
    '@/components/user-menu-content': { UserMenuContent: () => element('div', { children: 'Profile settings Log out' }) },
    '@/hooks/use-initials': { useInitials: () => (name) => name.slice(0, 1) },
};
const loaded = { exports: {} };
const mockRequire = (name) => {
    if (name === 'react/jsx-runtime') return require('react/jsx-runtime');
    if (name === 'react') return React;
    if (!(name in modules)) throw new Error(`Unexpected module in admin layout render test: ${name}`);
    const value = modules[name];
    return typeof value === 'function' ? value : { __esModule: true, ...value };
};
new Function('require', 'module', 'exports', compiled)(mockRequire, loaded, loaded.exports);
const AdminLayout = loaded.exports.default;

test('admin header provides the account menu with profile settings and logout actions', () => {
    const markup = renderToStaticMarkup(React.createElement(AdminLayout, { children: element('main', { children: 'Admin page' }) }));

    assert.match(markup, /Open account menu/);
    assert.match(markup, /Profile settings/);
    assert.match(markup, /Log out/);
});
