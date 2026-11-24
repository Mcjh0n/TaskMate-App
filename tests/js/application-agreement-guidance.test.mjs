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

function element(tag, { children, ...props } = {}) {
    return React.createElement(
        tag,
        props,
        typeof children === 'function'
            ? children({ errors: {}, processing: false })
            : children,
    );
}

function loadPage(path, modules, label, exportName = 'default') {
    const source = readFileSync(resolve(root, path), 'utf8');
    const compiled = ts.transpileModule(source, {
        compilerOptions: {
            jsx: ts.JsxEmit.ReactJSX,
            module: ts.ModuleKind.CommonJS,
            target: ts.ScriptTarget.ES2022,
            esModuleInterop: true,
        },
    }).outputText;
    const loaded = { exports: {} };
    const mockRequire = (name) => {
        if (name === 'react/jsx-runtime') return require('react/jsx-runtime');
        if (name === 'react') return modules.react ?? React;
        if (!(name in modules))
            throw new Error(
                `Unexpected module in ${label} render test: ${name}`,
            );
        const value = modules[name];
        return typeof value === 'function'
            ? value
            : { __esModule: true, ...value };
    };
    runInNewContext(compiled, {
        require: mockRequire,
        module: loaded,
        exports: loaded.exports,
    });
    return loaded.exports[exportName];
}

const Link = ({ href, children, ...props }) =>
    element('a', {
        ...props,
        href: typeof href === 'string' ? href : (href?.url ?? '#'),
        children,
    });
const Button = ({ asChild: _asChild, ...props }) => element('button', props);
const Form = ({ onSubmit: _onSubmit, ...props }) => element('form', props);

void test('message thread displays a new server snapshot and avoids a duplicate from polling', () => {
    const sent = {
        id: 2,
        senderName: 'Test sender',
        isMine: true,
        body: 'Newly saved reply',
        createdAt: null,
    };
    const polled = { ...sent, id: 3, body: 'Other reply', isMine: false };
    const Conversation = loadPage(
        'resources/js/pages/messages/show.tsx',
        {
            react: {
                ...React,
                useState: () => [
                    [{ ...sent, body: 'Older polling copy' }, polled],
                    () => {},
                ],
            },
            '@inertiajs/react': {
                Form: ({ resetOnSuccess, ...props }) =>
                    element('form', {
                        ...props,
                        'data-reset-on-success': resetOnSuccess?.join(','),
                    }),
                Head: () => null,
                Link,
            },
            '@/layouts/app-layout': ({ children }) => children,
            '@/components/ui/button': { Button },
            '@/components/ui/textarea': {
                Textarea: (props) => element('textarea', props),
            },
            '@/components/input-error': () => null,
            '@/components/verification-status-badge': {
                VerificationStatusBadge: () => null,
            },
            '@/components/problem-report-form': {
                ProblemReportForm: () => null,
            },
            '@/lib/format': { formatDateTime: (value) => value ?? '-' },
        },
        'messages',
    );
    const markup = renderToStaticMarkup(
        React.createElement(Conversation, {
            conversation: {
                id: 7,
                lastMessageId: 2,
                otherPerson: {},
                canSend: true,
            },
            messages: [sent],
            events: [],
        }),
    );
    assert.equal(markup.match(/Newly saved reply/g)?.length, 1);
    assert.doesNotMatch(markup, /Older polling copy/);
    assert.match(markup, /Other reply/);
    assert.match(markup, /data-reset-on-success="body"/);
});

const MyApplications = loadPage(
    'resources/js/pages/applications/index.tsx',
    {
        '@inertiajs/react': { Form, Head: () => null, Link },
        '@/lib/format': {
            formatDate: (value) => value ?? '—',
            formatAmount: (value) => String(value),
        },
        '@/components/ui/button': { Button },
        '@/components/application-status-badge': {
            ApplicationStatusBadge: ({ label }) =>
                element('span', { children: label }),
        },
        '@/routes/applications': {
            default: {
                task: (id) => ({ url: `/my-applications/${id}/task` }),
                withdraw: {
                    form: (id) => ({
                        action: `/my-applications/${id}/withdraw`,
                        method: 'post',
                    }),
                },
            },
        },
        '@/routes/browse': { default: { index: () => ({ url: '/browse' }) } },
        '@/routes/agreements': {
            default: {
                rate: (id) => ({ url: `/agreements/${id}/rate` }),
                show: (id) => ({ url: `/agreements/${id}` }),
            },
        },
        '@/components/payment-status-badge': { PaymentStatusBadge: () => null },
    },
    'my applications',
);

const AgreementDetails = loadPage(
    'resources/js/components/agreement-details.tsx',
    {
        '@inertiajs/react': { Form, Link },
        'lucide-react': { Printer: () => null },
        '@/lib/format': {
            formatDate: (value) => value ?? '—',
            formatDateTime: (value) => value ?? '—',
            formatAmount: (value) => String(value),
        },
        '@/components/ui/button': { Button },
        '@/components/ui/input': { Input: (props) => element('input', props) },
        '@/components/ui/textarea': {
            Textarea: (props) => element('textarea', props),
        },
        '@/components/input-error': () => null,
        '@/components/agreement-status-badge': {
            AgreementStatusBadge: () =>
                element('span', { children: 'Negotiating' }),
        },
        '@/routes/agreements': {
            default: {
                store: {
                    form: (id) => ({
                        action: `/tasks/${id}/agreement`,
                        method: 'post',
                    }),
                },
                confirm: {
                    form: () => ({
                        action: '/agreements/1/confirm',
                        method: 'post',
                    }),
                },
                revise: {
                    form: () => ({
                        action: '/agreements/1/versions',
                        method: 'post',
                    }),
                },
                sendBack: {
                    form: () => ({
                        action: '/agreements/1/send-back',
                        method: 'post',
                    }),
                },
                cancel: {
                    form: () => ({
                        action: '/agreements/1/cancel',
                        method: 'post',
                    }),
                },
            },
        },
        '@/components/completion-payment-panel': {
            CompletionPaymentPanel: () => null,
        },
        '@/components/star-rating': { StarRating: () => null },
        '@/components/rating-summary': { ratingNotice: 'Rating notice' },
    },
    'agreement details',
    'AgreementDetails',
);

void test('my applications explains the status and next step for pending and accepted applications', () => {
    const markup = renderToStaticMarkup(
        React.createElement(MyApplications, {
            applications: {
                links: [],
                data: [
                    {
                        id: 1,
                        taskId: 1,
                        taskTitle: 'Synthetic pending task',
                        posterName: 'Synthetic Poster',
                        budget: '500.00',
                        status: 'pending',
                        statusLabel: 'Pending',
                        taskStatus: 'Approved',
                        taskApproved: true,
                        otherApplicantAccepted: true,
                        agreementId: null,
                        agreementStatus: null,
                        agreementStatusLabel: null,
                        paymentStatus: null,
                        paymentStatusLabel: null,
                        appliedAt: null,
                        needsRating: false,
                        conversationId: null,
                        messageBlockedReason: null,
                    },
                    {
                        id: 2,
                        taskId: 2,
                        taskTitle: 'Synthetic accepted task',
                        posterName: 'Synthetic Poster',
                        budget: '700.00',
                        status: 'accepted',
                        statusLabel: 'Accepted',
                        taskStatus: 'Assigned',
                        taskApproved: false,
                        otherApplicantAccepted: false,
                        agreementId: 3,
                        agreementStatus: 'negotiating',
                        agreementStatusLabel: 'Negotiating',
                        paymentStatus: null,
                        paymentStatusLabel: null,
                        appliedAt: null,
                        needsRating: false,
                        conversationId: null,
                        messageBlockedReason: null,
                    },
                    {
                        id: 3,
                        taskId: 3,
                        taskTitle: 'Synthetic draft agreement task',
                        posterName: 'Synthetic Poster',
                        budget: '700.00',
                        budgetDisplay: '₱700.00',
                        status: 'accepted',
                        statusLabel: 'Accepted',
                        taskStatus: 'Assigned',
                        taskApproved: false,
                        otherApplicantAccepted: false,
                        agreementId: null,
                        agreementStatus: 'draft',
                        agreementStatusLabel: 'Draft',
                        paymentStatus: null,
                        paymentStatusLabel: null,
                        appliedAt: null,
                        needsRating: false,
                        conversationId: null,
                        messageBlockedReason: null,
                    },
                ],
            },
        }),
    );

    assert.match(
        markup,
        /Your application stays active until an agreement is confirmed/,
    );
    assert.match(
        markup,
        /Review the agreement and confirm the same version before work begins/,
    );
    assert.match(markup, /The poster is reviewing the prepared agreement/);
    assert.match(markup, /View agreement/);
});

void test('agreement page clearly states that both people must confirm the same version before work begins', () => {
    const markup = renderToStaticMarkup(
        React.createElement(AgreementDetails, {
            agreement: {
                id: 1,
                status: 'negotiating',
                statusLabel: 'Negotiating',
                lockedAt: null,
                cancelledAt: null,
                cancelledBy: null,
                cancellationReason: null,
                recordReference: null,
                task: {
                    id: 1,
                    title: 'Synthetic task',
                    budget: '500.00',
                    status: 'Assigned',
                },
                poster: { id: 2, name: 'Synthetic Poster' },
                student: { id: 3, name: 'Synthetic Worker' },
                currentVersion: {
                    id: 1,
                    versionNumber: 1,
                    proposerId: 2,
                    proposerName: 'Synthetic Poster',
                    deliverables: 'A synthetic result',
                    terms: 'Complete the task as discussed.',
                    finalAmount: '500.00',
                    startDate: null,
                    durationDays: 3,
                    workMode: 'Remote',
                    workplaceName: null,
                    workplaceAddress: null,
                    paymentArrangement: 'Direct payment after completion.',
                    comment: null,
                    createdAt: null,
                    confirmations: [],
                },
                versions: [],
                messages: [],
            },
            completionPayment: {},
            viewerId: 3,
            actionsAllowed: true,
            notice: 'Agreement notice',
            termsMaxLength: 3000,
        }),
    );

    assert.match(
        markup,
        /Review the work, amount, schedule, and payment arrangement/,
    );
    assert.match(markup, /both people confirm the same version/);
    assert.match(markup, /I agree/);
    assert.match(markup, /Propose different terms/);
    assert.match(markup, /Request a change/);
    for (const id of [
        'revise-deliverables',
        'revise-terms',
        'revise-final-amount',
        'revise-start-date',
        'revise-payment-arrangement',
        'revise-comment',
        'send-back-body',
        'cancel-reason',
    ]) {
        assert.match(
            markup,
            new RegExp(`for=\\"${id}\\"`),
            `Expected a visible or screen-reader label for ${id}.`,
        );
        assert.match(
            markup,
            new RegExp(`id=\\"${id}\\"`),
            `Expected the field ${id} to match its label.`,
        );
    }
});

void test('poster sees a prefilled draft summary and can send it with one action', () => {
    const markup = renderToStaticMarkup(
        React.createElement(AgreementDetails, {
            agreement: {
                id: 7,
                status: 'draft',
                statusLabel: 'Draft',
                lockedAt: null,
                cancelledAt: null,
                cancelledBy: null,
                cancellationReason: null,
                recordReference: null,
                task: {
                    id: 4,
                    title: 'Synthetic task',
                    budget: '500.00',
                    budgetDisplay: '₱500.00',
                    status: 'Assigned',
                },
                poster: { id: 2, name: 'Synthetic Poster' },
                student: { id: 3, name: 'Synthetic Worker' },
                currentVersion: {
                    id: 9,
                    versionNumber: 1,
                    proposerId: 2,
                    proposerName: 'Synthetic Poster',
                    deliverables: 'Prepared deliverables',
                    terms: 'Prepared terms',
                    finalAmount: '450.00',
                    startDate: null,
                    durationDays: 3,
                    workMode: 'Remote',
                    workplaceName: null,
                    workplaceAddress: null,
                    paymentArrangement: 'Prepared arrangement',
                    comment: null,
                    createdAt: null,
                    confirmations: [],
                },
                versions: [],
                messages: [],
            },
            completionPayment: {},
            viewerId: 2,
            actionsAllowed: true,
            notice: 'Agreement notice',
            termsMaxLength: 3000,
        }),
    );

    assert.match(markup, /Send agreement/);
    assert.match(markup, /Prepared deliverables/);
    assert.match(markup, /₱450\.00/);
    assert.match(markup, /Prepared arrangement/);
    assert.match(markup, /same version before work begins/);
});
